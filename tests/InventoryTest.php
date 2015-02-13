<?php

use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Maintenance\Models\Category;
use Stevebauman\Inventory\Models\InventoryStockMovement;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class InventoryTest extends FunctionalTestCase {

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
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

    public function testInventoryStockCreation()
    {
        $this->testInventoryCreation();

        $this->testLocationCreation();

        $location = Location::find(1);

        $inventory = Inventory::find(1);

        $stock = new InventoryStock;
        $stock->inventory_id = $inventory->id;
        $stock->location_id = $location->id;
        $stock->quantity = 20;
        $stock->cost = '5.20';
        $stock->reason = 'I bought some';
        $stock->save();

        $this->assertEquals(20, $stock->quantity);
    }

    public function testStockPut()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $stock->put(10, 'Added some', 15);

        $this->assertEquals(30, $stock->quantity);
    }

    public function testStockTake()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $stock->take(10, 'Removed some', 15);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testStockMove()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $newLocation = Location::create(array(
            'name' => 'New Location'
        ));

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $stock->moveTo($newLocation);

        $this->assertEquals(2, $stock->location_id);
    }

}