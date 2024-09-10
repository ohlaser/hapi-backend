<?php

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
        global $olcdb;

        $this->procNum = $procNum;
        $this->procToken = $procToken;
        $this->isAdmin = $isAdmin;
        
        $this->pdo = new PDO(
            $olcdb['dsn'], 
            $olcdb['username'], 
            $olcdb['password'], 
            $olcdb['options']);
    }
    
    public function verify()
    {
        $result = false;

        $headers = apache_request_headers();
        $token = null;
        if (array_key_exists("Authorization", $headers))
        {
            preg_match('/Bearer\s(\S+)/', $headers["Authorization"], $matches);
            $token = $matches[1];
        }
        $auth = self::SALT.'$'. ($this->isAdmin ? self::AUTH_ADMIN : self::AUTH);
        if (crypt($token, self::SALT) == $auth)
        {
            $result = $this->procToken
                ? verifyWithProcessorToken()
                : verifyWithoutProcessorToken();
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
                proc_no = :proc_no;
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
}

?>
