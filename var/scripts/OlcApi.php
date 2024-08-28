<?php

/**
 * The class for communication with OLC.
 */
class OlcApi
{
    /**
     * Bearer string for OLC access.
     */
    private $auth;

    /**
     * OLC user id.
     */
    private $userId;

    /**
     * OLC user password.
     */
    private $userPass;

    /**
     * OLC host name
     */
    private $hostName;


    /**
     * constructor.
     */
    function __construct()
    {
        $this->auth = '6170695f6b65793d6f6c635f617069';
        $this->userId = 'API_token_01';
        $this->userPass = 'vcJfyJ9F!cqMEU_M';
        $this->hostName = 'https://cms.oh-laser.com';
    }

    /**
     * create new token onto olc server.
     */
    public function CreateToken($startOfExpiry, $endOfExpiry)
    {
        // execute shell command
        `curl -X POST -H 'Authorization: Bearer {$this->auth}' -d '{"user_id":"{$this->userId}", "user_pass":"{$this->userPass}", "start":"{$startOfExpiry}", "end":"{$endOfExpiry}"}'  -H 'Content-Type:application/json' "{$this->hostName}/api/create_token/" -i`;
    }

    /**
     * delete specified token on olc server.
     */
    public function DeleteToken($token)
    {
        // execute shell command
        `curl -X PUT -H 'Authorization: Bearer {$this->auth}' -d '{"user_id":"{$this->userId}","user_pass":"{$this->userPass}","token":"{$token}"}' -H 'Content-Type:application/json' "{$this->hostName}/api/delete_token/" -i`;
    }

    /**
     * get tokens from olc server.
     */
    public function GetTokens($json_decode, $_withoutExpired = false)
    {
        // execute shell command
        $withoutExpired = $_withoutExpired ? 'true' : 'false';
        $tokens = `curl -X POST -H 'Authorization: Bearer {$this->auth}' -d '{"user_id":"{$this->userId}","user_pass":"{$this->userPass}","limit":"{$withoutExpired}"}' -H 'Content-Type:application/json' "{$this->hostName}/api/get_token/" `;
        if ($json_decode)
        {
            $tokens = json_decode($tokens);
        }
        return $tokens;
    }

}

?>
