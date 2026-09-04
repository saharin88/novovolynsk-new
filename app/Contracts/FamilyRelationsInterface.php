<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @template TRelatedModel of Model
 * @template TDeclaringModel of Model
 */
interface FamilyRelationsInterface
{
    const string PARENT_ID = 'parent_id';

    /** @return BelongsTo<TRelatedModel, TDeclaringModel> */
    public function parent(): BelongsTo;

    /** @return BelongsTo<TRelatedModel, TDeclaringModel> */
    public function ancestors(): BelongsTo;

    /** @return HasMany<TRelatedModel, TDeclaringModel> */
    public function children(): HasMany;

    /** @return HasMany<TRelatedModel, TDeclaringModel> */
    public function descendants(): HasMany;
}
