<?php
/**
 * 中古サブスクにおける従量制課金額を更新する。
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once('vendor/autoload.php');
require_once('log.php');


// アクセスの妥当性を確認
$verifier = new ApiVerifier();
$verifier->verify();


// stripe apiを使用
// h-apiサーバーにいったん送る
// 契約サイクル毎に料金を算出するためにデータベースが必要？ => ファームは無理。pcローカルはナンセンス。故に必要


// default status
http_response_code(400);

if (array_key_exists('meter-type', $_POST)
    && array_key_exists('metered-value', $_POST)
    && array_key_exists('proc-num', $_POST)) 
{
    $success = false;
    $meterType = $_POST['meter-type'];
    $meteredValue = $_POST['metered-value'];
    $procNum = $_POST['proc-num'];

    try {

        if ($meterType === 'processing-time') {
            $success = updateBilledProcessingTime((int)$meteredValue, (int)$procNum);
        
        } else {
            throw new Exception('invalid argument');
        }
        

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), $procNum);
    }

    if ($success) {
        http_response_code(200);
    
    } else {
        http_response_code(500);
    }

} else {
    http_response_code(400);
}


/**
 * 加工時間に関する従量課金処理
 */
function updateBilledProcessingTime($procTime, $procNum)
{
    global $backendDir;
    $success = false;
    
    $json =file_get_contents($backendDir.'/data/access_keys.json');
    $keys = json_decode($json, true);
    
    // stripe初期化
    $stripe = new \Stripe\StripeClient([
        'api_key' => $keys['stripe']['secret_key'],
        'stripe_version' => $keys['stripe']['api_version']]);

    // 初回サーチ後は番号とサブスクidの紐づけをolcサーバーに保存してもよいのでは？
    // オーバーヘッドも減る
    $subs = $stripe->subscriptions->search(['query' => 'metadata["proc_no"]:"' . $procNum . '"']);
    if (count($subs->data) === 0)
        throw new Exception('invalid processor number');

    $customerId = $subs->data[0]->customer;

    // 暫定で従量制固定
    $stripe->billing->meterEvents->create([
        'event_name' => 'processing_time',
        'payload' => [
            'value' => $procTime,
            'stripe_customer_id' => $customerId,
        ]
    ]);

    $success = true;

    return $success;
}


?>