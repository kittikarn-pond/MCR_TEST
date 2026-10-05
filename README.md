# User Management System

ระบบจัดการข้อมูลผู้ใช้งาน (แบบทดสอบ PHP Laravel Developer)

- **Frontend:** PHP Blade (Bootstrap 5 ผ่าน CDN)
- **Backend:** PHP Laravel 13
- **Database:** MySQL

## ฟีเจอร์

| หน้า | URL | รายละเอียด |
|---|---|---|
| Login | `/login` | กรอก Email/Password ถูก → Dashboard, ผิด → แสดงข้อความแจ้งเตือน |
| Dashboard | `/dashboard` | เมนู Navigation: ลิงก์ไปหน้าจัดการผู้ใช้งาน + ปุ่มออกจากระบบ |
| จัดการผู้ใช้งาน | `/users` | CRUD: แสดงรายการ (ตาราง), เพิ่ม, แก้ไข, ลบ (ชื่อ, Email, รหัสผ่าน, วันที่สร้าง) |
| แก้ไขโปรไฟล์ | `/profile/edit` | ผู้ใช้ที่ล็อกอินแก้ไขชื่อ, Email, รหัสผ่านของตนเอง |

- ทุกหน้าที่ไม่ใช่ `/login` ถูกป้องกันด้วย `auth` Middleware (ยังไม่ล็อกอิน → redirect ไป `/login`)
- รหัสผ่านถูก hash อัตโนมัติ (`hashed` cast ใน `User` model); เว้นรหัสผ่านว่างตอนแก้ไข = ไม่เปลี่ยนรหัสผ่าน
- เมื่อเปลี่ยนรหัสผ่านของผู้ใช้ (ทั้งแก้เองที่ `/profile/edit` หรือแก้ผ่าน `/users`) ทุก session เก่าของผู้ใช้นั้นจะถูกออกจากระบบ (`AuthenticateSession` middleware); session ที่ใช้เปลี่ยนรหัสผ่านยังล็อกอินอยู่
- ไม่สามารถลบบัญชีที่กำลังล็อกอินอยู่ได้
- การลบผู้ใช้งานเป็น **Soft Delete**: ไม่ลบแถวออกจากฐานข้อมูล แต่บันทึกเวลาลงคอลัมน์ `deleted_at` และผู้ใช้นั้นจะไม่แสดงในหน้า `/users` และล็อกอินไม่ได้ (Email ของผู้ใช้ที่ถูกลบจะยังถูกจองไว้ ใช้สมัครซ้ำไม่ได้)

## ความต้องการของระบบ

- PHP >= 8.3 (extensions: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`)
- Composer
- MySQL / MariaDB (เช่น XAMPP)

## วิธี Setup

1. **ติดตั้ง dependencies**

   ```bash
   composer install
   ```

2. **ตั้งค่า `.env`** — ไฟล์ `.env` **แนบมาใน repo นี้แล้ว** (ตั้งค่า MySQL ของ XAMPP ไว้ให้) จึงไม่ต้องคัดลอกหรือสร้าง key ใหม่ ตรวจเฉพาะ `DB_PORT` / `DB_USERNAME` / `DB_PASSWORD` ให้ตรงกับ MySQL ในเครื่องของคุณ

   > ถ้าไม่มีไฟล์ `.env` (เช่น ถูกลบไป) ให้สร้างใหม่จากตัวอย่าง:
   >
   > ```bash
   > cp .env.example .env
   > php artisan key:generate
   > ```

   ค่า Database ใน `.env` ที่แนบมา (ค่าเริ่มต้นของ XAMPP — **XAMPP ในเครื่องผู้พัฒนาใช้พอร์ต 3307** ปรับ `DB_PORT` ให้ตรงกับเครื่องของคุณ เช่น `3306`):

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_DATABASE=user_management
   DB_USERNAME=root
   DB_PASSWORD=
   ```

3. **สร้างฐานข้อมูล** (เปิด MySQL ก่อน)

   ```sql
   CREATE DATABASE user_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

   หรือผ่าน phpMyAdmin ที่ `http://localhost/phpmyadmin`

4. **สร้างตารางและข้อมูลเริ่มต้น**

   ```bash
   php artisan migrate --seed
   ```

5. **รันเซิร์ฟเวอร์**

   ```bash
   php artisan serve
   ```

   เปิด <http://localhost:8000>

## บัญชีสำหรับทดสอบ

| Email | Password |
|---|---|
| `admin@example.com` | `password123` |

> หน้าเว็บโหลด Bootstrap จาก CDN จึงต้องต่ออินเทอร์เน็ตเพื่อให้แสดงผลสวยงาม (ฟังก์ชันทำงานได้ปกติแม้ออฟไลน์)

## การทดสอบอัตโนมัติ

ใช้ฐานข้อมูลแยกชื่อ `user_management_test` (สร้างไว้ก่อน):

```sql
CREATE DATABASE user_management_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan test
```

## โครงสร้างโค้ดหลัก

```
app/Http/Controllers/Auth/LoginController.php   # login / logout
app/Http/Controllers/DashboardController.php
app/Http/Controllers/UserController.php         # CRUD /users
app/Http/Controllers/ProfileController.php      # /profile/edit
app/Http/Requests/UserRequest.php               # validation ร่วมของ create / update / profile
app/Models/User.php
resources/views/                                # Blade (layouts, auth, users, profile)
routes/web.php
tests/Feature/                                  # AuthTest, UserCrudTest, ProfileTest
```
