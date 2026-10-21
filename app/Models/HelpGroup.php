<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $source
 * @property string $group_key
 * @property int $position
 * @property bool $enabled
 * @property bool $overridden
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['source', 'group_key', 'position', 'enabled', 'overridden'])]
class HelpGroup extends Model
{
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'enabled' => 'boolean',
            'overridden' => 'boolean',
        ];
    }

    /**
     * @return HasMany<HelpEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(HelpEntry::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Provenance status used by the admin UI.
     */
    public function status(): string
    {
        return $this->overridden ? 'overridden' : 'auto';
    }
}
