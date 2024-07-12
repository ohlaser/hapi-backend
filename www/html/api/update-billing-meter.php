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

if (array_key_exists('processing_time', $_POST)
    && array_key_exists('proc_no', $_POST)) 
{
    $success = false;

    try {
        $procTime = $_POST['processing_time'];
        $procNo = $_POST['proc_no'];
    
        $json =file_get_contents($backendDir.'/data/access_keys.json');
        $keys = json_decode($json, true);
        
    
        // OLCに接続して更新
        $pdo = new PDO(
            $keys->OLC->dsn, 
            $keys->OLC->username, 
            $keys->OLC->password, 
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

        // 
        $stmt = $pdo->prepare('SELECT count(*) FROM t_api_auth WHERE api_token = :token');
        $stmt->execute(['token' => $olcToken]);
        
        // stripe初期化
        $stripe = new \Stripe\StripeClient([
            'api_key' => $keys->stripe->secret_key,
            'stripe_version' => $keys->stripe->api_version]);
        

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__));
    }

    if ($success) {
        http_response_code(200);
    
    } else {
        http_response_code(500);
    }

} else {
    http_response_code(400);
}

?>