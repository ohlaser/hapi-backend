<?php

/**
 * 加工機番号に紐づくアクセストークンについてステータス取得
 * 加工機管理以外からは受け付けない
 */
 $backendDir = dirname(__FILE__, 4);

 require_once($backendDir.'/scripts/ApiVerifier.php');
 require_once($backendDir.'/scripts/Resources.php');
 require_once('log.php');

 
 $verifier = new ApiVerifier(null, null, true);
 $verifier->verifyForManagementProcess();


 http_response_code(400);
 
if (array_key_exists('proc-num', $_POST) 
    && array_key_exists('access-token', $_POST))
{
    $procNum = $_POST['proc-num'];
    $token = $_POST['access-token'];

    try {
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

        // 加工機に紐づくトークンの存在確認
        $sql = <<<SQL
            SELECT
                count(1) as count
            FROM
                t_proc_no_token as pt
            WHERE
                proc_no = :proc_no
                AND
                token = :token
            FOR UPDATE;
            SQL;
            
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['proc_no' => $procNum, 'token' => $token]);

        if ($stmt->fetch()['count'] != 0) {
            http_response_code(200);

        } else {
            http_response_code(204);
        }

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), (int)$procNum);
    }
}
?>