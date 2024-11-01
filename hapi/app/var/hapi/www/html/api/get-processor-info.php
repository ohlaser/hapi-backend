<?php
/**
 * 指定の加工機に関する情報を返す。
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once($backendDir.'/scripts/Resources.php');
require_once($backendDir.'/scripts/OlcApi.php');
require_once($backendDir.'/scripts/getBilledProcessingTime.php');
require_once($backendDir.'/scripts/StripeController.php');
require_once('vendor/autoload.php');
require_once('hapi.php');
require_once('log.php');


enum Membership: string
{
    /// <summary>
    /// 不明、または無効なユーザー（サブスク期限切れなど）
    /// </summary>
    case InActive = 'InActive';
    /// <summary>
    /// 加工機買い切り、非保守会員
    /// </summary>
    case Free = 'Free';

    /// <summary>
    /// 加工機買い切り、保守会員
    /// </summary>
    case Paid = 'Paid';

    /// <summary>
    /// 中古サブスク 基本料金のみプラン
    /// </summary>
    case SubscriptionA = 'SubscriptionA';

    /// <summary>
    /// 中古サブスク 従量制課金プラン
    /// </summary>
    case SubscriptionB = 'SubscriptionB';
}  

class ProcessorInfoGetter
{
    /**
     * 加工機番号
     */
    private $procNum;

    /**
     * 加工機トークン
     */
    private $procToken;

    /**
     * 契約種別
     */
    private $contractType;

    /**
     * 顧客種別
     */
    private Membership $membership;

    /**
     * Stripeサブスクリプションリソース
     */
    private $subscription;

    /**
     * コンストラクタ
     */
    function __construct($procNum, $procToken, $contractType)
    {
        $this->procNum = $procNum;
        $this->procToken = $procToken;
        $this->contractType = $contractType;
        $this->membership = Membership::InActive;
        $this->subscription = null;
        
        $this->stripe = new \Stripe\StripeClient([
            'api_key' => Resources::$stripeSecretKey,
            'stripe_version' => Resources::$stripeApiVersion]);
    }

    /**
     * リクエストの処理を実行
     */
    public function handleRequest()
    {
        $result = null;

        // OLC API(の代替)から取得可能な情報を得る
        $olcJson = $this->getProcessorCoreDataAsJson();

        if ($olcJson) {
            $olcJson = $this->overrideMaintDataIfRequired($olcJson);

            // 顧客種別
            $this->checkMembership($olcJson);
    
            // 機能制限
            $featureJson = $this->getFeatureLimitationAsJson();
            
            // 自動更新の有無
            $isAutoUpdateStr = $this->isAutoExtensionAsJson();
    
            // 従量データ
            $billingJson = $this->getBillableProcessingDataAsJson();

            // 非課金対象加工時間
            $nonBillableDuration = $this->getNonBillableDurationAsJson();

            // 各種データを統合して1つのJSONとして返送
            $result = <<<JSON
            {
                "membership": "{$this->membership->value}",
                "olc_data": {$olcJson},
                "feature_limitation": {$featureJson},
                "auto_update": {$isAutoUpdateStr},
                "billalbe_processing_time": {$billingJson},
                "non_billable_dration": {$nonBillableDuration}
            }
            JSON;
            http_response_code(200);

        } else {
            $result = '';
            http_response_code(204);
        }

        echo $result;
    }

    /**
     * 加工機番号に紐づくStripeサブスクリソースの取得
     */
    private function getStripeSubsciption()
    {
        if ($this->subscription == null) 
        {
            $this->subscription = false;
            $subs = StripeController::executeWithRetry(
                [$this->stripe->subscriptions, 'search'], 
                ['query' => 'metadata["proc_no"]:"' . $this->procNum . '" AND status:"active"']);

            if (count($subs->data)) {
                $this->subscription = $subs->data[0];
            }
        }
        return $this->subscription;
    }

    /**
     * とりあえずOLC APIの代替。もっと効率的にできる可能性あり。
     */
    private function getProcessorCoreDataAsJson()
    {
        $olcdb = Resources::$olcdb;
        $pdo = new PDO(
            $olcdb['dsn'], 
            $olcdb['username'], 
            $olcdb['password'], 
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
        $sql = <<<SQL
			SELECT
                o.delivery_date, 
				mp1.prod_cd,
				mt.expiration_date 
			FROM 
				t_maintenance AS mt 
			LEFT JOIN 
				t_order_detail AS od 
			ON 
				mt.order_id = od.order_id 
				AND 
				mt.detail_id = od.detail_id
				AND
				od.del_flg = 0
			LEFT JOIN
				m_product AS mp1 -- 保守プラン用
			ON
				mt.plan_id = mp1.prod_id
			LEFT JOIN
				t_order AS o
			ON
				od.order_id = o.order_id
				AND
				o.del_flg = 0
			WHERE
				od.proc_no = :proc_no 
				AND 
				mt.del_flg = 0
			ORDER BY 
				mt.expiration_date DESC
			LIMIT 
				1;
			SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['proc_no' => (int)$this->procNum]);
        $line = $stmt->fetch();

        return $line ? json_encode($line) : null;
    }

    /**
     * 保守についてStripeデータと連携していて且つStripeの(実質的な)契約開始日を迎えている場合は保守情報をStripeのものに上書き
     * OLC側の反映が遅れている可能性があるため
     */
    private function overrideMaintDataIfRequired($olcJson)
    {
        $olcData = json_decode($olcJson);

        $invoices = [];
        $subs = $this->getStripeSubsciption();

        // 契約時刻と現サイクル開始時刻一致しない場合は2回目以降のサイクル(有償)と判断
        // Stripe側のサイクル更新遅延を考慮して、現サイクル終了時刻を超過している場合も真とする
        if ($subs) {
            if ($subs->created != $subs->current_period_start
                || time() > $subs->current_period_end) {
                $olcData->prod_cd = $subs->items->data[0]->price->metadata->prod_cd; 
            }
        }

        return json_encode($olcData);   // 無編集項目について元のデータと同一の結果になることを確認する
    }

    /**
     * 顧客種別の判断
     */
    private function checkMembership($olcJson)
    {
        // olcデータのコードから機能制限データを判定
        $olcData = json_decode($olcJson);
        if ($olcData->prod_cd) {
            switch ($olcData->prod_cd) {
                case 'HAJIME/STANDARD':
                case 'HAJIMECL1/STANDARD':
                case 'HAJIME/PREMIUM':
                case 'HAJIMECL1/PREMIUM':
                case 'SP-GEN1-ST':
                case 'SP-GEN1-PR':
                case 'SP-CL1P-ST':
                case 'SP-CL1P-PR':
                case 'SP-V2-ST':
                case 'SP-V2-PR':
                    $this->membership = Membership::Paid;
                    break;
    
                case 'SP-SUB-ST';
                    $this->membership = Membership::SubscriptionA;
                    break;
                case 'SP-SUB-LT';
                    $this->membership = Membership::SubscriptionB;
                    break;

                default:
                    throw new Exception('unexpected error: invalid prod_cd '.$olcData->prod_cd);
            }
        } else {
            if ($this->contractType == 'sell') {
                $this->membership = Membership::Free;
            } else if ($this->contractType == 'subscription') {
                $this->membership = Membership::InActive;
            } else {
                throw new Exception('unexpected error: invalid contract type '.$this->contractType);
            }
        }
    }

    /**
     * 機能制限データをJsonデータとして取得
     */
    private function getFeatureLimitationAsJson()
    {
        global $backendDir;

        $result = null;

        switch ($this->membership) {
            case Membership::Paid:
                $result = file_get_contents($backendDir."/data/FeatureLimitationPaid.json");
                break;

            case Membership::SubscriptionA;
                $result = file_get_contents($backendDir."/data/FeatureLimitationSubsA.json");
                break;
            case Membership::SubscriptionB;
                $result = file_get_contents($backendDir."/data/FeatureLimitationSubsB.json");
                break;
            default:
                $result = 'null';   // クライアント側で一貫して判断
                break;
        }

        return $result;
    }

    /**
     * 自動更新の有無
     */
    private function isAutoExtensionAsJson()
    {
        return $this->getStripeSubsciption() ? 'true' : 'false';
    }

    /**
     * 従量データの取得
     */
    private function getBillableProcessingDataAsJson()
    {
        if ($this->membership != Membership::SubscriptionB)
            return 'null';

        $result = getBilledProcessingTime($this->procNum);
    
        return json_encode($result);
    }

    /**
     * 非請求対象の加工時間
     */
    private function getNonBillableDurationAsJson()
    {
        return $this->membership == Membership::SubscriptionB ? '300' : 'null';
    }
}



// エントリーポイント
Hapi::init();
http_response_code(400);

if (array_key_exists('proc-num', $_POST)
    && array_key_exists('proc-token', $_POST)
    && array_key_exists('contract-type', $_POST)) 
{
    $procNum = $_POST['proc-num'];
    $procToken = $_POST['proc-token'];
    $contractType = $_POST['contract-type'];

    try {
        $verifier = new ApiVerifier($procNum, $procToken);
        $verifier->verify();

        $handler = new ProcessorInfoGetter((int)$procNum, $procToken, $contractType);
        $handler->handleRequest();


    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), $procNum);
    }
}


?>
