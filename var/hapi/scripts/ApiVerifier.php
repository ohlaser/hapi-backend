<?php

$backendDir = dirname(__FILE__, 2);
require_once($backendDir.'/scripts/Resources.php');


/**
 * 認証処理を行うクラス
 */
class ApiVerifier
{
    private const SALT = '$6$sdkdjbksjbsaJs';
    private const AUTH = "8iO0RCbzckdwYbfu30/gEr5KHdpei1DQhhyB98nRlYgTXiMPMbQn5TW.j.G6FSrGGWBVrenZrYYZwf8/kzcl41";
    private const AUTH_ADMIN = "k/insuuGpG/7HwaZpwjUrOaDI0e0BDhtpi2iX6axDbrlIj8HBccfFl.6Kd8FIQHqCQsHkgh0sXKtvGSpAkdre.";

    /**
     * 加工機番号
     */
    private $procNum;
    /**
     * 加工機トークン
     */
    private $procToken;
    /**
     * 管理者権限の有無
     */
    private $isAdmin;

    /**
     * PDOオブジェクト
     */
    private $pdo;

    
    function __construct($procNum, $procToken, $isAdmin = false)
    {
        global $backendDir;

        $this->procNum = $procNum;
        $this->procToken = $procToken;
        $this->isAdmin = $isAdmin;
        
        $olcdb = Resources::$olcdb;
        $this->pdo = new PDO(
            $olcdb['dsn'], 
            $olcdb['username'], 
            $olcdb['password'], 
            $olcdb['options']);
    }
    
    /**
     * 認証処理 標準
     */
    public function verify()
    {
        $result = false;

        if ($this->verifySharedToken())
        {
            $result = $this->procToken
                ? $this->verifyWithProcessorToken()
                : $this->verifyWithoutProcessorToken();
        }
        
        if (!$result) {
            http_response_code(401);
            exit();
        }
    }

    /**
     * 加工機トークンありの場合の認証処理
     */
    private function verifyWithProcessorToken()
    {
        $result = false;
        
        // 加工機番号とトークンのマッピングを検証する
        $sql = <<< SQL
            SELECT
                proc_no
            FROM
                t_proc_no_token
            WHERE
                proc_no = :proc_no
                AND
                token = :token
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['proc_no' => (int)$this->procNum, 'token' => $this->procToken]);

        if ($stmt->fetch()) {
            $result = true;
        }

        return $result;
    }

    /**
     * 加工機トークンなしの場合の認証処理
     */
    private function verifyWithoutProcessorToken()
    {
        $result = false;

        // トークンありの加工機番号ではないことを確認して有効範囲を狭めておく
        $sql = <<< SQL
            SELECT
                proc_no
            FROM
                t_proc_no_token
            WHERE
                proc_no = :proc_no;
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['proc_no' => (int)$this->procNum]);

        if (!$stmt->fetch()) {
            $result = true;
        }

        return $result;
    }

    /**
     * 管理者用の処理を行う際の認証処理
     */
    public function verifyForManagementProcess()
    {
        return $this->isAdmin && $this->verifySharedToken();
    }

    /**
     * 共有トークンの認証処理
     */
    private function verifySharedToken()
    {
        $result = false;

        $headers = apache_request_headers();
        $token = null;
        if (array_key_exists("Authorization", $headers))
        {
            preg_match('/Bearer\s(\S+)/', $headers["Authorization"], $matches);
            $token = (count($matches) >= 2) ? $matches[1] : "";
        }
        $auth = self::SALT.'$'. ($this->isAdmin ? self::AUTH_ADMIN : self::AUTH);
        $result = crypt($token, self::SALT) == $auth;
        
        return $result;
    }

}

?>
