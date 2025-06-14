## Các bước để run
1. Tải XAMPP
2. Vào folder htdocs và tạo folder mới tên là "website"
3. Clone cả cái git này bỏ vô
4. Bật XAMPP enable Apache và MySQL rồi vô localhost/website trên trình duyệt (chrome, edge,...)
5. Vào phpmyadmin để mở giao diện database -> Bấm Import và nhét file db.sql vào.

## Làm Thêm phần này nếu muốn real-time

1. Tải Node.js
2. Tạo 1 folder mới đặt đại tên (websocket hay gì cx đc)
3.  Mở terminal rồi chạy npm init -> npm install ws (hay websocket cx đc)
4.  Bỏ cái file server.js ở trên github vô folder đó -> vô terminal chạy Node server.js
5.  Lúc chạy web cứ để terminal ở phía sau, ctrl + F5 để load lại hết sạch trang

### Note
- Ko có push ảnh lên nên là sẽ ko có ảnh nào hết
- Mới vừa update thêm cơ chế xóa real-time giữa waiter-kitchen nên khả năng cao giờ hủy đơn bên waiter sẽ bị lỗi nếu chưa tải lib (Muốn tải thì tải php composer, kéo đường vào xampp, xong vào terminal composer require textalk/websocket)