<?php

namespace Aanugerah\WeddingPro\Jobs;

use Aanugerah\WeddingPro\Filament\User\Resources\PackageResource;
use Aanugerah\WeddingPro\Filament\User\Resources\VoucherResource;
use Aanugerah\WeddingPro\Models\Message;

use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBotReply implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $messageId;

    protected $locale;

    /**
     * Create a new job instance.
     */
    public function __construct(int $messageId, ?string $locale = null)
    {
        $this->messageId = $messageId;
        $this->locale = $locale ?? app()->getLocale();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set the locale for the job execution to match the user's preference
        app()->setLocale($this->locale);

        $userMessage = Message::find($this->messageId);
        if (! $userMessage) {
            return;
        }

        $inbox = $userMessage->inbox;

        $sender = (config('auth.providers.users.model'))::find($userMessage->user_id);

        // Don't reply if the message was sent by the bot itself to prevent infinite loops
        if ($userMessage->meta && isset($userMessage->meta['is_bot']) && $userMessage->meta['is_bot']) {
            return;
        }

        // --- ADVANCED AI BRAIN ---
        $text = strtolower($userMessage->message);
        $reply = '';

        // Personalization: Get User's First Name
        $userName = $sender ? explode(' ', $sender->name)[0] : __('Kak');

        // 1. Time-aware Logic
        $hour = now()->hour;
        if ($hour < 11) {
            $greeting = __('Selamat pagi');
        } elseif ($hour < 15) {
            $greeting = __('Selamat siang');
        } elseif ($hour < 19) {
            $greeting = __('Selamat sore');
        } else {
            $greeting = __('Selamat malam');
        }

        // 2. Emotion & Tone Control
        $warmClosure = __('Semoga hari Anda menyenangkan! ✨');
        $urgentClosure = __('Kami prioritaskan pesan Anda sekarang juga. 🙏');

        // 3. THE DECISION ENGINE (Simulated Intelligence)
        switch (true) {
            // A. Context: New Order (High Priority)
            case $userMessage->meta && isset($userMessage->meta['is_order']) && $userMessage->meta['is_order']:
                $orderNumber = $userMessage->meta['order_number'];
                $reply = __('Wah, :greeting :userName! Kami sangat antusias menerima pesanan Anda (:orderNumber). 😍 Tim kami sedang melakukan pengecekan jadwal dan detail teknis untuk memastikan semuanya sempurna. Kami akan segera menghubungi Anda untuk langkah selanjutnya. Terima kasih telah mempercayakan momen spesial Anda kepada kami!', [
                    'greeting' => $greeting,
                    'userName' => $userName,
                    'orderNumber' => $orderNumber,
                ]);
                break;

                // B. Context: Product/Package Discovery
            case $userMessage->meta && isset($userMessage->meta['type']):
                $name = $userMessage->meta['name'];
                $reply = __('Halo :userName, pilihan yang luar biasa! :name memang sedang menjadi tren dan sangat diminati. Admin kami sedang menyiapkan detail ketersediaan untuk tanggal acara Anda. Sambil menunggu, apakah :userName punya preferensi warna bunga tertentu untuk tema ini?', [
                    'userName' => $userName,
                    'name' => $name,
                ]);
                break;

                // C. Intent: Urgent / Complaints
            case preg_match('/(urgent|cepat|darurat|lama|komplain|masalah|kecewa|tolong|help)/', $text):
                $reply = __('Mohon maaf atas ketidaknyamanannya, :userName. Kami memahami ini sangat penting bagi Anda. Saya telah menandai pesan ini sebagai Prioritas Utama. Admin senior kami akan segera masuk ke percakapan ini untuk membantu Anda secara langsung. :urgentClosure', [
                    'userName' => $userName,
                    'urgentClosure' => $urgentClosure,
                ]);
                break;

                // D. Intent: Specific Flowers / Themes
            case preg_match('/(mawar|rose|lily|tulip|anggrek|melati|bunga|warna|tema|konsep)/', $text):
                $reply = __('Menarik sekali! Kami memiliki berbagai koleksi bunga segar dan premium. Admin kami akan mengirimkan beberapa referensi konsep dan kombinasi bunga yang cocok dengan keinginan :userName. Tunggu sebentar ya, kami sedang mengumpulkan foto portofolio yang relevan. 🌸', [
                    'userName' => $userName,
                ]);
                break;

                // E. Intent: Price & Budget (Sales Conversion)
            case preg_match('/(harga|berapa|price|biaya|budget|mahal|murah|diskon|promo|voucher)/', $text):
                $reply = __('Halo :userName! Terkait biaya, kami sangat fleksibel dan memiliki paket yang bisa disesuaikan dengan budget Anda. Kabar baiknya, ada beberapa promo eksklusif yang bisa Anda cek di sini: :url. Admin kami akan segera memberikan estimasi penawaran yang paling kompetitif untuk Anda! 💰', [
                    'userName' => $userName,
                    'url' => VoucherResource::getUrl(),
                ]);
                break;

                // F. Intent: Location & Logistics
            case preg_match('/(lokasi|alamat|dimana|where|tempat|kantor|area|luar kota)/', $text):
                $wo = WeddingOrganizer::getBrand();
                $fallbackAddress = __('Rajasinga, Kec. Terisi, Kabupaten Indramayu, Jawa Barat');
                $officeAddress = ($wo && $wo->address) ? $wo->address : $fallbackAddress;
                
                $reply = __('Tentu :userName! Kantor utama kami berlokasi di **:address**. Kami melayani dekorasi untuk area lokal maupun luar kota. Jika :userName ingin berkunjung untuk konsultasi tatap muka, admin kami akan segera memberikan titik koordinatnya. 📍', [
                    'userName' => $userName,
                    'address' => $officeAddress,
                ]);
                break;

                // G. Intent: Booking & Procedure
            case preg_match('/(pesan|booking|order|beli|cara|prosedur|syarat)/', $text):
                $reply = __('Tentu, :userName! Prosedurnya sangat simpel: Pilih paket, konsultasi tema, DP untuk kunci tanggal, dan sisanya kami yang urus. Anda bisa mulai dengan memilih paket di sini: :url. Admin kami akan memandu Anda langkah demi langkah sebentar lagi. 📝', [
                    'userName' => $userName,
                    'url' => PackageResource::getUrl(),
                ]);
                break;

                // H. Intent: Greetings & Small Talk
            case preg_match('/(halo|hi|hey|pagi|siang|sore|malam|permisi|apa kabar|assalamu)/', $text):
                $reply = __(':greeting, :userName! Senang sekali bisa menyapa Anda. Ada yang bisa kami bantu untuk mewujudkan pernikahan impian Anda hari ini? Kami siap memberikan solusi dekorasi terbaik! 😊', [
                    'greeting' => $greeting,
                    'userName' => $userName,
                ]);
                break;

                // I. Intent: Gratitude
            case preg_match('/(terima kasih|thanks|makasih|thx|tq|oke|ok|sip)/', $text):
                $reply = __('Sama-sama, :userName! Sudah menjadi komitmen kami untuk memberikan layanan terbaik. Ada hal lain yang ingin Anda ketahui? :warmClosure', [
                    'userName' => $userName,
                    'warmClosure' => $warmClosure,
                ]);
                break;

                // J. Intent: Manual Admin Request
            case preg_match('/(admin|manusia|orang|balas|tanya admin|panggil)/', $text):
                $reply = __('Baik :userName, saya sedang memanggil Admin kami untuk bergabung ke percakapan ini. Mohon tunggu sebentar ya, kami akan segera melayani Anda secara langsung. 🙏', [
                    'userName' => $userName,
                ]);
                break;

                // K. Fallback (The 'AI Thinking' response)
            default:
                $reply = __(':greeting, :userName! Terima kasih telah menghubungi kami. Pesan Anda sangat berharga bagi kami. Admin kami sedang mempelajari permintaan Anda dan akan segera memberikan jawaban yang paling akurat dalam beberapa saat. Sambil menunggu, silakan lihat koleksi terbaru kami di: :url 🙏', [
                    'greeting' => $greeting,
                    'userName' => $userName,
                    'url' => PackageResource::getUrl(),
                ]);
                break;
        }

        // Send the bot message
        $admin = (config('auth.providers.users.model'))::whereHas('roles', function ($q) {
            $q->where('name', 'super_admin');
        })->first();

        if ($admin) {
            Message::create([
                'inbox_id' => $inbox->id,
                'user_id' => $admin->id,
                'message' => $reply,
                'meta' => ['is_bot' => true],
            ]);
        }
    }
}
