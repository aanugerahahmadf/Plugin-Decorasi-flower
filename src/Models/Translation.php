<?php

namespace Aanugerah\WeddingPro\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $source_hash
 * @property string $source_text
 * @property string $target_locale
 * @property string $translated_text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereSourceHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation whereTargetLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Translation updateOrCreate(array $attributes, array $values = [])
 *
 * @mixin \Eloquent
 */
class Translation extends Model
{
    protected $fillable = [
        'source_hash',
        'source_text',
        'target_locale',
        'translated_text',
    ];
}
