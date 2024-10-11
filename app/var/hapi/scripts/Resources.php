<?php
/**
 * データベース情報、Stripe情報など外部リソース情報の定義
 */

class Resources
{

    /**
     * テストモードフラグ
     */
    public static $isTest;

    /**
     * OLCデータベース情報
     */
    public static $olcdb;

    /**
     * ワードプレス環境データベース情報
     */
    public static $olWpdbParam;

    /**
     * Stripeシークレットキー
     */
    public static $stripeSecretKey;

    /**
     * stirpe パブリックキー
     */
    public static $stripePublicKey;

    /**
     * Stripe API バージョン
     */
    public static $stripeApiVersion;

    /**
     * stripe 加工時間メーター名
     */
    public static $procTimeMeterName;

    /**
     * stripe 加工時間メーター 価格ID
     */
    public static $procTimeMeterPriceId;
    
    /**
     * KOMOJU
     */
    public static $komojuSecretKey;


    /**
     * クラスイニシャライザ
     */
    public static function init()
    {
        // OLC database informations
        $olcdb = [];
        $olcdb['dsn'] = 'mysql:host=153.127.49.86;dbname=ol_kokyaku;port=3306';
        $olcdb['username'] = 'remote_user';
        $olcdb['password'] = 'UGABDc5ktVPmVszjXsEDGfre3Jwk5RUd';
        $olcdb['options'] = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        self::$olcdb = $olcdb;

        // stripe 
        self::$stripeApiVersion = '2024-04-10';
        
        // ohlaser database info on wordpress server
        self::$olWpdbParam = [
            /* 'dsn' => */ 'mysql:host=localhost;dbname=knzdkgya_stripe;port=3306',
            /* 'username' => */ 'knzdkgya_stripe',
            /* 'password' => */ 'yPtYRMjBPb8Xp2Vb4WtdL75PruQDMwVF',
            /* 'options' => */ [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];

        self::$procTimeMeterName = 'processing_time';

        // テストモードか否かで変化するリソースの初期化
        // 本番環境では基本触らないのでロック無し
        if (substr(file_get_contents(dirname(__FILE__).'/../data/testmode'), 0, 1) === '1') { 
            self::initAsTestMode();
        } else {
            self::initAsLiveMode();
        }
    }

    
    /**
     * テスト環境用に初期化する
     */
    private static function initAsTestMode() {
        
        self::$isTest = true;

        // stripe settings
        self::$stripeSecretKey = 'sk_test_51ODMxoDSRUXumGeOSAzEYSkfvkh3Fsx8fMChXJM3SvI90PFTzGR6q2Orh82dsH8FCEFn1GsoUSRdYCcp2lhu85Ek00lFzmiyjN';
        self::$stripePublicKey = 'pk_test_51ODMxoDSRUXumGeO6qFkTa5Y89XgjBavnLGWmXYMJjpQrftQRl9shm6VnluSbqK1Ifi4s9s022IcKnVlCSS1lG7D00cmAsjPac';
        self::$procTimeMeterPriceId = 'price_1PiSqcDSRUXumGeOmvBdofAI';

        // KOMOJU
        self::$komojuSecretKey = 'sk_test_8iurno66j8vhcghv1ee6twvy';
    }


    /**
     * 本番環境用に初期化する
     */
    private static function initAsLiveMode() {

        self::$isTest = false;

        // stripe settings
        self::$stripeSecretKey = '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';
        self::$stripePublicKey = 'pk_live_51ODMxoDSRUXumGeOIXxdo8sygYIyz8W2coN8jr6gc9ZHlCC5W0rMmUs6zmC47ROuGV4hxSnM6wbcGv26GLheWY5n00MJ9Els5V';
        self::$procTimeMeterPriceId = 'price_1Q0cpzDSRUXumGeOsEzEKP0v';

        // KOMOJU
        self::$komojuSecretKey = 'sk_live_2o6am2nt194gtmidmznvs7bj';
    }

}


?>