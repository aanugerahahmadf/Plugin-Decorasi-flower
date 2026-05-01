<?php

namespace Aanugerah\WeddingPro\Filament\Resources;

use Aanugerah\WeddingPro\Filament\User\Pages\MessagesPage;
use Aanugerah\WeddingPro\Filament\Resources\WeddingOrganizerResource\Pages;
use Aanugerah\WeddingPro\Helpers\NativeNotificationHelper;
use Aanugerah\WeddingPro\Models\Message;
use Aanugerah\WeddingPro\Models\WeddingOrganizer;
use Dotswan\MapPicker\Infolists\MapEntry;
use Filament\Facades\Filament;
use Filament\Forms\Form;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class WeddingOrganizerResource extends Resource
{
    protected static ?string $model = WeddingOrganizer::class;

    protected static ?string $slug = 'home';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Message::query()
            ->whereJsonDoesntContain('read_by', Filament::auth()->id(), 'and')
            ->where('user_id', '!=', Filament::auth()->id())
            ->count('id');

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Beranda');
    }

    public static function getNavigationLabel(): string
    {
        return __('Home');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Home');
    }

    public static function getModelLabel(): string
    {
        return __('Home');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('id', 1);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'default' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 6,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Gallery cover photo - using existing cover_image_url accessor
                    Tables\Columns\ImageColumn::make('cover_image_url')
                        ->label('')
                        ->height('10rem')
                        ->width('100%')
                        ->extraAttributes(['class' => 'w-full overflow-hidden rounded-t-xl'])
                        ->extraImgAttributes([
                            'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
                            'style' => 'width: 100%; height: 100%; object-fit: cover;',
                        ]),

                    // Info block
                    Tables\Columns\Layout\Stack::make([
                        // Studio name
                        Tables\Columns\TextColumn::make('name')
                            ->formatStateUsing(fn ($state) => __($state))
                            ->weight('bold')
                            ->size('sm')
                            ->icon('heroicon-s-sparkles')
                            ->color('primary')
                            ->lineClamp(1),

                        // Rating & address row
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('address')
                                ->icon('heroicon-m-map-pin')
                                ->color('gray')
                                ->size('xs')
                                ->lineClamp(1),
                            Tables\Columns\TextColumn::make('rating')
                                ->badge()
                                ->color('warning')
                                ->icon('heroicon-s-star')
                                ->alignEnd(),
                        ]),
                    ])->space(1)->extraAttributes(['class' => 'p-2']),
                ])->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-900 rounded-xl shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-800 transition-all duration-300 group overflow-hidden cursor-pointer',
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Lihat Profil'))
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->icon('heroicon-m-eye')
                    ->extraAttributes(['class' => 'w-full justify-center !rounded-lg'])
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Detail Dekorasi Bunga Pernikahan')),
            ])
            ->actionsAlignment('center');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // SHOPEE HEADER (PURE FILAMENT NATIVE ARCHITECTURE)
                Section::make()
                    ->schema([
                        // BANNER PREVIEW
                        ImageEntry::make('cover_image_url')
                            ->hiddenLabel()
                            ->columnSpanFull()
                            ->alignCenter()
                            ->height('20rem')
                            ->extraImgAttributes([
                                'class' => 'mx-auto object-contain rounded-xl shadow-sm bg-white/5',
                            ]),

                        // IDENTITY SPLIT
                        Split::make([
                            // INFO
                            Group::make([
                                TextEntry::make('name')
                                    ->label('')
                                    ->hiddenLabel()
                                    ->weight('bold')
                                    ->size('3xl')
                                    ->alignCenter(),

                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('is_verified')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->badge()
                                            ->color('success')
                                            ->icon('heroicon-s-check-badge')
                                            ->getStateUsing(fn ($record) => $record->is_verified ? __('Terverifikasi') : '')
                                            ->visible(fn ($record) => $record->is_verified)
                                            ->alignCenter(),

                                        TextEntry::make('city')
                                            ->label('')
                                            ->hiddenLabel()
                                            ->formatStateUsing(fn ($state) => __($state))
                                            ->icon('heroicon-m-map-pin')
                                            ->color('gray')
                                            ->size('sm')
                                            ->alignCenter(),
                                    ]),
                            ])->grow(),

                            // ACTIONS
                            Actions::make([
                                Action::make('chat')
                                    ->label(__('Pesan'))
                                    ->icon('heroicon-m-chat-bubble-left-right')
                                    ->button()
                                    ->color('primary')
                                    ->url(fn () => MessagesPage::getUrl())
                                    ->openUrlInNewTab(false),
                            ])->grow(false),
                        ])
                            ->extraAttributes(['class' => 'items-center gap-6 p-6']),
                    ])
                    ->compact()
                    ->extraAttributes(['class' => 'shadow-md border-gray-100 dark:border-gray-800 rounded-3xl overflow-hidden mb-4']),

                // MAIN CONTENT TABS
                Tabs::make('ProfileTabs')
                    ->tabs([
                        // ABOUT US TAB
                        Tabs\Tab::make(__('Tentang Kami'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextEntry::make('description')
                                    ->hiddenLabel()
                                    ->formatStateUsing(fn ($state) => __($state))
                                    ->prose()
                                    ->extraAttributes(['class' => 'leading-loose text-lg text-gray-700 dark:text-gray-300']),
                                Section::make(__('Lokasi'))
                                    ->icon('heroicon-o-map-pin')
                                    ->schema([
                                        TextEntry::make('address')
                                            ->label(__('Alamat Studio'))
                                            ->size('lg')
                                            ->columnSpanFull(),
                                        MapEntry::make('location')
                                            ->state(fn ($record) => [
                                                'lat' => (float) ($record->latitude),
                                                'lng' => (float) ($record->longitude),
                                            ])
                                            ->hiddenLabel()
                                            ->extraStyles([
                                                'height: 500px',
                                                'z-index: 1',
                                            ])
                                            ->columnSpanFull(),
                                    ])->compact(),
                            ]),

                        // PACKAGES CATALOG TAB
                        Tabs\Tab::make(__('Pilihan Paket'))
                            ->icon('ri-gift-line')
                            ->schema([
                                RepeatableEntry::make('packages')
                                    ->hiddenLabel()
                                    ->grid(3)
                                    ->schema([
                                        Section::make()
                                            ->schema([
                                                ImageEntry::make('image_url')
                                                    ->hiddenLabel()
                                                    ->alignCenter()
                                                    ->extraImgAttributes(['class' => 'h-40 object-contain mx-auto rounded-xl shadow-md border border-gray-100 dark:border-gray-800 bg-white']),

                                                Group::make([
                                                    TextEntry::make('name')
                                                        ->formatStateUsing(fn ($state) => __($state))
                                                        ->weight('bold')
                                                        ->size('lg')
                                                        ->lineClamp(1),

                                                    TextEntry::make('price')
                                                        ->money('idr')
                                                        ->color('primary')
                                                        ->weight('extrabold')
                                                        ->size('lg'),
                                                ]),

                                                Actions::make([
                                                    Action::make('view_details')
                                                        ->label(__('Lihat Detail'))
                                                        ->icon('heroicon-m-eye')
                                                        ->button()
                                                        ->color('primary')
                                                        ->url(fn ($record) => PackageResource::getUrl('view', ['record' => $record->id])),
                                                ])->fullWidth(),
                                            ])
                                            ->collapsible()
                                            ->extraAttributes(['class' => 'dark:bg-white/5 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden hover:border-blue-500 shadow-sm transition-all']),
                                    ]),
                            ]),

                        // PRODUCTS CATALOG TAB
                        Tabs\Tab::make(__('Katalog Bunga'))
                            ->icon('ri-flower-line')
                            ->schema([
                                RepeatableEntry::make('products')
                                    ->hiddenLabel()
                                    ->grid(3)
                                    ->schema([
                                        Section::make()
                                            ->schema([
                                                ImageEntry::make('image_url')
                                                    ->hiddenLabel()
                                                    ->alignCenter()
                                                    ->extraImgAttributes(['class' => 'h-40 object-contain mx-auto rounded-xl shadow-md border border-gray-100 dark:border-gray-800 bg-white']),

                                                Group::make([
                                                    TextEntry::make('name')
                                                        ->formatStateUsing(fn ($state) => __($state))
                                                        ->weight('bold')
                                                        ->size('lg')
                                                        ->lineClamp(1),

                                                    TextEntry::make('final_price')
                                                        ->money('idr')
                                                        ->color('success')
                                                        ->weight('extrabold')
                                                        ->size('lg'),
                                                ]),

                                                Actions::make([
                                                    Action::make('view_product_detail')
                                                        ->label(__('Lihat Detail'))
                                                        ->icon('heroicon-m-eye')
                                                        ->button()
                                                        ->color('success')
                                                        ->url(fn ($record) => ProductResource::getUrl('view', ['record' => $record->id])),
                                                ])->fullWidth(),
                                            ])
                                            ->collapsible()
                                            ->extraAttributes(['class' => 'dark:bg-white/5 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden hover:border-green-500 shadow-sm transition-all']),
                                    ]),
                            ]),

                        // CONTACTS TAB
                        Tabs\Tab::make(__('Informasi Kontak'))
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Section::make(__('Saluran Komunikasi'))
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        TextEntry::make('whatsapp')
                                                            ->label(__('WhatsApp'))
                                                            ->icon('ri-whatsapp-line')
                                                            ->getStateUsing(fn ($record) => $record->whatsapp ?: ($record->phone ?: '-')),

                                                        TextEntry::make('email')
                                                            ->label(__('Email'))
                                                            ->icon('heroicon-o-envelope')
                                                            ->getStateUsing(fn ($record) => $record->email ?: '-'),

                                                        TextEntry::make('instagram')
                                                            ->label(__('Instagram'))
                                                            ->icon('ri-instagram-line')
                                                            ->getStateUsing(fn ($record) => $record->instagram ? "@{$record->instagram}" : '-'),
                                                    ]),

                                                Actions::make([
                                                    Action::make('whatsapp_contact')
                                                        ->label(__('Chat via WhatsApp'))
                                                        ->icon('heroicon-m-chat-bubble-left-right')
                                                        ->button()
                                                        ->color('success')
                                                        ->url(fn ($record) => 'https://wa.me/'.preg_replace('/[^0-9]/', '', $record->whatsapp ?: $record->phone))
                                                        ->openUrlInNewTab()
                                                        ->visible(fn ($record) => filled($record->whatsapp) || filled($record->phone)),
                                                    Action::make('email_contact')
                                                        ->label(__('Kirim Email'))
                                                        ->icon('heroicon-m-envelope')
                                                        ->button()
                                                        ->color('info')
                                                        ->url(fn ($record) => "mailto:{$record->email}")
                                                        ->openUrlInNewTab()
                                                        ->visible(fn ($record) => filled($record->email)),
                                                    Action::make('instagram_contact')
                                                        ->label(__('Lihat Instagram'))
                                                        ->icon('ri-instagram-line')
                                                        ->button()
                                                        ->color('danger')
                                                        ->url(fn ($record) => "https://instagram.com/{$record->instagram}")
                                                        ->openUrlInNewTab()
                                                        ->visible(fn ($record) => filled($record->instagram)),
                                                ])->fullWidth(),
                                            ]),

                                        Section::make(__('Waktu Operasional'))
                                            ->schema([
                                                TextEntry::make('operational_hours')
                                                    ->label(__('Jam Kerja'))
                                                    ->icon('heroicon-o-clock')
                                                    ->getStateUsing(fn ($record) => $record->operational_hours ?: 'Senin - Minggu: 09:00 - 18:00'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'border-0 shadow-none mt-4 rounded-3xl']),
            ])
            ->columns(1);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ViewWeddingOrganizer::route('/{record?}'),
        ];
    }
}
