<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/configpath.php';
require_once dirname(__DIR__, 2) . '/connected.php';
$page_title = "Tất cả tác giả";
include dirname(__DIR__, 2) . '/layout/header.php';
?>
<div class="section_author_featured" style="padding: 40px 0;">
    <div class="container">
        <div class="title-border">
            <h2 class="title-module">
                <a href="#" title="Các tác giả">Tất cả tác giả</a>
            </h2>
        </div>
        <div class="slide-author-wrap relative swiper-button-main">
            <div class="swiper-container slide-author" style="cursor: grab;">
                <div class="swiper-wrapper" style="display: flex; flex-wrap: wrap; gap: 20px;">
                    <?php
                    $sql_authors = "SELECT * FROM tac_gia";
                    $result_authors = $conn->query($sql_authors);
                    if ($result_authors && $result_authors->num_rows > 0) {
                        $i = 1;
                        while ($row = $result_authors->fetch_assoc()) {
                            $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $row['TenTacGia']));
                            echo '<div class="swiper-slide" style="width: 250px; margin-right: 10px; margin-bottom: 30px;">';
                            echo '<div class="d-none"></div>';
                            echo '<article class="item_author_base">';
                            echo '<a class="thumb image_thumb" href="' . BASE_URL . '/customer/module/review/authorreview.php?matacgia=' . urlencode($row['MaTacGia']) . '" title="' . htmlspecialchars($row['TenTacGia']) . '">';
                            echo '<img width="150" height="150" src="' . htmlspecialchars($row['HinhAnh']) . '" data-src="' . htmlspecialchars($row['HinhAnh']) . '" alt="' . htmlspecialchars($row['TenTacGia']) . '" class="lazyload img-responsive loaded" data-was-processed="true">';
                            echo '</a>';
                            echo '<h3><a href="' . BASE_URL . '/customer/module/review/authorreview.php?matacgia=' . urlencode($row['MaTacGia']) . '" title="' . htmlspecialchars($row['TenTacGia']) . '" class="a-title">' . htmlspecialchars($row['TenTacGia']) . '</a></h3>';
                            echo '</article>';
                            echo '</div>';
                            $i++;
                        }
                    } else {
                        echo '<div>Không có tác giả nào.</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include dirname(__DIR__, 2) . '/layout/footer.php'; ?>