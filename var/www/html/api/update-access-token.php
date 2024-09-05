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
    && array_key_exists('access-token', $_POST)
    && array_key_exists('old-token', $_POST)
    && array_key_exists('force', $_POST))
{
    $procNum = $_POST['proc-num'];
    $token = $_POST['access-token'];
    $oldToken = $_POST['old-token'];
    $force = (bool)$_POST['force'];
    $resBody = '';
    $firstTime = false;

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
        $pdo->beginTransaction();

        // 加工機に紐づくトークンの存在確認
        $sql = <<<SQL
            SELECT FOR UPDATE
                proc_no,
                token
            FROM
                t_proc_no_token as pt
            WHERE
                proc_no = :proc_no
            SQL;
            
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['proc_no' => $procNum]);
        $line = $stmt->fetch();
            
        if ($line) {
            // トークンの重複確認
            $sql2 = <<<SQL
                SELECT
                    proc_no
                FROM
                    t_proc_no_token as pt
                WHERE
                    token = :token
                    AND
                    NOT proc_no = :proc_no
                SQL;
                
            $stmt = $pdo->prepare($sql2);
            $stmt->execute(['proc_no' => $procNum, 'token' => $token]);
            $line2 = $stmt->fetch();
            
            if ($line2) {
                // error: トークンが重複
                $resBody = <<<JSON
                {
                    "status": "TOKEN_CONFLICTED",
                    "proc_no": {$line2['proc_no']}
                }
                JSON;
                http_response_code(409);

            }
            else if ($line['token'] !== $oldToken && !$force) {
                // error: サーバー側のトークン情報と一致しない
                $resBody = <<<JSON
                {
                    "status": "CURRENT_TOKEN_NOT_MATCHED"
                }
                JSON;
                http_response_code(400);
            
            } else {
                // 更新可能
            }

        } else {
            // 初回の対応付け
            $firstTime = true;

            if (!$force) {
                $resBody = <<<JSON
                {
                    "status": "NO_CONTENT"
                }
                JSON;
                http_response_code(204);
            }
        }

        if (!$resBody) { 
            // 更新を実行
            if ($firstTime) {
                $insertSql = <<<SQL
                    INSERT INTO
                        t_proc_no_token
                    (
                        proc_no,
                        token,
                        create_date,
                        update_date
                    )
                    VALUES
                    {
                        :proc_no,
                        :token,
                        NOW(),
                        NOW()
                    };
                    SQL;

                $stmt = $pdo->prepare($insertSql);
            
            } else {
                $updateSql = <<<SQL
                    UPDATE
                        t_proc_no_token
                    SET
                        token = :token,
                        update_date = NOW()
                    WHERE
                        proc_no = :proc_no;
                    SQL;

                $stmt = $pdo->prepare($updateSql);
            }
            $stmt->execute(['proc_no' => $procNum, 'token' => $token]);
            $pdo->commit();

            $resBody = <<<JSON
            {
                "status": "OK"
            }
            JSON;
            http_response_code(201);
        }
        

    } catch (Exception $e) {
        if ($pdo && get_class($pdo) === 'PDO') {
            try {
                $pdo->rollBack();

            } catch (Exception $e) {
            }
        }
        writeLog($e, basename(__FILE__), (int)$procNum);
    }
}

?>
