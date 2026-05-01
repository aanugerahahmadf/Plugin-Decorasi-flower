<div class="flex justify-center">
    @php
        $user = auth()->user();
        $avatarUrl = $user->avatar_url;
    @endphp

    <div class="relative group">
        <img 
            src="{{ $avatarUrl }}" 
            alt="Profile Picture" 
            class="w-32 h-32 rounded-full object-cover border-4 border-white shadow-lg dark:border-gray-800"
            onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($user->full_name) }}&color=7F9CF5&background=EBF4FF'"
        >
        
        @if(filter_var($user->getRawOriginal('avatar_url'), FILTER_VALIDATE_URL))
            <div class="absolute -bottom-2 -right-2 bg-blue-500 text-white p-1.5 rounded-full shadow-md" title="Synced with Google">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12.24 10.285V14.4h6.806c-.275 1.765-2.056 5.174-6.806 5.174-4.095 0-7.439-3.389-7.439-7.574s3.344-7.574 7.439-7.574c2.33 0 3.891.989 4.785 1.849l3.254-3.138C18.189 1.186 15.479 0 12.24 0c-6.635 0-12 5.365-12 12s5.365 12 12 12c6.926 0 11.52-4.869 11.52-11.726 0-.788-.085-1.39-.189-1.989H12.24z"/>
                </svg>
            </div>
        @endif
    </div>
</div>
