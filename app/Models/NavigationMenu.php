<?php

namespace App\Models;

use App\Enums\NavigationAudience;
use Database\Factories\NavigationMenuFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property NavigationAudience $audience
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['audience'])]
class NavigationMenu extends Model
{
    /** @use HasFactory<NavigationMenuFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['audience' => NavigationAudience::class];
    }

    /**
     * @return HasMany<NavigationGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(NavigationGroup::class)->orderBy('position')->orderBy('id');
    }
}
