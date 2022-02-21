<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\Inventory\Models\Category;

/**
 * Inventory Category Test
 * 
 * @coversDefaultClass \Stevebauman\Inventory\Traits\CategoryTrait
 */
class InventoryCategoryTest extends FunctionalTestCase
{
    public function testScopedInventories()
    {
        $metric = new Metric;
        $metric->name = 'Test Metric';
        $metric->symbol = 'Test Symbol';
        $metric->save();

        $productsCategory = Category::create([
            'name' => 'Products',
            'belongs_to' => 'Products'
        ]);

        $miscCategory = Category::create([
            'name' => 'Misc',
            'belongs_to' => 'Misc',
        ]);

        $noItemCategory = Category::create([
            'name' => 'No Items',
        ]);

        $productItem = Inventory::create([
            'name' => '',
            'category_id' => $productsCategory->id,
            'metric_id' => $metric->id,
        ]);

        $miscItem = Inventory::create([
            'name' => 'Item 1',
            'category_id' => $miscCategory->id,
            'metric_id' => $metric->id,
        ]);

        $miscItem2 = Inventory::create([
            'name' => 'Item 2',
            'category_id' => $miscCategory->id,
            'metric_id' => $metric->id,
        ]);

        $this->assertEquals(0, $noItemCategory->inventories()->count());
        $this->assertEquals(1, $productsCategory->inventories()->count());
        $this->assertEquals(2, $miscCategory->inventories()->count());
    }
}