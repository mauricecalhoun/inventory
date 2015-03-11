<?php

use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\InventoryStockMovement;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Lang;
use Illuminate\Database\Eloquent\Model as Eloquent;

class InventoryTest extends FunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        Eloquent::unguard();

        InventoryStockMovement::flushEventListeners();
        InventoryStockMovement::boot();

        InventoryStock::flushEventListeners();
        InventoryStock::boot();

        Inventory::flushEventListeners();
        Inventory::boot();
    }

    public function testMetricCreation()
    {
        $metric = Metric::create(array(
            'name' => 'Litres',
            'symbol' => 'L'
        ));

        $this->assertEquals('Litres', $metric->name);
    }

    public function testCategoryCreation()
    {
        $category = Category::create(array(
            'name' => 'Drinks'
        ));

        $this->assertEquals('Drinks', $category->name);
    }

    public function testLocationCreation()
    {
        $location = Location::create(array(
            'name' => 'Warehouse',
            'belongs_to' => ''
        ));

        $this->assertEquals('Warehouse', $location->name);
    }

    public function testInventoryCreation()
    {
        $this->testMetricCreation();

        $this->testCategoryCreation();

        $metric = Metric::find(1);

        $category = Category::find(1);

        $inventory = Inventory::create(array(
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'name' => 'Milk',
            'description' => 'Delicious Milk',
        ));

        $this->assertEquals(null, $inventory->user_id);
        $this->assertEquals(1, $inventory->category_id);
        $this->assertEquals(1, $inventory->metric_id);
    }

    public function testInventoryHasMetric()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);

        $this->assertTrue($item->hasMetric());
    }

    public function testInventoryDoesNotHaveMetric()
    {
        $this->testInventoryCreation();

        $metric = Metric::find(1);
        $metric->delete();

        $item = Inventory::find(1);

        $this->assertFalse($item->hasMetric());
    }

    public function testInventoryCreateStockOnLocation()
    {
        $this->testInventoryCreation();

        $this->testLocationCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        Lang::shouldReceive('get')->once();

        $item->createStockOnLocation(10, $location);

        $stock = InventoryStock::find(1);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryInvalidQuantityException()
    {
        $this->testInventoryCreation();

        $this->testLocationCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->createStockOnLocation('invalid quantity', $location);
    }

    public function testInventoryHasCategory()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);

        $this->assertTrue($item->hasCategory());
    }

    public function testInventoryDoesNotHaveCategory()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);
        $item->category_id = NULL;
        $item->save();

        $this->assertFalse($item->hasCategory());
    }

}