<?php

namespace Stevebauman\Inventory\Models;

use Baum\Node;

/**
 * Class Category
 * @package Stevebauman\Inventory\Models
 */
class Category extends Node
{
    protected $table = 'categories';

    protected $fillable = [
        'name'
    ];

    protected $scoped = ['belongs_to'];
}
