<?php
/*****************************************
 * called by cron as scheduled processing.
 ****************************************/
$backendDir = dirname(__FILE__).'/..';
require_once($backendDir.'/scripts/OlcApi.php');

$newTokenNum = 3;
$DurationOfDays = 30;
$tokenFile = $backendDir.'/data/tokens';
$crypto_setting = $backendDir."/data/crypto-setting.xml";
$currTime = time();



date_default_timezone_set('Asia/Tokyo');
$olcApi = new OlcApi();


// delete expired tokens.
$allTokens = $olcApi->GetTokens(true, false)->tokens;
foreach ($allTokens as $token)
{
    $endOfExpiry = strtotime($token->end);
    if ($endOfExpiry < $currTime)
    {
        $olcApi->DeleteToken($token->token);
    }
}


// create new tokens.
$startOfExpiry = date("Y-m-d", $currTime);
$endOfExpiry = date(
    "Y-m-d", 
    mktime(0, 0, 0, date("m") , date("d") + $DurationOfDays, date("Y"))
);
for ($i = 0; $i < $newTokenNum; $i++)
{
    $olcApi->CreateToken($startOfExpiry, $endOfExpiry);
}

// AES256 encryption for tokens
$livingTokens = $olcApi->GetTokens(false, true);
encryptTokens($crypto_setting, $livingTokens, $tokenFile);


/**
 * トークンの暗号化
 */
function encryptTokens($setting, $inputData, $outputFile)
{
    
    // read setting from file.
    $xml = simplexml_load_file($setting);
    $archiveFormat = (string) $xml->ArchiveFormat;
    $cryptoFormat = (string) $xml->CryptoFormat;
    
    if ($cryptoFormat === "AES-256") {
        $aesKey = (string) $xml->AesKey;
        $aesIv = (string) $xml->AesIv;
    
        // encrypt by AES-256.
        $encryptedData = openssl_encrypt($inputData, 'aes-256-cbc', hex2bin($aesKey), OPENSSL_RAW_DATA, hex2bin($aesIv));
    
        // write encrypted data to file.
        $crypted = '';
        file_put_contents($outputFile, $encryptedData, LOCK_EX);
        
    } else {
        echo "unknown encryption format received.\n";
        exit(1);
    }
}

?>
