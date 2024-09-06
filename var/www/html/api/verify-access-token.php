<?php

/**
 * 加工機番号に紐づくアクセストークンについてステータス取得
 * 加工機管理以外からは受け付けない
 */
 $backendDir = dirname(__FILE__, 4);

 require_once($backendDir.'/scripts/ApiVerifier.php');
 require_once('log.php');

 
 $verifier = new ApiVerifier();
 $verifier->verify(true);


 http_response_code(400);
 
if (array_key_exists('proc-num', $_POST) 
    && array_key_exists('access-token', $_POST))
{
    $procNum = $_POST['proc-num'];
    $token = $_POST['access-token'];

    try {
        $json =file_get_contents($backendDir.'/data/access_keys.json');
        $keys = json_decode($json, true);

        $pdo = new PDO(
            $keys['OLC']['dsn'], 
            $keys['OLC']['username'], 
            $keys['OLC']['password'], 
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

        // 加工機に紐づくトークンの存在確認
        $sql = <<<SQL
            SELECT FOR UPDATE
                count(1)
            FROM
                t_proc_no_token as pt
            WHERE
                proc_no = :proc_no
                AND
                token = :token
            SQL;
            
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['proc_no' => $procNum, 'token' => $token]);

        if ($stmt->fetch()) {
            http_response_code(200);

        } else {
            http_response_code(204);
        }

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), (int)$procNum);
    }
}
?>