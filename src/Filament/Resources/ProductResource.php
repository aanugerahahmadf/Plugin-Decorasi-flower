<?php

namespace Aanugerah\WeddingPro\Filament\Resources;

use Aanugerah\WeddingPro\Enums\OrderPaymentStatus;
use Aanugerah\WeddingPro\Enums\OrderStatus;
use Aanugerah\WeddingPro\Filament\User\Pages\MessagesPage;
use Aanugerah\WeddingPro\Filament\Resources\ProductResource\Pages;
use Aanugerah\WeddingPro\Models\Cart;
use Aanugerah\WeddingPro\Models\Order;
use Aanugerah\WeddingPro\Models\Product;
use Aanugerah\WeddingPro\Models\Transaction;
use Aanugerah\WeddingPro\Models\Voucher;
use Aanugerah\WeddingPro\Models\Wishlist;
use Aanugerah\WeddingPro\Services\CBIRService;
use Aanugerah\WeddingPro\Services\ChatService;
use Aanugerah\WeddingPro\Services\MidtransService;
use emmanpbarrameda\FilamentTakePictureField\Forms\Components\TakePicture;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\File\File;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'ri-flower-line';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'category.name', 'weddingOrganizer.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Belanja & Jelajahi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Katalog Bunga');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Katalog Bunga');
    }

    public static function getModelLabel(): string
    {
        return __('Katalog Bunga');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->poll(\Aanugerah\WeddingPro\NativeServiceProvider::isNativeMobile() ? null : '30s')
            ->headerActions([
                Tables\Actions\Action::make('visual_search')
                    ->label(__('Pencarian Bunga Cerdas'))
                    ->icon('heroicon-o-camera')
                    ->color('primary')
                    ->slideOver()
                    ->modalWidth('full')
                    ->modalHeading(__('Pencarian Visual Cerdas'))
                    ->modalDescription(__('Temukan dekorasi impian Anda dengan mudah. Unggah foto atau ambil gambar langsung untuk melihat koleksi terbaik dari Weeding Flower Decoration.'))
                    ->action(fn () => null)
                    ->modalSubmitActionLabel(__('Tampilkan di Katalog Utama'))
                    ->modalCancelActionLabel(__('Tutup'))
                    ->extraModalWindowAttributes(['class' => 'bg-gray-50/50 backdrop-blur-3xl'])
                    ->form([
                        Forms\Components\Section::make('')
                            ->compact()
                            ->schema([
                                Forms\Components\TextInput::make('search')
                                    ->label(__('Cari Visual'))
                                    ->placeholder(__('Ketik, ambil foto, atau galeri...'))
                                    ->prefixIcon('heroicon-m-magnifying-glass')
                                    ->prefixIconColor('gray')
                                    ->live(debounce: 500)
                                    ->afterStateUpdated(function (Component $livewire, $state, Forms\Set $set) {
                                        if (empty($state)) {
                                            session()->forget(['cbir_mixed_results', 'cbir_product_results_ids', 'cbir_search_time', 'cbir_context']);
                                            $set('status_message', null);
                                            $livewire->dispatch('refresh_items');
                                            $livewire->dispatch('refresh_catalog');
                                            return;
                                        }

                                        $products = Product::query()
                                            ->where('name', 'like', "%{$state}%")
                                            ->orWhere('description', 'like', "%{$state}%")
                                            ->orWhereHas('category', fn ($q) => $q->where('name', 'like', "%{$state}%"))
                                            ->with(['weddingOrganizer', 'category'])
                                            ->limit(20)
                                            ->get();

                                        if ($products->isEmpty()) {
                                            session()->forget(['cbir_mixed_results', 'cbir_product_results_ids', 'cbir_search_time', 'cbir_context']);
                                            $set('status_message', __('Tidak ada produk yang cocok untuk pencarian teks.'));
                                            $livewire->dispatch('refresh_items');
                                            $livewire->dispatch('refresh_catalog');
                                            return;
                                        }

                                        $mixedResults = $products->map(function ($model) {
                                            return [
                                                'type' => 'product',
                                                'similarity' => 100,
                                                'data' => array_merge($model->toArray(), [
                                                    'image_url' => $model->image_url,
                                                    'wedding_organizer' => $model->weddingOrganizer?->toArray(),
                                                ]),
                                            ];
                                        })->all();

                                        session()->put('cbir_mixed_results', $mixedResults);
                                        session()->put('cbir_product_results_ids', collect($mixedResults)->pluck('data.id')->all());
                                        session()->put('cbir_search_time', 0);
                                        session()->put('cbir_context', 'product');

                                        $set('status_message', __('Berhasil menemukan :count hasil teks!', ['count' => count($mixedResults)]));
                                        $livewire->dispatch('refresh_items');
                                        $livewire->dispatch('refresh_catalog');
                                    })
                                    ->suffixActions([
                                        Forms\Components\Actions\Action::make('toggle_camera_search')
                                            ->icon('heroicon-o-camera')
                                            ->color('gray')
                                            ->tooltip(__('Ambil Foto'))
                                            ->action(fn (Forms\Set $set, Forms\Get $get) => $set('show_camera', ! $get('show_camera'))),
                                        Forms\Components\Actions\Action::make('toggle_gallery_search')
                                            ->icon('heroicon-o-photo')
                                            ->color('gray')
                                            ->tooltip(__('Pilih Galeri'))
                                            ->action(fn (Forms\Set $set, Forms\Get $get) => $set('show_upload', ! $get('show_upload'))),
                                    ]),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                TakePicture::make('camera_image')
                                    ->hiddenLabel()
                                    ->visible(fn (Forms\Get $get) => $get('show_camera'))
                                    ->live()
                                    ->disk('public')
                                    ->directory('cbir-camera')
                                    ->afterStateUpdated(function (Component $livewire, $state, Forms\Set $set, CBIRService $cbirService) {
                                        if (! $state) {
                                            return;
                                        }
                                        $filePath = storage_path('app/public/'.$state);
                                        if (! file_exists($filePath)) {
                                            return;
                                        }
                                        $file = new File($filePath);
                                        $response = $cbirService->searchByImage($file, 20);

                                        if (isset($response['error']) || ! ($response['success'] ?? false)) {
                                            $set('status_message', $response['message'] ?? __('Server AI Offline.'));

                                            return;
                                        }

                                        $results = $response['results'] ?? [];
                                        if (! empty($results)) {
                                            $searchTime = $response['query_time_seconds'] ?? 0;
                                            $mixedResults = PackageResource::buildCbirMixedResults($results);

                                            session()->put('cbir_mixed_results', $mixedResults);
                                            session()->put('cbir_product_results_ids', collect($mixedResults)->where('type', 'product')->pluck('data.id')->all());
                                            session()->put('cbir_search_time', $searchTime);
                                            session()->put('cbir_context', 'product');

                                            $topScore = number_format(($mixedResults[0]['similarity'] ?? 0), 1);
                                            $set('status_message', __('Berhasil menemukan :count hasil! Akurasi: :score%', ['count' => count($mixedResults), 'score' => $topScore]));
                                            $livewire->dispatch('refresh_items');
                                            $livewire->dispatch('refresh_catalog');
                                        } else {
                                            session()->forget(['cbir_mixed_results', 'cbir_product_results_ids', 'cbir_search_time', 'cbir_context']);
                                            $set('status_message', __('Tidak ada product yang cocok.'));
                                            $livewire->dispatch('refresh_items');
                                            $livewire->dispatch('refresh_catalog');
                                        }
                                    }),

                                Forms\Components\FileUpload::make('search_image')
                                    ->hiddenLabel()
                                    ->image()
                                    ->imageEditor()
                                    ->visible(fn (Forms\Get $get) => $get('show_upload'))
                                    ->directory('cbir-queries')
                                    ->live()
                                    ->afterStateUpdated(function (Component $livewire, $state, Forms\Set $set, CBIRService $cbirService) {
                                        if (! $state) {
                                            return;
                                        }
                                        $fileObj = is_array($state) ? reset($state) : $state;
                                        $filePath = $fileObj instanceof TemporaryUploadedFile
                                            ? $fileObj->getRealPath()
                                            : storage_path('app/public/'.$fileObj);

                                        if (! file_exists($filePath)) {
                                            return;
                                        }

                                        $file = new File($filePath);
                                        $response = $cbirService->searchByImage($file, 20);

                                        if (isset($response['error']) || ! ($response['success'] ?? false)) {
                                            $set('status_message', $response['message'] ?? __('Server AI Offline.'));

                                            return;
                                        }

                                        $results = $response['results'] ?? [];
                                        if (! empty($results)) {
                                            $searchTime = $response['query_time_seconds'] ?? 0;
                                            $mixedResults = PackageResource::buildCbirMixedResults($results);

                                            session()->put('cbir_mixed_results', $mixedResults);
                                            session()->put('cbir_product_results_ids', collect($mixedResults)->where('type', 'product')->pluck('data.id')->all());
                                            session()->put('cbir_search_time', $searchTime);
                                            session()->put('cbir_context', 'product');

                                            $topScore = number_format(($mixedResults[0]['similarity'] ?? 0), 1);
                                            $set('status_message', __('Berhasil menemukan :count hasil! Akurasi: :score%', ['count' => count($mixedResults), 'score' => $topScore]));
                                            $livewire->dispatch('refresh_items');
                                            $livewire->dispatch('refresh_catalog');
                                        } else {
                                            session()->forget(['cbir_mixed_results', 'cbir_product_results_ids', 'cbir_search_time', 'cbir_context']);
                                            $set('status_message', __('Product tidak ditemukan.'));
                                            $livewire->dispatch('refresh_items');
                                            $livewire->dispatch('refresh_catalog');
                                        }
                                    }),

                                Forms\Components\Placeholder::make('status_message')
                                    ->label('')
                                    ->content(fn (Forms\Get $get) => new HtmlString(
                                        '<div class="text-sm">'.e($get('status_message')).'</div>'
                                    ))
                                    ->visible(fn (Forms\Get $get) => (bool) $get('status_message'))
                                    ->extraAttributes(['class' => 'text-center p-3 bg-primary-600 rounded-xl text-white font-medium shadow-md']),

                                // ── CBIR Results Preview ──
                                Forms\Components\View::make('filament.user.components.cbir-results-preview')
                                    ->visible(fn () => ! empty(session('cbir_mixed_results'))),
                            ]),
                    ]),
                Tables\Actions\Action::make('clear_visual_search')
                    ->label(__('Reset'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (Component $livewire) {
                        session()->forget(['cbir_mixed_results', 'cbir_product_results_ids', 'cbir_search_time', 'cbir_context']);
                        $livewire->dispatch('refresh_items');
                    })
                    ->visible(fn () => session()->has('cbir_mixed_results')),
            ])
            ->emptyStateHeading(__('Belum ada product tersedia'))
            ->emptyStateDescription(function () {
                if (session()->has('cbir_product_results_ids')) {
                    return new HtmlString((string) __('Tidak ada product yang cocok dengan foto Anda. Silakan coba foto lain.'));
                }

                return new HtmlString((string) __('Temukan product impianmu di sini!'));
            })
            ->emptyStateActions([
                Tables\Actions\Action::make('reset_search')
                    ->label(__('Tampilkan Semua'))
                    ->action(function (Component $livewire) {
                        session()->forget(['cbir_mixed_results', 'cbir_product_results_ids', 'cbir_search_time', 'cbir_context']);
                        $livewire->dispatch('refresh_items');
                    })
                    ->visible(fn () => session()->has('cbir_product_results_ids')),
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\ImageColumn::make('image_url')
                            ->label('')
                            ->height('220px')
                            ->width('100%')
                            ->extraAttributes(['class' => 'w-full flex justify-center products-center bg-gray-50 dark:bg-gray-800 rounded-t-2xl overflow-hidden fi-card-img-wrap'])
                            ->extraImgAttributes([
                                'class' => 'object-cover transition-all duration-500 group-hover:scale-110 !mx-auto fi-card-img',
                                'style' => 'width: 100%; object-fit: cover;',
                            ]),

                        Tables\Columns\TextColumn::make('discount_pct')
                            ->state(fn ($record) => $record?->discount_price > 0 ? '-'.round((($record->price - $record->discount_price) / $record->price) * 100).'%' : null)
                            ->extraAttributes([
                                'class' => 'absolute top-2 right-2 font-black px-2 py-1 rounded shadow-lg transform scale-100 group-hover/img-overlay:scale-110 transition-transform duration-300',
                                'style' => 'background-color: #dc2626 !important; color: #ffffff !important; width: fit-content; font-size: 0.8rem; line-height: 1; pointer-events: none; visibility: visible !important;',
                            ])
                            ->visible(fn ($record) => $record?->discount_price > 0),
                    ])->extraAttributes(['class' => 'relative overflow-hidden group/img-overlay']),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('category.name')
                            ->badge()
                            ->color('info')
                            ->size('xs')
                            ->extraAttributes(['class' => 'mt-1 mb-1']),
                        Tables\Columns\TextColumn::make('name')
                            ->weight('bold')
                            ->size('xs')
                            ->lineClamp(2),
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('final_price')
                                ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.'))
                                ->weight('black')
                                ->color('primary')
                                ->size('xs'),
                            Tables\Columns\TextColumn::make('price')
                                ->formatStateUsing(fn ($state, $record) => $record?->discount_price > 0 ? 'Rp '.number_format($state, 2, ',', '.') : '')
                                ->size('xs')
                                ->color('danger')
                                ->extraAttributes(['class' => 'line-through opacity-60'])
                                ->visible(fn ($record) => $record?->discount_price > 0),
                        ])->space(0.5),
                        Tables\Columns\TextColumn::make('stock')
                            ->formatStateUsing(fn ($state) => $state > 0 ? $state.' '.__('Tersedia') : __('Habis'))
                            ->size('xs')
                            ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'gray')),
                    ])->space(1)->extraAttributes(['class' => 'p-2.5 flex-1 flex flex-col']),
                ])->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 border border-transparent dark:border-white/10 overflow-hidden',
                ]),
            ])
            ->contentGrid([
                'default' => 2,
                'md' => 4,
                'lg' => 5,
                'xl' => 6,
            ])
            ->filters([
                SelectFilter::make('category')
                    ->searchable()
                    ->label(__('Kategori'))
                    ->relationship('category', 'name')
                    ->preload(),

                SelectFilter::make('has_discount')
                    ->searchable()
                    ->label(__('Diskon'))
                    ->options([
                        'yes' => __('Ada Diskon'),
                        'no'  => __('Tanpa Diskon'),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'yes' => $query->where('discount_price', '>', 0),
                        'no'  => $query->where(fn ($q) => $q->whereNull('discount_price')->orWhere('discount_price', 0)),
                        default => $query,
                    }),

                SelectFilter::make('in_stock')
                    ->searchable()
                    ->label(__('Ketersediaan'))
                    ->options([
                        'yes' => __('Tersedia'),
                        'no'  => __('Habis'),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'yes' => $query->where('stock', '>', 0),
                        'no'  => $query->where('stock', '<=', 0),
                        default => $query,
                    }),

                SelectFilter::make('sort_by')
                    ->searchable()
                    ->label(__('Urutkan'))
                    ->options([
                        'latest'       => __('Terbaru'),
                        'price_asc'    => __('Harga: Terendah'),
                        'price_desc'   => __('Harga: Tertinggi'),
                        'most_ordered' => __('Paling Banyak Dipesan'),
                    ])
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? null) {
                        'price_asc'    => $query->reorder('price', 'asc'),
                        'price_desc'   => $query->reorder('price', 'desc'),
                        'latest'       => $query->reorder('created_at', 'desc'),
                        'most_ordered' => $query->withCount('orders')->reorder('orders_count', 'desc'),
                        default        => $query,
                    }),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContentCollapsible)
            // ->actions([
            //     Tables\Actions\Action::make('view_detail')
            //         ->label(__('Lihat Detail'))
            //         ->color('warning')
            //         ->button()
            //         ->size('sm')
            //         ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

            //     Tables\Actions\Action::make('buy_now')
            //         ->label(__('Beli'))
            //         ->button()
            //         ->color('success')
            //         ->icon('heroicon-m-bolt')
            //         ->size('sm')
            //         ->extraAttributes(['class' => 'flex-1 justify-center rounded-lg shadow-sm font-bold'])
            //         ->disabled(fn (Product $record) => $record->stock <= 0)
            //         ->slideOver()
            //         ->modalWidth('2xl')
            //         ->modalHeading(__('Checkout Produk'))
            //         ->steps(fn (Product $record) => static::getCheckoutWizardSteps($record))
            //         ->action(function (Product $record, array $data, Component $livewire) {
            //             return static::handleCheckout($record, $data, $livewire);
            //         }),

            //     Tables\Actions\Action::make('add_to_cart')
            //         ->label('')
            //         ->button()
            //         ->size('sm')
            //         ->icon('heroicon-o-shopping-cart')
            //         ->color('warning')
            //         ->extraAttributes(['class' => 'justify-center rounded-lg shadow-sm'])
            //         ->action(function ($record) {
            //             Cart::updateOrCreate([
            //                 'user_id' => auth()->id(),
            //                 'product_id' => $record->id,
            //             ], [
            //                 'quantity' => DB::raw('quantity + 1'),
            //             ]);

            //             Notification::make()
            //                 ->title(__('Berhasil masuk keranjang'))
            //                 ->success()
            //                 ->icon('heroicon-o-shopping-cart')
            //                 ->send();
            //         })
            //         ->tooltip(__('Masukkan ke Keranjang')),

            //     Tables\Actions\Action::make('toggle_wishlist')
            //         ->label('')
            //         ->button()
            //         ->size('sm')
            //         ->icon(fn ($record) => $record->is_wishlisted ? 'heroicon-s-heart' : 'heroicon-o-heart')
            //         ->color(fn ($record) => $record->is_wishlisted ? 'danger' : 'gray')
            //         ->extraAttributes(['class' => 'justify-center rounded-lg shadow-sm'])
            //         ->action(fn ($record, Component $livewire) => $livewire->dispatch('toggle_wishlist', id: $record->id))
            //         ->tooltip(__('Simpan Favorit')),
            // ])
            ->actionsAlignment('center')
            ->extraAttributes([
                'class' => 'filament-table-actions-container !flex !flex-row !gap-1 !p-3 !bg-gray-50/50 dark:!bg-white/5 !border-0',
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(12)
                            ->schema([
                                // LEFT: PRODUCT IMAGE
                                Group::make([
                                    Infolists\Components\ImageEntry::make('image_url')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->alignCenter()
                                        ->height('18rem')
                                        ->extraAttributes(['class' => 'flex products-center justify-center bg-white/5 rounded-3xl overflow-hidden border border-white/10 shadow-inner'])
                                        ->extraImgAttributes([
                                            'class' => 'max-w-full max-h-full object-contain mx-auto transition-transform hover:scale-105 duration-500 p-2',
                                        ]),
                                ])->columnSpan([
                                    'default' => 12,
                                    'md' => 5,
                                ]),

                                // RIGHT: PRODUCT IDENTITY
                                Group::make([
                                    // CATEGORY BADGE
                                    Infolists\Components\TextEntry::make('category.name')
                                        ->formatStateUsing(fn ($state) => __($state))
                                        ->label('')
                                        ->badge()
                                        ->color('info')
                                        ->icon('heroicon-m-tag')
                                        ->extraAttributes(['class' => 'mb-2']),

                                    // PRODUCT NAME
                                    Infolists\Components\TextEntry::make('name')
                                        ->formatStateUsing(fn ($state) => __($state))
                                        ->label('')
                                        ->hiddenLabel()
                                        ->weight('black')
                                        ->size('2xl')
                                        ->extraAttributes(['class' => 'tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400 mb-4 uppercase leading-tight']),

                                    // PRICE DISPLAY
                                    Group::make([
                                        Infolists\Components\TextEntry::make('final_price')
                                            ->label('')
                                            ->formatStateUsing(fn ($state) => 'Rp '.number_format($state, 2, ',', '.'))
                                            ->size('2xl')
                                            ->weight('black')
                                            ->color('success')
                                            ->extraAttributes(['class' => 'drop-shadow-sm']),

                                        Infolists\Components\TextEntry::make('price')
                                            ->label('')
                                            ->formatStateUsing(fn ($record) => $record?->discount_price > 0 ? 'Rp '.number_format($record->price, 2, ',', '.') : '')
                                            ->size('sm')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'line-through opacity-50 ml-4'])
                                            ->visible(fn ($record) => $record?->discount_price > 0),
                                    ])->extraAttributes(['class' => 'flex products-baseline mb-6']),

                                    // DESCRIPTION
                                    Infolists\Components\Section::make(__('Tentang Product Ini'))
                                        ->compact()
                                        ->schema([
                                            Infolists\Components\TextEntry::make('description')
                                                ->formatStateUsing(fn ($state) => __($state))
                                                ->label('')
                                                ->html()
                                                ->prose()
                                                ->extraAttributes(['class' => 'text-gray-600 dark:text-gray-300 leading-relaxed text-sm']),
                                        ])->icon('heroicon-o-document-text')->iconColor('primary'),

                                    // PRIMARY CTA: BUY & CART
                                    Actions::make([
                                        Action::make('buy_now_detail')
                                            ->label(fn ($record) => $record->stock > 0 ? __('Pesan Sekarang') : __('Stok Habis'))
                                            ->icon(fn ($record) => $record->stock > 0 ? 'heroicon-m-bolt' : 'heroicon-m-x-circle')
                                            ->button()
                                            ->color(fn ($record) => $record->stock > 0 ? 'success' : 'danger')
                                            ->disabled(fn ($record) => $record->stock <= 0)
                                            ->size(ActionSize::Large)
                                            ->extraAttributes(['class' => 'w-full py-2 text-sm rounded-xl shadow-sm transition-all'])
                                            ->slideOver()
                                            ->modalWidth('2xl')
                                            ->modalHeading(__('Checkout Product'))
                                            ->steps(fn ($record) => static::getCheckoutWizardSteps($record))
                                            ->action(function ($record, array $data, Component $livewire) {
                                                return static::handleCheckout($record, $data, $livewire);
                                            }),

                                        Action::make('add_to_cart_detail')
                                            ->label(__('Masukkan ke Keranjang'))
                                            ->icon('heroicon-m-shopping-cart')
                                            ->button()
                                            ->color('warning')
                                            ->outlined()
                                            ->size(ActionSize::Large)
                                            ->extraAttributes(['class' => 'w-full py-2 text-sm rounded-xl shadow-sm transition-all'])
                                            ->action(function ($record) {
                                                Cart::updateOrCreate([
                                                    'user_id' => auth()->id(),
                                                    'product_id' => $record->id,
                                                ], [
                                                    'quantity' => DB::raw('quantity + 1'),
                                                ]);

                                                Notification::make()
                                                    ->title(__('Berhasil masuk keranjang'))
                                                    ->success()
                                                    ->icon('heroicon-o-shopping-cart')
                                                    ->send();
                                            })
                                            ->visible(fn ($record) => $record->stock > 0),
                                    ])->fullWidth()->extraAttributes(['class' => '!mb-0']),

                                    // SECONDARY: CHAT & WISHLIST
                                    Actions::make([
                                        Action::make('chat_admin')
                                            ->label(__('Chat Admin'))
                                            ->icon('heroicon-m-chat-bubble-left-right')
                                            ->button()
                                            ->color('info')
                                            ->outlined()
                                            ->size(ActionSize::Large)
                                            ->extraAttributes(['class' => 'w-full flex-1 rounded-xl py-2 text-sm shadow-sm transition-all'])
                                            ->action(function ($record) {
                                                $inbox = ChatService::getOrCreateInboxWithAdmin(auth()->id());
                                                ChatService::sendContextMessage($inbox, [
                                                    'type' => 'product',
                                                    'id' => $record->id,
                                                    'name' => $record->name,
                                                    'price' => $record->final_price,
                                                    'image' => $record->getFirstMediaUrl('product_image') ?: $record->image_url,
                                                    'url' => ProductResource::getUrl('view', ['record' => $record->id]),
                                                ]);

                                                return redirect(MessagesPage::getUrl(['id' => $inbox->id]));
                                            }),

                                        Action::make('wishlist_detail')
                                            ->label(fn ($record) => $record->is_wishlisted ? __('Hapus dari Favorit') : __('Tambah ke Favorit'))
                                            ->icon(fn ($record) => $record->is_wishlisted ? 'heroicon-s-heart' : 'heroicon-o-heart')
                                            ->button()
                                            ->color(fn ($record) => $record->is_wishlisted ? 'danger' : 'gray')
                                            ->outlined(fn ($record) => ! $record->is_wishlisted)
                                            ->size(ActionSize::Large)
                                            ->extraAttributes(['class' => 'w-full flex-1 rounded-xl py-2 text-sm shadow-sm transition-all duration-300'])
                                            ->action(function ($record) {
                                                $userId = Filament::auth()->id();
                                                $deleted = Wishlist::query()->where('user_id', $userId)
                                                    ->where('product_id', $record->id)
                                                    ->delete();

                                                if ($deleted) {
                                                    Notification::make()
                                                        ->title(__('Dihapus dari Favorit'))
                                                        ->warning()
                                                        ->icon('heroicon-o-heart')
                                                        ->send();
                                                } else {
                                                    Wishlist::create([
                                                        'user_id' => $userId,
                                                        'product_id' => $record->id,
                                                    ]);
                                                    Notification::make()
                                                        ->title(__('Disimpan ke Favorit'))
                                                        ->success()
                                                        ->icon('heroicon-s-heart')
                                                        ->iconColor('danger')
                                                        ->send();
                                                }
                                            }),
                                    ])->fullWidth()->extraAttributes(['class' => '!mt-2']),
                                ])->columnSpan([
                                    'default' => 12,
                                    'md' => 7,
                                ]),
                            ])
                            ->extraAttributes(['class' => 'gap-10 p-2']),
                    ])
                    ->extraAttributes(['class' => 'border-none bg-transparent shadow-none']),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }

    public static function handleCheckout(Product $product, array $data, ?Component $livewire = null): mixed
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return null;
        }

        // Update user phone if changed
        if ($data['phone'] !== $user->phone) {
            $user->update(['phone' => $data['phone']]);
        }

        // Stock Check
        if ($product->stock <= 0) {
            Notification::make()
                ->title(__('Stok Habis'))
                ->body(__('Mohon maaf, product ini sudah tidak tersedia.'))
                ->danger()
                ->send();

            return null;
        }

        // Decrease Stock
        $product->decrement('stock', 1);

        $finalPrice = (float) $product->final_price;

        // Default statuses
        $orderStatus = OrderStatus::PENDING;
        $orderPaymentStatus = OrderPaymentStatus::PENDING;

        // Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'order_number' => 'ORD-ITM-'.strtoupper(str()->random(8)),
            'total_price' => $finalPrice,
            'status' => $orderStatus,
            'payment_status' => $orderPaymentStatus,
            'booking_date' => $data['booking_date'],
            'notes' => $data['notes'],
        ]);

        // Send message to Admin Panel Chat
        try {
            $inbox = ChatService::getOrCreateInboxWithAdmin($user->id);
            ChatService::sendOrderMessage($inbox, $order);
        } catch (\Exception $e) {
            Log::error('Failed to send order message: '.$e->getMessage());
        }

        // Process Transaction
        $reference = 'TRX-ITM-'.time().'-'.strtoupper(str()->random(4));

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'type' => 'order',
            'reference_number' => $reference,
            'amount' => $finalPrice,
            'admin_fee' => 0,
            'total_amount' => $finalPrice,
            'payment_gateway' => 'midtrans',
            'status' => 'pending',
            'notes' => null,
        ]);

        // Process via Midtrans
        try {
            $midtrans = new MidtransService;
            $transactionCount = $midtrans->createTransactionSnap($transaction);

            if ($livewire) {
                $livewire->dispatch('open-midtrans-snap', token: $transactionCount->snap_token);

                return null;
            }

            return redirect($transactionCount->payment_url);
        } catch (\Exception $e) {
            Log::error('[Midtrans] Product Checkout Redirect failed', [
                'reference' => $transaction->reference_number,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title(__('Gagal Membuat Pembayaran'))
                ->body(__('Midtrans error: '.$e->getMessage().'. Transaksi Anda tersimpan, silakan ulangi pembayaran di "Pesanan Saya".'))
                ->danger()
                ->send();

            return redirect()->route('filament.user.resources.orders.index');
        }
    }

    public static function getCheckoutWizardSteps(Product $product): array
    {
        return [
            Forms\Components\Wizard\Step::make(__('Detail Acara'))
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Forms\Components\Section::make(__('Pilih Waktu & Kebutuhan'))
                        ->schema([
                            Forms\Components\DatePicker::make('booking_date')
                                ->label(__('Rencana Tanggal Acara'))
                                ->required()
                                ->native(false)
                                ->minDate(now()->addDays(7))
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('notes')
                                ->label(__('Catatan Khusus / Alamat Lokasi'))
                                ->rows(4)
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]),
            Forms\Components\Wizard\Step::make(__('Info Kontak'))
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Forms\Components\Section::make(__('Verifikasi Data Anda'))
                        ->schema([
                            Forms\Components\TextInput::make('customer_name')
                                ->label(__('Nama Lengkap'))
                                ->default(auth()->user()?->name)
                                ->required(),
                            Forms\Components\TextInput::make('phone')
                                ->label(__('Nomor WhatsApp'))
                                ->default(auth()->user()?->phone)
                                ->tel()
                                ->required(),
                        ])->columns(2),
                ]),
            Forms\Components\Wizard\Step::make(__('Voucher & Diskon'))
                ->icon('heroicon-o-ticket')
                ->schema([
                    Forms\Components\Section::make(__('Pilih Voucher Anda'))
                        ->description(__('Gunakan voucher yang telah Anda klaim di menu Voucher.'))
                        ->icon('heroicon-o-ticket')
                        ->schema([
                            Forms\Components\Select::make('voucher_id')
                                ->searchable()
                                ->label(__('Voucher Tersedia'))
                                ->prefixIcon('heroicon-o-ticket')
                                ->options(function () {
                                    $user = Filament::auth()->user();
                                    if (! $user) {
                                        return [];
                                    }

                                    return Voucher::query()->where('is_active', true)
                                        ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                                        ->where(function ($q) use ($user) {
                                            $q->where('is_global', true)
                                                ->orWhereHas('users', fn ($u) => $u->where('users.id', $user->id));
                                        })
                                        ->get()
                                        ->pluck('name', 'id');
                                })
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set) {
                                    $set('applied_voucher', $state);
                                }),
                        ]),
                ]),
        ];
    }

    /**
     * Format similarity score to specific percentage steps.
     */
    public static function formatSimilarityPct(float $score): int
    {
        $pct = (int) (round($score * 100 / 5) * 5);
        if ($pct === 30) {
            $pct = 35;
        }

        return min(100, max(0, $pct));
    }
}
