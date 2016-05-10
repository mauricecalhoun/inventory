<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;

trait CommonMethodsTrait
{
    /**
     * {@inheritdoc}
     */
    abstract public function getKey();

    /**
     * {@inheritdoc}
     */
    abstract public function getKeyName();

    /**
     * {@inheritdoc}
     */
    abstract public function getAttribute($key);

    /**
     * {@inheritdoc}
     */
    abstract public function setAttribute($key, $value);

    /**
     * Returns true if the specified quantity is valid, throws
     * InvalidQuantityException otherwise.
     *
     * @param int|float|string $quantity
     *
     * @throws InvalidQuantityException
     *
     * @return bool
     */
    public function isValidQuantity($quantity)
    {
        if ($this->isPositive($quantity)) {
            return true;
        }

        $message = Lang::get('inventory::exceptions.InvalidQuantityException', [
            'quantity' => $quantity,
        ]);

        throw new InvalidQuantityException($message);
    }

    /**
     * Alias for firing events easily that implement this trait.
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    protected function fireEvent($name, $args = [])
    {
        return Event::fire((string) $name, (array) $args);
    }

    /**
     * Alias for beginning a database transaction.
     *
     * @return mixed
     */
    protected function dbStartTransaction()
    {
        return DB::beginTransaction();
    }

    /**
     * Alias for committing a database transaction.
     *
     * @return mixed
     */
    protected function dbCommitTransaction()
    {
        return DB::commit();
    }

    /**
     * Alias for rolling back a transaction.
     *
     * @return mixed
     */
    protected function dbRollbackTransaction()
    {
        return DB::rollback();
    }

    /**
     * Returns true/false if the number specified is numeric.
     *
     * @param int|float|string $number
     *
     * @return bool
     */
    protected function isNumeric($number)
    {
        return (is_numeric($number) ? true : false);
    }

    /**
     * Returns true or false if the number inserted is positive.
     *
     * @param int|float|string $number
     *
     * @return bool
     */
    protected function isPositive($number)
    {
        if ($this->isNumeric($number)) {
            return ($number >= 0 ? true : false);
        }

        return false;
    }

    /**
     * Returns true/false if the specified model is a subclass
     * of the Eloquent Model.
     *
     * @param mixed $model
     *
     * @return bool
     */
    protected function isModel($model)
    {
        return $model instanceof Model;
    }
}
