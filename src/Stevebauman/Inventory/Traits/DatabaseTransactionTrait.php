<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

/**
 * Trait DatabaseTransactionTrait
 * @package Stevebauman\Inventory\Traits
 */
trait DatabaseTransactionTrait
{
    /**
     * Alias for firing events easily that implement this trait
     *
     * @param $name
     * @param array $args
     * @return mixed
     */
    protected function fireEvent($name, $args = [])
    {
        return Event::fire((string) $name, (array) $args);
    }

    /**
     * Alias for beginning a database transaction
     *
     * @return mixed
     */
    protected function dbStartTransaction()
    {
        return DB::beginTransaction();
    }

    /**
     * Alias for committing a database transaction
     *
     * @return mixed
     */
    protected function dbCommitTransaction()
    {
        return DB::commit();
    }

    /**
     * Alias for rolling back a transaction
     *
     * @return mixed
     */
    protected function dbRollbackTransaction()
    {
        return DB::rollback();
    }
}