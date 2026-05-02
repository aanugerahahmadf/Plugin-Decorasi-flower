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
}
