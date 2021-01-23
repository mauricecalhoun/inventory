<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Helper;
use Illuminate\Database\Eloquent\Model;

trait InventoryStockMovementTrait
{
    use CommonMethodsTrait;

    /**
     * The belongsTo stock relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function stock();

    /**
     * Overrides the models boot function to set
     * the user ID automatically to every new record.
     *
     * @return void
     */
    public static function bootInventoryStockMovementTrait()
    {
        static::creating(function (Model $record) {
            $record->setAttribute('user_id', Helper::getCurrentUserId());
        });
    }

    /**
     * Rolls back the current movement.
     *
     * @param bool $recursive
     *
     * @return mixed
     */
    public function rollback($recursive = false)
    {
        return $this->stock->rollback($this, $recursive);
    }
}
