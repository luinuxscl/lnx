<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $help_group_id
 * @property string $source
 * @property string $slug
 * @property string $type
 * @property string $title_key
 * @property string $summary_key
 * @property string $body_key
 * @property string|null $title_es
 * @property string|null $title_en
 * @property string|null $summary_es
 * @property string|null $summary_en
 * @property string|null $body_es
 * @property string|null $body_en
 * @property array<int, string>|null $roles
 * @property int $position
 * @property string|null $route_name
 * @property bool $enabled
 * @property bool $overridden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'help_group_id', 'source', 'slug', 'type',
    'title_key', 'summary_key', 'body_key',
    'title_es', 'title_en', 'summary_es', 'summary_en', 'body_es', 'body_en',
    'roles', 'position', 'route_name', 'enabled', 'overridden',
])]
class HelpEntry extends Model
{
    public const TYPES = ['article', 'faq'];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'position' => 'integer',
            'enabled' => 'boolean',
            'overridden' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<HelpGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(HelpGroup::class, 'help_group_id');
    }

    /**
     * Provenance status used by the admin UI: `auto` when the package still
     * owns the entry, `overridden` when root edited, hid or reordered it.
     */
    public function status(): string
    {
        return $this->overridden ? 'overridden' : 'auto';
    }

    /**
     * Resolve a content field for the current locale: root's override when the
     * entry is overridden, otherwise the package translation key. Views run
     * `__()` on the result, so both keys and literal texts render correctly.
     */
    public function content(string $field): string
    {
        if ($this->overridden) {
            $locale = app()->getLocale() === 'en' ? 'en' : 'es';
            $value = $this->getAttribute($field.'_'.$locale);

            if (is_string($value) && $value !== '') {
                return $value;
            }

            $fallback = $this->getAttribute($field.'_'.($locale === 'en' ? 'es' : 'en'));

            if (is_string($fallback) && $fallback !== '') {
                return $fallback;
            }
        }

        return (string) $this->getAttribute($field.'_key');
    }

    /**
     * @return array<int, string>
     */
    public function roleList(): array
    {
        return array_values(array_map('strval', $this->roles ?? []));
    }
}
