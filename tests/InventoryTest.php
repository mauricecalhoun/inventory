<?php

use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\InventoryStockMovement;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class InventoryTest extends FunctionalTestCase {

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

    public function testCreateStockOnLocation()
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

    public function testInvalidLocationException()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidLocationException');

        $item->getStockFromLocation('testing');
    }

    public function testInvalidMovementException()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidMovementException');

        $stock->getMovement('testing');
    }

    public function testInvalidQuantityException()
    {
        $this->testInventoryCreation();

        $this->testLocationCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->createStockOnLocation('invalid quantity', $location);
    }

    public function testNotEnoughStockException()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $stock->take(1000);
    }

    public function testStockAlreadyExistsException()
    {
        $this->testInventoryStockCreation();

        $location = Location::find(1);

        $item = Inventory::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\StockAlreadyExistsException');

        $item->createStockOnLocation(1, $location);
    }

    public function testStockNotFoundException()
    {
        $this->testInventoryCreation();

        $this->testLocationCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\StockNotFoundException');

        $item->getStockFromLocation($location);
    }

}