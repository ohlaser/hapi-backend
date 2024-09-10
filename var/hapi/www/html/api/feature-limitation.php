<?php
/**
 * HARUKAの機能制限に関する情報を返す。
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once('log.php');



http_response_code(400);

if (array_key_exists("membership-type", $_POST))
{
    $result = "";
    $type = $_POST["membership-type"];
    $procNum = $_POST["proc-num"];
    $procToken = $_POST["proc-token"];

    try {
        $verifier = new ApiVerifier((int)$procNum, $procToken);
        $verifier->verify();

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
        http_response_code(200);

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), 0);
    }

    if ($result) {
        echo $result;
    }
    
}

?>
