<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * @implements CastsAttributes<float|null, int|null>
 */
class AsMoney implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if (is_null($value)) {
            return null;
        }

        return (float) number_format($value / 100, 2, '.', '');
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if (is_null($value)) {
            return null;
        }

        return (int) round((float) $value * 100);
    }
}
