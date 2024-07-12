<?php
$backendDir = dirname(__FILE__, 4);



function writeLog($e, $logFileName, $processorId) {

    $fp = fopen($backendDir.'/log/'.$logFileName, 'a');
    flock($fp, LOCK_EX);

    fwrite($fp, date("Y-m-d H:i:s"). ' proc_no: '. $processorId . ', message: ' . $e);

    flock($fp, LOCK_UN);
    fclose($fp);
}

?>