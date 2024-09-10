<?php
/**
 * 契約が自動更新であるかを取得する
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once('vendor/autoload.php');
require_once('log.php');



http_response_code(400);

if (array_key_exists('proc-num', $_GET)) 
{   
    $result = null;
    $procNum = $_GET['proc-num'];
    $procToken = $_GET['proc-token'];

    try{
        $verifier = new ApiVerifier((int)$procNum, $procToken);
        $verifier->verify();

        $json =file_get_contents($backendDir.'/data/access_keys.json');
        $keys = json_decode($json, true);

        $stripe = new \Stripe\StripeClient([
            'api_key' => $keys['stripe']['secret_key'],
            'stripe_version' => $keys['stripe']['api_version']]);

        $subs = $stripe->subscriptions->search(['query' => 'metadata["proc_no"]:"' . $procNum . '"']);
        
        if (count($subs->data)){
            $result = '1';
            http_response_code(200);
        }
        else
            http_response_code(204);

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), $procNum);
    }

    if ($result){
        echo $result;
    }
}


?>