# Wedding Pro Filament Plugin 💍✨

**Wedding Pro** adalah solusi *all-in-one* untuk membangun platform Wedding Organizer profesional menggunakan Filament PHP. Plugin ini dirancang untuk menjadi backend yang kuat bagi aplikasi web maupun mobile (Android/iOS).

[![Packagist Version](https://img.shields.io/packagist/v/aanugerah/wedding-pro)](https://packagist.org/packages/aanugerah/wedding-pro)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://packagist.org/packages/aanugerah/wedding-pro)
[![Filament Version](https://img.shields.io/badge/filament-%5E3.3%20%7C%7C%20%5E4.0-orange)](https://filamentphp.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## ✨ Fitur Utama

### 1. 📸 AI Visual Search (CBIR)
Cari paket dekorasi berdasarkan **kemiripan gambar** menggunakan teknologi Content-Based Image Retrieval. Upload foto atau ambil gambar langsung dari kamera — sistem akan menemukan paket yang paling mirip secara visual dengan akurasi tinggi.

> **Demo:** Upload foto bunga → sistem menemukan 10 paket dengan akurasi 100% dalam 1.48 detik.

![CBIR Upload](https://raw.githubusercontent.com/aanugerahahmadf/Plugin-Decorasi-flower/main/docs/cbir-upload.png)
![CBIR Results](https://raw.githubusercontent.com/aanugerahahmadf/Plugin-Decorasi-flower/main/docs/cbir-results.png)
![CBIR List](https://raw.githubusercontent.com/aanugerahahmadf/Plugin-Decorasi-flower/main/docs/cbir-list.png)

**Cara kerja:**
- Ketik teks untuk pencarian biasa
- Klik ikon 📷 untuk ambil foto langsung dari kamera
- Klik ikon 🖼️ untuk upload dari galeri
- Sistem menampilkan hasil dengan persentase kemiripan visual

### 2. 🤖 AI Consultant Bot
Asisten otomatis yang merespon pesan pelanggan seputar paket pernikahan langsung dari panel admin.

### 3. 💳 Midtrans Payment Gateway
Integrasi pembayaran lengkap dengan Snap Token — modal pembayaran muncul otomatis di panel Filament maupun aplikasi mobile.

### 4. � NativePHP Mobile Ready
Seluruh API dan konfigurasi sudah dioptimalkan untuk berjalan di aplikasi Android/iOS via NativePHP.

### 5. 🌐 Auto Translation
Terjemahan otomatis konten ke berbagai bahasa menggunakan MyMemory API dengan caching cerdas.

---

## 🚀 Instalasi

### Langkah 1: Install via Composer
```bash
composer require aanugerah/wedding-pro
```

### Langkah 2: Daftarkan Plugin
Buka `app/Providers/Filament/AdminPanelProvider.php` dan tambahkan:

```php
use Aanugerah\WeddingPro\WeddingProPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... konfigurasi lainnya
        ->plugin(WeddingProPlugin::make());
}
```

### Langkah 3: Jalankan Migrasi & Publish Aset
```bash
php artisan migrate
php artisan filament:assets
```

### Langkah 4: (Opsional) Tambahkan Trait ke User Model
Agar fitur language switcher berfungsi, tambahkan trait ke `app/Models/User.php`:

```php
use Aanugerah\WeddingPro\Traits\HasWeddingPro;
use Aanugerah\WeddingPro\Traits\InteractsWithLanguages;

class User extends Authenticatable
{
    use HasWeddingPro;
    use InteractsWithLanguages;
}
```

---

## ⚙️ Konfigurasi `.env`

```env
# Midtrans Payment Gateway
# Dapatkan dari: https://dashboard.midtrans.com/
MIDTRANS_MERCHANT_ID=Gxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxx
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxx
MIDTRANS_IS_PRODUCTION=false

# AI Server (Python Flask/FastAPI)
AI_CORE_URL=http://127.0.0.1:5000
CBIR_API_URL=http://127.0.0.1:5000

# NativePHP Mobile (opsional)
NATIVEPHP_RUNNING=false
NATIVE_HOST_IP=10.0.2.2
NATIVE_SERVER_PORT=8000
NATIVE_DB_PROXY_SECRET=your-secret-key
```

---

## � Dependencies

Plugin ini secara otomatis menginstall:

| Package | Fungsi |
|---|---|
| `filament/filament` | Core Filament panel |
| `filament/spatie-laravel-media-library-plugin` | Upload & manajemen media |
| `tomatophp/filament-language-switcher` | Switcher bahasa di panel |
| `dotswan/filament-map-picker` | Peta interaktif untuk lokasi organizer |
| `emmanpbarrameda/filament-take-picture-field` | Ambil foto langsung dari kamera |
| `midtrans/midtrans-php` | Payment gateway Midtrans |
| `spatie/laravel-csp` | Content Security Policy untuk Midtrans Snap |
| `stichoza/google-translate-php` | Auto-translate konten |
| `nativephp/laravel` + `nativephp/mobile` | Mobile app support |

---

## 📱 API Mobile

### Endpoint Publik (Tanpa Login)
| Method | Endpoint | Keterangan |
|---|---|---|
| GET | `/api/ping` | Cek status server |
| GET | `/api/packages/public` | Daftar semua paket |
| GET | `/api/organizers/public` | Daftar organizer |
| GET | `/api/settings` | Konfigurasi aplikasi |

### Endpoint Terproteksi (Bearer Token)
| Method | Endpoint | Keterangan |
|---|---|---|
| POST | `/api/register` | Daftar akun baru |
| POST | `/api/login` | Login |
| GET | `/api/profile` | Data profil user |
| POST | `/api/bookings` | Buat pesanan baru |
| GET | `/api/orders` | Riwayat pesanan |
| POST | `/api/cbir/search` | Visual search (kirim `image` via form-data) |
| POST | `/api/messages/send` | Kirim pesan ke organizer |
| GET | `/api/wishlist` | Daftar favorit |
| POST | `/api/vouchers/apply` | Pakai voucher diskon |

### Webhook Midtrans
Di Dashboard Midtrans, arahkan **Payment Notification URL** ke:
```
https://domain-anda.com/api/webhooks/midtrans
```

---

## �️ Database Tables

Plugin membuat tabel-tabel berikut secara otomatis saat `php artisan migrate`:

`users` · `user_languages` · `categories` · `wedding_organizers` · `articles` · `packages` · `products` · `orders` · `transactions` · `reviews` · `banners` · `vouchers` · `wishlists` · `carts` · `histories` · `fm_inboxes` · `fm_messages` · `translations`

---

## 🛠️ Data Contoh (Seeding)

```bash
php artisan db:seed --class="Aanugerah\WeddingPro\Database\Seeders\WeddingProSeeder"
```

---

## 📄 Lisensi

MIT License — bebas digunakan untuk proyek komersial maupun personal.

---

Dibuat dengan ❤️ oleh [Ahmad Fachrurochim](https://github.com/aanugerahahmadf)
