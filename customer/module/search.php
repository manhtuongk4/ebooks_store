<?php
session_start();

require_once dirname(__DIR__, 2) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/connected.php';

// API gợi ý tìm kiếm (AJAX)
if (isset($_GET['ajax'])) {
    $keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

    if ($keyword === '') {
        echo '';
        exit;
    }

    // Chuẩn bị truy vấn an toàn
    $like = '%' . $keyword . '%';
    $sql = "SELECT MaSach, TenSach, DonGiaBan, Anh, NamXuatBan FROM sach WHERE TenSach LIKE ? ORDER BY NamXuatBan DESC LIMIT 8";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $url = BASE_URL . '/customer/module/review/bookreview.php?masach=' . urlencode($row['MaSach']);
                $img = !empty($row['Anh']) ? $row['Anh'] : BASE_URL . '/image/no-image.png';
                $title = htmlspecialchars($row['TenSach']);
                $price = number_format((float)$row['DonGiaBan'], 0, ',', '.') . '₫';

                echo '<a class="search-suggestions-item" href="' . $url . '">';
                echo '<div class="search-suggestions-thumb">';
                echo '<img src="' . htmlspecialchars($img) . '" alt="' . $title . '">';
                echo '</div>';
                echo '<div class="search-suggestions-info">';
                echo '<div class="search-suggestions-title">' . $title . '</div>';
                echo '<div class="search-suggestions-price">' . $price . '</div>';
                echo '</div>';
                echo '</a>';
            }
        } else {
            echo '<div class="search-suggestions-empty">Không tìm thấy sách phù hợp</div>';
        }

        $stmt->close();
    } else {
        echo '<div class="search-suggestions-empty">Lỗi truy vấn dữ liệu</div>';
    }

    exit;
}

// Trang kết quả tìm kiếm đầy đủ
$keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

$page_title = 'Kết quả tìm kiếm';
include dirname(__DIR__, 2) . '/layout/header.php';

?>

<main class="wrapperMain_content" style="padding: 40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">
                Kết quả tìm kiếm cho "<?php echo htmlspecialchars($keyword); ?>"
            </h2>
        </div>

        <?php
        if ($keyword === '') {
            echo '<p>Vui lòng nhập từ khóa để tìm kiếm sách.</p>';
        } else {
            $like = '%' . $keyword . '%';
            $sqlList = "SELECT MaSach, TenSach, DonGiaBan, DonGiaNhap, Anh FROM sach WHERE TenSach LIKE ? ORDER BY NamXuatBan DESC";
            if ($stmtList = $conn->prepare($sqlList)) {
                $stmtList->bind_param('s', $like);
                $stmtList->execute();
                $resultList = $stmtList->get_result();

                if ($resultList && $resultList->num_rows > 0) {
                    echo '<div class="row" style="display:flex; flex-wrap:wrap; gap:20px;">';
                    while ($row = $resultList->fetch_assoc()) {
                        $url = BASE_URL . '/customer/module/review/bookreview.php?masach=' . urlencode($row['MaSach']);
                        $img = !empty($row['Anh']) ? $row['Anh'] : BASE_URL . '/image/no-image.png';
                        $title = htmlspecialchars($row['TenSach']);
                        $price = number_format((float)$row['DonGiaBan'], 0, ',', '.') . '₫';

                        echo '<div class="col-6 col-md-3" style="max-width:250px; margin-bottom:30px;">';
                        echo '<div class="item_product_main">';
                        echo '<form action="/cart/add" method="post" class="variants product-action wishItem" data-cart-form="" enctype="multipart/form-data">';
                        echo '<div class="thumb">';
                        echo '<a class="image_thumb" href="' . $url . '" title="' . $title . '">';
                        echo '<img src="' . htmlspecialchars($img) . '" alt="' . $title . '" class="lazyload img-responsive center-block" style="width:100%; height:220px; object-fit:cover;">';
                        echo '</a>';
                        echo '<div class="action-cart">';
                        echo '<button type="button" class="btn btn-lg btn-gray add_to_cart btn_buy buy-normal" title="Thêm vào giỏ" data-masach="' . htmlspecialchars($row['MaSach']) . '" data-qty="1">';
                        echo '<svg class="icon"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#addcarticon"></use></svg>';
                        echo '</button>';
                        echo '<button type="button" class="btn-buy btn-left btn-views btn-buy-now-grid" title="Mua ngay" data-masach="' . htmlspecialchars($row['MaSach']) . '" data-qty="1">Mua ngay</button>';
                        echo '</div>';
                        echo '</div>';
                        echo '<div class="info-product">';
                        echo '<h3 class="product-name" style="font-size:14px; margin-top:8px;"><a href="' . $url . '" title="' . $title . '">' . $title . '</a></h3>';
                        echo '<div class="price-box"><span class="price">' . $price . '</span></div>';
                        echo '</div>';
                        echo '</form>';
                        echo '</div>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>Không tìm thấy sách phù hợp với từ khóa này.</p>';
                }

                $stmtList->close();
            } else {
                echo '<p>Có lỗi xảy ra khi tìm kiếm dữ liệu.</p>';
            }
        }
        ?>
    </div>
</main>

<?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>