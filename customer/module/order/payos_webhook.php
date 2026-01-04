<?php
// Webhook nhận thông báo thanh toán từ PayOS
// Cần cấu hình URL này trong dashboard PayOS

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Đảm bảo luôn trả về JSON
header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// Đọc payload JSON từ PayOS
$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body']);
    exit;
}

// Lấy cấu hình PayOS: ưu tiên biến môi trường, nếu thiếu thì dùng hằng số trong configpath.php
$payosClientId = getenv('PAYOS_CLIENT_ID') ?: (defined('PAYOS_CLIENT_ID') ? PAYOS_CLIENT_ID : '');
$payosApiKey = getenv('PAYOS_API_KEY') ?: (defined('PAYOS_API_KEY') ? PAYOS_API_KEY : '');
$payosChecksumKey = getenv('PAYOS_CHECKSUM_KEY') ?: (defined('PAYOS_CHECKSUM_KEY') ? PAYOS_CHECKSUM_KEY : '');

$autoloadPath = dirname(__DIR__, 3) . '/vendor/autoload.php';

if (!$payosClientId || !$payosApiKey || !$payosChecksumKey || !file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PayOS configuration or SDK not available on server',
    ]);
    exit;
}

require_once $autoloadPath;

try {
    $payOS = new \PayOS\PayOS(
        clientId: $payosClientId,
        apiKey: $payosApiKey,
        checksumKey: $payosChecksumKey
    );

    // Xác minh chữ ký webhook
    try {
        $webhookData = $payOS->webhooks->verified($payload);
    } catch (\PayOS\Exceptions\WebhookException $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid webhook: ' . $e->getMessage(),
        ]);
        exit;
    }

    // Chuyển sang dạng mảng để dễ thao tác (tuỳ theo kiểu trả về)
    if (is_object($webhookData)) {
        $webhookArray = json_decode(json_encode($webhookData), true);
    } else {
        $webhookArray = $webhookData;
    }

    // Lấy một số trường quan trọng (tên trường có thể thay đổi tùy PayOS, nên dùng kiểm tra phòng thủ)
    $status = $webhookArray['status'] ?? ($webhookArray['data']['status'] ?? null);
    $amount = $webhookArray['amount'] ?? ($webhookArray['data']['amount'] ?? null);
    $description = $webhookArray['description'] ?? ($webhookArray['data']['description'] ?? '');

    // Một số phiên bản PayOS có thể trả về các giá trị trạng thái khác nhau cho giao dịch thành công
    // (ví dụ: SUCCESS, PAID, SUCCEEDED...). Ta coi các trạng thái này là thành công.
    $statusUpper = $status ? strtoupper($status) : '';
    $successStatuses = ['SUCCESS', 'PAID', 'SUCCEEDED'];

    if ($statusUpper === '' || !in_array($statusUpper, $successStatuses, true)) {
        // Không phải thanh toán thành công thì bỏ qua (trả 200 để PayOS không retry quá nhiều)
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Ignored non-success status: ' . $statusUpper]);
        exit;
    }

    // Tìm mã đơn hàng trong description, ví dụ: "Thanh toán đơn hàng HD0001"
    $orderId = null;
    if (preg_match('/HD[0-9]+/', (string)$description, $m)) {
        $orderId = $m[0];
    }

    if (!$orderId) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'No order code found in description',
        ]);
        exit;
    }

    $amountValue = $amount !== null ? (float)$amount : 0.0;

    // Bắt đầu cập nhật CSDL
    $conn->begin_transaction();

    try {
        // Lấy thông tin hóa đơn
        $order = null;
        if ($stmt = $conn->prepare('SELECT MaHD, TongTien, TrangThaiThanhToan FROM hoa_don WHERE MaHD = ? LIMIT 1')) {
            $stmt->bind_param('s', $orderId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                if ($res) {
                    $order = $res->fetch_assoc();
                }
            }
            $stmt->close();
        }

        if (!$order) {
            // Không tìm thấy đơn, vẫn commit để tránh retry liên tục
            $conn->commit();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Order not found for webhook',
            ]);
            exit;
        }

        // Nếu đã thanh toán rồi thì bỏ qua, tránh ghi trùng
        if (strtoupper($order['TrangThaiThanhToan'] ?? '') === 'DATHANHTOAN') {
            $conn->commit();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Order already marked as paid',
            ]);
            exit;
        }

        $orderAmount = (float)$order['TongTien'];
        // Nếu amount > 0 thì kiểm tra gần đúng với tổng tiền đơn (chênh <= 1đ)
        if ($amountValue > 0 && abs($orderAmount - $amountValue) > 1) {
            $conn->commit();
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Amount mismatch, ignore webhook',
            ]);
            exit;
        }

        // Ghi log giao dịch vào bảng giao_dich_thanh_toan
        $maGD = 'GD' . date('ymdHis') . rand(100, 999);
        $jsonBank = json_encode($webhookArray, JSON_UNESCAPED_UNICODE);
        $trangThaiGD = 'DaThanhToan';

        if ($stmtGD = $conn->prepare('INSERT INTO giao_dich_thanh_toan (MaGiaoDich, MaHD, SoTien, NoiDungThanhToan, TrangThai, PhanHoiTuNganHang) VALUES (?, ?, ?, ?, ?, ?)')) {
            $stmtGD->bind_param('ssdsss', $maGD, $orderId, $orderAmount, $description, $trangThaiGD, $jsonBank);
            $stmtGD->execute();
            $stmtGD->close();
        }

        // Cập nhật trạng thái thanh toán của hóa đơn
        if ($stmtUpOrder = $conn->prepare('UPDATE hoa_don SET TrangThaiThanhToan = ? WHERE MaHD = ?')) {
            $paidStatus = 'DaThanhToan';
            $stmtUpOrder->bind_param('ss', $paidStatus, $orderId);
            $stmtUpOrder->execute();
            $stmtUpOrder->close();
        }

        $conn->commit();

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Webhook processed, order marked as paid',
        ]);
        exit;
    } catch (\Throwable $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'DB error: ' . $e->getMessage(),
        ]);
        exit;
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal error: ' . $e->getMessage(),
    ]);
    exit;
}
