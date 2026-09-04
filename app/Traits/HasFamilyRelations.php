<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasFamilyRelations
{
    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, self::PARENT_ID);
    }

    /** @return BelongsTo<self, $this> */
    public function ancestors(): BelongsTo
    {
        return $this->parent()->with('ancestors');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, self::PARENT_ID);
    }

    /** @return HasMany<self, $this> */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
}
