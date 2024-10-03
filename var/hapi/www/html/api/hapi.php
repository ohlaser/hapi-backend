<?php
$backendDir = dirname(__FILE__, 4);
require_once($backendDir.'/scripts/Resources.php');


/**
 * hapi共通の処理
 */
class Hapi
{
    /**
     * クラスの初期化
     */
    public static function init()
    {
        date_default_timezone_set('Asia/Tokyo');
        Resources::init();
    }
}

?>
