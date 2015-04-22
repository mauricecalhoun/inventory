<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;

class InventoryStockTest extends InventoryTest
{
    protected function newInventoryStock()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = new InventoryStock;
        $stock->inventory_id = $item->id;
        $stock->location_id = $location->id;
        $stock->quantity = 20;
        $stock->cost = '5.20';
        $stock->reason = 'I bought some';
        $stock->save();

        return $stock;
    }

    public function testInventoryStockCreation()
    {
        $stock = $this->newInventoryStock();

        $this->assertEquals(20, $stock->quantity);
    }

    public function testStockPut()
    {
        $stock = $this->newInventoryStock();

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $stock->put(10, 'Added some', 15);

        $this->assertEquals(30, $stock->quantity);
    }

    public function testStockTake()
    {
        $stock = $this->newInventoryStock();

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $stock->take(10, 'Removed some', 15);

        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals('Removed some', $stock->reason);
        $this->assertEquals(15, $stock->cost);
    }

    public function testStockMove()
    {
        $stock = $this->newInventoryStock();

        $newLocation = Location::create([
            'name' => 'New Location'
        ]);

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $stock->moveTo($newLocation);

        $this->assertEquals(2, $stock->location_id);
    }

    public function testStockIsValidQuantitySuccess()
    {
        $stock = $this->newInventoryStock();

        $this->assertTrue($stock->isValidQuantity(500));
        $this->assertTrue($stock->isValidQuantity(5,000));
        $this->assertTrue($stock->isValidQuantity('500'));
        $this->assertTrue($stock->isValidQuantity('500.00'));
        $this->assertTrue($stock->isValidQuantity('500.0'));
        $this->assertTrue($stock->isValidQuantity('1.500'));
        $this->assertTrue($stock->isValidQuantity('15000000'));
    }

    public function testStockIsValidQuantityFailure()
    {
        $stock = $this->newInventoryStock();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $stock->isValidQuantity('40a');
        $stock->isValidQuantity('5,000');
        $stock->isValidQuantity('5.000.00');
    }

    public function testInvalidMovementException()
    {
        $stock = $this->newInventoryStock();

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidMovementException');

        $stock->getMovement('testing');
    }

    public function testUpdateStockQuantity()
    {
        $stock = $this->newInventoryStock();

        $stock->updateQuantity(10);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testUpdateStockQuantityFailure()
    {
        $stock = $this->newInventoryStock();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $stock->updateQuantity(-100);
    }

    public function testNotEnoughStockException()
    {
        $stock = $this->newInventoryStock();

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $stock->take(1000);
    }

    public function testStockAlreadyExistsException()
    {
        $this->newInventoryStock();

        $location = Location::find(1);

        $item = Inventory::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\StockAlreadyExistsException');

        $item->createStockOnLocation(1, $location);
    }

    public function testStockNotFoundException()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\StockNotFoundException');

        $item->getStockFromLocation($location);
    }

    public function testInventoryTakeFromManyLocations()
    {
        $this->newInventoryStock();

        $item = Inventory::find(1);

        $location = Location::find(1);

        $item->takeFromManyLocations(10, [$location]);

        $stock = InventoryStock::find(1);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryAddToManyLocations()
    {
        $this->newInventoryStock();

        $item = Inventory::find(1);

        $location = Location::find(1);

        $item->addToManyLocations(10, [$location]);

        $stock = InventoryStock::find(1);

        $this->assertEquals(30, $stock->quantity);
    }

    public function testInventoryMoveItemStock()
    {
        $this->newInventoryStock();

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
        $this->newInventoryStock();

        $item = Inventory::find(1);

        $this->assertEquals(20, $item->getTotalStock());
    }

    public function testInventoryInvalidLocationException()
    {
        $this->newInventoryStock();

        $item = Inventory::find(1);

        Lang::shouldReceive('get')->once();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidLocationException');

        $item->getStockFromLocation('testing');
    }

    public function testInventoryStockNewTransaction()
    {
        $this->newInventoryStock();

        $stock = InventoryStock::find(1);

        $transaction = $stock->newTransaction();

        $this->assertInstanceOf('Stevebauman\Inventory\Interfaces\StateableInterface', $transaction);
    }
}