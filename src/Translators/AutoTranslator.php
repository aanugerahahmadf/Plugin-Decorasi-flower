<?php

namespace Aanugerah\WeddingPro\Translators;

use Aanugerah\WeddingPro\Services\AutoTranslationService;
use Illuminate\Translation\Translator;

class AutoTranslator extends Translator
{
    protected ?AutoTranslationService $autoService = null;

    public function setAutoTranslationService(AutoTranslationService $service): void
    {
        $this->autoService = $service;
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true): string|array|null
    {
        $targetLocale = $locale ?? $this->getLocale();

        // 1. PRIORITAS UTAMA: Gunakan Laravel Asli (Cek file JSON/PHP di /lang)
        // Ini memastikan terjemahan yang sudah dikurasi manual selalu menang.
        $translated = parent::get($key, $replace, $targetLocale, $fallback);

        // Jika Laravel berhasil menerjemahkan (hasil != key), langsung kembalikan.
        if ($translated !== $key) {
            return $translated;
        }

        // 2. FALLBACK: Jika tidak ada di file, gunakan Layanan Auto-Translation
        if ($this->autoService !== null) {
            $autoTranslated = $this->autoService->translate($key, $targetLocale);

            if ($autoTranslated !== $key) {
                return $this->makeReplacements($autoTranslated, $replace);
            }
        }

        return $translated;
    }
}
