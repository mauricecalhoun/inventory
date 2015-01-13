<?php

namespace Stevebauman\Inventory\Services;

use Stevebauman\Inventory\Exceptions\InventoryCategoryNotFoundException;
use Stevebauman\Inventory\Models\Category;

/**
 * Class CategoryService
 * @package Stevebauman\Inventory\Services
 */
class CategoryService extends BaseCategoryService
{

    protected $scoped_id = 'inventories';

    public function __construct(Category $inventoryCategory, InventoryCategoryNotFoundException $notFoundException)
    {
        $this->model = $inventoryCategory;
        $this->notFoundException = $notFoundException;
    }

}