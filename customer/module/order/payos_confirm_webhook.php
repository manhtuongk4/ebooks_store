<?php
// Script test/confirm webhook URL với PayOS
// Chỉ dùng cho môi trường dev/admin, không nên để public lâu dài.

require_once dirname(__DIR__, 3) . '/config/configpath.php';

header('Content-Type: text/plain; charset=utf-8');

$payosClientId = getenv('PAYOS_CLIENT_ID') ?: (defined('PAYOS_CLIENT_ID') ? PAYOS_CLIENT_ID : '');
$payosApiKey = getenv('PAYOS_API_KEY') ?: (defined('PAYOS_API_KEY') ? PAYOS_API_KEY : '');
$payosChecksumKey = getenv('PAYOS_CHECKSUM_KEY') ?: (defined('PAYOS_CHECKSUM_KEY') ? PAYOS_CHECKSUM_KEY : '');

$autoloadPath = dirname(__DIR__, 3) . '/vendor/autoload.php';

if (!$payosClientId || !$payosApiKey || !$payosChecksumKey) {
    echo "Thiếu cấu hình PAYOS_CLIENT_ID / PAYOS_API_KEY / PAYOS_CHECKSUM_KEY (biến môi trường hoặc hằng số trong configpath.php)" . PHP_EOL;
    exit;
}

if (!file_exists($autoloadPath)) {
    echo "Không tìm thấy vendor/autoload.php. Hãy chạy composer require payos/payos guzzlehttp/guzzle" . PHP_EOL;
    exit;
}

require_once $autoloadPath;

try {
    $payOS = new \PayOS\PayOS(
        clientId: $payosClientId,
        apiKey: $payosApiKey,
        checksumKey: $payosChecksumKey
    );

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $domain = $scheme . '://' . $host;

    $webhookUrl = $domain . BASE_URL . '/customer/module/order/payos_webhook.php';

    $result = $payOS->webhooks->confirm($webhookUrl);

    echo "Đã gọi webhooks->confirm thành công cho URL: " . $webhookUrl . PHP_EOL;
    echo 'Kết quả: ' . print_r($result, true) . PHP_EOL;
} catch (Throwable $e) {
    echo 'Lỗi khi confirm webhook: ' . $e->getMessage() . PHP_EOL;
}
