<?php

namespace Stevebauman\Inventory\tests;

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

    protected function newInventory()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        return Inventory::create([
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'name' => 'Milk',
            'description' => 'Delicious Milk',
        ]);
    }

    protected function newMetric()
    {
        return Metric::create([
            'name' => 'Litres',
            'symbol' => 'L',
        ]);
    }

    protected function newLocation()
    {
        return Location::create([
            'name' => 'Warehouse',
            'belongs_to' => '',
        ]);
    }

    protected function newCategory()
    {
        return Category::create([
            'name' => 'Drinks',
        ]);
    }

    public function testMetricCreation()
    {
        $metric = $this->newMetric();

        $this->assertEquals('Litres', $metric->name);
    }

    public function testCategoryCreation()
    {
        $category = $this->newCategory();

        $this->assertEquals('Drinks', $category->name);
    }

    public function testLocationCreation()
    {
        $location = $this->newLocation();

        $this->assertEquals('Warehouse', $location->name);
    }

    public function testInventoryCreation()
    {
        $inventory = $this->newInventory();

        $this->assertEquals(null, $inventory->user_id);
        $this->assertEquals(1, $inventory->category_id);
        $this->assertEquals(1, $inventory->metric_id);
    }

    public function testInventoryHasMetric()
    {
        $item = $this->newInventory();

        $this->assertTrue($item->hasMetric());
    }

    public function testInventoryDoesNotHaveMetric()
    {
        $item = $this->newInventory();

        $metric = Metric::find(1);
        $metric->delete();

        $this->assertFalse($item->hasMetric());
    }

    public function testInventoryCreateStockOnLocation()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        Lang::shouldReceive('get')->once();

        $item->createStockOnLocation(10, $location);

        $stock = InventoryStock::find(1);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryNewStockOnLocation()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = $item->newStockOnLocation($location);

        $this->assertEquals(1, $stock->inventory_id);
        $this->assertEquals(1, $stock->location_id);
    }

    public function testInventoryNewStockOnLocationFailure()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = $item->newStockOnLocation($location);
        $stock->save();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\StockAlreadyExistsException');

        $item->newStockOnLocation($location);
    }

    public function testInventoryInvalidQuantityException()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->createStockOnLocation('invalid quantity', $location);
    }

    public function testInventoryHasCategory()
    {
        $item = $this->newInventory();

        $this->assertTrue($item->hasCategory());
    }

    public function testInventoryDoesNotHaveCategory()
    {
        $this->newInventory();

        $item = Inventory::find(1);
        $item->category_id = null;
        $item->save();

        $this->assertFalse($item->hasCategory());
    }
}
