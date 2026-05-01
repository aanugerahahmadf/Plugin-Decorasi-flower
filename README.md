# Wedding Pro Filament Plugin 💍✨

**Wedding Pro** adalah solusi *all-in-one* untuk membangun platform Wedding Organizer profesional menggunakan Filament PHP. Plugin ini dirancang untuk menjadi backend yang kuat bagi aplikasi web maupun mobile (Android/iOS).

## ✨ Fitur Utama & Cara Kerja

### 1. 🤖 AI Consultant Bot
Sistem asisten otomatis yang membantu pengguna menjawab pertanyaan seputar paket pernikahan.
- **Cara Pakai**: Cukup buka menu *Chat/Inboxes* di panel admin. Bot akan otomatis merespon pesan baru jika integrasi AI Core aktif.

### 2. 📸 AI Visual Search (CBIR)
Pengguna bisa mencari dekorasi atau paket berdasarkan kemiripan gambar.
- **Cara Pakai**: Pada aplikasi mobile, unggah foto ke endpoint `/api/cbir/search`. Plugin akan mengembalikan daftar paket dengan visual serupa.

### 3. 💳 Midtrans Payment Gateway
Pembayaran otomatis yang aman dan handal.
- **Cara Pakai**: Saat checkout, sistem akan menggenerate *Snap Token*. Modal pembayaran akan muncul secara otomatis di panel Filament maupun aplikasi mobile.

---

## 🚀 Panduan Instalasi Step-by-Step

### Langkah 1: Instalasi Package
Jalankan perintah ini di terminal proyek Laravel Anda:
```bash
composer require aanugerah/wedding-pro
```

### Langkah 2: Registrasi Plugin
Buka file `app/Providers/Filament/AdminPanelProvider.php` (atau Panel Provider lainnya), lalu tambahkan plugin ini:

```php
use Aanugerah\WeddingPro\WeddingProPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... konfigurasi lainnya
        ->plugin(WeddingProPlugin::make());
}
```

### Langkah 3: Database & Aset
Jalankan migrasi untuk membuat tabel-tabel yang diperlukan dan publish aset visual:
```bash
php artisan migrate
php artisan filament:assets
```

---

## ⚙️ Konfigurasi Penting (.env)

Buka file `.env` dan lengkapi detail berikut agar semua fitur cerdas berfungsi:

### 1. Pengaturan Midtrans
Dapatkan kunci ini dari [Dashboard Midtrans](https://dashboard.midtrans.com/):
```env
MIDTRANS_MERCHANT_ID=Gxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxx
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxx
MIDTRANS_IS_PRODUCTION=false
```

### 2. Pengaturan Server AI
Jika Anda menjalankan server AI secara lokal (Python Flask/FastAPI):
```env
AI_CORE_URL=http://127.0.0.1:5000
CBIR_API_URL=http://127.0.0.1:5000
```

---

## 📱 Dokumentasi API Mobile (Super Detail)

Plugin ini sudah menyediakan endpoint API yang siap dikonsumsi oleh aplikasi Android/iOS.

### 🔗 Endpoint Publik (Tanpa Login)
- **Cek Status Server**: `GET /api/ping`
- **Daftar Paket**: `GET /api/packages/public`
- **Daftar Organizer**: `GET /api/organizers/public`

### 🔒 Endpoint Terproteksi (Butuh Bearer Token)
Gunakan middleware `auth:sanctum` untuk mengakses:
- **Profile**: `GET /api/profile`
- **Buat Pesanan**: `POST /api/bookings`
- **Kirim Chat**: `POST /api/messages/send`
- **Visual Search**: `POST /api/cbir/search` (Kirim file `image` via Form-Data)

### 📡 Webhook Payment (Wajib Disetting!)
Di Dashboard Midtrans, arahkan URL Notification ke:
`https://domain-anda.com/api/webhooks/midtrans`

---

## 🛠️ Data Awal (Seeding)
Untuk mencoba fitur dengan data contoh (Dummy Data), jalankan:
```bash
php artisan db:seed --class="Aanugerah\WeddingPro\Database\Seeders\WeddingProSeeder"
```

## 📄 Lisensi & Dukungan
Plugin ini berlisensi MIT. Jika Anda menemukan bug atau butuh bantuan, silakan buka *Issue* di repositori resmi.

---
Dibuat dengan ❤️ oleh [Ahmad Fachrurochim](https://github.com/aanugerahahmadf)
