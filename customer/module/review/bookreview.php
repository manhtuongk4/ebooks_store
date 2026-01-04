<?php
session_start();
require_once dirname(__DIR__, 3) . '/config/configpath.php';
require_once dirname(__DIR__, 3) . '/connected.php';

// Lấy mã sách từ tham số GET
$maSach = isset($_GET['masach']) ? trim($_GET['masach']) : '';

if ($maSach === '') {
    // Không có mã sách, chuyển về trang danh sách
    header('Location: ' . BASE_URL . '/customer/module/books.php');
    exit;
}

// Xử lý gửi đánh giá sách
$review_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    // Chỉ cho phép khách hàng (không phải admin) gửi đánh giá
    $isCustomerLoggedIn = isset($_SESSION['logged_in'], $_SESSION['user_id'])
        && $_SESSION['logged_in'] === true
        && !isset($_SESSION['admin_id']);

    if (!$isCustomerLoggedIn) {
        $review_error = 'Bạn cần đăng nhập bằng tài khoản khách hàng để đánh giá.';
    } else {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $content = trim($_POST['review_content'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $review_error = 'Vui lòng chọn số sao từ 1 đến 5.';
        } else {
            $maKH = $_SESSION['user_id'];

            $stmtReview = $conn->prepare(
                "INSERT INTO danh_gia_binh_luan (MaSach, MaKH, SoSao, NoiDung, NgayDang)
                 VALUES (?, ?, ?, ?, NOW())"
            );

            if ($stmtReview) {
                $stmtReview->bind_param('ssis', $maSach, $maKH, $rating, $content);
                $stmtReview->execute();
                $stmtReview->close();

                // Chống gửi lại form khi F5
                header('Location: ' . BASE_URL . '/customer/module/review/bookreview.php?masach=' . urlencode($maSach) . '#reviews');
                exit;
            } else {
                $review_error = 'Không thể lưu đánh giá lúc này. Vui lòng thử lại sau.';
            }
        }
    }
}

// Chuẩn bị câu lệnh truy vấn thông tin sách + tác giả + thể loại + NXB (nếu có)
$stmt = $conn->prepare("SELECT s.*, tg.TenTacGia, tl.TenTheLoai, nxb.TenNXB
                        FROM sach s
                        LEFT JOIN tac_gia tg ON s.MaTacGia = tg.MaTacGia
                        LEFT JOIN the_loai tl ON s.MaTheLoai = tl.MaTheLoai
                        LEFT JOIN nha_xuat_ban nxb ON s.MaNXB = nxb.MaNXB
                        WHERE s.MaSach = ?");

if ($stmt) {
    $stmt->bind_param('s', $maSach);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();
} else {
    $book = null;
}

if (!$book) {
    // Không tìm thấy sách, chuyển về trang danh sách
    header('Location: ' . BASE_URL . '/customer/module/books.php');
    exit;
}

// Thông tin thêm 4 sách khác ngẫu nhiên (trừ cuốn sách đang xem)
$otherBooks = [];
$stmtOtherBooks = $conn->prepare(
    "SELECT * FROM sach WHERE MaSach <> ? ORDER BY RAND() LIMIT 4"
);
if ($stmtOtherBooks) {
    $stmtOtherBooks->bind_param('s', $maSach);
    $stmtOtherBooks->execute();
    $rsOtherBooks = $stmtOtherBooks->get_result();
    while ($rowBook = $rsOtherBooks->fetch_assoc()) {
        $otherBooks[] = $rowBook;
    }
    $stmtOtherBooks->close();
}

// Lấy danh sách dịch giả (nếu có)
$translators = [];
$stmtTrans = $conn->prepare(
    "SELECT dg.TenDichGia
     FROM sach_dichgia sd
     JOIN dich_gia dg ON sd.MaDichGia = dg.MaDichGia
     WHERE sd.MaSach = ?"
);
if ($stmtTrans) {
    $stmtTrans->bind_param('s', $maSach);
    $stmtTrans->execute();
    $rsTrans = $stmtTrans->get_result();
    while ($rowTrans = $rsTrans->fetch_assoc()) {
        if (!empty($rowTrans['TenDichGia'])) {
            $translators[] = $rowTrans['TenDichGia'];
        }
    }
    $stmtTrans->close();
}

// Lấy email khách hàng đang đăng nhập (nếu có)
$userEmail = null;
$isCustomerLoggedIn = isset($_SESSION['logged_in'], $_SESSION['user_id'])
    && $_SESSION['logged_in'] === true
    && !isset($_SESSION['admin_id']);
if ($isCustomerLoggedIn && isset($_SESSION['email'])) {
    $userEmail = $_SESSION['email'];
}

// Lấy danh sách đánh giá cho sách hiện tại
$reviews = [];
$stmtReviews = $conn->prepare(
    "SELECT dg.SoSao, dg.NoiDung, dg.NgayDang, kh.Email
     FROM danh_gia_binh_luan dg
     JOIN khach_hang kh ON dg.MaKH = kh.MaKH
     WHERE dg.MaSach = ?
     ORDER BY dg.NgayDang DESC"
);
if ($stmtReviews) {
    $stmtReviews->bind_param('s', $maSach);
    $stmtReviews->execute();
    $rsReviews = $stmtReviews->get_result();
    while ($row = $rsReviews->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmtReviews->close();
}

$page_title = htmlspecialchars($book['TenSach']);

include dirname(__DIR__, 3) . '/layout/header.php';
?>

<main class="wrapperMain_content" style="padding:40px 0;">
    <div class="container">
        <div style="margin-bottom:15px;">
            <a href="<?php echo BASE_URL; ?>/customer/module/books.php" class="btn btn-secondary" style="padding:6px 12px; border-radius:4px; border:1px solid #ccc; color:#333; text-decoration:none; font-size:14px;">
                ← Quay lại danh sách sách
            </a>
        </div>
        <div class="row">
            <div class="col-lg-4 col-md-5 col-12">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($book['Anh']); ?>" alt="<?php echo htmlspecialchars($book['TenSach']); ?>" style="max-width:100%; height:auto; border-radius:4px; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
                </div>
            </div>
            <div class="col-lg-8 col-md-7 col-12">
                <h1 class="title-module" style="font-size:28px; margin-bottom:10px; color:green;">
                    <?php echo htmlspecialchars($book['TenSach']); ?>
                </h1>
                <div style="margin-bottom:10px; padding:12px; border-radius:4px; background:#fafafa;">
                    <div style="display:grid; grid-template-columns: 140px 1fr; row-gap:6px; column-gap:12px; align-items:start;">
                        <?php if (!empty($book['TenTacGia'])): ?>
                            <div style="font-weight:bold;">Tác giả</div>
                            <div><?php echo htmlspecialchars($book['TenTacGia']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($translators)): ?>
                            <div style="font-weight:bold;">Dịch giả</div>
                            <div><?php echo htmlspecialchars(implode(', ', $translators)); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($book['TenTheLoai'])): ?>
                            <div style="font-weight:bold;">Thể loại</div>
                            <div><?php echo htmlspecialchars($book['TenTheLoai']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($book['TenNXB'])): ?>
                            <div style="font-weight:bold;">Nhà xuất bản</div>
                            <div><?php echo htmlspecialchars($book['TenNXB']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($book['NamXuatBan'])): ?>
                            <div style="font-weight:bold;">Năm xuất bản</div>
                            <div><?php echo htmlspecialchars($book['NamXuatBan']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($book['SoTrang'])): ?>
                            <div style="font-weight:bold;">Số trang</div>
                            <div><?php echo (int)$book['SoTrang']; ?></div>
                        <?php endif; ?>
                        <?php if (!empty($book['KichThuoc'])): ?>
                            <div style="font-weight:bold;">Kích thước</div>
                            <div><?php echo htmlspecialchars($book['KichThuoc']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="price-box" style="margin:15px 0;">
                    <span class="price" style="font-size:24px; color:#e53935; font-weight:bold;">
                        <?php echo number_format($book['DonGiaBan'], 0, ',', '.'); ?>₫
                    </span>
                    <?php if (!empty($book['DonGiaNhap']) && $book['DonGiaNhap'] > 0 && $book['DonGiaNhap'] < $book['DonGiaBan']): ?>
                        <span class="compare-price" style="margin-left:10px; text-decoration:line-through; color:#888;">
                            <?php echo number_format($book['DonGiaNhap'], 0, ',', '.'); ?>₫
                        </span>
                    <?php endif; ?>
                </div>

                <div style="margin:20px 0;">
                    <form action="<?php echo BASE_URL; ?>/customer/module/order/cart_api.php" method="post" style="display:flex; align-items:center; gap:10px;">
                        <input type="hidden" name="MaSach" value="<?php echo htmlspecialchars($book['MaSach']); ?>">
                        <input type="number" name="quantity" value="1" min="1" style="width:80px;" />
                        <button type="submit" class="btn btn-primary add_to_cart" style="padding:10px 18px; background:#228B22; border:none; color:#fff; border-radius:4px; cursor:pointer;">
                            Thêm vào giỏ
                        </button>
                    </form>
                </div>

                <?php if (!empty($book['MoTa'])): ?>
                    <div class="product-description" style="margin-top:20px; border:1px solid #ddd; padding:15px; border-radius:4px; background:#fafafa;">
                        <h3 style="font-size:20px; margin-bottom:10px; color:green;">Mô tả sách</h3>
                        <p style="white-space:pre-line; line-height:1.6;">
                            <?php echo nl2br(htmlspecialchars($book['MoTa'])); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="product-reviews" id="reviews" style="margin-top:25px; border:1px solid #ddd; padding:15px; border-radius:4px; background:#fff;">
                    <h3 style="font-size:20px; margin-bottom:12px; color:green;">Đánh giá sách</h3>

                    <div style="margin-bottom:10px;">
                        <strong>Email của bạn:</strong>
                        <?php if ($userEmail): ?>
                            <span> <?php echo htmlspecialchars($userEmail); ?></span>
                        <?php else: ?>
                            <span> Bạn chưa đăng nhập ?. <a style="color: blue;" href="<?php echo BASE_URL; ?>/login_register/login.php">Đăng nhập để đánh giá</a></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($userEmail): ?>
                        <form method="post" action="<?php echo BASE_URL; ?>/customer/module/review/bookreview.php?masach=<?php echo urlencode($book['MaSach']); ?>#reviews">
                            <input type="hidden" name="action" value="submit_review">
                            <div style="margin-bottom:10px;">
                                <label><strong>Đánh giá bằng sao:</strong></label>
                                <div class="star-rating" style="margin-top:4px; display:inline-flex; align-items:center; gap:4px;">
                                    <input type="hidden" name="rating" id="rating-input" required>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star" data-value="<?php echo $i; ?>" style="font-size:22px; cursor:pointer; color:#ccc;">★</span>
                                    <?php endfor; ?>
                                    <span id="star-count" style="margin-left:6px; font-size:14px; color:#555;">0 sao</span>
                                </div>
                            </div>

                            <div style="margin-bottom:10px;">
                                <label for="review_content"><strong>Nội dung đánh giá:</strong></label>
                                <textarea id="review_content" name="review_content" rows="4" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px;" required></textarea>
                            </div>

                            <?php if (!empty($review_error)): ?>
                                <div style="color:red; margin-bottom:8px;">
                                    <?php echo htmlspecialchars($review_error); ?>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary" style="padding:8px 16px; background:#228B22; border:none; border-radius:4px; color:#fff; cursor:pointer;">Gửi đánh giá</button>
                        </form>
                    <?php elseif (!empty($review_error)): ?>
                        <div style="color:red; margin-bottom:8px;">
                            <?php echo htmlspecialchars($review_error); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:15px;">
                        <h4 style="font-size:16px; margin-bottom:8px;">Các đánh giá khác</h4>
                        <?php if (empty($reviews)): ?>
                            <div>Chưa có đánh giá nào.</div>
                        <?php else: ?>
                            <?php foreach ($reviews as $rev): ?>
                                <div style="border-top:1px solid #eee; padding-top:8px; margin-top:8px;">
                                    <div style="font-size:14px;">
                                        <strong><?php echo htmlspecialchars($rev['Email']); ?></strong>
                                        <span style="margin-left:8px; color:#ffc107;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php echo $i <= (int)$rev['SoSao'] ? '★' : '☆'; ?>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($rev['NoiDung'])): ?>
                                        <div style="margin-top:4px;">
                                            <?php echo nl2br(htmlspecialchars($rev['NoiDung'])); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($rev['NgayDang'])): ?>
                                        <div style="margin-top:2px; font-size:12px; color:#888;">
                                            <?php echo htmlspecialchars($rev['NgayDang']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($otherBooks)): ?>
            <section class="other-books" style="margin-top:40px; border-top:1px solid #eee; padding-top:25px;">
                <div class="title-border">
                    <h2 class="title-module">
                        <a href="<?php echo BASE_URL; ?>/customer/module/books.php" title="Gợi ý cho bạn">Gợi ý cho bạn</a>
                    </h2>
                </div>
                <div class="slide-book-new-wrap relative swiper-button-main">
                    <div class="swiper-container" style="cursor: grab; overflow: hidden;">
                        <div class="swiper-wrapper" style="display: flex; flex-wrap: nowrap; gap: 20px; justify-content: center;">
                            <?php foreach ($otherBooks as $ob): ?>
                                <div class="swiper-slide" style="width: 20%; max-width: 250px;">
                                    <div class="item_product_main">
                                        <form action="/cart/add" method="post" class="variants product-action wishItem" data-cart-form="" enctype="multipart/form-data">
                                            <div class="thumb">
                                                <a class="image_thumb" href="<?php echo BASE_URL; ?>/customer/module/review/bookreview.php?masach=<?php echo urlencode($ob['MaSach']); ?>" title="<?php echo htmlspecialchars($ob['TenSach']); ?>">
                                                    <img width="199" height="199" src="<?php echo htmlspecialchars($ob['Anh']); ?>" alt="<?php echo htmlspecialchars($ob['TenSach']); ?>" class="lazyload img-responsive center-block">
                                                </a>
                                                <div class="action-cart">
                                                    <button type="button" class="btn btn-lg btn-gray add_to_cart btn_buy buy-normal " title="Thêm vào giỏ" data-masach="<?php echo htmlspecialchars($ob['MaSach']); ?>" data-qty="1">
                                                        <svg class="icon">
                                                            <use xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="#addcarticon"></use>
                                                        </svg>
                                                    </button>
                                                    <button type="button" class="btn-buy btn-left btn-views btn-buy-now-grid" title="Mua ngay" data-masach="<?php echo htmlspecialchars($ob['MaSach']); ?>" data-qty="1">Mua ngay</button>
                                                </div>
                                            </div>
                                            <div class="info-product">
                                                <h3 class="product-name"><a href="<?php echo BASE_URL; ?>/customer/module/review/bookreview.php?masach=<?php echo urlencode($ob['MaSach']); ?>" title="<?php echo htmlspecialchars($ob['TenSach']); ?>"><?php echo htmlspecialchars($ob['TenSach']); ?></a></h3>
                                                <div class="price-box">
                                                    <span class="price"><?php echo number_format($ob['DonGiaBan'], 0, ",", "."); ?>₫</span>
                                                    <?php if (!empty($ob['DonGiaNhap']) && $ob['DonGiaNhap'] > 0 && $ob['DonGiaNhap'] < $ob['DonGiaBan']): ?>
                                                        <span class="compare-price"><?php echo number_format($ob['DonGiaNhap'], 0, ",", "."); ?>₫</span>
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
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include dirname(__DIR__, 3) . '/layout/footer.php'; ?>

<script>
    // Tương tác đánh giá bằng sao cho form đánh giá
    (function() {
        var container = document.querySelector('.product-reviews .star-rating');
        if (!container) return;

        var stars = container.querySelectorAll('.star');
        var input = document.getElementById('rating-input');
        var countLabel = document.getElementById('star-count');
        var selected = 0;

        function render(value) {
            stars.forEach(function(star) {
                var v = parseInt(star.getAttribute('data-value'), 10);
                star.style.color = v <= value ? '#ffc107' : '#ccc';
            });
            if (countLabel) {
                var n = value || 0;
                countLabel.textContent = n + ' sao';
            }
        }

        stars.forEach(function(star) {
            star.addEventListener('mouseenter', function() {
                var v = parseInt(this.getAttribute('data-value'), 10) || 0;
                render(v);
            });

            star.addEventListener('click', function() {
                selected = parseInt(this.getAttribute('data-value'), 10) || 0;
                if (input) {
                    input.value = selected;
                }
                render(selected);
            });
        });

        container.addEventListener('mouseleave', function() {
            render(selected);
        });

        // Khởi tạo
        render(0);
    })();

    (function() {
        function asyncLoad() {
            var urls = ["https://aff.sapoapps.vn/api/proxy/scripttag.js?store=nhanamvn.mysapo.net", "https://statistic-blog-v2.sapoapps.vn/api/script-tag.js?store=nhanamvn.mysapo.net", "https://google-shopping-v2.sapoapps.vn/api/conversion-tracker/global-tag/4000?store=nhanamvn.mysapo.net"];
            for (var i = 0; i < urls.length; i++) {
                var s = document.createElement('script');
                s.type = 'text/javascript';
                s.async = true;
                s.src = urls[i];
                var x = document.getElementsByTagName('script')[0];
                x.parentNode.insertBefore(s, x);
            }
        };
        window.attachEvent ? window.attachEvent('onload', asyncLoad) : window.addEventListener('load', asyncLoad, false);
    })();
</script>


<script src="/dist/js/stats.min.js?v=96f2ff2"></script>
<script async="" src="//bizweb.dktcdn.net/web/assets/lib/js/fp.v3.3.0.min.js"></script>
<script async="" src="//bizweb.dktcdn.net/web/assets/lib/js/fp.v3.3.0.min.js"></script>


<script>
    window.dataLayer = window.dataLayer || [];

    function gtag() {
        dataLayer.push(arguments);
    }
    gtag('js', new Date());
    gtag('config', 'G-DLLTLF5DTT');
</script>
<script>
    window.enabled_enhanced_ecommerce = false;
</script>

<style>
    .--savior-overlay-transform-reset {
        transform: none !important;
    }

    .--savior-overlay-z-index-top {
        z-index: 2147483643 !important;
    }

    .--savior-overlay-position-relative {
        position: relative;
    }

    .--savior-overlay-position-static {
        position: static !important;
    }

    .--savior-overlay-overflow-hidden {
        overflow: hidden !important;
    }

    .--savior-overlay-overflow-x-visible {
        overflow-x: visible !important;
    }

    .--savior-overlay-overflow-y-visible {
        overflow-y: visible !important;
    }

    .--savior-overlay-z-index-reset {
        z-index: auto !important;
    }

    .--savior-overlay-display-none {
        display: none !important;
    }

    .--savior-overlay-clearfix {
        clear: both;
    }

    .--savior-overlay-reset-filter {
        filter: none !important;
        backdrop-filter: none !important;
    }

    .--savior-tooltip-host {
        z-index: 9999;
        position: absolute;
        top: 0;
    }

    /*Override css styles for Twitch.tv*/
    main.--savior-overlay-z-index-reset {
        z-index: auto !important;
    }

    .modal__backdrop.--savior-overlay-z-index-reset {
        position: static !important;
    }

    main.--savior-overlay-z-index-top {
        z-index: auto !important;
    }

    main.--savior-overlay-z-index-top .channel-root__player-container+div,
    main.--savior-overlay-z-index-top .video-player-hosting-ui__container+div {
        opacity: 0.1;
    }

    /*Dirty hack for facebook big video page e.g: https://www.facebook.com/abc/videos/...*/
    .--savior-backdrop {
        position: fixed !important;
        z-index: 2147483642 !important;
        top: 0;
        left: 0;
        height: 100vh;
        width: 100vw !important;
        background-color: rgba(0, 0, 0, 0.9);
    }

    .--savior-overlay-twitter-video-player {
        position: fixed;
        width: 80%;
        height: 80%;
        top: 10%;
        left: 10%;
    }

    .--savior-overlay-z-index-reset [class*="DivSideNavContainer"],
    .--savior-overlay-z-index-reset [class*="DivHeaderContainer"],
    .--savior-overlay-z-index-reset [class*="DivBottomContainer"],
    .--savior-overlay-z-index-reset [class*="DivCategoryListWrapper"],
    .--savior-overlay-z-index-reset [data-testid="sidebarColumn"],
    .--savior-overlay-z-index-reset header[role="banner"],
    .--savior-overlay-z-index-reset [data-testid="cellInnerDiv"]:not(.--savior-overlay-z-index-reset),
    .--savior-overlay-z-index-reset [aria-label="Home timeline"]>div:first-child,
    .--savior-overlay-z-index-reset [aria-label="Home timeline"]>div:nth-child(3) {
        z-index: -1 !important;
    }

    .--savior-overlay-z-index-reset [data-testid="cellInnerDiv"] .--savior-backdrop+div {
        z-index: 2147483643 !important;
    }

    .--savior-overlay-z-index-reset [data-testid="primaryColumn"]>[aria-label="Home timeline"] {
        z-index: 0 !important;
    }

    .--savior-overlay-z-index-reset#mtLayer,
    .--savior-overlay-z-index-reset.media-layer {
        z-index: 3000 !important;
    }

    .--savior-overlay-position-relative [class*="SecBar_secBar_"],
    .--savior-overlay-position-relative .woo-box-flex [class*="Frame_top_"] {
        z-index: 0 !important;
    }

    .--savior-overlay-position-relative .vue-recycle-scroller__item-view:not(.--savior-overlay-z-index-reset),
    .--savior-overlay-position-relative .woo-panel-main[class*="BackTop_main_"],
    .--savior-overlay-position-relative [class*="Main_side_"] {
        z-index: -1 !important;
    }

    /* Fix conflict css with zingmp3 */
    .zm-video-modal.--savior-overlay-z-index-reset {
        position: absolute;
    }

    /* Dirty hack for xvideos99 */
    #page #main.--savior-overlay-z-index-reset {
        z-index: auto !important;
    }

    /* Overlay for ok.ru */
    #vp_w.--savior-overlay-z-index-reset.media-layer.media-layer__video {
        overflow-y: hidden;
        z-index: 2147483643 !important;
    }

    /* Fix missing controller for tv.naver.com */
    .--savior-overlay-z-index-top.rmc_controller,
    .--savior-overlay-z-index-top.rmc_setting_intro,
    .--savior-overlay-z-index-top.rmc_highlight,
    .--savior-overlay-z-index-top.rmc_control_settings {
        z-index: 2147483644 !important;
    }

    /* Dirty hack for douyi.com */
    .swiper-wrapper.--savior-overlay-z-index-reset .swiper-slide:not(.swiper-slide-active),
    .swiper-wrapper.--savior-overlay-transform-reset .swiper-slide:not(.swiper-slide-active) {
        display: none;
    }

    .videoWrap+div>div {
        pointer-events: unset;
    }

    /* Dirty hack for fpt.ai */
    .mfp-wrap.--savior-overlay-z-index-top {
        position: relative;
    }

    .mfp-wrap.--savior-overlay-z-index-top .mfp-close {
        display: none;
    }

    .mfp-wrap.--savior-overlay-z-index-top .mfp-content {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    section.--savior-overlay-z-index-reset>main[role="main"].--savior-overlay-z-index-reset+nav {
        z-index: -1 !important;
    }

    section.--savior-overlay-z-index-reset>main[role="main"].--savior-overlay-z-index-reset section.--savior-overlay-z-index-reset div.--savior-overlay-z-index-reset~div {
        position: relative;
    }

    .watching-movie #video-player.--savior-overlay-z-index-top {
        z-index: 2147483644 !important;
    }

    div[class^="tiktok"].--savior-overlay-z-index-reset {
        z-index: 2147483644 !important;
    }

    .--savior-lightoff-fix section:not(:has([class*="--savior-overlay-"])),
    .--savior-lightoff-fix section.section_video~section {
        z-index: -1;
        position: relative;
    }

    .--savior-lightoff-fix header,
    .--savior-lightoff-fix footer,
    .--savior-lightoff-fix .top-header,
    .--savior-lightoff-fix .swiper-container,
    .--savior-lightoff-fix #to_top,
    .--savior-lightoff-fix #button-adblock {
        z-index: -1 !important;
    }

    @-moz-keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @-webkit-keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @-o-keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }
</style>
</head>

<body>


</body><en2vi-host class="corom-element" version="3" style="all: initial; position: absolute; top: 0; left: 0; right: 0; height: 0; margin: 0; text-align: left; z-index: 10000000000; pointer-events: none; border: none; display: block"></en2vi-host><savior-host style="all: unset; position: absolute; top: 0; left: 0; z-index: 99999999999999; display: block !important; overflow: unset"></savior-host>

</html>