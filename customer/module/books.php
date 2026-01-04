<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/connected.php';
//Lấy danh sách thể loại
$theloai_sql = "SELECT * FROM the_loai ORDER BY TenTheLoai ASC";
$theloai_result = $conn->query($theloai_sql);

$page_title = "Tất cả sách mới";
include dirname(__DIR__, 2) . '/layout/header.php';

//Xử lý filter thể loại
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
                                    echo '<button type="button" class="btn btn-lg btn-gray add_to_cart btn_buy buy-normal " title="Thêm vào giỏ" data-masach="' . htmlspecialchars($row['MaSach']) . '" data-qty="1">';
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
                    <div class="pagination" style="margin-top: 30px; text-align: center; display: flex; justify-content: center; align-items: center; gap: 4px;">
                        <?php
                        $query_params = $_GET;
                        // Prev button
                        if ($page > 1) {
                            $query_params['page'] = $page - 1;
                            $query = http_build_query($query_params);
                            echo '<a href="?' . $query . '" style="margin:0 5px; font-weight:bold; font-size:18px;">&lt;</a>';
                        }
                        // Page numbers
                        for ($p = 1; $p <= $total_pages; $p++) {
                            $query_params['page'] = $p;
                            $query = http_build_query($query_params);
                            if ($p == $page) {
                                echo '<span style="margin:0 5px; font-weight:bold;">' . $p . '</span>';
                            } else {
                                echo '<a href="?' . $query . '" style="margin:0 5px;">' . $p . '</a>';
                            }
                        }
                        // Next button
                        if ($page < $total_pages) {
                            $query_params['page'] = $page + 1;
                            $query = http_build_query($query_params);
                            echo '<a href="?' . $query . '" style="margin:0 5px; font-weight:bold; font-size:18px;">&gt;</a>';
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
                            <?php
                            // Phân loại thể loại theo Hư cấu và Phi hư cấu
                            $hu_cau = [
                                'TL001', // Văn học hiện đại
                                'TL002', // Văn học kinh điển
                                'TL003', // Văn học thiếu nhi
                                'TL004', // Lãng mạn
                                'TL005', // Trinh thám - Kinh dị
                                'TL006', // Kỳ ảo
                                'TL007', // Khoa học viễn tưởng
                                'TL008', // Phiêu lưu ly kỳ
                                'TL009', // Tản Văn
                                'TL010', // Truyện tranh (graphic novel)
                                'TL011', // Tranh sách (picture book)
                                'TL025', // Thơ - kịch (nếu có)
                            ];
                            $phi_hu_cau = [
                                'TL012', // Triết học
                                'TL013', // Sử học
                                'TL014', // Khoa học
                                'TL015', // Kinh doanh
                                'TL016', // Kinh tế chính trị
                                'TL017', // Kỹ năng
                                'TL018', // Nghệ thuật
                                'TL019', // Tâm lý học
                                'TL020', // Hồi ký
                                'TL021', // Y học - Sức khỏe
                                'TL022', // Tâm linh - Tôn giáo
                                'TL023', // Kiến thức phổ thông
                                'TL024', // Phong cách sống
                            ];
                            // Lấy lại danh sách thể loại (vì $theloai_result đã fetch_assoc hết)
                            $theloai_sql2 = "SELECT * FROM the_loai ORDER BY TenTheLoai ASC";
                            $theloai_result2 = $conn->query($theloai_sql2);
                            $theloai_arr = [];
                            if ($theloai_result2 && $theloai_result2->num_rows > 0) {
                                while ($tl = $theloai_result2->fetch_assoc()) {
                                    $theloai_arr[$tl['MaTheLoai']] = $tl['TenTheLoai'];
                                }
                            }
                            ?>
                            <div style="margin-bottom: 10px; color: green">
                                <strong>Hư cấu</strong>
                            </div>
                            <?php
                            foreach ($hu_cau as $ma) {
                                if (isset($theloai_arr[$ma])) {
                                    echo '<div style="margin-bottom: 8px;">';
                                    echo '<label class="custom-checkbox">';
                                    echo '<input type="checkbox" name="theloai[]" value="' . htmlspecialchars($ma) . '" ' . (in_array($ma, $selected_theloai) ? 'checked' : '') . '>';
                                    echo '<span class="checkmark"></span>';
                                    echo htmlspecialchars($theloai_arr[$ma]);
                                    echo '</label>';
                                    echo '</div>';
                                }
                            }
                            ?>
                            <div style="margin: 15px 0 10px 0; color: green;">
                                <strong>Phi hư cấu</strong>
                            </div>
                            <?php
                            foreach ($phi_hu_cau as $ma) {
                                if (isset($theloai_arr[$ma])) {
                                    echo '<div style="margin-bottom: 8px;">';
                                    echo '<label class="custom-checkbox">';
                                    echo '<input type="checkbox" name="theloai[]" value="' . htmlspecialchars($ma) . '" ' . (in_array($ma, $selected_theloai) ? 'checked' : '') . '>';
                                    echo '<span class="checkmark"></span>';
                                    echo htmlspecialchars($theloai_arr[$ma]);
                                    echo '</label>';
                                    echo '</div>';
                                }
                            }
                            // Các thể loại khác (nếu có)
                            $other = array_diff(array_keys($theloai_arr), $hu_cau, $phi_hu_cau);
                            if (!empty($other)) {
                                echo '<div style="margin: 15px 0 10px 0;"><strong>Khác</strong></div>';
                                foreach ($other as $ma) {
                                    echo '<div style="margin-bottom: 8px;">';
                                    echo '<label class="custom-checkbox">';
                                    echo '<input type="checkbox" name="theloai[]" value="' . htmlspecialchars($ma) . '" ' . (in_array($ma, $selected_theloai) ? 'checked' : '') . '>';
                                    echo '<span class="checkmark"></span>';
                                    echo htmlspecialchars($theloai_arr[$ma]);
                                    echo '</label>';
                                    echo '</div>';
                                }
                            }
                            if (empty($theloai_arr)) {
                                echo '<div>Không có thể loại.</div>';
                            }
                            ?>
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px; width: 100%;">Lọc</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>