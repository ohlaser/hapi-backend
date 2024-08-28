<?php

class ApiVerifier
{
    private const AUTH = "ba7f012bc4f41333f9c0267fe60c5dd2";
    
    function __construct()
    {}

    public function verify()
    {
        $headers = apache_request_headers();
        $token = null;
        if (array_key_exists("Authorization", $headers))
        {
            preg_match('/Bearer\s(\S+)/', $headers["Authorization"], $matches);
            $token = $matches[1];
        }
        if (md5($token) == self::AUTH)
        {
            /* nothing to do */
        }
        else
        {
            header('HTTP/1.1 403 Forbidden');
            exit();
        }
    }
}

?>
