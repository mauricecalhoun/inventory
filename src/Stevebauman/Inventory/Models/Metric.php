<?php

namespace Stevebauman\Inventory\Models;

use Stevebauman\CoreHelper\Models\BaseModel;

/**
 * Class Metric
 * @package Stevebauman\Inventory\Models
 */
class Metric extends BaseModel
{

    protected $table = 'metrics';

    protected $fillable = array(
        'user_id',
        'name',
        'symbol'
    );

    protected $revisionFormattedFieldNames = array(
        'name' => 'Name',
        'symbol' => 'Symbol',
    );

    /**
     * Allows revisionable to show the metric name instead of ID
     *
     * @return string
     */
    public function identifiableName()
    {
        return $this->name;
    }

}