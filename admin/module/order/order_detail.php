<?php
session_start();

require_once __DIR__ . '/../../../config/configpath.php';
require_once __DIR__ . '/../../../connected.php';

// Chỉ cho phép admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: ' . BASE_URL . '/login_register/login.php');
    exit;
}

// Lấy mã đơn từ URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: order.php');
    exit;
}

$maHD = $_GET['id'];

// Lấy thông tin đơn hàng + khách hàng
$maHD_safe = mysqli_real_escape_string($conn, $maHD);

$sql_order = "SELECT h.MaHD, h.NgayLapHD, h.TongTien, h.PhuongThucThanhToan, h.TrangThaiThanhToan, 
					 h.MaKH, k.HoTenKH, k.DiaChi, k.SoDienThoai, k.Email
			  FROM hoa_don h
			  LEFT JOIN khach_hang k ON h.MaKH = k.MaKH
			  WHERE h.MaHD = '$maHD_safe'
			  LIMIT 1";

$order_res = mysqli_query($conn, $sql_order);
if (!$order_res || mysqli_num_rows($order_res) === 0) {
    echo '<div style="padding:24px; color:#ff7f7f;">Không tìm thấy đơn hàng!</div>';
    exit;
}

$order = mysqli_fetch_assoc($order_res);

// Lấy chi tiết đơn hàng
$items = [];
$sql_items = "SELECT c.MaSach, c.SoLuongBan, c.DonGiaBan, c.ThanhTien, s.TenSach
			  FROM chi_tiet_hoa_don c
			  LEFT JOIN sach s ON c.MaSach = s.MaSach
			  WHERE c.MaHD = '$maHD_safe'";

if ($res_it = mysqli_query($conn, $sql_items)) {
    while ($row = mysqli_fetch_assoc($res_it)) {
        $items[] = $row;
    }
}

// Hàm hiển thị trạng thái thanh toán
function render_payment_status_detail(string $statusRaw): string
{
    $st = strtoupper(trim($statusRaw));
    if ($st === 'DATHANHTOAN' || $st === 'DA THANHTOAN') {
        return 'Đã thanh toán';
    }
    if ($st === 'CHOTHANHTOAN' || $st === 'CHO THANHTOAN') {
        return 'Chờ thanh toán';
    }
    if ($st === 'CHUATHANHTOAN' || $st === 'CHUA THANHTOAN') {
        return 'Chưa thanh toán';
    }
    if ($st === 'HUY') {
        return 'Đã hủy';
    }
    return htmlspecialchars($statusRaw, ENT_QUOTES, 'UTF-8');
}
// Chuẩn hóa mã trạng thái từ dạng UPPER về key chuẩn dùng trong hệ thống
function normalize_status_key(string $statusRaw): ?string
{
    $st = strtoupper(trim($statusRaw));
    if ($st === 'CHUATHANHTOAN' || $st === 'CHUA THANHTOAN') {
        return 'ChuaThanhToan';
    }
    if ($st === 'CHOTHANHTOAN' || $st === 'CHO THANHTOAN') {
        return 'ChoThanhToan';
    }
    if ($st === 'DATHANHTOAN' || $st === 'DA THANHTOAN') {
        return 'DaThanhToan';
    }
    if ($st === 'HUY') {
        return 'Huy';
    }
    return null;
}

// Tính các trạng thái được phép chọn theo phương thức thanh toán + trạng thái hiện tại
function get_allowed_status_keys(string $methodUpper, string $statusUpper): array
{
    $isPaid = ($statusUpper === 'DATHANHTOAN' || $statusUpper === 'DA THANHTOAN');
    $isPending = ($statusUpper === 'CHOTHANHTOAN' || $statusUpper === 'CHO THANHTOAN');
    $isUnpaid = ($statusUpper === 'CHUATHANHTOAN' || $statusUpper === 'CHUA THANHTOAN');
    $isCanceled = ($statusUpper === 'HUY');

    $allowed = [];

    if ($methodUpper === 'BIDV') {
        // Banking BIDV: chờ thanh toán -> đã thanh toán / hủy; đã thanh toán -> hủy; hủy -> giữ nguyên
        if ($isPending || $isUnpaid) {
            $allowed = ['ChoThanhToan', 'DaThanhToan', 'Huy'];
        } elseif ($isPaid) {
            $allowed = ['DaThanhToan', 'Huy'];
        } elseif ($isCanceled) {
            $allowed = ['Huy'];
        } else {
            $allowed = ['ChoThanhToan', 'DaThanhToan', 'Huy'];
        }
    } else {
        // COD hoặc phương thức khác: chưa thanh toán -> đã thanh toán / hủy; đã thanh toán -> hủy; hủy -> giữ nguyên
        if ($isUnpaid || $isPending) {
            $allowed = ['ChuaThanhToan', 'DaThanhToan', 'Huy'];
        } elseif ($isPaid) {
            $allowed = ['DaThanhToan', 'Huy'];
        } elseif ($isCanceled) {
            $allowed = ['Huy'];
        } else {
            $allowed = ['ChuaThanhToan', 'DaThanhToan', 'Huy'];
        }
    }

    return array_values(array_unique($allowed));
}

$page_title = 'Chi tiết đơn hàng - ' . htmlspecialchars($order['MaHD'], ENT_QUOTES, 'UTF-8');

// Bảng nhãn hiển thị cho các trạng thái
$statusLabels = [
    'ChuaThanhToan' => 'Chưa thanh toán',
    'ChoThanhToan' => 'Chờ thanh toán',
    'DaThanhToan' => 'Đã thanh toán',
    'Huy' => 'Đã hủy',
];

// Xử lý thay đổi trạng thái từ bảng điều khiển nhỏ
$updateMsg = '';
$updateType = '';

$methodUpper = strtoupper($order['PhuongThucThanhToan'] ?? '');
$statusUpper = strtoupper(trim($order['TrangThaiThanhToan'] ?? ''));
$currentKey = normalize_status_key($order['TrangThaiThanhToan'] ?? '') ?? 'ChuaThanhToan';
$allowedStatusKeys = get_allowed_status_keys($methodUpper, $statusUpper);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $newKey = $_POST['TrangThaiThanhToan'] ?? '';

    if (!isset($statusLabels[$newKey])) {
        $updateMsg = 'Trạng thái không hợp lệ.';
        $updateType = 'error';
    } elseif (!in_array($newKey, $allowedStatusKeys, true)) {
        $updateMsg = 'Không được phép chuyển sang trạng thái này với phương thức thanh toán hiện tại.';
        $updateType = 'error';
    } else {
        // Nếu không thay đổi thì bỏ qua
        if ($newKey === $currentKey) {
            $updateMsg = 'Trạng thái không thay đổi.';
            $updateType = 'success';
        } else {
            $sql_upd = 'UPDATE hoa_don SET TrangThaiThanhToan = ? WHERE MaHD = ?';
            $stmt = mysqli_prepare($conn, $sql_upd);
            mysqli_stmt_bind_param($stmt, 'ss', $newKey, $maHD_safe);
            if (mysqli_stmt_execute($stmt)) {
                $order['TrangThaiThanhToan'] = $newKey;
                $statusUpper = strtoupper(trim($newKey));
                $currentKey = $newKey;
                $allowedStatusKeys = get_allowed_status_keys($methodUpper, $statusUpper);
                $updateMsg = 'Cập nhật trạng thái đơn hàng thành công.';
                $updateType = 'success';
            } else {
                $updateMsg = 'Có lỗi khi cập nhật trạng thái đơn hàng.';
                $updateType = 'error';
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #181a1b;
            color: #f1f1f1;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 32px;
            background: #202225;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 22px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s, transform 0.1s;
            font-size: 13px;
        }

        .btn-secondary {
            background: #343a40;
            color: #fff;
        }

        .btn-secondary:hover {
            background: #23272a;
        }

        .card {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            padding: 20px 18px;
            margin-bottom: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        .card h2 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 8px 16px;
            font-size: 14px;
        }

        .info-label {
            color: #9aa4b5;
        }

        .table-wrap {
            background: #181a1b;
            border: 1px solid #2c2f33;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #f1f1f1;
        }

        thead {
            background: #23272a;
        }

        th,
        td {
            padding: 10px 12px;
            text-align: left;
            font-size: 14px;
        }

        th {
            font-weight: 700;
        }

        tbody tr:nth-child(odd) {
            background: #1f2225;
        }

        tbody tr:nth-child(even) {
            background: #1b1e21;
        }

        tbody tr:hover {
            background: #2a2e32;
        }

        .text-right {
            text-align: right;
        }

        .badge-status {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 999px;
        }

        .badge-status-paid {
            background: #14532d;
            color: #bbf7d0;
        }

        .badge-status-pending {
            background: #422006;
            color: #fed7aa;
        }

        .badge-status-unpaid {
            background: #4a044e;
            color: #fbcfe8;
        }

        .badge-status-cancel {
            background: #450a0a;
            color: #fecaca;
        }

        .alert {
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }

        .alert-success {
            background: #223d29;
            color: #7fffa7;
            border: 1px solid #2e7d32;
        }

        .alert-error {
            background: #3d2222;
            color: #ff7f7f;
            border: 1px solid #c62828;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../../layout/header.php'; ?>

    <div class="admin-container">
        <?php include __DIR__ . '/../../partials/sidebar.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <div>
                    <h1>Chi tiết đơn hàng #<?php echo htmlspecialchars($order['MaHD'], ENT_QUOTES, 'UTF-8'); ?></h1>
                </div>
                <a class="btn btn-secondary" href="order.php"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
            </div>

            <?php
            $raw = $order['TrangThaiThanhToan'] ?? '';
            $label = render_payment_status_detail($raw);
            $cls = 'badge-status-unpaid';
            $upper = strtoupper(trim($raw));
            if ($upper === 'DATHANHTOAN' || $upper === 'DA THANHTOAN') {
                $cls = 'badge-status-paid';
            } elseif ($upper === 'CHOTHANHTOAN' || $upper === 'CHO THANHTOAN') {
                $cls = 'badge-status-pending';
            } elseif ($upper === 'HUY') {
                $cls = 'badge-status-cancel';
            }

            $pm = strtoupper($order['PhuongThucThanhToan'] ?? '');
            if ($pm === 'COD') {
                $pm_label = 'COD';
            } elseif ($pm === 'BIDV') {
                $pm_label = 'BIDV';
            } else {
                $pm_label = htmlspecialchars($order['PhuongThucThanhToan'], ENT_QUOTES, 'UTF-8');
            }
            ?>

            <?php if ($updateMsg): ?>
                <div class="alert <?php echo $updateType === 'success' ? 'alert-success' : 'alert-error'; ?>"><?php echo htmlspecialchars($updateMsg, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <div class="card">
                <h2>Bảng điều khiển trạng thái</h2>
                <form method="post" autocomplete="off">
                    <div class="info-grid">
                        <div>
                            <div class="info-label">Phương thức</div>
                            <div><?php echo $pm_label; ?></div>
                        </div>
                        <div>
                            <div class="info-label">Trạng thái hiện tại</div>
                            <div><span class="badge-status <?php echo $cls; ?>"><?php echo $label; ?></span></div>
                        </div>
                        <div>
                            <div class="info-label">Chọn trạng thái mới</div>
                            <select name="TrangThaiThanhToan" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid #343a40;background:#23272a;color:#f1f1f1;">
                                <?php foreach ($allowedStatusKeys as $key): ?>
                                    <option value="<?php echo $key; ?>" <?php echo ($currentKey === $key) ? 'selected' : ''; ?>>
                                        <?php echo $statusLabels[$key]; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:16px; display:flex; justify-content:flex-end; gap:10px;">
                        <button type="submit" name="change_status" class="btn btn-secondary"><i class="fas fa-save"></i> Lưu trạng thái</button>
                    </div>
                    <div style="margin-top:10px; font-size:12px; color:#9aa4b5;">
                        <strong>Quy tắc:</strong>
                        <ul style="margin:6px 0 0 18px; padding:0; list-style:disc;">
                            <li>COD: từ "Chưa thanh toán" có thể chuyển sang "Đã thanh toán" hoặc "Đã hủy"; từ "Đã thanh toán" chỉ được chuyển sang "Đã hủy".</li>
                            <li>Banking (BIDV): từ "Chờ thanh toán" có thể chuyển sang "Đã thanh toán" (khi đã kiểm tra tiền về) hoặc "Đã hủy"; đơn "Đã thanh toán" chỉ được chuyển sang "Đã hủy".</li>
                        </ul>
                    </div>
                </form>
            </div>

            <div class="card">
                <h2>Thông tin chung</h2>
                <div class="info-grid">
                    <div>
                        <div class="info-label">Mã đơn</div>
                        <div><?php echo htmlspecialchars($order['MaHD'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Ngày lập</div>
                        <div><?php echo htmlspecialchars($order['NgayLapHD'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Khách hàng</div>
                        <div><?php echo htmlspecialchars($order['HoTenKH'] ?? ('KH: ' . $order['MaKH']), ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Số điện thoại</div>
                        <div><?php echo htmlspecialchars($order['SoDienThoai'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Email</div>
                        <div><?php echo htmlspecialchars($order['Email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Địa chỉ</div>
                        <div><?php echo htmlspecialchars($order['DiaChi'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Phương thức thanh toán</div>
                        <div><?php echo $pm_label; ?></div>
                    </div>
                    <div>
                        <div class="info-label">Trạng thái thanh toán</div>
                        <div><span class="badge-status <?php echo $cls; ?>"><?php echo $label; ?></span></div>
                    </div>
                    <div>
                        <div class="info-label">Tổng tiền</div>
                        <div><?php echo number_format($order['TongTien'], 0, ',', '.'); ?>₫</div>
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Mã sách</th>
                            <th>Tên sách</th>
                            <th class="text-right">Số lượng</th>
                            <th class="text-right">Đơn giá</th>
                            <th class="text-right">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)) : ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:16px; color:#9aa4b5;">Đơn hàng không có chi tiết sản phẩm.</td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($items as $it) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($it['MaSach'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($it['TenSach'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-right"><?php echo (int)$it['SoLuongBan']; ?></td>
                                    <td class="text-right"><?php echo number_format($it['DonGiaBan'], 0, ',', '.'); ?>₫</td>
                                    <td class="text-right"><?php echo number_format($it['ThanhTien'], 0, ',', '.'); ?>₫</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../../layout/footer.php'; ?>
</body>

</html>