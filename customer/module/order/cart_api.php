<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

header('Content-Type: application/json; charset=utf-8');

// Chuẩn hoá phản hồi JSON
function json_response($data)
{
    echo json_encode($data);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Tính tổng số lượng sản phẩm trong giỏ
function get_cart_count()
{
    if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
        if ($qty < 1) {
            $qty = 1;
        }
        $count += $qty;
    }
    return $count;
}

if ($action === 'add') {
    // Admin chỉ được xem, không được thao tác giỏ hàng
    if (isset($_SESSION['admin_id'])) {
        json_response([
            'success' => false,
            'message' => 'Tài khoản quản trị chỉ được xem, không thể đặt mua.'
        ]);
    }

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        json_response([
            'success' => false,
            'require_login' => true,
            'message' => 'Bạn cần đăng nhập trước khi thêm vào giỏ hàng.'
        ]);
    }

    $maSach = isset($_POST['masach']) ? trim($_POST['masach']) : '';
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;

    if ($qty < 1) {
        $qty = 1;
    }

    if ($maSach === '' || !preg_match('/^[A-Za-z0-9]+$/', $maSach)) {
        json_response([
            'success' => false,
            'message' => 'Mã sách không hợp lệ.'
        ]);
    }

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$maSach])) {
        $_SESSION['cart'][$maSach]['quantity'] += $qty;
    } else {
        $_SESSION['cart'][$maSach] = [
            'quantity' => $qty
        ];
    }

    json_response([
        'success' => true,
        'cartCount' => get_cart_count()
    ]);
} elseif ($action === 'remove') {
    $maSach = isset($_POST['masach']) ? trim($_POST['masach']) : '';

    if ($maSach !== '' && isset($_SESSION['cart'][$maSach])) {
        unset($_SESSION['cart'][$maSach]);
    }

    json_response([
        'success' => true,
        'cartCount' => get_cart_count()
    ]);
} elseif ($action === 'clear') {
    unset($_SESSION['cart']);
    json_response([
        'success' => true,
        'cartCount' => 0
    ]);
} elseif ($action === 'get_count') {
    json_response([
        'success' => true,
        'cartCount' => get_cart_count()
    ]);
}

json_response([
    'success' => false,
    'message' => 'Hành động không hợp lệ.'
]);
