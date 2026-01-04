<?php
session_start();

require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

$pageAction = isset($_POST['action']) ? $_POST['action'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($pageAction === 'checkout') {
        if (!empty($_POST['selected']) && is_array($_POST['selected'])) {
            $checkoutItems = [];

            foreach ($_POST['selected'] as $maSach) {
                $maSach = trim($maSach);
                if ($maSach === '' || !isset($_SESSION['cart'][$maSach])) {
                    continue;
                }

                if (!preg_match('/^[A-Za-z0-9]+$/', $maSach)) {
                    continue;
                }

                $qty = isset($_SESSION['cart'][$maSach]['quantity'])
                    ? (int)$_SESSION['cart'][$maSach]['quantity']
                    : 1;

                if ($qty < 1) {
                    $qty = 1;
                }

                $checkoutItems[$maSach] = [
                    'quantity' => $qty,
                ];
            }

            if (!empty($checkoutItems)) {
                $_SESSION['checkout_items'] = $checkoutItems;
                header('Location: ' . BASE_URL . '/customer/module/order/order_details.php');
                exit;
            }
        }

        // Không có sản phẩm hợp lệ nào được chọn, quay lại giỏ hàng
        header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
        exit;
    }

    if ($pageAction === 'clear_cart') {
        unset($_SESSION['cart']);
        header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
        exit;
    }

    if ($pageAction === 'remove_selected' && !empty($_POST['selected']) && is_array($_POST['selected'])) {
        foreach ($_POST['selected'] as $maSach) {
            $maSach = trim($maSach);
            if ($maSach !== '' && isset($_SESSION['cart'][$maSach])) {
                unset($_SESSION['cart'][$maSach]);
            }
        }
        header('Location: ' . BASE_URL . '/customer/module/order/ordercart.php');
        exit;
    }
}

$page_title = 'Giỏ hàng';
include dirname(__DIR__, 3) . '/layout/header.php';

// Cấu trúc giỏ hàng dự kiến:
// $_SESSION['cart'][MaSach] = ['quantity' => so_luong];
$cart = isset($_SESSION['cart']) && is_array($_SESSION['cart']) ? $_SESSION['cart'] : [];

$cartItems = [];

if (!empty($cart)) {
    $bookIds = array_keys($cart);
    $safeIds = [];

    foreach ($bookIds as $id) {
        if (!preg_match('/^[A-Za-z0-9]+$/', $id)) {
            continue;
        }
        $safeIds[] = "'" . $conn->real_escape_string($id) . "'";
    }

    if (!empty($safeIds)) {
        $sql = "SELECT * FROM sach WHERE MaSach IN (" . implode(',', $safeIds) . ")";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $maSach = $row['MaSach'];
                $qty = isset($cart[$maSach]['quantity']) && (int)$cart[$maSach]['quantity'] > 0
                    ? (int)$cart[$maSach]['quantity']
                    : 1;

                $row['quantity'] = $qty;
                $row['line_total'] = $qty * (float)$row['DonGiaBan'];
                $cartItems[$maSach] = $row;
            }
        }
    }
}

// Tính tổng tiền ban đầu (tất cả sản phẩm được chọn)
$initialTotal = 0;
foreach ($cartItems as $item) {
    $initialTotal += $item['line_total'];
}
?>

<div class="cart-page" style="padding: 40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">Giỏ hàng của bạn</h2>
        </div>

        <?php if (empty($cartItems)): ?>
            <p>Giỏ hàng của bạn đang trống.</p>
            <a href="<?php echo BASE_URL; ?>/customer/module/books.php" class="btn button-default">
                Tiếp tục mua sắm
            </a>
        <?php else: ?>
            <form method="post">
                <div class="cart-table-wrap" style="overflow-x:auto; margin-top:20px;">
                    <table class="table cart-table" style="width:100%; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid #eee;">
                                <th style="padding:10px; text-align:center; width:40px;">
                                    <input type="checkbox" id="cart-select-all" checked>
                                </th>
                                <th style="padding:10px; text-align:left;">Sản phẩm</th>
                                <th style="padding:10px; text-align:right; width:120px;">Đơn giá</th>
                                <th style="padding:10px; text-align:center; width:80px;">Số lượng</th>
                                <th style="padding:10px; text-align:right; width:140px;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): ?>
                                <?php $maSachSafe = htmlspecialchars($item['MaSach'], ENT_QUOTES, 'UTF-8'); ?>
                                <tr style="border-bottom:1px solid #f0f0f0;">
                                    <td style="padding:10px; text-align:center; cursor:pointer;">
                                        <label style="display:block; width:100%; height:100%; margin:0; cursor:pointer;">
                                            <input
                                                type="checkbox"
                                                class="cart-item-checkbox"
                                                name="selected[]"
                                                value="<?php echo $maSachSafe; ?>"
                                                checked
                                                data-price="<?php echo (float)$item['DonGiaBan']; ?>"
                                                data-qty="<?php echo (int)$item['quantity']; ?>">
                                        </label>
                                    </td>
                                    <td style="padding:20px; display:flex; align-items:center; gap:20px;">
                                        <div style="width:55px; height:70px; flex-shrink:0;">
                                            <img src="<?php echo htmlspecialchars($item['Anh'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                alt="<?php echo htmlspecialchars($item['TenSach'], ENT_QUOTES, 'UTF-8'); ?>"
                                                style="width:60px; height:60px; object-fit:cover; border-radius:4px;">
                                        </div>
                                        <div>
                                            <div style="font-weight:600;">
                                                <?php echo htmlspecialchars($item['TenSach'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div style="font-size:12px; color:#888;">
                                                Mã sách: <?php echo htmlspecialchars($item['MaSach'], ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:10px; text-align:right;">
                                        <?php echo number_format($item['DonGiaBan'], 0, ',', '.'); ?>₫
                                    </td>
                                    <td style="padding:10px; text-align:center;">
                                        <?php echo (int)$item['quantity']; ?>
                                    </td>
                                    <td style="padding:10px; text-align:right; font-weight:600; color:var(--price, #228b22);">
                                        <?php echo number_format($item['line_total'], 0, ',', '.'); ?>₫
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-summary" style="margin-top:20px; text-align:right;">
                    <div style="font-size:16px;">
                        Tổng tiền (các sản phẩm đã chọn):
                        <strong id="cart-total-price" style="font-size:18px; color:var(--price, #228b22);">
                            <?php echo number_format($initialTotal, 0, ',', '.'); ?>₫
                        </strong>
                    </div>
                    <div style="margin-top:10px; display:inline-flex; gap:10px; justify-content:flex-end;">
                        <button type="submit" name="action" value="remove_selected" class="btn button-default">
                            Xóa sản phẩm đã chọn
                        </button>
                        <button type="submit" name="action" value="checkout" class="btn button-default">
                            Thanh toán
                        </button>
                        <button type="submit" name="action" value="clear_cart" class="btn button-default">
                            Xóa toàn bộ giỏ hàng
                        </button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
    (function() {
        function calculateTotal() {
            var checkboxes = document.querySelectorAll('.cart-item-checkbox');
            var total = 0;

            checkboxes.forEach(function(cb) {
                if (cb.checked) {
                    var price = parseFloat(cb.getAttribute('data-price')) || 0;
                    var qty = parseInt(cb.getAttribute('data-qty'), 10) || 1;
                    total += price * qty;
                }
            });

            var totalEl = document.getElementById('cart-total-price');
            if (totalEl) {
                totalEl.textContent = total.toLocaleString('vi-VN') + '₫';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var selectAll = document.getElementById('cart-select-all');
            var itemCheckboxes = document.querySelectorAll('.cart-item-checkbox');

            if (selectAll) {
                selectAll.addEventListener('change', function() {
                    itemCheckboxes.forEach(function(cb) {
                        cb.checked = selectAll.checked;
                    });
                    calculateTotal();
                });
            }

            itemCheckboxes.forEach(function(cb) {
                cb.addEventListener('change', function() {
                    if (selectAll) {
                        var allChecked = true;
                        itemCheckboxes.forEach(function(item) {
                            if (!item.checked) {
                                allChecked = false;
                            }
                        });
                        selectAll.checked = allChecked;
                    }
                    calculateTotal();
                });
            });

            // Tính tổng lần đầu khi tải trang
            calculateTotal();
        });
    })();
</script>

<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>