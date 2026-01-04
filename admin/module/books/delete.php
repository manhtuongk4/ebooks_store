<?php
session_start();
require_once dirname(__DIR__, 3) . '/connected.php';
require_once dirname(__DIR__, 3) . '/config/configpath.php';

if (!isset($_GET['id'])) {
    die('Thiếu mã sách.');
}
$id = mysqli_real_escape_string($conn, $_GET['id']);

// Kiểm tra sách có tồn tại không
$sql = "SELECT * FROM sach WHERE MaSach='$id'";
$result = mysqli_query($conn, $sql);
if (!$result || mysqli_num_rows($result) == 0) {
    die('Không tìm thấy sách.');
}

// Xóa sách
$delete = "DELETE FROM sach WHERE MaSach='$id'";
if (mysqli_query($conn, $delete)) {
    // Xóa thành công, chuyển về danh sách
    header('Location: books.php?msg=deleted');
    exit;
} else {
    die('Lỗi xóa sách: ' . mysqli_error($conn));
}
