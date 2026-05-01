@php
    use App\Enums\Messages\MediaCollectionType;
@endphp
@props(['selectedConversation'])
<!-- Right Section (Chat Box) -->
<div class="w-full h-full bg-white rounded-xl dark:divide-white/10 dark:bg-gray-900 overflow-hidden flex flex-col">
    @if ($selectedConversation)
        <!-- Chat Header : Start -->
        <div class="grid grid-cols-[--cols-default] lg:grid-cols-[--cols-lg] p-6"
            style="--cols-default: repeat(1, minmax(0, 1fr)); --cols-lg: repeat(1, minmax(0, 1fr));">
            <div style="--col-span-default: 1 / -1;" class="col-[--col-span-default]">
                <div class="flex gap-4 items-center">
                @if ($this->panelId === 'admin')
                    <x-filament::icon-button
                        icon="heroicon-o-chevron-left"
                        color="gray"
                        size="md"
                        class="-ms-2"
                        href="{{ \App\Filament\Admin\Pages\MessagesPage::getUrl() }}"
                        tag="a"
                        wire:navigate
                    />
                @endif


                    @php
                        $avatar = $selectedConversation->primary_avatar;
                        $alt = urlencode($selectedConversation->inbox_title);
                    @endphp

                    <x-filament::avatar src="{{ $avatar }}" alt="{{ $alt }}" size="lg" />

                    <div class="flex-1 overflow-hidden">
                        <div class="flex justify-between items-center gap-2">
                            <p class="text-base font-bold truncate text-gray-900 dark:text-white">{{ $selectedConversation->inbox_title }}</p>
                        </div>

                        @if ($selectedConversation->title)
                            <p class="text-sm truncate text-gray-600 dark:text-gray-400">
                                {{ $selectedConversation->other_users->pluck('name')->implode(', ') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- Chat Header : End -->
        <!-- Chat Box : Start -->
        <div wire:poll.visible.{{ $pollInterval }}="pollMessages()" id="chatContainer"
            class="flex flex-col-reverse flex-1 p-5 overflow-y-auto">
            @foreach ($conversationMessages as $index => $message)
                <div @class([
                    'flex mb-2 px-2 items-end gap-2',
                    'justify-end' => $message->user_id === auth()->id(),
                    'justify-start' => $message->user_id !== auth()->id(),
                ]) wire:key="{{ $message->id }}">
                    @if ($message->user_id !== auth()->id())
                        @php
                            $avatar = $message->sender->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($message->sender->name);
                            $alt = urlencode($message->sender->name);
                        @endphp
                        <x-filament::avatar src="{{ $avatar }}" alt="{{ $alt }}" size="sm" />

                    @endif
                    <div>
                        @if ($message->user_id !== auth()->id())
                            <p class="text-xs mb-2 text-gray-500 dark:text-gray-400">{{ $message->sender->name }}</p>
                        @endif
                        <div @class([
                            'max-w-md p-2 rounded-xl mb-2',
                            'text-white bg-primary-600 dark:bg-primary-500' =>
                                $message->user_id === auth()->id(),
                            'text-gray-900 dark:text-white bg-gray-100 dark:bg-gray-500' =>
                                $message->user_id !== auth()->id(),
                        ]) @style([
                            'border-bottom-right-radius: 0' => $message->user_id === auth()->id(),
                            'border-bottom-left-radius: 0' => $message->user_id !== auth()->id(),
                        ])>
                            <div class="px-1">
                                @if ($message->meta && isset($message->meta['type']))
                                    @php
                                        $meta = $message->meta;
                                        $itemImage = $meta['image'] ?? null;
                                        
                                        // If image is missing or broken, try to fetch it from the model
                                        if (!$itemImage || str_contains($itemImage, 'placeholder')) {
                                            $modelClass = $meta['type'] === 'product' ? \App\Models\Product::class : \App\Models\Package::class;
                                            $item = $modelClass::find($meta['id']);
                                            if ($item) {
                                                $itemImage = $item->image_url;
                                            }
                                        }

                                        if (!$itemImage || $itemImage === '') {
                                            $itemImage = 'https://ui-avatars.com/api/?name=' . urlencode($meta['name']) . '&background=f3f4f6&color=a1a1aa&size=128';
                                        }
                                    @endphp
                                    <div class="mb-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden group max-w-sm">
                                        <div class="flex items-center p-3 gap-3">
                                            <div class="relative w-16 h-16 flex-shrink-0">
                                                <img src="{{ $itemImage }}" 
                                                     class="w-full h-full rounded-lg object-cover border border-gray-100 dark:border-gray-600" 
                                                     alt="{{ $meta['name'] }}"
                                                     onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($meta['name']) }}&background=f3f4f6&color=a1a1aa&size=128'">
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start gap-2">
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-[9px] text-primary-600 dark:text-primary-400 font-black tracking-tighter mb-0.5">
                                                            @if(isset($meta['is_order']) && $meta['is_order'])
                                                                {{ __('Pesanan') }} #{{ $meta['order_number'] }}
                                                            @else
                                                                {{ __($meta['type'] == 'product' ? 'Produk' : 'Paket') }}
                                                            @endif
                                                        </p>
                                                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                            {{ $meta['name'] }}
                                                        </p>
                                                        <p class="text-xs font-black text-orange-600 dark:text-orange-400 mt-0.5">
                                                            Rp {{ number_format($meta['price'], 0, ',', '.') }}
                                                        </p>
                                                    </div>
                                                    <div class="flex-shrink-0 self-center">
                                                        <a href="{{ $meta['url'] }}"
                                                           wire:navigate
                                                           class="inline-flex items-center px-3 py-1.5 text-[11px] bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-400 text-white hover:text-white visited:text-white rounded-lg font-bold transition-all shadow-sm active:scale-95 whitespace-nowrap">
                                                            {{ isset($meta['is_order']) && $meta['is_order'] ? __('Ubah Pesanan') : __('Detail') }}
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if ($message->message)
                                    @php
                                        $displayMessage = $message->message;
                                        $meta = $message->meta ?? [];

                                        if (is_array($meta) && isset($meta['type'], $meta['name']) && ! isset($meta['is_order'])) {
                                            $displayMessage = __('Saya menanyakan tentang :itemType ini: :name', [
                                                'itemType' => __($meta['type'] === 'product' ? 'Produk' : 'Paket'),
                                                'name' => $meta['name'],
                                            ]);
                                        }

                                        if (is_array($meta) && ! empty($meta['is_order']) && isset($meta['order_number'])) {
                                            $displayMessage = __('Halo Admin, saya baru saja membuat pesanan baru dengan nomor: :orderNumber', [
                                                'orderNumber' => $meta['order_number'],
                                            ]);
                                        }
                                    @endphp
                                    <p class="text-sm">
                                        {!! nl2br(e($displayMessage)) !!}
                                    </p>
                                @endif
                                @if (
                                    $message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value) &&
                                        count($message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value)) > 0)
                                    @foreach ($message->getMedia(MediaCollectionType::FILAMENT_MESSAGES->value) as $index => $media)
                                        @php
                                            $isImage = $this->validateImage($media->file_name);
                                        @endphp
                                        
                                        @if($isImage)
                                            <div class="my-2 relative group">
                                                <img src="{{ $media->getUrl() }}" 
                                                     class="rounded-lg max-w-full h-auto cursor-pointer border border-white/20 shadow-sm"
                                                     wire:click="downloadAttachment({{ $media->id }})" />
                                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center rounded-lg pointer-events-none">
                                                     <x-filament::icon icon="heroicon-o-arrow-down-tray" class="w-6 h-6 text-white" />
                                                </div>
                                            </div>
                                        @else
                                            <div wire:click="downloadAttachment({{ $media->id }})"
                                                @class([
                                                    'flex items-center gap-2 p-2 my-2 rounded-lg group cursor-pointer',
                                                    'bg-gray-200 dark:bg-gray-600' => $message->user_id !== auth()->id(),
                                                    'bg-primary-500 dark:bg-primary-400' => $message->user_id === auth()->id(),
                                                ])>
                                                <div @class([
                                                    'p-2 rounded-full',
                                                    'bg-gray-100 dark:bg-gray-500' => $message->user_id !== auth()->id(),
                                                    'bg-primary-600 group-hover:bg-primary-700 group-hover:dark:bg-primary-900' =>
                                                        $message->user_id === auth()->id(),
                                                ])>
                                                    @php
                                                        $icon = 'heroicon-o-document';
                                                        if ($this->validateDocument($media->file_name)) {
                                                            $icon = 'heroicon-o-paper-clip';
                                                        }

                                                        if ($this->validateVideo($media->file_name)) {
                                                            $icon = 'heroicon-o-video-camera';
                                                        }

                                                        if ($this->validateAudio($media->file_name)) {
                                                            $icon = 'heroicon-o-speaker-wave';
                                                        }
                                                    @endphp
                                                    <x-filament::icon icon="{{ $icon }}" class="w-4 h-4" />
                                                </div>
                                                <p class="text-sm">
                                                    {{ $media->file_name }}
                                                </p>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <div @class([
                            'flex items-center gap-1.5 mt-1',
                            'justify-end' => $message->user_id === auth()->id(),
                            'justify-start' => $message->user_id !== auth()->id(),
                        ])>
                            <p @class([
                                'text-[10px] opacity-70',
                                'text-white/80' => $message->user_id === auth()->id(),
                                'text-gray-500 dark:text-gray-400' => $message->user_id !== auth()->id(),
                            ])>
                                @php
                                    $createdAt = \Carbon\Carbon::parse($message->created_at)->setTimezone(
                                        config('messages.timezone', 'app.timezone'),
                                    );

                                    if ($createdAt->isToday()) {
                                        $date = $createdAt->format('H:i');
                                    } else {
                                        $date = $createdAt->format('d/m/y H:i');
                                    }
                                @endphp
                                {{ $date }}
                            </p>

                            @if($message->user_id === auth()->id())
                                @php
                                    // Check if anyone else has read it (excluding the sender)
                                    $isRead = !empty($message->read_by) && count(array_filter($message->read_by, fn($id) => $id !== auth()->id())) > 0;
                                @endphp
                                <div class="flex items-center">
                                    @if($isRead)
                                        <x-filament::icon icon="heroicon-m-check-badge" class="w-3 h-3 text-white" />
                                        <span class="text-[9px] text-white/70 ml-1">{{ __('Dilihat') }}</span>
                                    @else
                                        <x-filament::icon icon="heroicon-m-check" class="w-3 h-3 text-white/50" />
                                        <span class="text-[9px] text-white/50 ml-1">{{ __('Terkirim') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @php
                    $nextMessage = $conversationMessages[$index + 1] ?? null;
                    $nextMessageDate = $nextMessage
                        ? \Carbon\Carbon::parse($nextMessage->created_at)
                            ->setTimezone(config('messages.timezone', 'app.timezone'))
                            ->format('Y-m-d')
                        : null;
                    $currentMessageDate = \Carbon\Carbon::parse($message->created_at)
                        ->setTimezone(config('messages.timezone', 'app.timezone'))
                        ->format('Y-m-d');
                    $showDateBadge = $currentMessageDate !== $nextMessageDate;
                @endphp
                @if ($showDateBadge)
                    <div class="flex justify-center my-4">
                        <x-filament::badge>
                            {{ \Carbon\Carbon::parse($message->created_at)->setTimezone(config('messages.timezone', 'app.timezone'))->translatedFormat('F j, Y') }}
                        </x-filament::badge>
                    </div>
                @endif
            @endforeach
            @if ($this->paginator->hasMorePages())
                <div x-intersect="$wire.loadMessages()">
                    <div class="w-full py-6 text-center text-gray-900 dark:text-gray-200">{{ __('Getting more messages...') }}</div>
                </div>
            @endif
        </div>
        <!-- Chat Box : End -->
        <!-- Chat Input : Start -->
        <div class="w-full p-4 relative">
            <form wire:submit="sendMessage()" class="flex items-end justify-between w-full gap-4">
                <div class="w-full max-h-96 overflow-y-auto p-1">
                    {{ $this->form }}
                </div>
                <div class="p-1">
                    <x-filament::button wire:click="sendMessage()" icon="heroicon-o-paper-airplane"
                        wire:loading.attr="disabled">{{ __('Kirim') }}</x-filament::button>
                </div>
            </form>
            <x-filament-actions::modals />
        </div>
        <!-- Chat Input : End -->

        <!-- Camera Modal : Start -->
        <div id="camera-modal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm" style="display:none;">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden w-full max-w-md mx-4">
                <!-- Modal Header -->
                <div class="flex items-center justify-between px-5 py-4 border-b dark:border-gray-700">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-camera" class="w-5 h-5 text-primary-500" />
                        {{ __('Take a Photo') }}
                    </h3>
                    <button id="close-camera-btn" type="button"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <x-filament::icon icon="heroicon-o-x-mark" class="w-5 h-5" />
                    </button>
                </div>
                <!-- Video / Preview -->
                <div class="relative bg-black">
                    <video id="camera-video" autoplay playsinline
                        class="w-full" style="max-height: 320px; object-fit: cover;"></video>
                    <canvas id="camera-canvas" class="hidden w-full" style="max-height: 320px; object-fit: cover;"></canvas>
                    <!-- Switch camera overlay button -->
                    <button id="switch-camera-btn" type="button"
                        class="absolute top-3 right-3 bg-black/50 hover:bg-black/70 text-white p-2 rounded-full transition">
                        <x-filament::icon icon="heroicon-o-arrow-path" class="w-5 h-5" />
                    </button>
                </div>
                <!-- Controls -->
                <div id="camera-controls-capture" class="flex items-center justify-center gap-4 p-5">
                    <button id="capture-btn" type="button"
                        class="w-16 h-16 bg-primary-600 hover:bg-primary-700 text-white rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105">
                        <x-filament::icon icon="heroicon-o-camera" class="w-7 h-7" />
                    </button>
                </div>
                <div id="camera-controls-preview" class="flex items-center justify-between gap-3 px-5 pb-5" style="display:none;">
                    <button id="retake-btn" type="button"
                        class="flex-1 py-2.5 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition">
                        {{ __('Retake') }}
                    </button>
                    <button id="send-photo-btn" type="button"
                        class="flex-1 py-2.5 rounded-xl bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium transition">
                        {{ __('Send Photo') }}
                    </button>
                </div>
            </div>
        </div>
        <!-- Camera Modal : End -->

    @else
        <div class="flex flex-col items-center justify-center h-full p-3">
            <div class="p-3 mb-4 bg-gray-100 rounded-full dark:bg-gray-500/20">
                <x-filament::icon icon="heroicon-o-x-mark" class="w-6 h-6 text-gray-500 dark:text-gray-400" />
            </div>
            <p class="text-base text-center text-gray-600 dark:text-gray-400">
                {{ __('No selected conversation') }}
            </p>
        </div>
    @endif
</div>
@script
    <script>
        $wire.on('chat-box-scroll-to-bottom', () => {

            chatContainer = document.getElementById('chatContainer');
            chatContainer.scrollTo({
                top: chatContainer.scrollHeight,
                behavior: 'smooth',
            });

            setTimeout(() => {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }, 400);
        });




        let cameraStream = null;
        let facingMode = 'user'; // 'user' = front, 'environment' = back

        async function startCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
            }
            try {
                cameraStream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: facingMode },
                    audio: false
                });
                const video = document.getElementById('camera-video');
                video.srcObject = cameraStream;

                // Reset to live view
                video.classList.remove('hidden');
                document.getElementById('camera-canvas').classList.add('hidden');
                document.getElementById('camera-controls-capture').classList.remove('hidden');
                document.getElementById('camera-controls-preview').style.display = 'none';
            } catch (err) {
                alert('{{ __('Cannot access camera. Please allow camera permission.') }}');
                closeCameraModal();
            }
        }

        function closeCameraModal() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(t => t.stop());
                cameraStream = null;
            }
            document.getElementById('camera-modal').style.display = 'none';
        }


        window.addEventListener('open-camera', () => {
            document.getElementById('camera-modal').style.display = 'flex';
            facingMode = 'environment';
            startCamera();
        });

        document.getElementById('close-camera-btn').addEventListener('click', closeCameraModal);

        document.getElementById('switch-camera-btn').addEventListener('click', () => {
            facingMode = facingMode === 'user' ? 'environment' : 'user';
            startCamera();
        });

        document.getElementById('capture-btn').addEventListener('click', () => {
            const video = document.getElementById('camera-video');
            const canvas = document.getElementById('camera-canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            canvas.getContext('2d').drawImage(video, 0, 0);

            video.classList.add('hidden');
            canvas.classList.remove('hidden');
            document.getElementById('camera-controls-capture').classList.add('hidden');
            document.getElementById('camera-controls-preview').style.display = 'flex';
        });

        document.getElementById('retake-btn').addEventListener('click', () => {
            startCamera();
        });

        document.getElementById('send-photo-btn').addEventListener('click', async () => {
            const canvas = document.getElementById('camera-canvas');
            canvas.toBlob(async (blob) => {
                const fileName = 'camera_' + Date.now() + '.jpg';
                const file = new File([blob], fileName, { type: 'image/jpeg' });

                // Use FilePond or native input — inject into a hidden file input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);

                // Find the Livewire file input for attachments
                const fileInputs = document.querySelectorAll('input[type="file"]');
                if (fileInputs.length > 0) {
                    const fileInput = fileInputs[0];
                    fileInput.files = dataTransfer.files;
                    fileInput.dispatchEvent(new Event('change', { bubbles: true }));
                    closeCameraModal();
                } else {
                    // Fallback: download the image
                    const a = document.createElement('a');
                    a.href = canvas.toDataURL('image/jpeg');
                    a.download = fileName;
                    a.click();
                    closeCameraModal();
                }
            }, 'image/jpeg', 0.92);
        });
    </script>
@endscript
