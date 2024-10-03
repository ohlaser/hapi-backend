<?php


/**
 * Stripeオブジェクトを操作するためのOhLaserラッパークラス
 * レート制限を考慮
 */
class StripeController
{

    /**
     * レート制限に達した際のリトライまでのスパン(秒)
     */
    private const RATE_LIMIT_RETRY_DURATION = 1;

    /**
     * レート制限時の最大リトライ秒数
     */
    private const RATE_LIMIT_MAX_RETRY_DURATION = 10;

    /**
     * 指定したStripe APIを呼び出す。
     * 失敗した場合は最大回数まで再試行する。
     */
    public static function executeWithRetry($apiCallback, ...$args) {
        $result = false;
        $startTime = time();
        $lastEx = null;

        while (time() < $startTime + self::RATE_LIMIT_MAX_RETRY_DURATION) {
            try {
                $result = $apiCallback(...$args);
                break;
    
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $lastEx = $e;
                if ($e->getHttpStatus === 429) { // レート制限
                    sleep($e->getHttpHeaders('Retry-After') ?? RATE_LIMIT_RETRY_DURATION);
                
                } else {
                    break;
                }
            }
        }
        if ($result === false) {
            throw $lastEx ?? new Exception('unexpected error');
        }

        return $result;
    }
}

?>
