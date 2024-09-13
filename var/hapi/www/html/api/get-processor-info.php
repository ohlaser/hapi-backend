<?php
/**
 * 指定の加工機に関する情報を返す。
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once($backendDir.'/scripts/OlcApi.php');
require_once($backendDir.'/scripts/getBilledProcessingTime.php');
require_once('vendor/autoload.php');
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
     * コンストラクタ
     */
    function __construct($procNum, $procToken, $contractType)
    {
        $this->procNum = $procNum;
        $this->procToken = $procToken;
        $this->contractType = $contractType;
        $this->membership = Membership::InActive;
    }

    /**
     * リクエストの処理を実行
     */
    public function handleRequest()
    {
        $result = null;

        // OLC APIから取得可能な情報を得る
        $olcJson = $this->getOlcDataAsJson();

        if ($olcJson) {
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
     * OLCデータをjsonデータとして取得
     */
    private function getOlcDataAsJson()
    {
        global $backendDir;
        $token_file = $backendDir.'/data/tokens';
        
        // OLCトークンの取得
        $olcApi = new OlcApi();

        $pickedToken = null;
        $longestLifeTime = 0;
        foreach ($olcApi->GetTokens(true, true)->tokens as $token)
        {
            $endOfExpiry = strtotime($token->end);
            if ($endOfExpiry > $longestLifeTime)
            {
                $pickedToken = $token->token;
                $longestLifeTime = $endOfExpiry;
            }
        }

        // トークンとともに取得処理を実行
        $url = 'https://cms.oh-laser.com/api/maintenance';
        $params = array(
            'proc_no' => (int)$this->procNum,
        );
        $query = http_build_query($params);
        $url .= '?' . $query;
        
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'header' => "Authorization: Bearer $pickedToken\r\n"
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents($url, false, $context);

        // すでにJson
        return $result;
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
                    if ($this->contractType == 'sell') {
                        $this->membership = Membership::Free;
                    } else if ($this->contractType == 'subscription') {
                        $this->membership = Membership::InActive;
                    } else {
                        throw new Exception('unexpected error');
                    }
                    break;
            }
        } else {
            throw new Exception('unexpected error');
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
        global $backendDir;

        $json =file_get_contents($backendDir.'/data/access_keys.json');
        $keys = json_decode($json, true);

        $stripe = new \Stripe\StripeClient([
            'api_key' => $keys['stripe']['secret_key'],
            'stripe_version' => $keys['stripe']['api_version']]);
            
        $subs = $stripe->subscriptions->search(['query' => 'metadata["proc_no"]:"' . $this->procNum . '"']);

        return count($subs->data) ? 'true' : 'false';
    }

    /**
     * 従量データの取得
     * TODO: 既存のファイルを参照する形に変更
     */
    private function getBillableProcessingDataAsJson()
    {
        if ($this->membership != Membership::SubscriptionB)
            return 'null';

        $result = getBilledProcessingTime((int)$this->procNum);
    
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
