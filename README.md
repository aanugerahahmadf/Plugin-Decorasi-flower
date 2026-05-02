# Wedding Pro Filament Plugin 💍✨

**Wedding Pro** adalah solusi *all-in-one* tingkat *enterprise* untuk membangun platform Wedding Organizer profesional menggunakan **Filament PHP** dan **Laravel**. Plugin ini dirancang untuk menjadi backend (Admin Panel) yang sangat kuat, sekaligus menyediakan API lengkap untuk diintegrasikan dengan aplikasi mobile (Android/iOS) via NativePHP.

[![Packagist Version](https://img.shields.io/packagist/v/aanugerah/wedding-pro)](https://packagist.org/packages/aanugerah/wedding-pro)
[![PHP Version](https://img.shields.io/badge/php-%5E8.2-blue)](https://packagist.org/packages/aanugerah/wedding-pro)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E11.0-red)](https://laravel.com)
[![Filament Version](https://img.shields.io/badge/filament-%5E3.3%20%7C%7C%20%5E4.0-orange)](https://filamentphp.com)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 📑 Daftar Isi

- [✨ Fitur Utama](#-fitur-utama)
- [📋 Persyaratan Sistem](#-persyaratan-sistem)
- [🚀 Instalasi](#-instalasi)
- [⚙️ Konfigurasi Lingkungan (.env)](#️-konfigurasi-lingkungan-env)
- [📱 Dokumentasi API Mobile](#-dokumentasi-api-mobile)
- [🗄️ Skema Database](#️-skema-database)
- [🤝 Kontribusi](#-kontribusi)
- [📄 Lisensi & Legal (Licensing)](#-lisensi--legal-licensing)

---

## ✨ Fitur Utama

Plugin ini membawa fitur-fitur mutakhir yang jarang ditemukan pada plugin Wedding Organizer standar:

### 1. 📸 AI Visual Search (CBIR)
Sistem pencarian cerdas berbasis *Content-Based Image Retrieval*.
- **Cara Kerja**: Pengguna (klien) mengunggah foto referensi dekorasi impian mereka. AI server akan menganalisis fitur visual gambar dan mencari paket dekorasi di database yang paling mirip secara visual.
- **Akurasi & Performa**: Menemukan kecocokan hingga 100% dalam waktu kurang dari 2 detik.
- **Integrasi Kamera**: Langsung ambil gambar melalui kamera perangkat untuk pencarian seketika.

> **Demo:** Upload foto bunga → sistem menemukan paket dengan akurasi tinggi.

![CBIR Upload](https://raw.githubusercontent.com/aanugerahahmadf/Plugin-Decorasi-flower/main/docs/cbir-upload.png)
![CBIR Results](https://raw.githubusercontent.com/aanugerahahmadf/Plugin-Decorasi-flower/main/docs/cbir-results.png)
![CBIR List](https://raw.githubusercontent.com/aanugerahahmadf/Plugin-Decorasi-flower/main/docs/cbir-list.png)

### 2. 🤖 AI Consultant Bot
Asisten chatbot pintar untuk merespon pertanyaan pelanggan.
- **Automasi**: Merespon ketersediaan paket, harga, dan detail layanan secara otomatis.
- **Monitoring**: Seluruh percakapan terekam dan dapat diambil alih oleh admin (organizer) kapan saja melalui panel pesan Filament.

### 3. 💳 Midtrans Payment Gateway Terintegrasi
Penyelesaian transaksi langsung dari aplikasi.
- **Snap Token**: Menampilkan modal pembayaran yang responsif dan aman.
- **Webhook**: Update status pesanan (Pending, Paid, Failed) secara *real-time* dan otomatis tanpa campur tangan admin.

### 4. 📱 NativePHP Mobile Ready
Arsitektur API dan *service provider* yang dioptimalkan secara khusus untuk berjalan di atas ekosistem **NativePHP**.
- Normalisasi URL otomatis (mengubah `localhost` ke `10.0.2.2` untuk emulator Android).
- Bypass *Content Security Policy* otomatis di perangkat mobile.

### 5. 🌐 Multi-language & Auto Translation
Sistem lokalisasi pintar yang mendobrak batasan bahasa.
- **MyMemory API**: Terjemahan teks antarmuka dan konten artikel secara otomatis.
- **Smart Caching**: Hasil terjemahan disimpan untuk meminimalisir beban request API dan mempercepat waktu muat.

---

## 📋 Persyaratan Sistem

Pastikan environment Anda memenuhi spesifikasi berikut sebelum menginstal plugin:

- **PHP**: `^8.2` atau lebih baru
- **Laravel**: `^11.0`
- **Filament**: `^3.3`
- **Ekstensi PHP**: `pdo`, `mbstring`, `intl`, `sqlite3` (untuk NativePHP), `exif` (untuk manipulasi gambar)
- **Composer**: Versi 2.x

---

## 🚀 Instalasi

Ikuti langkah-langkah di bawah ini untuk menginstal Wedding Pro ke dalam proyek Laravel Anda.

### Langkah 1: Install via Composer
Jalankan perintah berikut di terminal Anda:
```bash
composer require aanugerah/wedding-pro
```

### Langkah 2: Daftarkan Plugin di Filament Panel
Buka file `app/Providers/Filament/AdminPanelProvider.php` (atau panel lain yang Anda gunakan) dan tambahkan plugin ke dalam rantai metode `panel()`:

```php
use Aanugerah\WeddingPro\WeddingProPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        // ... plugin lain ...
        ->plugin(WeddingProPlugin::make());
}
```

### Langkah 3: Modifikasi Model User (Opsional namun Disarankan)
Agar fitur peralihan bahasa dan asosiasi pesanan berfungsi maksimal, tambahkan *trait* berikut ke model `User` bawaan aplikasi Anda:

```php
namespace App\Models;

use Aanugerah\WeddingPro\Traits\HasWeddingPro;
use Aanugerah\WeddingPro\Traits\InteractsWithLanguages;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasWeddingPro;
    use InteractsWithLanguages;
    
    // ...
}
```

### Langkah 4: Jalankan Migrasi & Publish Aset
Plugin ini memiliki struktur database yang ekstensif. Jalankan perintah migrasi:
```bash
php artisan migrate
php artisan filament:assets
```

### Langkah 5: Data Seeding (Contoh Data)
Untuk melihat bagaimana plugin bekerja dengan data yang terisi penuh (seperti dummy package, organizer, dan artikel):
```bash
php artisan db:seed --class="Aanugerah\WeddingPro\Database\Seeders\WeddingProSeeder"
```

---

## ⚙️ Konfigurasi Lingkungan (.env)

Tambahkan variabel berikut ke dalam file `.env` proyek Laravel Anda:

```env
# ==========================================
# MIDTRANS PAYMENT GATEWAY
# Dapatkan kredensial di: https://dashboard.midtrans.com/
# ==========================================
MIDTRANS_MERCHANT_ID=Gxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxx
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxx
MIDTRANS_IS_PRODUCTION=false

# ==========================================
# AI SERVER & VISUAL SEARCH (CBIR)
# URL menuju server Python (Flask/FastAPI) Anda
# ==========================================
AI_CORE_URL=http://127.0.0.1:5000
CBIR_API_URL=http://127.0.0.1:5000

# ==========================================
# NATIVEPHP & MOBILE SETTINGS (OPSIONAL)
# ==========================================
NATIVEPHP_RUNNING=false
NATIVE_HOST_IP=10.0.2.2
NATIVE_SERVER_PORT=8000
NATIVE_DB_PROXY_SECRET=your-secret-key
```

---

## 📱 Dokumentasi API Mobile

Plugin ini menyediakan RESTful API *out-of-the-box* untuk dikonsumsi oleh *frontend* terpisah atau aplikasi mobile.

### Endpoint Publik (Tanpa Autentikasi)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/ping` | Pengecekan *health status* server. |
| `GET` | `/api/packages/public` | Mendapatkan daftar katalog paket dekorasi. |
| `GET` | `/api/organizers/public` | Mendapatkan daftar profil Wedding Organizer. |
| `GET` | `/api/settings` | Mengambil konfigurasi dinamis aplikasi. |

### Endpoint Terproteksi (Wajib Bearer Token)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `POST` | `/api/register` | Pendaftaran pengguna baru. |
| `POST` | `/api/login` | Autentikasi dan pengambilan token sesi. |
| `GET` | `/api/profile` | Mendapatkan detail informasi *user* yang login. |
| `POST` | `/api/bookings` | Membuat reservasi/pesanan paket baru. |
| `GET` | `/api/orders` | Mengambil riwayat pesanan klien. |
| `POST` | `/api/cbir/search` | Mengirim data `image` (form-data) untuk pencarian visual. |
| `POST` | `/api/messages/send` | Mengirim pesan (chat) ke organizer. |
| `GET` | `/api/wishlist` | Mengelola daftar favorit pengguna. |
| `POST` | `/api/vouchers/apply` | Menerapkan kode promo/voucher ke keranjang. |

### Konfigurasi Webhook Midtrans
Agar status pembayaran diupdate secara otomatis oleh server Midtrans, pastikan Anda menyetel **Payment Notification URL** di dashboard Midtrans ke:
`https://domain-anda.com/api/webhooks/midtrans`

---

## 🗄️ Skema Database

Ketika Anda menjalankan migrasi, plugin akan membuat ekosistem tabel berikut:
- **Pengguna & Lokalisasi**: `users`, `user_languages`, `translations`
- **Katalog & Organizer**: `wedding_organizers`, `categories`, `packages`, `products`
- **Transaksi & Keuangan**: `orders`, `transactions`, `vouchers`, `carts`
- **Interaksi Pengguna**: `reviews`, `wishlists`, `histories`
- **Komunikasi & Konten**: `fm_inboxes`, `fm_messages`, `articles`, `banners`

---

## 🤝 Kontribusi

Kami sangat menyambut kontribusi dari komunitas *open-source*! Jika Anda menemukan kutu (bug), memiliki ide fitur baru, atau ingin memperbaiki dokumentasi:

1. Lakukan *Fork* pada repositori ini.
2. Buat *branch* fitur Anda (`git checkout -b fitur/FiturLuarBiasa`).
3. Tulis kode Anda beserta *unit test* jika memungkinkan.
4. Lakukan *Commit* perubahan Anda (`git commit -m 'Menambahkan FiturLuarBiasa'`).
5. Dorong ke *branch* Anda (`git push origin fitur/FiturLuarBiasa`).
6. Buka **Pull Request** di GitHub.

Seluruh *Pull Request* harus melewati proses CI (Continuous Integration) menggunakan PHPUnit untuk memastikan tidak ada fitur yang rusak.

---

## 📄 Lisensi & Legal (Licensing)

Perangkat lunak ini adalah sumber terbuka (Open-Source) dan dilisensikan di bawah pengawasan **[MIT License](https://opensource.org/licenses/MIT)**.

### Hak Pengguna (What You Can Do)
Berdasarkan MIT License, Anda diberikan kebebasan penuh tanpa batasan untuk:
- Menggunakan plugin ini untuk proyek **pribadi** maupun **komersial** (digunakan untuk klien Anda).
- **Memodifikasi** kode sumber sesuai dengan kebutuhan spesifik bisnis Anda.
- **Mendistribusikan ulang** aplikasi yang di-build menggunakan kerangka kerja plugin ini.
- Melakukan komersialisasi dan menjual produk turunan Anda secara legal.

### Kewajiban Pengguna (Conditions)
- Anda **wajib** menyertakan pemberitahuan hak cipta asli (*copyright notice*) dan salinan lisensi MIT ini di setiap salinan atau bagian substansial dari perangkat lunak.

### Sanggahan Hukum (Disclaimer of Warranty & Limitation of Liability)
> PERANGKAT LUNAK INI DISEDIAKAN "APA ADANYA" (AS IS), TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN TERSIRAT, TERMASUK NAMUN TIDAK TERBATAS PADA JAMINAN KELAYAKAN JUAL, KESESUAIAN UNTUK TUJUAN TERTENTU, DAN KETIADAAN PELANGGARAN.
> 
> DALAM KEADAAN APA PUN, PENGARANG (AUTHOR) ATAU PEMEGANG HAK CIPTA TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN, ATAU KEWAJIBAN LAINNYA, BAIK DALAM TINDAKAN KONTRAK, PELANGGARAN, ATAU LAINNYA, YANG TIMBUL DARI, DI LUAR, ATAU SEHUBUNGAN DENGAN PERANGKAT LUNAK INI ATAU PENGGUNAAN ATAU TRANSAKSI LAIN DALAM PERANGKAT LUNAK INI. PENGGUNAAN KODE INI BERADA SEPENUHNYA DI BAWAH RISIKO PENGGUNA.

### Lisensi Pihak Ketiga (Third-Party Licenses)
Harap dicatat bahwa plugin ini mengimpor dan bergantung pada beberapa perpustakaan pihak ketiga (*dependencies*). Perpustakaan-perpustakaan tersebut tunduk pada lisensinya masing-masing (sebagian besar MIT). Dengan menggunakan plugin ini, Anda juga setuju untuk mematuhi lisensi dari pustaka pihak ketiga berikut:
- **Laravel Framework** (MIT License)
- **Filament PHP** (MIT License)
- **Spatie Media Library** (MIT License)
- **Midtrans PHP Client** (MIT License)

---

<div align="center">
    Dibuat dan dipelihara dengan ❤️ oleh <a href="https://github.com/aanugerahahmadf">Anugerah Ahmad Fachrurochim</a>. <br>
    <em>Solusi Sistem Informasi Pernikahan Cerdas Masa Depan.</em>
</div>
