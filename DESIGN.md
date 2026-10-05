# User Management System — โครงสร้างระบบ (Laravel)

อ้างอิงจาก "โจทย์ข้อสอบ — PHP Laravel Developer". ใช้ศัพท์ของ codebase-design: **module / interface / seam / depth / locality**

## 1. การตัดสินใจหลัก

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| Laravel | 11 หรือ 12 (PHP 8.3 มีในเครื่องแล้ว) | โจทย์ให้เลือกเวอร์ชันเอง |
| Database | **MySQL** (database `user_management`, charset `utf8mb4`) | ตามที่กำหนด — ตั้งค่าผ่าน `.env` (`DB_CONNECTION=mysql`) |
| Authentication | **Laravel Auth (guard `web`) + Blade เขียนเอง ไม่ใช้ Breeze** | Breeze ใช้ route `/profile` ไม่ตรงกับโจทย์ `/profile/edit` และมีไฟล์ที่ไม่ต้องใช้จำนวนมาก (shallow) |
| Frontend | **PHP Blade** + CSS ธรรมดา | ตามที่กำหนด — render ฝั่ง server ไม่ต้อง build ด้วย Node |
| Authorization | ผู้ใช้ที่ล็อกอินแล้วทุกคนจัดการ `/users` ได้ | โจทย์ไม่มี role — ไม่เพิ่มเอง |

## 2. Modules และ Interface

หลักคิด: ใช้ module ที่ Laravel ให้มาอยู่แล้วเป็น module ที่ลึก (Auth guard, `auth` middleware, Eloquent `hashed` cast, FormRequest) แล้วเขียนโค้ดบางๆ ครอบแค่เท่าที่โจทย์ต้องการ **ไม่สร้าง Repository / Service ครอบ Eloquent** (มี adapter เดียว = seam สมมติ — ไม่คุ้ม)

### 2.1 `Auth` (login/logout) — `LoginController`
- Interface: `GET /login`, `POST /login` (email, password), `POST /logout`
- ซ่อนไว้ข้างใน: `Auth::attempt`, session regenerate, throttle, redirect `intended` → `/dashboard`
- Error mode: credential ผิด → กลับหน้า login พร้อมข้อความแจ้งเตือน (เก็บ email เดิม)

### 2.2 `Route protection` — middleware
- `guest` กลุ่ม: `/login`
- `auth` กลุ่ม: `/dashboard`, `/users/*`, `/profile/edit`, `POST /logout`
- Invariant: ผู้ที่ยังไม่ล็อกอินเข้าหน้าอื่นไม่ได้ → redirect `/login` (กำหนดที่ `routes/web.php` จุดเดียว = locality)

### 2.3 `User` model — ที่เดียวที่รู้เรื่อง password
- ฟิลด์: `name`, `email` (unique), `password`, `created_at`
- `casts: ['password' => 'hashed']` → **hash ที่ model ที่เดียว** ผู้เรียกส่ง plain text มาได้เลย ไม่ต้อง `Hash::make` ซ้ำใน controller

### 2.4 `UserRequest` (FormRequest) — กฎ validation ร่วม
- ใช้ร่วมกันทั้ง **Users CRUD** และ **Profile edit** (ลึก: กฎเดียว ใช้ 2 ที่)
- กฎ: `name` required; `email` required|email|unique (ยกเว้น record ของตัวเอง); `password` required ตอนสร้าง, nullable|min:8|confirmed ตอนแก้ไข
- Invariant: แก้ไขแล้วเว้นรหัสผ่านว่าง = **ไม่เปลี่ยนรหัสผ่าน** (ซ่อนไว้ใน module นี้/controller helper ไม่ให้รั่วไป view)

### 2.5 `UserController` (resource) — `/users`
| Method | URI | หน้าที่ |
|---|---|---|
| GET | `/users` | ตารางผู้ใช้ทั้งหมด (ชื่อ, Email, วันที่สร้าง, ปุ่มแก้ไข/ลบ), paginate |
| GET | `/users/create` | ฟอร์มเพิ่ม |
| POST | `/users` | บันทึก |
| GET | `/users/{user}/edit` | ฟอร์มแก้ไข |
| PUT | `/users/{user}` | อัปเดต |
| DELETE | `/users/{user}` | ลบ (ฟอร์ม + confirm) |

- ไม่ต้องมี `show` → `Route::resource('users', ...)->except('show')`
- Invariant: **ห้ามลบตัวเองที่กำลังล็อกอิน** (กันระบบไม่มีผู้ใช้เหลือ/ถูกเตะออกเอง) → ตรวจที่ `destroy` จุดเดียว

### 2.6 `ProfileController` — `/profile/edit`
- `GET /profile/edit`, `PUT /profile` — แก้ได้เฉพาะ `Auth::user()` (ไม่รับ id จาก URL → แก้ของคนอื่นไม่ได้โดยโครงสร้าง)
- ใช้ `UserRequest` ตัวเดียวกับ 2.5

### 2.7 Views (Blade)
- `layouts/app.blade.php` — navbar: ลิงก์ "จัดการผู้ใช้งาน", (ลิงก์ "แก้ไขโปรไฟล์"), ปุ่ม **ออกจากระบบ** (POST + CSRF), flash message
- `users/_form.blade.php` — partial ฟอร์มเดียวใช้ทั้ง create/edit
- โจทย์ระบุเมนู Navigation อยู่ที่ Dashboard → เก็บไว้ใน layout เพื่อให้ทุกหน้าเห็นเหมือนกัน

## 3. โครงสร้างไฟล์

```
MCR_TEST/                        (laravel new → ที่นี่ หรือโฟลเดอร์ย่อย)
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/LoginController.php      # show, store, destroy
│   │   │   ├── DashboardController.php       # (หรือ route closure view)
│   │   │   ├── UserController.php            # index create store edit update destroy
│   │   │   └── ProfileController.php         # edit, update
│   │   └── Requests/UserRequest.php          # rules ร่วม สร้าง/แก้ไข/โปรไฟล์
│   └── Models/User.php                       # hashed cast
├── database/
│   ├── migrations/…create_users_table.php    # name, email unique, password, timestamps
│   ├── factories/UserFactory.php
│   └── seeders/DatabaseSeeder.php            # admin@example.com / password (ระบุใน README)
├── resources/views/
│   ├── layouts/app.blade.php
│   ├── auth/login.blade.php
│   ├── dashboard.blade.php
│   ├── users/{index,create,edit,_form}.blade.php
│   └── profile/edit.blade.php
├── routes/web.php
├── tests/Feature/
│   ├── AuthTest.php
│   ├── UserCrudTest.php
│   └── ProfileTest.php
├── .env                                       # แนบตามโจทย์ (มี DB_USERNAME/DB_PASSWORD ของเครื่อง dev — ใช้ค่า local เท่านั้น)
├── .env.example
└── README.md                                  # วิธี Setup
```

## 4. Routes (`routes/web.php`)

```php
Route::middleware('guest')->group(function () {
    Route::get('/login',  [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::resource('users', UserController::class)->except('show');

    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile',      [ProfileController::class, 'update'])->name('profile.update');
});
```

## 5. Test surface (Feature test ผ่าน HTTP = interface เดียวกับผู้ใช้)

ไม่ mock อะไร ใช้ MySQL database แยกสำหรับทดสอบ (`user_management_test` ตั้งใน `phpunit.xml`) + `RefreshDatabase` — ไม่ใช้ SQLite เพื่อให้พฤติกรรม unique index/collation ตรงกับของจริง

- **AuthTest**: login สำเร็จ → `/dashboard`; รหัสผิด → error + ยัง guest; guest เข้า `/dashboard` `/users` `/profile/edit` → redirect `/login`; logout
- **UserCrudTest**: list แสดงผู้ใช้; create (รหัสผ่านถูก hash); email ซ้ำ → error; update โดยเว้นรหัสผ่านว่าง = รหัสเดิม; delete; ลบตัวเองไม่ได้
- **ProfileTest**: แก้ชื่อ/email/รหัสผ่านของตัวเอง; ใช้ email ที่คนอื่นมีไม่ได้; email ตัวเองเดิมบันทึกซ้ำได้

## 6. ขั้นตอนทำงาน (ลำดับแนะนำ)

1. `composer create-project laravel/laravel .` → สร้าง DB `user_management` ใน MySQL → ตั้ง `.env` เป็น MySQL → `php artisan migrate`
2. Seeder + `User` model (`hashed` cast)
3. Login/Logout + middleware + `/dashboard` + layout navbar
4. `UserRequest` + `UserController` + views
5. `ProfileController` + view
6. Feature tests → `php artisan test`
7. README + `.env` (ต้องไม่ถูก `.gitignore` ถ้าจะ commit — หรือแนบไฟล์แยกตามโจทย์) → push GitHub → ส่ง link

## 7. Checklist ส่งงาน (ตามโจทย์)

- [ ] Login ถูก → Dashboard / ผิด → แจ้งเตือน
- [ ] Middleware กันหน้าอื่นเมื่อยังไม่ล็อกอิน
- [ ] Dashboard มีลิงก์ไปจัดการผู้ใช้ + ปุ่ม Logout
- [ ] `/users` CRUD ครบ 4 อย่าง (ชื่อ, Email, รหัสผ่าน, วันที่สร้าง)
- [ ] `/profile/edit` แก้ชื่อ/Email/รหัสผ่านของตนเอง
- [ ] GitHub repo + แนบ `.env` + `README.md` (Setup: ติดตั้ง MySQL, `CREATE DATABASE user_management CHARACTER SET utf8mb4;`, `composer install`, copy `.env.example` → `.env` แล้วใส่ DB_USERNAME/DB_PASSWORD, `php artisan key:generate`, `php artisan migrate --seed`, `php artisan serve`, ข้อมูลล็อกอินทดสอบ)
