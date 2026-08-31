<?php

namespace App\Support\Eloquent;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Re-fetches a model that was just locked (`lockForUpdate()`) and modified
 * earlier in the same transaction, where the row's continued existence is
 * already guaranteed by that lock. `Model::fresh()` is typed nullable
 * because it can, in general, return null if the row was deleted — a case
 * that cannot occur here while the transaction still holds the row lock.
 * This makes that guarantee explicit instead of leaving every call site to
 * silently assume it.
 */
final class FreshOrFail
{
    /**
     * @template TModel of Model
     *
     * @param  TModel  $model
     * @param  array<int, string>|string  $with
     * @return TModel
     */
    public static function reload(Model $model, array|string $with = []): Model
    {
        return $model->fresh($with) ?? throw new RuntimeException(
            sprintf(
                '%s#%s vanished mid-transaction after being locked with lockForUpdate() — this should be unreachable.',
                $model::class,
                $model->getKey()
            )
        );
    }
}
