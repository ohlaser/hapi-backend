<?php

/**
 * 加工機番号に紐づくアクセストークンについて更新を行う
 * 加工機管理以外からは受け付けない
 */
 $backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once($backendDir.'/scripts/Resources.php');
require_once('hapi.php');
require_once('log.php');

 
Hapi::init();
$verifier = new ApiVerifier(null, null, true);
$verifier->verifyForManagementProcess();


http_response_code(400);

if (array_key_exists('proc-num', $_POST))
{
    $procNum = $_POST['proc-num'];

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


        // トークンを削除
        $sql = <<<SQL
            DELETE
            FROM
                t_proc_no_token
            WHERE
                proc_no = :proc_no
            SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['proc_no' => $procNum]);

        http_response_code(200);
        

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), (int)$procNum);
    }
}

?>
