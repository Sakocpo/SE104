
<p align="center">
  <a href="https://www.uit.edu.vn/" title="Trường Đại học Công nghệ Thông tin" style="border: none;">
    <img src="https://i.imgur.com/WmMnSRt.png" alt="Trường Đại học Công nghệ Thông tin | University of Information Technology">
  </a>
</p>

<h1 align="center"><b>NHẬP MÔN CÔNG NGHỆ PHẦN MỀM</b></h1>

# Thành viên nhóm
| STT    | MSSV          | Họ và Tên              |Chức Vụ    |                      Github                             | Email                   |
| ------ |:-------------:| ----------------------:|----------:|--------------------------------------------------------:|-------------------------:
| 1      | 23520418      | Nguyễn Ngọc Hải        |Nhóm trưởng|            https://github.com/Sakocpo                   |23520418@gm.uit.edu.vn   |
| 2      | 23520352      | Nguyễn Lê Tùng Dương   |Thành viên |            https://github.com/DuongDangHocCode          |23520352@gm.uit.edu.vn   |
| 3      | 23520110      | Lê Thiên Bảo           |Thành viên |            https://github.com/baotodale06               |23520110@gm.uit.edu.vn   |

# GIỚI THIỆU MÔN HỌC
* **Tên môn học:** Nhập môn công nghệ phần mềm
* **Mã môn học:** SE104
* **Mã lớp:** SE104.P28
* **Giảng viên**: Đỗ Văn Tiến

# ĐỒ ÁN CUỐI KÌ
* **Đề tài:** Website quản lý quán cà phê.


## Công nghệ sử dụng
- Xampp + MySQL – Môi trường máy chủ và hệ quản trị cơ sở dữ liệu để lưu trữ thông tin.
- Composer – Trình quản lý thư viện PHP để tự động tải và cập nhật các dependency.
- Node.js - Xử lý API, back-end, hỗ trợ giao tiếp real-time.


# 📦 Cài đặt

## ✅ Yêu cầu môi trường

- [XAMPP](https://www.apachefriends.org/index.html) (PHP + MySQL)
- [Node.js](https://nodejs.org/) v19 trở lên
- Trình duyệt web (Chrome, Firefox,...)

---

## ⚙️ Cài đặt và chạy hệ thống


### 1. Khởi chạy Apache và MySQL

- Mở XAMPP → Start Apache + MySQL
- Truy cập hệ thống tại: [http://localhost/SE104-master](http://localhost/SE104-master)

---

### 2. Khởi tạo cơ sở dữ liệu

- Mở `phpMyAdmin`: http://localhost/phpmyadmin
- Tạo một database mới, tên là "users_db"
- Import file `db.sql` có sẵn trong thư mục dự án

---

### 3. Cài đặt và chạy WebSocket Server

```bash
npm install
npm start
node server.js
composer require textalk/websocket
```

> WebSocket server dùng để xử lý các chức năng thời gian thực (real-time) như trạng thái món ăn, thông báo giữa bếp và phục vụ.

