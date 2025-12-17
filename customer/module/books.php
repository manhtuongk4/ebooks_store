<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/connected.php';
// Lấy danh sách thể loại
$theloai_sql = "SELECT * FROM the_loai ORDER BY TenTheLoai ASC";
$theloai_result = $conn->query($theloai_sql);

$page_title = "Tất cả sách mới";
include dirname(__DIR__, 2) . '/layout/header.php';

// Xử lý filter thể loại
$selected_theloai = isset($_GET['theloai']) && is_array($_GET['theloai']) ? array_filter($_GET['theloai']) : [];

$limit = 6; // books per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

//Điều kiện lọc thể loại
$where = '';
if (!empty($selected_theloai)) {
    $escaped = array_map(function ($tl) use ($conn) {
        return "'" . $conn->real_escape_string($tl) . "'";
    }, $selected_theloai);
    $where = "WHERE MaTheLoai IN (" . implode(",", $escaped) . ")";
}

//Đếm tổng số sách sau lọc
$total_books_sql = "SELECT COUNT(*) as total FROM sach $where";
$total_books_result = $conn->query($total_books_sql);
$total_books = $total_books_result ? (int)$total_books_result->fetch_assoc()['total'] : 0;
$total_pages = $total_books > 0 ? ceil($total_books / $limit) : 1;

// Lấy sách trang hiện tại
$sql_books = "SELECT * FROM sach $where ORDER BY NamXuatBan DESC, MaSach DESC LIMIT $limit OFFSET $offset";
$result_books = $conn->query($sql_books);
?>

<div class="section_book_list" style="padding: 40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">
                <a href="#" title="Tất cả sách">Tất cả sách</a>
            </h2>
        </div>
        <div class="row" style="display: flex; flex-wrap: wrap;">
            <!-- Sách bên trái -->
            <div class="col-lg-9 col-md-8 col-12" style="flex: 1 1 0; min-width: 0;">
                <div class="slide-book-list-wrap relative swiper-button-main">
                    <div class="swiper-container slide-book-list" style="cursor: grab;">
                        <div class="swiper-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
                            <?php
                            if ($result_books && $result_books->num_rows > 0) {
                                $i = 1;
                                while ($row = $result_books->fetch_assoc()) {
                                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $row['TenSach']));
                                    echo '<div class="swiper-slide" style="width: 250px; margin-right: 10px; margin-bottom: 30px;">';
                                    echo '<div class="item_product_main">';
                                    echo '<form action="/cart/add" method="post" class="variants product-action wishItem" data-cart-form="" enctype="multipart/form-data">';
                                    echo '<div class="thumb">';
                                    echo '<a class="image_thumb" href="/' . $slug . '" title="' . htmlspecialchars($row['TenSach']) . '">';
                                    echo '<img width="199" height="199" src="' . htmlspecialchars($row['Anh']) . '" data-src="' . htmlspecialchars($row['Anh']) . '" alt="' . htmlspecialchars($row['TenSach']) . '" class="lazyload img-responsive center-block loaded" data-was-processed="true">';
                                    echo '</a>';
                                    echo '<div class="action-cart">';
                                    echo '<button type="button" class="btn btn-lg btn-gray add_to_cart btn_buy buy-normal " title="Thêm vào giỏ">';
                                    echo '<svg class="icon"><use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#addcarticon"></use></svg>';
                                    echo '</button>';
                                    echo '<button class="btn-buy btn-left btn-views  btn-buy-now-grid" title="Mua ngay">Mua ngay</button>';
                                    echo '</div>';
                                    echo '</div>';
                                    echo '<div class="info-product">';
                                    echo '<h3 class="product-name"><a href="/' . $slug . '" title="' . htmlspecialchars($row['TenSach']) . '">' . htmlspecialchars($row['TenSach']) . '</a></h3>';
                                    echo '<div class="price-box">';
                                    echo '<span class="price">' . number_format($row['DonGiaBan'], 0, ",", ".") . '₫</span>';
                                    if (!empty($row['DonGiaNhap']) && $row['DonGiaNhap'] > 0 && $row['DonGiaNhap'] < $row['DonGiaBan']) {
                                        // Hiển thị giá nhập nếu cần
                                    }
                                    echo '</div>';
                                    echo '</div>';
                                    echo '</form>';
                                    echo '</div>';
                                    echo '</div>';
                                    $i++;
                                }
                            } else {
                                echo '<div>Không có sách nào.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php if ($total_pages > 1): ?>
                    <div class="pagination" style="margin-top: 30px; text-align: center;">
                        <?php
                        // Giữ lại filter khi chuyển trang
                        $query_params = $_GET;
                        for ($p = 1; $p <= $total_pages; $p++) {
                            $query_params['page'] = $p;
                            $query = http_build_query($query_params);
                            if ($p == $page) {
                                echo '<span style="margin:0 5px; font-weight:bold;">' . $p . '</span>';
                            } else {
                                echo '<a href="?' . $query . '" style="margin:0 5px;">' . $p . '</a>';
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Bộ lọc thể loại bên phải -->
            <div class="col-lg-3 col-md-4 col-12" style="min-width: 220px; max-width: 350px;">
                <style>
                    .custom-checkbox {
                        position: relative;
                        padding-left: 28px;
                        cursor: pointer;
                        user-select: none;
                        display: inline-block;
                        font-size: 16px;
                    }

                    .custom-checkbox input[type="checkbox"] {
                        position: absolute;
                        opacity: 0;
                        cursor: pointer;
                        height: 0;
                        width: 0;
                    }

                    .checkmark {
                        position: absolute;
                        left: 0;
                        top: 2px;
                        height: 18px;
                        width: 18px;
                        background-color: #fff;
                        border: 2px solid #888;
                        border-radius: 3px;
                        box-sizing: border-box;
                    }

                    .custom-checkbox input:checked~.checkmark {
                        background-color: #007bff;
                        border-color: #007bff;
                    }

                    .checkmark:after {
                        content: "";
                        position: absolute;
                        display: none;
                    }

                    .custom-checkbox input:checked~.checkmark:after {
                        display: block;
                    }

                    .custom-checkbox .checkmark:after {
                        left: 5px;
                        top: 1px;
                        width: 5px;
                        height: 10px;
                        border: solid #fff;
                        border-width: 0 3px 3px 0;
                        transform: rotate(45deg);
                        content: "";
                    }
                </style>
                <div class="filter-box" style="background: #f8f8f8; border-radius: 8px; padding: 20px; margin-left: 20px;">
                    <form method="get" action="" id="filterForm">
                        <h4 class="title-module" style="margin-bottom: 15px;">Bộ lọc thể loại</h4>
                        <div style="max-height: 350px; overflow-y: auto;">
                            <?php if ($theloai_result && $theloai_result->num_rows > 0): ?>
                                <?php while ($tl = $theloai_result->fetch_assoc()): ?>
                                    <div style="margin-bottom: 8px;">
                                        <label class="custom-checkbox">
                                            <input type="checkbox" name="theloai[]" value="<?php echo htmlspecialchars($tl['MaTheLoai']); ?>" <?php echo in_array($tl['MaTheLoai'], $selected_theloai) ? 'checked' : ''; ?>>
                                            <span class="checkmark"></span>
                                            <?php echo htmlspecialchars($tl['TenTheLoai']); ?>
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div>Không có thể loại.</div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px; width: 100%;">Lọc</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>