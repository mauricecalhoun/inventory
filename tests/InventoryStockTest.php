<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;

class InventoryStockTest extends InventoryTest
{
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

    public function testInvalidMovementException()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidMovementException');

        $stock->getMovement('testing');
    }

    public function testUpdateStockQuantity()
    {
        $this->testInventoryStockCreation();

        $stock = InventoryStock::find(1);

        $stock->updateQuantity(10);

        $this->assertEquals(10, $stock->quantity);
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

    public function testInventoryTakeFromManyLocations()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        $item->takeFromManyLocations(10, array($location));

        $stock = InventoryStock::find(1);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryAddToManyLocations()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        $item->addToManyLocations(10, array($location));

        $stock = InventoryStock::find(1);

        $this->assertEquals(30, $stock->quantity);
    }

    public function testInventoryMoveItemStock()
    {
        $this->testInventoryStockCreation();

        $locationFrom = Location::find(1);

        $locationTo = new Location();
        $locationTo->name = 'New Location';
        $locationTo->save();

        $item = Inventory::find(1);

        $item->moveStock($locationFrom, $locationTo);

        $stock = InventoryStock::find(1);

        $this->assertEquals(2, $stock->location_id);
    }

    public function testInventoryGetTotalStock()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        $this->assertEquals(20, $item->getTotalStock());
    }

    public function testInventoryInvalidLocationException()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidLocationException');

        $item->getStockFromLocation('testing');
    }
}