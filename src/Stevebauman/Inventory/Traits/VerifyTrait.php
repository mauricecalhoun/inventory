<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\Exceptions\InvalidQuantityException;
use Illuminate\Support\Facades\Lang;

/**
 * Class VerifyTrait
 * @package Stevebauman\Inventory\Traits
 */
trait VerifyTrait
{
    /**
     * Returns true or false if the specified quantity is valid
     *
     * @param $quantity
     * @return bool
     * @throws InvalidQuantityException
     */
    public function isValidQuantity($quantity)
    {
        if($this->isPositive($quantity)) return true;

        $message = Lang::get('inventory::exceptions.InvalidQuantityException', array(
            'quantity' => $quantity,
        ));

        throw new InvalidQuantityException($message);
    }

    /**
     * Returns true/false if the number specified is numeric
     *
     * @param int $number
     * @return bool
     */
    private function isNumeric($number)
    {
        return (is_numeric($number) ? true : false);
    }

    /**
     * Returns true or false if the number inserted is positive
     *
     * @param $number
     * @return bool
     */
    private function isPositive($number)
    {
        if($this->isNumeric($number)) return ($number >= 0 ? true : false);
    }

    /**
     * Returns true/false if the specified model is a subclass
     * of the Eloquent Model
     *
     * @param $model
     * @return bool
     */
    private function isModel($model)
    {
        return is_subclass_of($model, 'Illuminate\Database\Eloquent\Model');
    }
}