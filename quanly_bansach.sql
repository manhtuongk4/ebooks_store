-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th1 06, 2026 lúc 03:16 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `quanly_bansach`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_hoa_don`
--

CREATE TABLE `chi_tiet_hoa_don` (
  `MaHD` varchar(10) NOT NULL,
  `MaSach` varchar(10) NOT NULL,
  `SoLuongBan` int(11) NOT NULL,
  `DonGiaBan` decimal(10,2) NOT NULL,
  `ThanhTien` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_hoa_don`
--

INSERT INTO `chi_tiet_hoa_don` (`MaHD`, `MaSach`, `SoLuongBan`, `DonGiaBan`, `ThanhTien`) VALUES
('HD04058258', 'S020', 1, 80000.00, 80000.00),
('HD59323999', 'S020', 1, 80000.00, 80000.00),
('HD92951611', 'S020', 1, 80000.00, 80000.00),
('HD95224789', 'S020', 1, 80000.00, 80000.00),
('HD95381880', 'S020', 1, 80000.00, 80000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_gia_binh_luan`
--

CREATE TABLE `danh_gia_binh_luan` (
  `MaDG` int(11) NOT NULL,
  `MaSach` varchar(10) NOT NULL,
  `MaKH` varchar(10) NOT NULL,
  `SoSao` int(11) DEFAULT NULL CHECK (`SoSao` >= 1 and `SoSao` <= 5),
  `NoiDung` text DEFAULT NULL,
  `TraLoi` text DEFAULT NULL,
  `NgayDang` datetime DEFAULT NULL,
  `NgayTraLoi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `danh_gia_binh_luan`
--

INSERT INTO `danh_gia_binh_luan` (`MaDG`, `MaSach`, `MaKH`, `SoSao`, `NoiDung`, `TraLoi`, `NgayDang`, `NgayTraLoi`) VALUES
(1, 'S020', 'KH001', 5, 'sách rất hay, tôi rất thích nó !', 'cảm ơn bạn đã ủng hộ shop !', '2026-01-05 00:53:24', '2026-01-05 01:17:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `dich_gia`
--

CREATE TABLE `dich_gia` (
  `MaDichGia` varchar(10) NOT NULL,
  `TenDichGia` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `dich_gia`
--

INSERT INTO `dich_gia` (`MaDichGia`, `TenDichGia`) VALUES
('DG001', 'Trịnh Lữ'),
('DG002', 'Phạm Xuân Nguyên'),
('DG003', 'Nguyễn Bích Lan'),
('DG004', 'Phạm Hảo'),
('DG005', 'Quang Lê'),
('DG006', 'Phạm Viêm Phương'),
('DG007', 'Bùi Văn Nam Sơn'),
('DG008', 'Đinh Tị'),
('DG009', 'Trần Đình Hiếu'),
('DG010', 'Nguyễn Lệ Chi'),
('DG011', 'Ngô Thị Thanh Vân'),
('DG012', 'Cao Xuân Hạo');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `giao_dich_thanh_toan`
--

CREATE TABLE `giao_dich_thanh_toan` (
  `MaGiaoDich` varchar(50) NOT NULL,
  `MaGiaoDichNganHang` varchar(100) DEFAULT NULL,
  `MaHD` varchar(10) DEFAULT NULL,
  `NgayGiaoDich` datetime DEFAULT current_timestamp(),
  `SoTien` decimal(12,2) DEFAULT NULL,
  `SoTaiKhoanNhan` varchar(20) DEFAULT NULL,
  `NoiDungThanhToan` text DEFAULT NULL,
  `TrangThai` varchar(50) DEFAULT 'Pending',
  `PhanHoiTuNganHang` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`PhanHoiTuNganHang`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `giao_dich_thanh_toan`
--

INSERT INTO `giao_dich_thanh_toan` (`MaGiaoDich`, `MaGiaoDichNganHang`, `MaHD`, `NgayGiaoDich`, `SoTien`, `SoTaiKhoanNhan`, `NoiDungThanhToan`, `TrangThai`, `PhanHoiTuNganHang`) VALUES
('GD260103042219576', NULL, 'HD04058258', '2026-01-03 10:22:19', 80000.00, NULL, 'Thanh toan don hang HD04058258 qua BIDV', 'KhoiTao', NULL),
('GD260103043447787', NULL, 'HD95381880', '2026-01-03 10:34:47', 80000.00, NULL, 'Thanh toan don hang HD95381880 qua BIDV', 'KhoiTao', NULL),
('GD260103043504774', NULL, 'HD95224789', '2026-01-03 10:35:04', 80000.00, NULL, 'Thanh toan don hang HD95224789 qua BIDV', 'KhoiTao', NULL),
('GD260103044857871', NULL, 'HD59323999', '2026-01-03 10:48:57', 80000.00, NULL, 'Thanh toan don hang HD59323999 qua BIDV', 'KhoiTao', NULL),
('GD260103051731227', NULL, 'HD92951611', '2026-01-03 11:17:31', 80000.00, NULL, 'Thanh toan don hang HD92951611 qua BIDV', 'KhoiTao', NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hoa_don`
--

CREATE TABLE `hoa_don` (
  `MaHD` varchar(10) NOT NULL,
  `NgayLapHD` date NOT NULL,
  `MaKH` varchar(10) DEFAULT NULL,
  `MaNhanVien` varchar(10) DEFAULT NULL,
  `TongTien` decimal(12,2) NOT NULL DEFAULT 0.00,
  `PhuongThucThanhToan` varchar(50) DEFAULT 'COD',
  `TrangThaiThanhToan` varchar(20) DEFAULT 'ChuaThanhToan',
  `MaGiaoDichNganHang` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `hoa_don`
--

INSERT INTO `hoa_don` (`MaHD`, `NgayLapHD`, `MaKH`, `MaNhanVien`, `TongTien`, `PhuongThucThanhToan`, `TrangThaiThanhToan`, `MaGiaoDichNganHang`) VALUES
('HD04058258', '2026-01-03', 'KH001', NULL, 80000.00, 'BIDV', 'ChoThanhToan', 'GD260103042219576'),
('HD59323999', '2026-01-03', 'KH001', NULL, 80000.00, 'BIDV', 'ChoThanhToan', 'GD260103044857871'),
('HD92951611', '2026-01-03', 'KH001', NULL, 80000.00, 'BIDV', 'DaThanhToan', 'GD260103051731227'),
('HD95224789', '2026-01-03', 'KH001', NULL, 80000.00, 'BIDV', 'DaThanhToan', 'GD260103043504774'),
('HD95381880', '2026-01-03', 'KH001', NULL, 80000.00, 'BIDV', 'DaThanhToan', 'GD260103043447787');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `khach_hang`
--

CREATE TABLE `khach_hang` (
  `MaKH` varchar(10) NOT NULL,
  `HoTenKH` varchar(150) NOT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `Avatar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `khach_hang`
--

INSERT INTO `khach_hang` (`MaKH`, `HoTenKH`, `DiaChi`, `SoDienThoai`, `Email`, `PasswordHash`, `Avatar`) VALUES
('KH001', 'Nguyễn Mạnh Tường', '123, Huyện Phù Ninh, Tỉnh Phú Thọ', '0868219140', 'tuong.nm.64cntt@ntu.edu.vn', '0868219140Tg.', 'https://thichtrangtri.com/wp-content/uploads/2025/05/anh-meo-gian-cute-3.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nhan_vien`
--

CREATE TABLE `nhan_vien` (
  `MaNhanVien` varchar(10) NOT NULL,
  `HoTenNV` varchar(150) NOT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `NgaySinh` date DEFAULT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL,
  `PasswordHash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nhan_vien`
--

INSERT INTO `nhan_vien` (`MaNhanVien`, `HoTenNV`, `Email`, `NgaySinh`, `DiaChi`, `SoDienThoai`, `PasswordHash`) VALUES
('NV001', 'ADMIN', 'admin@mote.vn', '2004-04-13', 'Vĩnh Thọ, Nha Trang, Khánh Hòa', '0868219140', '0868219140Tg.');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nha_xuat_ban`
--

CREATE TABLE `nha_xuat_ban` (
  `MaNXB` varchar(10) NOT NULL,
  `TenNXB` varchar(200) NOT NULL,
  `DiaChi` varchar(255) DEFAULT NULL,
  `SoDienThoai` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `nha_xuat_ban`
--

INSERT INTO `nha_xuat_ban` (`MaNXB`, `TenNXB`, `DiaChi`, `SoDienThoai`) VALUES
('NXB01', 'Nhà xuất bản Trẻ', '161B Lý Chính Thắng, P.7, Q.3, TP.HCM', '02839316281'),
('NXB02', 'Nhà xuất bản Kim Đồng', '55 Quang Trung, Hai Bà Trưng, Hà Nội', '02439434730'),
('NXB03', 'Nhà xuất bản Hội Nhà Văn', '65 Nguyễn Du, Hai Bà Trưng, Hà Nội', '02438253160'),
('NXB04', 'Nhà xuất bản Tổng hợp TP.HCM', '62 Nguyễn Thị Minh Khai, P.Đa Kao, Q.1, TP.HCM', '02838225340'),
('NXB05', 'Nhà xuất bản Lao Động', '175 Giảng Võ, Ba Đình, Hà Nội', '02438515320');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sach`
--

CREATE TABLE `sach` (
  `MaSach` varchar(10) NOT NULL,
  `TenSach` varchar(255) NOT NULL,
  `MaTacGia` varchar(10) DEFAULT NULL,
  `MaTheLoai` varchar(10) DEFAULT NULL,
  `MaNXB` varchar(10) DEFAULT NULL,
  `DonGiaNhap` decimal(10,2) DEFAULT NULL,
  `DonGiaBan` decimal(10,2) NOT NULL,
  `NamXuatBan` year(4) DEFAULT NULL,
  `SoLuongTon` int(11) DEFAULT 0,
  `MoTa` text DEFAULT NULL,
  `KichThuoc` varchar(50) DEFAULT NULL,
  `SoTrang` int(11) DEFAULT NULL,
  `Anh` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sach`
--

INSERT INTO `sach` (`MaSach`, `TenSach`, `MaTacGia`, `MaTheLoai`, `MaNXB`, `DonGiaNhap`, `DonGiaBan`, `NamXuatBan`, `SoLuongTon`, `MoTa`, `KichThuoc`, `SoTrang`, `Anh`) VALUES
('S001', 'Tôi thấy hoa vàng trên cỏ xanh', 'TG001', 'TL001', 'NXB01', 50000.00, 85000.00, '2010', 150, 'Câu chuyện tuổi thơ xúc động ở vùng quê Việt Nam.', '13x20.5 cm', 350, 'https://nhasachmienphi.com/images/thumbnail/nhasachmienphi-toi-thay-hoa-vang-tren-co-xanh.jpg'),
('S002', 'Cho tôi xin một vé đi tuổi thơ', 'TG001', 'TL003', 'NXB01', 45000.00, 78000.00, '2008', 120, 'Nhìn cuộc sống qua lăng kính trẻ thơ đầy hồn nhiên.', '13x20.5 cm', 280, 'https://upload.wikimedia.org/wikipedia/vi/c/c9/Cho_t%C3%B4i_xin_m%E1%BB%99t_v%C3%A9_%C4%91i_tu%E1%BB%95i_th%C6%A1.jpg'),
('S003', 'Rừng Na Uy', 'TG002', 'TL001', 'NXB03', 90000.00, 150000.00, '2005', 80, 'Tiểu thuyết về tuổi trẻ, tình yêu và sự mất mát.', '14x20.5 cm', 550, 'https://bizweb.dktcdn.net/thumb/1024x1024/100/363/455/products/rungnauy004-f9a8f341-50e7-47b2-bccf-6923e33c998d.jpg?v=1723778526173'),
('S004', 'Kafka bên bờ biển', 'TG002', 'TL006', 'NXB03', 120000.00, 195000.00, '2007', 70, 'Hành trình kỳ ảo tìm kiếm bản thân của chàng trai Kafka Tamura.', '15x22 cm', 650, 'https://cdn1.fahasa.com/media/catalog/product/8/9/8935235242654.jpg'),
('S005', 'Harry Potter và Hòn đá Phù thủy', 'TG004', 'TL006', 'NXB02', 100000.00, 160000.00, '2000', 200, 'Khởi đầu của cậu bé phù thủy nổi tiếng thế giới.', '14x20 cm', 420, 'https://cdn1.fahasa.com/media/catalog/product/8/9/8934974179672.jpg'),
('S006', 'Đắc Nhân Tâm', 'TG006', 'TL017', 'NXB04', 70000.00, 110000.00, '2015', 300, 'Nghệ thuật thu phục lòng người và giao tiếp hiệu quả.', '16x24 cm', 320, 'https://product.hstatic.net/200000122283/product/2dc271bbb10ba65659682d524346486d_4c4f90dc4f4f47088148797ffef87336.jpg'),
('S007', 'Quẳng gánh lo đi và vui sống', 'TG006', 'TL024', 'NXB04', 65000.00, 95000.00, '2017', 180, 'Bí quyết loại bỏ lo âu và tận hưởng cuộc sống.', '14x20.5 cm', 250, 'https://pos.nvncdn.com/fd5775-40602/ps/20240621_421uEPwSge.png'),
('S008', 'Sapiens: Lược sử loài người', 'TG007', 'TL013', 'NXB05', 150000.00, 240000.00, '2014', 90, 'Phân tích lịch sử tiến hóa của loài người từ 70,000 năm trước.', '16x24 cm', 600, 'https://salt.tikicdn.com/cache/w300/ts/product/47/52/56/160fe47458e1d77cd11be085ac85e170.jpg'),
('S009', 'Homo Deus: Lược sử tương lai', 'TG007', 'TL014', 'NXB05', 140000.00, 225000.00, '2016', 60, 'Dự đoán tương lai con người khi khoa học và công nghệ lên ngôi.', '16x24 cm', 580, 'https://yds.edu.vn/wp-content/uploads/2024/11/homo-deus-luoc-su-tuong-lai-pdf.jpg'),
('S010', 'Thuật xử thế', 'TG003', 'TL012', 'NXB03', 40000.00, 68000.00, '1970', 110, 'Những nguyên tắc đạo đức và cách ứng xử trong xã hội.', '13x19 cm', 220, 'https://cdn1.fahasa.com/media/flashmagazine/images/page_images/thuat_xu_the_cua_nguoi_xua_tai_ban_2021/2021_05_14_14_13_53_1-390x510.jpg'),
('S011', 'Vợ chồng A Phủ', 'TG008', 'TL002', 'NXB03', 30000.00, 55000.00, '1952', 130, 'Tác phẩm kinh điển về cuộc sống của người dân miền núi.', '13x19 cm', 180, 'https://m.media-amazon.com/images/S/compressed.photo.goodreads.com/books/1640952539i/59984375.jpg'),
('S012', 'The Shining (Ngôi nhà ma)', 'TG005', 'TL005', 'NXB04', 110000.00, 175000.00, '2011', 40, 'Tiểu thuyết kinh dị nổi tiếng về khách sạn Overlook.', '14x20.5 cm', 450, 'https://cdn1.fahasa.com/media/catalog/product/i/m/image_147113.jpg'),
('S013', 'Khởi nghiệp tinh gọn (The Lean Startup)', 'TG010', 'TL015', 'NXB04', 100000.00, 155000.00, '2011', 190, 'Phương pháp xây dựng sản phẩm và doanh nghiệp hiệu quả.', '16x24 cm', 300, 'https://masterihomes.com.vn/wp-content/uploads/2025/08/sach-khoi-nghiep-tinh-gon-the-lean-startup-pdf.png'),
('S014', 'The Great Gatsby', 'TG012', 'TL002', 'NXB03', 75000.00, 120000.00, '2015', 85, 'Câu chuyện về giấc mơ Mỹ và tình yêu tan vỡ.', '13x20.5 cm', 280, 'https://cdn1.fahasa.com/media/catalog/product/t/h/the_great_gatsby_1_2018_10_27_11_49_37.jpg'),
('S015', 'Mật mã Da Vinci', 'TG005', 'TL005', 'NXB05', 90000.00, 140000.00, '2010', 100, 'Tiểu thuyết trinh thám ly kỳ về biểu tượng và nghệ thuật.', '15x23 cm', 500, 'https://nhasachhaian.com/wp-content/uploads/2025/11/mat-ma-da-vinci-pdf.jpg'),
('S016', 'Bố già', 'TG009', 'TL008', 'NXB04', 130000.00, 210000.00, '2008', 140, 'Câu chuyện về gia đình mafia Corleone.', '14x21 cm', 700, 'https://saigonsachcu.com/wp-content/uploads/2023/08/Bo-gia-Godfather.jpg'),
('S017', 'Nhà giả kim', 'TG007', 'TL012', 'NXB03', 60000.00, 95000.00, '2012', 250, 'Truyện ngụ ngôn về việc theo đuổi ước mơ.', '13x20 cm', 200, 'https://jes.edu.vn/wp-content/uploads/2022/11/nha-gia-kim-pdf.jpg'),
('S018', 'Tâm lý học về tiền', 'TG006', 'TL019', 'NXB04', 80000.00, 130000.00, '2020', 160, 'Cách suy nghĩ về tiền ảnh hưởng đến quyết định tài chính.', '15x22 cm', 380, 'https://nxbhcm.com.vn/Image/Biasach/tamlyhocvetien.jpg'),
('S019', 'Lịch sử khoa học', 'TG011', 'TL014', 'NXB02', 95000.00, 150000.00, '2021', 100, 'Tổng quan về các phát minh khoa học vĩ đại.', '17x25 cm', 400, 'https://product.hstatic.net/1000328521/product/bia_1_-_lich_su_khoa_hoc_3861b2776def49a897def4a6af39e0ce.jpg'),
('S020', 'Chuyện tử tế', 'TG003', 'TL009', 'NXB01', 50000.00, 80000.00, '2018', 120, 'Tản văn về lối sống và đạo đức con người.', '13x20.5 cm', 240, 'https://cdn0.fahasa.com/media/flashmagazine/images/page_images/nguyen_hien_le_cuoc_doi_va_tac_pham/2023_06_05_16_04_52_1-390x510.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `sach_dichgia`
--

CREATE TABLE `sach_dichgia` (
  `MaSach` varchar(10) NOT NULL,
  `MaDichGia` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `sach_dichgia`
--

INSERT INTO `sach_dichgia` (`MaSach`, `MaDichGia`) VALUES
('S003', 'DG001'),
('S004', 'DG001'),
('S005', 'DG004'),
('S008', 'DG003'),
('S009', 'DG003'),
('S012', 'DG004'),
('S013', 'DG010'),
('S014', 'DG002'),
('S016', 'DG012'),
('S017', 'DG001'),
('S018', 'DG005');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tac_gia`
--

CREATE TABLE `tac_gia` (
  `MaTacGia` varchar(10) NOT NULL,
  `TenTacGia` varchar(150) NOT NULL,
  `NgaySinh` date DEFAULT NULL,
  `HinhAnh` varchar(255) DEFAULT NULL,
  `MoTa` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tac_gia`
--

INSERT INTO `tac_gia` (`MaTacGia`, `TenTacGia`, `NgaySinh`, `HinhAnh`, `MoTa`) VALUES
('TG001', 'Nguyễn Nhật Ánh', '1955-05-15', 'https://www.nxbtre.com.vn/Images/Writer/nxbtre_thumb_30552016_085555.jpg', 'Nguyễn Nhật Ánh là một nam nhà văn người Việt Nam. Được xem là một trong những nhà văn hiện đại xuất sắc nhất Việt Nam hiện nay, ông được biết đến qua nhiều tác phẩm văn học về đề tài tuổi trẻ. Nhiều tác phẩm của ông được nhiều thế hệ độc giả yêu mến và giới chuyên môn đánh giá cao, được chuyển thể thành phim và kịch nói.'),
('TG002', 'Haruki Murakami', '1949-01-12', 'https://static.tuoitre.vn/tto/i/s626/2014/11/04/s0xUXnxi.jpg', 'Murakami Haruki là một trong những tiểu thuyết gia, dịch giả văn học người Nhật Bản được biết đến nhiều nhất hiện nay cả trong lẫn ngoài nước Nhật. Từ thời điểm nhận giải thưởng Nhà văn mới Gunzo năm 1979 đến nay, hơn một phần tư thế kỷ hoạt động và viết lách, tác phẩm của ông đã được dịch ra khoảng 50 thứ tiếng trên thế giới, đồng thời trong nước ông là người luôn tồn tại ở tiền cảnh sân khấu văn học Nhật Bản. Murakami đã trở thành hiện tượng trong văn học Nhật Bản đương đại với những mĩ danh \"nhà văn được yêu thích\", \"nhà văn bán chạy nhất\", \"nhà văn của giới trẻ\"'),
('TG003', 'Nguyễn Hiến Lê', '1912-01-11', 'https://upload.wikimedia.org/wikipedia/vi/a/a9/Nguyenhienle.jpg', 'Nguyễn Hiến Lê là một học giả, nhà văn, dịch giả, nhà ngôn ngữ học, nhà giáo dục và hoạt động văn hóa độc lập người Việt Nam với 120 tác phẩm sáng tác, biên soạn và dịch thuật thuộc nhiều lĩnh vực khác nhau như giáo dục, văn học, ngữ học, triết học, lịch sử, du ký, gương danh nhân, chính trị, kinh tế, v.v.'),
('TG004', 'J.K. Rowling', '1965-07-31', 'https://lovebookslovelife.vn/wp-content/uploads/2019/11/jjk-01-1200x1200.png', 'Joanne Rowling, thường được biết đến với bút danh J. K. Rowling, là một nhà văn, nhà từ thiện, nhà sản xuất phim và truyền hình, nhà biên kịch người Anh. Bà nổi tiếng là tác giả của bộ truyện giả tưởng Harry Potter từng đoạt nhiều giải thưởng và bán được hơn 500 triệu bản, trở thành bộ sách bán chạy nhất trong lịch sử. Bộ sách đã được chuyển thể thành một loạt phim ăn khách mà chính bà đã phê duyệt kịch bản và cũng là nhà sản xuất của hai phần cuối. Bà cũng viết tiểu thuyết trinh thám hình sự dưới bút danh Robert Galbraith.'),
('TG005', 'Stephen King', '1947-09-21', 'https://cdn.britannica.com/20/217720-050-857D712B/American-novelist-Stephen-King-2004.jpg', 'Stephen Edwin King là một tác gia người mỹ. Vì danh tiếng của ông gắn liền với các tiểu thuyết kinh dị, nên ông nhận được danh xưng là \"Ông hoàng kinh dị\". Tuy nhiên, ông cũng đã thử sức với nhiều thể loại truyện khác, trong đó nổi bật nhất bao gồm giật gân, tội phạm, khoa học viễn tưởng, kỳ ảo và bí ẩn. Ngoài những cuốn tiểu thuyết, ông cũng đã sáng tác khoảng 200 truyện ngắn.'),
('TG006', 'Dale Carnegie', '1888-11-24', 'https://ebookvie.com/wp-content/uploads/2024/02/Tac-gia-Dale-Carnegie.png', 'Dale Breckenridge Carnegie là một nhà văn và nhà thuyết trình Mỹ và là người phát triển các lớp tự giáo dục, nghệ thuật bán hàng, huấn luyện đoàn thể, nói trước công chúng và các kỹ năng giao tiếp giữa mọi người. Ra đời trong cảnh nghèo đói tại một trang trại ở Missouri, ông là tác giả cuốn Đắc Nhân Tâm, được xuất bản lần đầu năm 1936, một cuốn sách hàng bán chạy nhất và được biết đến nhiều nhất cho đến tận ngày nay, nội dung nói về cách ứng xử, cư xử trong cuộc sống. Ông cũng viết một cuốn tiểu sử Abraham Lincoln, với tựa đề Lincoln con người chưa biết, và nhiều cuốn sách khác.'),
('TG007', 'Yuval Noah Harari', '1976-08-24', 'https://upload.wikimedia.org/wikipedia/commons/f/f6/MKr364740_Yuval_Noah_Harari_%28Frankfurter_Buchmesse_2024%29_%28cropped%29.jpg', 'Yuval Noah Harari là một nhà sử học người Israel và là giáo sư Khoa Lịch sử tại Đại học Hebrew Jerusalem. Ông là tác giả của các cuốn sách bán chạy thế giới Sapiens: Lược sử loài người (2014), Homo Deus: Lược sử tương lai (2016) và 21 bài học cho thế kỷ 21 (2018). Bài viết của ông xoay quanh ý chí tự do, ý thức và trí thông minh và hạnh phúc.'),
('TG008', 'Tô Hoài', '1920-09-27', 'https://upload.wikimedia.org/wikipedia/vi/7/73/Nhavan_t%C3%B4_ho%C3%A0i.jpg', 'Tô Hoài là một nhà văn Việt Nam được tặng Giải thưởng Hồ Chí Minh về Văn học Nghệ thuật năm 1996. Ông là Tổng Thư ký đầu tiên của Hội Nhà văn Việt Nam (1957-1963). Dế Mèn phiêu lưu ký là tác phẩm được nhiều người biết nhất của ông dành cho thiếu nhi.'),
('TG009', 'Hermann Hesse', '1877-07-02', 'https://upload.wikimedia.org/wikipedia/commons/thumb/d/da/Hermann_Hesse_2.jpg/250px-Hermann_Hesse_2.jpg', 'Hermann Hesse là một nhà thơ, nhà văn và họa sĩ người Đức. Năm 1946 ông được tặng Giải Goethe và Giải Nobel Văn học.'),
('TG010', 'Eric Ries', '1978-09-22', 'https://upload.wikimedia.org/wikipedia/commons/5/55/Eric_Ries2.jpg', 'Eric Ries là một doanh nhân người Mỹ, blogger và tác giả của The Lean Startup, một cuốn sách về phong trào khởi nghiệp tinh gọn. Ông cũng là tác giả của The Startup Way, một cuốn sách về quản lý doanh nhân hiện đại.'),
('TG011', 'Nguyễn Duy Cần', '1907-08-15', 'https://upload.wikimedia.org/wikipedia/vi/8/8c/Nguyen_Duy_Can.jpg', 'Nguyễn Duy Cần (1907-1998), hiệu Thu Giang, là một học giả, nhà văn, nhà biên khảo và trước tác kỳ cựu vào bậc nhất Việt Nam giữa thế kỷ 20.'),
('TG012', 'F. Scott Fitzgerald', '1896-09-24', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTG6UnW0FcDrbG6vrZarVez4Yo_EtWrz6bRhg&shttps://upload.wikimedia.org/wikipedia/commons/5/5c/F_Scott_Fitzgerald_1921.jpg', 'Francis Scott Key Fitzgerald là một nhà văn nổi tiếng người Mĩ. Những tác phẩm của ông được biết đến rộng rãi bởi âm hưởng và tinh thần từ Thời đại Jazz, đây cũng là cụm từ được chính Fitzgerald phổ biến từ tập truyện ngắn Truyện kể Thời đại Jazz (Tales of Jazz Age). Ông cũng được biết tới là một trong những cây bút nổi bật của Thế hệ đã mất với tác phẩm nổi tiếng nhất là Gatsby vĩ đại (The Great Gatsby).');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `the_loai`
--

CREATE TABLE `the_loai` (
  `MaTheLoai` varchar(10) NOT NULL,
  `TenTheLoai` varchar(100) NOT NULL,
  `MoTa` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `the_loai`
--

INSERT INTO `the_loai` (`MaTheLoai`, `TenTheLoai`, `MoTa`) VALUES
('TL001', 'Văn học hiện đại', 'Hư cấu'),
('TL002', 'Văn học kinh điển', 'Hư cấu'),
('TL003', 'Văn học thiếu nhi', 'Hư cấu'),
('TL004', 'Lãng mạn', 'Hư cấu'),
('TL005', 'Trinh thám - Kinh dị', 'Hư cấu'),
('TL006', 'Kỳ ảo', 'Hư cấu'),
('TL007', 'Khoa học viễn tưởng', 'Hư cấu'),
('TL008', 'Phiêu lưu ly kỳ', 'Hư cấu'),
('TL009', 'Tản Văn', 'Hư cấu'),
('TL010', 'Truyện tranh', 'Hư cấu'),
('TL011', 'Tranh sách (picture book)', 'Hư cấu'),
('TL012', 'Triết học', 'Phi hư cấu'),
('TL013', 'Sử học', 'Phi hư cấu'),
('TL014', 'Khoa học', 'Phi hư cấu'),
('TL015', 'Kinh doanh', 'Phi hư cấu'),
('TL016', 'Kinh tế chính trị', 'Phi hư cấu'),
('TL017', 'Kỹ năng', 'Phi hư cấu'),
('TL018', 'Nghệ thuật', 'Phi hư cấu'),
('TL019', 'Tâm lý học', 'Phi hư cấu'),
('TL020', 'Hồi ký', 'Phi hư cấu'),
('TL021', 'Y học - Sức khỏe', 'Phi hư cấu'),
('TL022', 'Tâm linh - Tôn giáo', 'Phi hư cấu'),
('TL023', 'Kiến thức phổ thông', 'Phi hư cấu'),
('TL024', 'Phong cách sống', 'Phi hư cấu'),
('TL025', 'Thơ - kịch', 'Hư cấu');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chi_tiet_hoa_don`
--
ALTER TABLE `chi_tiet_hoa_don`
  ADD PRIMARY KEY (`MaHD`,`MaSach`),
  ADD KEY `MaSach` (`MaSach`);

--
-- Chỉ mục cho bảng `danh_gia_binh_luan`
--
ALTER TABLE `danh_gia_binh_luan`
  ADD PRIMARY KEY (`MaDG`),
  ADD KEY `MaSach` (`MaSach`),
  ADD KEY `MaKH` (`MaKH`);

--
-- Chỉ mục cho bảng `dich_gia`
--
ALTER TABLE `dich_gia`
  ADD PRIMARY KEY (`MaDichGia`);

--
-- Chỉ mục cho bảng `giao_dich_thanh_toan`
--
ALTER TABLE `giao_dich_thanh_toan`
  ADD PRIMARY KEY (`MaGiaoDich`),
  ADD KEY `MaHD` (`MaHD`);

--
-- Chỉ mục cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD PRIMARY KEY (`MaHD`),
  ADD KEY `MaKH` (`MaKH`),
  ADD KEY `MaNhanVien` (`MaNhanVien`);

--
-- Chỉ mục cho bảng `khach_hang`
--
ALTER TABLE `khach_hang`
  ADD PRIMARY KEY (`MaKH`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Chỉ mục cho bảng `nhan_vien`
--
ALTER TABLE `nhan_vien`
  ADD PRIMARY KEY (`MaNhanVien`),
  ADD UNIQUE KEY `Email` (`Email`);

--
-- Chỉ mục cho bảng `nha_xuat_ban`
--
ALTER TABLE `nha_xuat_ban`
  ADD PRIMARY KEY (`MaNXB`);

--
-- Chỉ mục cho bảng `sach`
--
ALTER TABLE `sach`
  ADD PRIMARY KEY (`MaSach`),
  ADD KEY `MaTacGia` (`MaTacGia`),
  ADD KEY `MaTheLoai` (`MaTheLoai`),
  ADD KEY `MaNXB` (`MaNXB`);

--
-- Chỉ mục cho bảng `sach_dichgia`
--
ALTER TABLE `sach_dichgia`
  ADD PRIMARY KEY (`MaSach`,`MaDichGia`),
  ADD KEY `MaDichGia` (`MaDichGia`);

--
-- Chỉ mục cho bảng `tac_gia`
--
ALTER TABLE `tac_gia`
  ADD PRIMARY KEY (`MaTacGia`);

--
-- Chỉ mục cho bảng `the_loai`
--
ALTER TABLE `the_loai`
  ADD PRIMARY KEY (`MaTheLoai`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `danh_gia_binh_luan`
--
ALTER TABLE `danh_gia_binh_luan`
  MODIFY `MaDG` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `chi_tiet_hoa_don`
--
ALTER TABLE `chi_tiet_hoa_don`
  ADD CONSTRAINT `chi_tiet_hoa_don_ibfk_1` FOREIGN KEY (`MaHD`) REFERENCES `hoa_don` (`MaHD`),
  ADD CONSTRAINT `chi_tiet_hoa_don_ibfk_2` FOREIGN KEY (`MaSach`) REFERENCES `sach` (`MaSach`);

--
-- Các ràng buộc cho bảng `danh_gia_binh_luan`
--
ALTER TABLE `danh_gia_binh_luan`
  ADD CONSTRAINT `danh_gia_binh_luan_ibfk_1` FOREIGN KEY (`MaSach`) REFERENCES `sach` (`MaSach`),
  ADD CONSTRAINT `danh_gia_binh_luan_ibfk_2` FOREIGN KEY (`MaKH`) REFERENCES `khach_hang` (`MaKH`);

--
-- Các ràng buộc cho bảng `giao_dich_thanh_toan`
--
ALTER TABLE `giao_dich_thanh_toan`
  ADD CONSTRAINT `giao_dich_thanh_toan_ibfk_1` FOREIGN KEY (`MaHD`) REFERENCES `hoa_don` (`MaHD`);

--
-- Các ràng buộc cho bảng `hoa_don`
--
ALTER TABLE `hoa_don`
  ADD CONSTRAINT `hoa_don_ibfk_1` FOREIGN KEY (`MaKH`) REFERENCES `khach_hang` (`MaKH`),
  ADD CONSTRAINT `hoa_don_ibfk_2` FOREIGN KEY (`MaNhanVien`) REFERENCES `nhan_vien` (`MaNhanVien`);

--
-- Các ràng buộc cho bảng `sach`
--
ALTER TABLE `sach`
  ADD CONSTRAINT `sach_ibfk_1` FOREIGN KEY (`MaTacGia`) REFERENCES `tac_gia` (`MaTacGia`),
  ADD CONSTRAINT `sach_ibfk_2` FOREIGN KEY (`MaTheLoai`) REFERENCES `the_loai` (`MaTheLoai`),
  ADD CONSTRAINT `sach_ibfk_3` FOREIGN KEY (`MaNXB`) REFERENCES `nha_xuat_ban` (`MaNXB`);

--
-- Các ràng buộc cho bảng `sach_dichgia`
--
ALTER TABLE `sach_dichgia`
  ADD CONSTRAINT `sach_dichgia_ibfk_1` FOREIGN KEY (`MaSach`) REFERENCES `sach` (`MaSach`),
  ADD CONSTRAINT `sach_dichgia_ibfk_2` FOREIGN KEY (`MaDichGia`) REFERENCES `dich_gia` (`MaDichGia`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
