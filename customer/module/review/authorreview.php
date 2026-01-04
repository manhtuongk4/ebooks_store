<?php
session_start();

// Đưa về thư mục gốc dự án (ebooks_store)
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Lấy mã tác giả từ tham số GET
$maTacGia = isset($_GET['matacgia']) ? trim($_GET['matacgia']) : '';

if ($maTacGia === '') {
    // Không có mã tác giả, quay lại trang danh sách tác giả
    header('Location: ' . BASE_URL . '/customer/module/author.php');
    exit;
}

// Lấy thông tin chi tiết tác giả
$author = null;
$stmtAuthor = $conn->prepare("SELECT * FROM tac_gia WHERE MaTacGia = ?");
if ($stmtAuthor) {
    $stmtAuthor->bind_param('s', $maTacGia);
    $stmtAuthor->execute();
    $resultAuthor = $stmtAuthor->get_result();
    $author = $resultAuthor->fetch_assoc();
    $stmtAuthor->close();
}

if (!$author) {
    // Không tìm thấy tác giả, quay lại trang danh sách tác giả
    header('Location: ' . BASE_URL . '/customer/module/author.php');
    exit;
}

// Lấy danh sách sách liên quan tới tác giả này
$books = [];
$stmtBooks = $conn->prepare("SELECT * FROM sach WHERE MaTacGia = ? ORDER BY NamXuatBan DESC, MaSach DESC");
if ($stmtBooks) {
    $stmtBooks->bind_param('s', $maTacGia);
    $stmtBooks->execute();
    $resultBooks = $stmtBooks->get_result();
    while ($row = $resultBooks->fetch_assoc()) {
        $books[] = $row;
    }
    $stmtBooks->close();
}

// Tiêu đề trang
$page_title = 'Tác giả: ' . htmlspecialchars($author['TenTacGia']);

include dirname(__DIR__, 3) . '/layout/header.php';
?>

<main class="wrapperMain_content" style="padding:40px 0;">
    <div class="container">
        <div style="margin-bottom:15px;">
            <a href="<?php echo BASE_URL; ?>/customer/module/author.php" class="btn btn-secondary" style="padding:6px 12px; border-radius:4px; border:1px solid #ccc; color:#333; text-decoration:none; font-size:14px;">
                ← Quay lại danh sách tác giả
            </a>
        </div>

        <div class="row">
            <div class="col-lg-4 col-md-5 col-12">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($author['HinhAnh']); ?>" alt="<?php echo htmlspecialchars($author['TenTacGia']); ?>" style="max-width:100%; height:auto; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,0.1); object-fit:cover;">
                </div>
            </div>
            <div class="col-lg-8 col-md-7 col-12">
                <h1 class="title-module" style="font-size:28px; margin-bottom:10px; color:green;">
                    <?php echo htmlspecialchars($author['TenTacGia']); ?>
                </h1>

                <div style="margin-bottom:10px; padding:12px; border-radius:4px; background:#fafafa;">
                    <div style="display:grid; grid-template-columns: 140px 1fr; row-gap:6px; column-gap:12px; align-items:start;">
                        <?php if (!empty($author['NgaySinh']) && $author['NgaySinh'] !== '0000-00-00'): ?>
                            <div style="font-weight:bold;">Ngày sinh</div>
                            <div><?php echo htmlspecialchars($author['NgaySinh']); ?></div>
                        <?php endif; ?>
                        <div style="font-weight:bold;">Mã tác giả</div>
                        <div><?php echo htmlspecialchars($author['MaTacGia']); ?></div>
                    </div>
                </div>

                <?php if (!empty($author['MoTa'])): ?>
                    <div class="product-description" style="margin-top:20px; border:1px solid #ddd; padding:15px; border-radius:4px; background:#fafafa;">
                        <h3 style="font-size:20px; margin-bottom:10px; color:green;">Giới thiệu tác giả</h3>
                        <p style="white-space:pre-line; line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($author['MoTa'])); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <section style="margin-top:40px; border-top:1px solid #eee; padding-top:25px;">
            <div class="title-border">
                <h2 class="title-module">
                    Sách có liên quan
                </h2>
            </div>

            <?php if (empty($books)): ?>
                <p>Hiện chưa có sách nào của tác giả này trong hệ thống.</p>
            <?php else: ?>
                <div class="slide-book-new-wrap relative" style="margin-top:15px;">
                    <div class="swiper-container" style="cursor: grab; overflow:hidden;">
                        <div class="swiper-wrapper" style="display:flex; flex-wrap:wrap; gap:20px;">
                            <?php foreach ($books as $bk): ?>
                                <div class="swiper-slide" style="width: 250px; margin-right: 10px;">
                                    <div class="item_product_main">
                                        <form action="/cart/add" method="post" class="variants product-action wishItem" data-cart-form="" enctype="multipart/form-data">
                                            <div class="thumb">
                                                <a class="image_thumb" href="<?php echo BASE_URL; ?>/customer/module/review/bookreview.php?masach=<?php echo urlencode($bk['MaSach']); ?>" title="<?php echo htmlspecialchars($bk['TenSach']); ?>">
                                                    <img width="199" height="199" src="<?php echo htmlspecialchars($bk['Anh']); ?>" alt="<?php echo htmlspecialchars($bk['TenSach']); ?>" class="lazyload img-responsive center-block">
                                                </a>
                                                <div class="action-cart">
                                                    <button type="button" class="btn btn-lg btn-gray add_to_cart btn_buy buy-normal " title="Thêm vào giỏ" data-masach="<?php echo htmlspecialchars($bk['MaSach']); ?>" data-qty="1">
                                                        <svg class="icon">
                                                            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#addcarticon"></use>
                                                        </svg>
                                                    </button>
                                                    <button type="button" class="btn-buy btn-left btn-views btn-buy-now-grid" title="Mua ngay" data-masach="<?php echo htmlspecialchars($bk['MaSach']); ?>" data-qty="1">Mua ngay</button>
                                                </div>
                                            </div>
                                            <div class="info-product">
                                                <h3 class="product-name"><a href="<?php echo BASE_URL; ?>/customer/module/review/bookreview.php?masach=<?php echo urlencode($bk['MaSach']); ?>" title="<?php echo htmlspecialchars($bk['TenSach']); ?>"><?php echo htmlspecialchars($bk['TenSach']); ?></a></h3>
                                                <div class="price-box">
                                                    <span class="price"><?php echo number_format($bk['DonGiaBan'], 0, ",", "."); ?>₫</span>
                                                    <?php if (!empty($bk['DonGiaNhap']) && $bk['DonGiaNhap'] > 0 && $bk['DonGiaNhap'] < $bk['DonGiaBan']): ?>
                                                        <span class="compare-price"><?php echo number_format($bk['DonGiaNhap'], 0, ",", "."); ?>₫</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>