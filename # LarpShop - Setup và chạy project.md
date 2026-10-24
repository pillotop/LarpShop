# LarpShop - Setup và chạy project

## 1. Clone project

Từ thư mục home:

```bash
cd ~
git clone "https://github.com/pillotop/LarpShop.git"
cd LarpShop
```

Kiểm tra project:

```bash
ls
```

---

## 2. Switch sang branch UC07

Xem các branch:

```bash
git branch -a
```

Switch sang branch:

```bash
git switch -c feature/uc07-vendor-storefront-and-products origin/feature/uc07-vendor-storefront-and-products
```

Kiểm tra branch hiện tại:

```bash
git branch
```

Kết quả mong muốn:

```text
* feature/uc07-vendor-storefront-and-products
  main
```

---

## 3. Cài PHP dependencies

Project Laravel cần thư mục `vendor/`.

Chạy:

```bash
composer install
```

Sau khi hoàn tất có thể kiểm tra:

```bash
ls vendor/autoload.php
```

Nếu file tồn tại thì Composer đã cài thành công.

---

## 4. Cài Node.js dependencies

Cài các package frontend:

```bash
npm install
```

---

## 5. Tạo và cấu hình `.env`

Tạo file:

```bash
nano .env
```

Cấu hình database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larpshop
DB_USERNAME=larpshop
DB_PASSWORD=<password>
DB_ROOT_PASSWORD=<root-password>
```

Project đã có `APP_KEY` thì không cần chạy:

```bash
php artisan key:generate
```

Lưu ý: `DB_HOST=127.0.0.1` trong `.env` là bình thường.

Khi Laravel chạy bên trong Docker Compose, `compose.yaml` sẽ override DB host thành:

```text
DB_HOST=db
```

Trong đó `db` là tên service MySQL trong Docker Compose.

---

## 6. Lỗi `could not find driver` nếu gặp

Nếu chạy migration trực tiếp bằng PHP và gặp:

```text
could not find driver
```

thì PHP chưa có MySQL PDO driver.

Kiểm tra PHP:

```bash
php -v
```

Cài MySQL extension:

```bash
sudo apt update
sudo apt install php-mysql
```

Kiểm tra:

```bash
php -m | grep -Ei 'pdo_mysql|mysqli|mysqlnd'
```

Có các module như:

```text
mysqli
mysqlnd
pdo_mysql
```

là OK.

Lưu ý: LarpShop sử dụng MySQL thông qua Docker Compose nên không cần cài MySQL Server trực tiếp trên Ubuntu.

---

## 7. Kiểm tra Docker Compose

Project có sẵn:

```text
Dockerfile
compose.yaml
```

Trong `compose.yaml` có hai service chính:

```text
app
db
```

Service `db` sử dụng:

```text
mysql:8.4
```

Service `app` sử dụng:

```text
DB_HOST=db
```

Do đó MySQL sẽ chạy trong Docker container.

---

## 8. Khởi động LarpShop bằng Docker

Từ thư mục project:

```bash
cd ~/LarpShop
docker compose up -d
```

Docker sẽ:

- Build image `larp-shop`
- Tạo network
- Tạo MySQL container
- Tạo database `larpshop`
- Khởi động Laravel app

Kiểm tra:

```bash
docker compose ps
```

Database cần có trạng thái:

```text
Healthy
```

App cần có trạng thái:

```text
Up
```

---

## 9. Tạo database và chạy migration

Không chạy:

```bash
php artisan migrate:fresh --seed
```

trực tiếp trên host nếu MySQL đang chạy trong Docker.

Thay vào đó chạy Artisan bên trong container `app`:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Nếu thành công sẽ thấy:

```text
INFO  Preparing database.

INFO  Running migrations.

...

INFO  Seeding database.
```

Lệnh này sẽ:

1. Xóa database hiện tại.
2. Tạo lại các bảng.
3. Chạy toàn bộ migration.
4. Seed dữ liệu mẫu.

### CẢNH BÁO

Không chạy `migrate:fresh` bừa nếu database đã có dữ liệu cần giữ.

```bash
docker compose exec app php artisan migrate:fresh --seed
```

sẽ reset database.

---

## 10. Truy cập website

Sau khi container chạy thành công, mở trình duyệt:

```text
http://localhost:8000
```

Docker Compose map:

```text
8000:8000
```

nên Laravel app có thể truy cập tại:

```text
http://localhost:8000
```

---

# Các lệnh sử dụng hằng ngày

## Khởi động project

Mỗi lần mở máy:

```bash
cd ~/LarpShop
docker compose up -d
```

Sau đó truy cập:

```text
http://localhost:8000
```

---

## Kiểm tra container

```bash
docker compose ps
```

---

## Xem log app

```bash
docker compose logs -f app
```

Thoát log:

```text
Ctrl + C
```

Việc thoát log không dừng container.

---

## Xem log database

```bash
docker compose logs -f db
```

---

## Dừng project

```bash
docker compose down
```

Lệnh này dừng và xóa các container/network của Compose nhưng volume database vẫn được giữ.

---

## Chạy lại project

```bash
docker compose up -d
```

Database cũ vẫn được sử dụng.

---

# Git workflow khi code

Kiểm tra branch:

```bash
git branch
```

Branch làm việc:

```text
feature/uc07-vendor-storefront-and-products
```

Kiểm tra thay đổi:

```bash
git status
```

Add code:

```bash
git add .
```

Commit:

```bash
git commit -m "your commit message"
```

Push:

```bash
git push
```

---

# Nếu cần cập nhật code từ remote

Kiểm tra branch hiện tại:

```bash
git branch
```

Fetch thay đổi:

```bash
git fetch origin
```

Nếu muốn cập nhật branch UC07:

```bash
git pull origin feature/uc07-vendor-storefront-and-products
```

---

# Nếu cần reset database về trạng thái ban đầu

```bash
docker compose exec app php artisan migrate:fresh --seed
```

CẢNH BÁO: lệnh trên xóa toàn bộ dữ liệu hiện tại trong database rồi tạo lại.

---

# Quy trình ngắn gọn

## Lần đầu setup

```bash
cd ~
git clone "https://github.com/pillotop/LarpShop.git"
cd LarpShop

git switch -c feature/uc07-vendor-storefront-and-products origin/feature/uc07-vendor-storefront-and-products

composer install
npm install

nano .env

docker compose up -d

docker compose exec app php artisan migrate:fresh --seed
```

Sau đó mở:

```text
http://localhost:8000
```

---

## Những lần sau

Chỉ cần:

```bash
cd ~/LarpShop
docker compose up -d
```

Sau đó mở:

```text
http://localhost:8000
```

---

## Tắt project

```bash
docker compose down
```

## Chạy lại

```bash
docker compose up -d
```

## Kiểm tra trạng thái

```bash
docker compose ps
```

## Xem log

```bash
docker compose logs -f app
```# LarpShop - Setup và chạy project

## 1. Clone project

Từ thư mục home:

```bash
cd ~
git clone "https://github.com/pillotop/LarpShop.git"
cd LarpShop
```

Kiểm tra project:

```bash
ls
```

---

## 2. Switch sang branch UC07

Xem các branch:

```bash
git branch -a
```

Switch sang branch:

```bash
git switch -c feature/uc07-vendor-storefront-and-products origin/feature/uc07-vendor-storefront-and-products
```

Kiểm tra branch hiện tại:

```bash
git branch
```

Kết quả mong muốn:

```text
* feature/uc07-vendor-storefront-and-products
  main
```

---

## 3. Cài PHP dependencies

Project Laravel cần thư mục `vendor/`.

Chạy:

```bash
composer install
```

Sau khi hoàn tất có thể kiểm tra:

```bash
ls vendor/autoload.php
```

Nếu file tồn tại thì Composer đã cài thành công.

---

## 4. Cài Node.js dependencies

Cài các package frontend:

```bash
npm install
```

---

## 5. Tạo và cấu hình `.env` lấy của Việt 

Tạo file:

```bash
nano .env
```

Cấu hình database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larpshop
DB_USERNAME=larpshop
DB_PASSWORD=<password>
DB_ROOT_PASSWORD=<root-password>
```

Project đã có `APP_KEY` thì không cần chạy:

```bash
php artisan key:generate
```

Lưu ý: `DB_HOST=127.0.0.1` trong `.env` là bình thường.

Khi Laravel chạy bên trong Docker Compose, `compose.yaml` sẽ override DB host thành:

```text
DB_HOST=db
```

Trong đó `db` là tên service MySQL trong Docker Compose.

---

## 6. Lỗi `could not find driver` nếu gặp

Nếu chạy migration trực tiếp bằng PHP và gặp:

```text
could not find driver
```

thì PHP chưa có MySQL PDO driver.

Kiểm tra PHP:

```bash
php -v
```

Cài MySQL extension:

```bash
sudo apt update
sudo apt install php-mysql
```

Kiểm tra:

```bash
php -m | grep -Ei 'pdo_mysql|mysqli|mysqlnd'
```

Có các module như:

```text
mysqli
mysqlnd
pdo_mysql
```

là OK.

Lưu ý: LarpShop sử dụng MySQL thông qua Docker Compose nên không cần cài MySQL Server trực tiếp trên Ubuntu.

---

## 7. Kiểm tra Docker Compose

Project có sẵn:

```text
Dockerfile
compose.yaml
```

Trong `compose.yaml` có hai service chính:

```text
app
db
```

Service `db` sử dụng:

```text
mysql:8.4
```

Service `app` sử dụng:

```text
DB_HOST=db
```

Do đó MySQL sẽ chạy trong Docker container.

---

## 8. Khởi động LarpShop bằng Docker

Từ thư mục project:

```bash
cd ~/LarpShop
docker compose up -d
```

Docker sẽ:

- Build image `larp-shop`
- Tạo network
- Tạo MySQL container
- Tạo database `larpshop`
- Khởi động Laravel app

Kiểm tra:

```bash
docker compose ps
```

Database cần có trạng thái:

```text
Healthy
```

App cần có trạng thái:

```text
Up
```

---

## 9. Tạo database và chạy migration

Không chạy:

```bash
php artisan migrate:fresh --seed
```

trực tiếp trên host nếu MySQL đang chạy trong Docker.

Thay vào đó chạy Artisan bên trong container `app`:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Nếu thành công sẽ thấy:

```text
INFO  Preparing database.

INFO  Running migrations.

...

INFO  Seeding database.
```

Lệnh này sẽ:

1. Xóa database hiện tại.
2. Tạo lại các bảng.
3. Chạy toàn bộ migration.
4. Seed dữ liệu mẫu.

### CẢNH BÁO

Không chạy `migrate:fresh` bừa nếu database đã có dữ liệu cần giữ.

```bash
docker compose exec app php artisan migrate:fresh --seed
```

sẽ reset database.

---

## 10. Truy cập website

Sau khi container chạy thành công, mở trình duyệt:

```text
http://localhost:8000
```

Docker Compose map:

```text
8000:8000
```

nên Laravel app có thể truy cập tại:

```text
http://localhost:8000
```

---

# Các lệnh sử dụng hằng ngày

## Khởi động project

Mỗi lần mở máy:

```bash
cd ~/LarpShop
docker compose up -d
```

Sau đó truy cập:

```text
http://localhost:8000
```

---

## Kiểm tra container

```bash
docker compose ps
```

---

## Xem log app

```bash
docker compose logs -f app
```

Thoát log:

```text
Ctrl + C
```

Việc thoát log không dừng container.

---

## Xem log database

```bash
docker compose logs -f db
```

---

## Dừng project

```bash
docker compose down
```

Lệnh này dừng và xóa các container/network của Compose nhưng volume database vẫn được giữ.

---

## Chạy lại project

```bash
docker compose up -d
```

Database cũ vẫn được sử dụng.

---

# Git workflow khi code

Kiểm tra branch:

```bash
git branch
```

Branch làm việc:

```text
feature/uc07-vendor-storefront-and-products
```

Kiểm tra thay đổi:

```bash
git status
```

Add code:

```bash
git add .
```

Commit:

```bash
git commit -m "your commit message"
```

Push:

```bash
git push
```

---

# Nếu cần cập nhật code từ remote

Kiểm tra branch hiện tại:

```bash
git branch
```

Fetch thay đổi:

```bash
git fetch origin
```

Nếu muốn cập nhật branch UC07:

```bash
git pull origin feature/uc07-vendor-storefront-and-products
```

---

# Nếu cần reset database về trạng thái ban đầu

```bash
docker compose exec app php artisan migrate:fresh --seed
```

CẢNH BÁO: lệnh trên xóa toàn bộ dữ liệu hiện tại trong database rồi tạo lại.

---

# Quy trình ngắn gọn

## Lần đầu setup

```bash
cd ~
git clone "https://github.com/pillotop/LarpShop.git"
cd LarpShop

git switch -c feature/uc07-vendor-storefront-and-products origin/feature/uc07-vendor-storefront-and-products

composer install
npm install

nano .env

docker compose up -d

docker compose exec app php artisan migrate:fresh --seed
```

Sau đó mở:

```text
http://localhost:8000
```

---

## Những lần sau

Chỉ cần:

```bash
cd ~/LarpShop
docker compose up -d
```

Sau đó mở:

```text
http://localhost:8000
```

---

## Tắt project

```bash
docker compose down
```

## Chạy lại

```bash
docker compose up -d
```

## Kiểm tra trạng thái

```bash
docker compose ps
```

## Xem log

```bash
docker compose logs -f app
```# LarpShop - Setup và chạy project

## 1. Clone project

Từ thư mục home:

```bash
cd ~
git clone "https://github.com/pillotop/LarpShop.git"
cd LarpShop
```

Kiểm tra project:

```bash
ls
```

---

## 2. Switch sang branch UC07

Xem các branch:

```bash
git branch -a
```

Switch sang branch:

```bash
git switch -c feature/uc07-vendor-storefront-and-products origin/feature/uc07-vendor-storefront-and-products
```

Kiểm tra branch hiện tại:

```bash
git branch
```

Kết quả mong muốn:

```text
* feature/uc07-vendor-storefront-and-products
  main
```

---

## 3. Cài PHP dependencies

Project Laravel cần thư mục `vendor/`.

Chạy:

```bash
composer install
```

Sau khi hoàn tất có thể kiểm tra:

```bash
ls vendor/autoload.php
```

Nếu file tồn tại thì Composer đã cài thành công.

---

## 4. Cài Node.js dependencies

Cài các package frontend:

```bash
npm install
```

---

## 5. Tạo và cấu hình `.env`

Tạo file:

```bash
nano .env
```

Cấu hình database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=larpshop
DB_USERNAME=larpshop
DB_PASSWORD=<password>
DB_ROOT_PASSWORD=<root-password>
```

Project đã có `APP_KEY` thì không cần chạy:

```bash
php artisan key:generate
```

Lưu ý: `DB_HOST=127.0.0.1` trong `.env` là bình thường.

Khi Laravel chạy bên trong Docker Compose, `compose.yaml` sẽ override DB host thành:

```text
DB_HOST=db
```

Trong đó `db` là tên service MySQL trong Docker Compose.

---

## 6. Lỗi `could not find driver` nếu gặp

Nếu chạy migration trực tiếp bằng PHP và gặp:

```text
could not find driver
```

thì PHP chưa có MySQL PDO driver.

Kiểm tra PHP:

```bash
php -v
```

Cài MySQL extension:

```bash
sudo apt update
sudo apt install php-mysql
```

Kiểm tra:

```bash
php -m | grep -Ei 'pdo_mysql|mysqli|mysqlnd'
```

Có các module như:

```text
mysqli
mysqlnd
pdo_mysql
```

là OK.

Lưu ý: LarpShop sử dụng MySQL thông qua Docker Compose nên không cần cài MySQL Server trực tiếp trên Ubuntu.

---

## 7. Kiểm tra Docker Compose

Project có sẵn:

```text
Dockerfile
compose.yaml
```

Trong `compose.yaml` có hai service chính:

```text
app
db
```

Service `db` sử dụng:

```text
mysql:8.4
```

Service `app` sử dụng:

```text
DB_HOST=db
```

Do đó MySQL sẽ chạy trong Docker container.

---

## 8. Khởi động LarpShop bằng Docker

Từ thư mục project:

```bash
cd ~/LarpShop
docker compose up -d
```

Docker sẽ:

- Build image `larp-shop`
- Tạo network
- Tạo MySQL container
- Tạo database `larpshop`
- Khởi động Laravel app

Kiểm tra:

```bash
docker compose ps
```

Database cần có trạng thái:

```text
Healthy
```

App cần có trạng thái:

```text
Up
```

---

## 9. Tạo database và chạy migration

Không chạy:

```bash
php artisan migrate:fresh --seed
```

trực tiếp trên host nếu MySQL đang chạy trong Docker.

Thay vào đó chạy Artisan bên trong container `app`:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Nếu thành công sẽ thấy:

```text
INFO  Preparing database.

INFO  Running migrations.

...

INFO  Seeding database.
```

Lệnh này sẽ:

1. Xóa database hiện tại.
2. Tạo lại các bảng.
3. Chạy toàn bộ migration.
4. Seed dữ liệu mẫu.

### CẢNH BÁO

Không chạy `migrate:fresh` bừa nếu database đã có dữ liệu cần giữ.

```bash
docker compose exec app php artisan migrate:fresh --seed
```

sẽ reset database.

---

## 10. Truy cập website

Sau khi container chạy thành công, mở trình duyệt:

```text
http://localhost:8000
```

Docker Compose map:

```text
8000:8000
```

nên Laravel app có thể truy cập tại:

```text
http://localhost:8000
```

---

# Các lệnh sử dụng hằng ngày

## Khởi động project

Mỗi lần mở máy:

```bash
cd ~/LarpShop
docker compose up -d
```

Sau đó truy cập:

```text
http://localhost:8000
```

---

## Kiểm tra container

```bash
docker compose ps
```

---

## Xem log app

```bash
docker compose logs -f app
```

Thoát log:

```text
Ctrl + C
```

Việc thoát log không dừng container.

---

## Xem log database

```bash
docker compose logs -f db
```

---

## Dừng project

```bash
docker compose down
```

Lệnh này dừng và xóa các container/network của Compose nhưng volume database vẫn được giữ.

---

## Chạy lại project

```bash
docker compose up -d
```

Database cũ vẫn được sử dụng.

---

# Git workflow khi code

Kiểm tra branch:

```bash
git branch
```

Branch làm việc:

```text
feature/uc07-vendor-storefront-and-products
```

Kiểm tra thay đổi:

```bash
git status
```

Add code:

```bash
git add .
```

Commit:

```bash
git commit -m "your commit message"
```

Push:

```bash
git push
```

---

# Nếu cần cập nhật code từ remote

Kiểm tra branch hiện tại:

```bash
git branch
```

Fetch thay đổi:

```bash
git fetch origin
```

Nếu muốn cập nhật branch UC07:

```bash
git pull origin feature/uc07-vendor-storefront-and-products
```

---

# Nếu cần reset database về trạng thái ban đầu

```bash
docker compose exec app php artisan migrate:fresh --seed
```

CẢNH BÁO: lệnh trên xóa toàn bộ dữ liệu hiện tại trong database rồi tạo lại.

---

# Quy trình ngắn gọn

## Lần đầu setup

```bash
cd ~
git clone "https://github.com/pillotop/LarpShop.git"
cd LarpShop

git switch -c feature/uc07-vendor-storefront-and-products origin/feature/uc07-vendor-storefront-and-products

composer install
npm install

nano .env

docker compose up -d

docker compose exec app php artisan migrate:fresh --seed
```

Sau đó mở:

```text
http://localhost:8000
```

---

## Những lần sau

Chỉ cần:

```bash
cd ~/LarpShop
docker compose up -d
```

Sau đó mở:

```text
http://localhost:8000
```

---

## Tắt project

```bash
docker compose down
```

## Chạy lại

```bash
docker compose up -d
```

## Kiểm tra trạng thái

```bash
docker compose ps
```

## Xem log

```bash
docker compose logs -f app
```