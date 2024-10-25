<?php
/**
 * メインサーバーの実行状態を確認して問題があればCloudFlare上のDNS設定を変更する。
 * 回復したら元に戻す。
 */

/**
 * 設定
 */
$masterServer = '153.127.49.86'; // マスターサーバーのIPアドレス
$failoverIP = '133.18.178.152'; // フェイルオーバーサーバーのIPアドレス
$zoneID = '74f3e595ec3c57635eb2448833023148'; // CloudflareのゾーンID
$dnsRecordID = 'd9668fbfc67fa4abd587443ee04bf998'; // 更新するDNSレコードのID
$apiToken = 'sq9Hi3DCioK-fvBWdmi3LFIzBCBkpqwJMdbQEEO7'; // Cloudflare APIトークン
$domainName = 'ytwv8sek59ictf9p.oh-laser.com'; // 監視するドメイン名


/**
 * マスターサーバーの状態を監視する関数
 */
function isMasterServerUp($server) {
    $output = [];
    $result = 0;
    exec("ping -c 1 $server", $output, $result);
    return $result === 0;
}

/**
 * 現在のDNSレコードを取得する関数
 */
function getCurrentDNSRecord($zoneID, $dnsRecordID, $apiToken) {
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneID/dns_records/$dnsRecordID";
    
    $options = [
        'http' => [
            'header' => [
                "Authorization: Bearer $apiToken"
            ],
            'method' => 'GET',
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        echo "DNSレコードの取得に失敗しました。\n";
        return null;
    }

    $data = json_decode($result, true);
    return $data['result'] ?? null;
}

/**
 * DNSレコードを更新する関数
 */
function updateDNSRecord($zoneID, $dnsRecordID, $ipAddress, $apiToken, $domainName) {
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneID/dns_records/$dnsRecordID";
    
    $data = [
        'type' => 'A',
        'name' => $domainName,
        'content' => $ipAddress,
        'ttl' => 300,
        'proxied' => true
    ];
    
    $options = [
        'http' => [
            'header' => [
                "Content-Type: application/json",
                "Authorization: Bearer $apiToken"
            ],
            'method' => 'PUT',
            'content' => json_encode($data),
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === false) {
        echo "DNS更新に失敗しました。\n";
    } else {
        echo "DNSが $ipAddress に更新されました。\n";
    }
}

/**
 * メイン処理
 */
$currentDNSRecord = getCurrentDNSRecord($zoneID, $dnsRecordID, $apiToken);

if ($currentDNSRecord) {
    $currentIP = $currentDNSRecord['content'];
    echo "現在のDNS設定: $currentIP\n";

    if (!isMasterServerUp($masterServer)) {
        echo "マスターサーバーがダウンしています。\n";
        if ($currentIP !== $failoverIP) {
            echo "DNSをフェイルオーバーサーバーに更新します。\n";
            updateDNSRecord($zoneID, $dnsRecordID, $failoverIP, $apiToken, $domainName);
        } else {
            echo "既にDNSはフェイルオーバーサーバーに設定されています。\n";
        }
    } else {
        echo "マスターサーバーは正常です。\n";
        if ($currentIP !== $masterServer) {
            echo "DNSを元のマスターサーバーに戻します。\n";
            updateDNSRecord($zoneID, $dnsRecordID, $masterServer, $apiToken, $domainName);
        } else {
            echo "既にDNSはマスターサーバーに設定されています。\n";
        }
    }
} else {
    echo "DNSレコードが見つかりませんでした。\n";
}

?>
