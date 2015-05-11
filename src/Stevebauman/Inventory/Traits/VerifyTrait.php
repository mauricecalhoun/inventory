<?php

namespace Stevebauman\Inventory\Traits;

/**
 * Trait VerifyTrait.
 */
trait VerifyTrait
{
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
