<?php
/**
 * 暗号化したトークンを返す。HARUKA上で復号する。
 */
$backendDir = dirname(__FILE__, 4);
require_once($backendDir.'/scripts/ApiVerifier.php');

$token_file = $backendDir.'/data/tokens';


$result = '';

if (array_key_exists('proc-num', $_GET)
    && array_key_exists('proc-token', $_GET)) 
{
    $procNum = $_GET['proc-num'];
    $procToken = $_GET['proc-token'];
    $verifier = new ApiVerifier((int)$procNum, $procToken);
    $verifier->verify();
    
    // AES256暗号化済のトークンを送り返す
    $fp = fopen($token_file, "r");
    if (flock($fp, LOCK_SH)) 
    {
        $result = file_get_contents($token_file);
        flock($fp, LOCK_UN);
    }
}

echo $result;


?>
