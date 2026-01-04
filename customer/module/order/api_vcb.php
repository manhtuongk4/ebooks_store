<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logged_in']) || !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'UNAUTHORIZED',
        'message' => 'Bạn cần đăng nhập để kiểm tra thanh toán.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];

$orderId = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = isset($_POST['order_id']) ? trim($_POST['order_id']) : '';
} else {
    $orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
}

if ($orderId === '' || !preg_match('/^HD[0-9]+$/', $orderId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_ORDER_ID',
        'message' => 'Mã đơn hàng không hợp lệ.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$order = null;
if ($stmt = $conn->prepare('SELECT h.MaHD, h.NgayLapHD, h.TongTien, h.PhuongThucThanhToan, h.TrangThaiThanhToan, h.MaGiaoDichNganHang, k.MaKH
							 FROM hoa_don h
							 LEFT JOIN khach_hang k ON h.MaKH = k.MaKH
							 WHERE h.MaHD = ? AND h.MaKH = ? LIMIT 1')) {
    $stmt->bind_param('ss', $orderId, $userId);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result) {
            $order = $result->fetch_assoc();
        }
    }
    $stmt->close();
}

if (!$order) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'ORDER_NOT_FOUND',
        'message' => 'Không tìm thấy đơn hàng.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$paymentMethod = strtoupper($order['PhuongThucThanhToan'] ?? '');
if ($paymentMethod !== 'BIDV') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'UNSUPPORTED_METHOD',
        'message' => 'Đơn hàng không sử dụng phương thức thanh toán BIDV.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$status = strtoupper($order['TrangThaiThanhToan'] ?? '');
if ($status === 'DATHANHTOAN') {
    echo json_encode([
        'success' => true,
        'paid' => true,
        'message' => 'Đơn hàng đã được thanh toán.',
        'redirect_url' => BASE_URL . '/customer/module/order/cart_completed.php?order_id=' . urlencode($order['MaHD']),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$cassoApiKey = 'REPLACE_WITH_YOUR_CASSO_API_KEY';
if ($cassoApiKey === '' || $cassoApiKey === 'REPLACE_WITH_YOUR_CASSO_API_KEY') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'MISSING_CASSO_API_KEY',
        'message' => 'Chưa cấu hình CASSO API Key.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$fromDate = null;
if (!empty($order['NgayLapHD'])) {
    $ts = strtotime($order['NgayLapHD']);
    if ($ts !== false) {
        $fromDate = date('Y-m-d', max($ts - 86400, 0));
    }
}
if ($fromDate === null) {
    $fromDate = date('Y-m-d', strtotime('-1 day'));
}

$queryParams = [
    'fromDate' => $fromDate,
    'sort' => 'DESC',
    'pageSize' => 50,
];

$url = 'https://oauth.casso.vn/v2/transactions?' . http_build_query($queryParams);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => [
        'Authorization: Apikey ' . $cassoApiKey,
        'Content-Type: application/json',
    ],
]);

$response = curl_exec($ch);
$curlErr = curl_error($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErr) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'CASSO_CONNECTION_ERROR',
        'message' => 'Không kết nối được tới Casso: ' . $curlErr,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'CASSO_HTTP_' . $httpCode,
        'message' => 'Casso trả về mã lỗi HTTP ' . $httpCode,
        'raw' => $response,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($response, true);
if (!is_array($data)) {
    http_response_code(502);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_CASSO_RESPONSE',
        'message' => 'Phản hồi từ Casso không hợp lệ.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$records = [];
if (isset($data['data']['records']) && is_array($data['data']['records'])) {
    $records = $data['data']['records'];
} elseif (isset($data['records']) && is_array($data['records'])) {
    $records = $data['records'];
}

if (empty($records)) {
    echo json_encode([
        'success' => true,
        'paid' => false,
        'message' => 'Chưa tìm thấy giao dịch phù hợp trên Casso.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$orderCode = $order['MaHD'];
$matchedTransaction = null;

foreach ($records as $record) {
    $description = isset($record['description']) ? (string)$record['description'] : '';
    if ($description === '') {
        continue;
    }

    if (stripos($description, $orderCode) === false) {
        continue;
    }

    $amountFromBank = isset($record['amount']) ? (float)$record['amount'] : 0.0;
    $orderAmount = isset($order['TongTien']) ? (float)$order['TongTien'] : 0.0;

    if ($amountFromBank > 0 && $orderAmount > 0) {
        if (abs($amountFromBank - $orderAmount) > 1) {
            continue;
        }
    }

    $matchedTransaction = $record;
    break;
}

if ($matchedTransaction === null) {
    echo json_encode([
        'success' => true,
        'paid' => false,
        'message' => 'Không tìm thấy giao dịch trùng khớp với mã đơn hàng.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$conn->begin_transaction();

try {
    if (!empty($order['MaGiaoDichNganHang'])) {
        if ($stmtUpTran = $conn->prepare('UPDATE giao_dich_thanh_toan SET TrangThai = ?, PhanHoiTuNganHang = ? WHERE MaGiaoDich = ?')) {
            $newStatus = 'DaThanhToan';
            $jsonBank = json_encode($matchedTransaction, JSON_UNESCAPED_UNICODE);
            $stmtUpTran->bind_param('sss', $newStatus, $jsonBank, $order['MaGiaoDichNganHang']);
            $stmtUpTran->execute();
            $stmtUpTran->close();
        }
    }

    if ($stmtUpOrder = $conn->prepare('UPDATE hoa_don SET TrangThaiThanhToan = ? WHERE MaHD = ?')) {
        $paidStatus = 'DaThanhToan';
        $stmtUpOrder->bind_param('ss', $paidStatus, $order['MaHD']);
        $stmtUpOrder->execute();
        $stmtUpOrder->close();
    }

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DB_ERROR',
        'message' => 'Lỗi khi cập nhật trạng thái thanh toán.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'success' => true,
    'paid' => true,
    'message' => 'Đã ghi nhận thanh toán thành công từ Casso.',
    'redirect_url' => BASE_URL . '/customer/module/order/cart_completed.php?order_id=' . urlencode($order['MaHD']),
    'transaction' => $matchedTransaction,
], JSON_UNESCAPED_UNICODE);
exit;
