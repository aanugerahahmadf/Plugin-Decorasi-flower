<?php

namespace Aanugerah\WeddingPro\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;

trait InteractsWithLanguages
{
    public function lang(): MorphOne
    {
        return $this->morphOne('Aanugerah\WeddingPro\Models\UserLanguage', 'model');
    }

    public function getLangAttribute()
    {
        // Langsung cek relation tanpa cache agar fitur Filament Language Switcher selalu terupdate real-time
        // ketika user menekan bendera bahasa tanpa terjebak cache lama.
        return $this->lang()->first(['*'])?->lang ?? 'en';
    }
}
