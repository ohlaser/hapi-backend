<?php
$backendDir = dirname(__FILE__, 4);



function writeLog($logString, $fileName, $processorId) {
    global $backendDir;

    $logString .= "\n";

    $fp = fopen($backendDir.'/log/'.$fileName.'.log', 'a');
    flock($fp, LOCK_EX);

    fwrite($fp, date("Y-m-d H:i:s"). ' proc_no: '. $processorId . ', message: ' . $logString);

    flock($fp, LOCK_UN);
    fclose($fp);
}

?>