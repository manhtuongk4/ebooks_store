<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: translator.php');
    exit();
}

$maDichGia = $_GET['id'];
$maDichGia_sql = mysqli_real_escape_string($conn, $maDichGia);

// Kiểm tra xem dịch giả có đang được liên kết với sách không
$sql_check = "SELECT COUNT(*) as total FROM sach_dichgia WHERE MaDichGia = '" . $maDichGia_sql . "'";
$result_check = mysqli_query($conn, $sql_check);
$canDelete = true;
if ($result_check && $row = mysqli_fetch_assoc($result_check)) {
    if ((int)$row['total'] > 0) {
        $canDelete = false;
    }
}

if ($canDelete) {
    $sql_delete = "DELETE FROM dich_gia WHERE MaDichGia = '" . $maDichGia_sql . "' LIMIT 1";
    mysqli_query($conn, $sql_delete);
}

header('Location: translator.php');
exit();
