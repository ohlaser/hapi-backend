<?php
/**
 * HARUKAの機能制限に関する情報を返す。
 */
$backendDir = dirname(__FILE__, 4);
require_once($backendDir.'/scripts/ApiVerifier.php');



$verifier = new ApiVerifier();
$verifier->verify();


if (array_key_exists("membership-type", $_POST))
{
    $result = "";
    $type = $_POST["membership-type"];
    if ($type === "paid")
    {
        $result = file_get_contents($backendDir."/data/FeatureLimitationPaid.json");
    }
    else if ($type === "free")
    {
        $result = file_get_contents($backendDir."/data/FeatureLimitationFree.json");
    }
    else if ($type === "subsA")
    {
	    $result = file_get_contents($backendDir."/data/FeatureLimitationSubsA.json");
    }
    else if ($type === "subsB")
    {
	    $result = file_get_contents($backendDir."/data/FeatureLimitationSubsB.json");
    }
    echo $result;
    
}

?>
