<?php

class ApiVerifier
{
    private const SALT = '$6$sdkdjbksjbsaJs';
    private const AUTH = "8iO0RCbzckdwYbfu30/gEr5KHdpei1DQhhyB98nRlYgTXiMPMbQn5TW.j.G6FSrGGWBVrenZrYYZwf8/kzcl41";
    private const AUTH_ADMIN = "k/insuuGpG/7HwaZpwjUrOaDI0e0BDhtpi2iX6axDbrlIj8HBccfFl.6Kd8FIQHqCQsHkgh0sXKtvGSpAkdre.";
    private $isAdmin;
    
    function __construct($isAdmin = false)
    {
        $this->isAdmin = $isAdmin;
    }

    public function verify()
    {
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
