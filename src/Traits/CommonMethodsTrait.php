<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;

trait CommonMethodsTrait
{
    /**
     * Returns the models identifier key.
     *
     * @return int|string
     */
    abstract public function getKey();

    /**
     * Returns the models identifier key name.
     *
     * @return string
     */
    abstract public function getKeyName();

    /**
     * Returns a attribute from the model.
     *
     * @param string $key
     *
     * @return mixed
     */
    abstract public function getAttribute($key);

    /**
     * Set a given attribute on the model.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
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
    private function isNumeric($number)
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
    private function isPositive($number)
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
    private function isModel($model)
    {
        return is_subclass_of($model, 'Illuminate\Database\Eloquent\Model');
    }
}
