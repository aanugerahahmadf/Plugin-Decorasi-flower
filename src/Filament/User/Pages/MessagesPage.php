<?php

namespace Aanugerah\WeddingPro\Filament\User\Pages;

use Filament\Pages\Page;

class MessagesPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'wedding-pro::livewire.messages.inbox';

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('Pesan');
    }

    public static function getSlug(): string
    {
        return 'messages';
    }

    /**
     * Polling interval dari config.
     * Dinonaktifkan otomatis saat NativePHP Mobile (gunakan WebSocket).
     */
    public function getPollingInterval(): ?string
    {
        if (\Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile()) {
            return null;
        }

        return config('wedding-pro.messages.polling_interval', '5s');
    }

    /**
     * Jumlah pesan per halaman dari config.
     */
    public static function getMessagesPerPage(): int
    {
        return (int) config('wedding-pro.messages.messages_per_page', 50);
    }

    /**
     * Apakah attachment diizinkan.
     */
    public static function isAttachmentsEnabled(): bool
    {
        return (bool) config('wedding-pro.messages.attachments_enabled', true);
    }

    /**
     * Ukuran maksimal attachment dalam KB.
     */
    public static function getMaxAttachmentSizeKb(): int
    {
        return (int) config('wedding-pro.messages.max_attachment_size_kb', 10240);
    }
}
