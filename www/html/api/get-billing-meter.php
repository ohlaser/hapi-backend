<?php
/**
 * 中古サブスクにおける従量制課金情報を取得する。
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once('vendor/autoload.php');
require_once('log.php');


// アクセスの妥当性を確認
$verifier = new ApiVerifier();
$verifier->verify();


// default status
http_response_code(400);

if (array_key_exists('meter-type', $_GET)
    && array_key_exists('proc-num', $_GET)) 
{
    $result = null;
    $meterType = $_GET['meter-type'];
    $procNum = $_GET['proc-num'];

    try {
        if ($meterType === 'processing-time') {
            $ret = getBilledProcessingTime((int)$procNum);
                    
            if ($ret) {
                $result = json_encode($ret);
                http_response_code(200);
            
            } else {
                http_response_code(500);
                throw new Exception('unexpected error');
            }
        } else {
            throw new Exception('invalid argument');
        }

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), $procNum);
    }

} else {
    http_response_code(400);
}

if ($result) {
    echo $result;
}



/**
 * 従量課金対象の加工時間の取得
 */
function getBilledProcessingTime($procNum) 
{
    global $backendDir;

    $result = null;
    
    $json =file_get_contents($backendDir.'/data/access_keys.json');
    $keys = json_decode($json, true);
    
    // stripe初期化
    $stripe = new \Stripe\StripeClient([
        'api_key' => $keys['stripe']['secret_key'],
        'stripe_version' => $keys['stripe']['api_version']]);

    $subs = $stripe->subscriptions->search(['query' => 'metadata["proc_no"]:"' . $procNum . '"']);
    if (count($subs->data) === 0)
        throw new Exception('invalid processor number');
    
    $sub = $subs->data[0];
    $customerId = $sub->customer;
    $currentPeriodStart = $sub->current_period_start;
    $currentPeriodEnd = $sub->current_period_end;

    // 次回分の請求情報概要を取得
    // TODO: キャッシュをolcに作成する？
    $result = [];
    $invoices = $stripe->invoices->upcoming(['customer' => $customerId]);

    foreach ($invoices->lines->data as $invoice) {
        if ($invoice->price->id !== 'price_1PXbnyDSRUXumGeOhoTJt9Lg') // note: 価格変更が行われた場合はprice_idを追加
            continue;

        $result['Amount'] = (string)$invoice['amount'];
        $result['Quantity'] = (string)$invoice['quantity'];
    }

    return $result;
}



?>