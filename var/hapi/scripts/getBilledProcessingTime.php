<?php


/**
 * 従量課金対象の加工時間の取得
 */
function getBilledProcessingTime($procNum) 
{
    $backendDir = dirname(__FILE__, 2);

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
        if ($invoice->price->id !== 'price_1PiSqcDSRUXumGeOmvBdofAI') // note: 価格変更が行われた場合はprice_idを追加
            continue;

        $result['Amount'] = $invoice['amount'];
        $result['Quantity'] = $invoice['quantity'];
    }
    if (count($result) === 0) 
        throw new Exception('Unexpected error (due to invalid price id ?)');
    

    return $result;
}

?>