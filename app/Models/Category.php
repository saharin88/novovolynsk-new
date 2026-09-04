<?php

namespace App\Models;

use App\Contracts\FamilyRelationsInterface;
use App\Traits\HasFamilyRelations;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 *
 * @implements FamilyRelationsInterface<Category, $this>
 */
#[Fillable(['parent_id', 'name'])]
class Category extends Model implements FamilyRelationsInterface
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasFamilyRelations;
    use SoftDeletes;

    /**
     * @return HasMany<Item, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
