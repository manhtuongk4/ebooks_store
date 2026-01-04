<?php
// File này giữ lại để tương thích với các đường dẫn cũ.
// Chuyển tiếp toàn bộ xử lý sang trang BIDV mới.

// Bảo toàn query string nếu có
$queryString = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';

header('Location: bidv.php' . $queryString);
exit;
