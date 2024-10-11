<?php
/**
 * 中古サブスクにおける従量制課金情報を取得する。
 */
$backendDir = dirname(__FILE__, 4);

require_once($backendDir.'/scripts/ApiVerifier.php');
require_once($backendDir.'/scripts/getBilledProcessingTime.php');
require_once('vendor/autoload.php');
require_once('hapi.php');
require_once('log.php');


Hapi::init();


// default status
http_response_code(400);

if (array_key_exists('meter-type', $_GET)
    && array_key_exists('proc-num', $_GET)) 
{
    $result = null;
    $meterType = $_GET['meter-type'];
    $procNum = $_GET['proc-num'];
    $procToken = $_GET['proc-token'];

    try {
        $verifier = new ApiVerifier((int)$procNum, $procToken);
        $verifier->verify();

        if ($meterType === 'processing-time') {
            $ret = getBilledProcessingTime((int)$procNum);
                    
            if ($ret) {
                $result = json_encode($ret);
                http_response_code(200);
            
            } else {
                http_response_code(500);
                throw new Exception('unexpected error');
            }
        } else {
            throw new Exception('invalid argument');
        }

    } catch (Exception $e) {
        writeLog($e, basename(__FILE__), $procNum);
    }

} else {
    http_response_code(400);
}

if ($result) {
    echo $result;
}



?>