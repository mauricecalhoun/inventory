<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;

/**
 * Inventory Stock Test
 * 
 * @coversDefaultClass \Traits\InventoryStockTrait
 */
class InventoryStockTest extends FunctionalTestCase
{
    public function testInventoryStockCreation()
    {
        $stock = $this->newInventoryStock();

        $this->assertEquals(20, $stock->quantity);
    }

    public function testStockPut()
    {
        $stock = $this->newInventoryStock();

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('dispatch')->once();

        $stock->put(10, 'Added some', 15);

        $this->assertEquals(30, $stock->quantity);
    }

    public function testStockTake()
    {
        $stock = $this->newInventoryStock();

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('dispatch')->once();

        $stock->take(10, 'Removed some', 15);

        $this->assertEquals(10, $stock->quantity);
        $this->assertEquals('Removed some', $stock->reason);
        $this->assertEquals(15, $stock->cost);
    }

    public function testStockMove()
    {
        $stock = $this->newInventoryStock();

        $newLocation = $this->newLocation();

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('dispatch')->once();

        $stock->moveTo($newLocation);

        $this->assertEquals($newLocation->id, $stock->location_id);
    }

    public function testStockIsValidQuantitySuccess()
    {
        $stock = $this->newInventoryStock();

        $this->assertTrue($stock->isValidQuantity(500));
        $this->assertTrue($stock->isValidQuantity(5, 000));
        $this->assertTrue($stock->isValidQuantity('500'));
        $this->assertTrue($stock->isValidQuantity('500.00'));
        $this->assertTrue($stock->isValidQuantity('500.0'));
        $this->assertTrue($stock->isValidQuantity('1.500'));
        $this->assertTrue($stock->isValidQuantity('15000000'));
    }

    public function testStockIsValidQuantityFailure()
    {
        $stock = $this->newInventoryStock();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $stock->isValidQuantity('40a');
        $stock->isValidQuantity('5,000');
        $stock->isValidQuantity('5.000.00');
    }

    public function testInvalidMovementException()
    {
        $stock = $this->newInventoryStock();

        Lang::shouldReceive('get')->once();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidMovementException');

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

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $stock->updateQuantity(-100);
    }

    public function testNotEnoughStockException()
    {
        $stock = $this->newInventoryStock();

        Lang::shouldReceive('get')->once();

        $this->expectException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $stock->take(1000);
    }

    public function testStockAlreadyExistsException()
    {
        $stock = $this->newInventoryStock();

        $location = Location::find($stock->location_id);

        $item = Inventory::find($stock->inventory_id);

        Lang::shouldReceive('get')->once();

        $this->expectException('Stevebauman\Inventory\Exceptions\StockAlreadyExistsException');

        $item->createStockOnLocation($stock->inventory_id, $location);
    }

    public function testStockNotFoundException()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        Lang::shouldReceive('get')->once();

        $this->expectException('Stevebauman\Inventory\Exceptions\StockNotFoundException');

        $item->getStockFromLocation($location);
    }

    public function testInventoryTakeFromManyLocations()
    {
        $newStock = $this->newInventoryStock();

        $item = Inventory::find($newStock->inventory_id);

        $location = Location::find($newStock->location_id);

        $item->takeFromManyLocations(10, [$location]);

        $stock = InventoryStock::where('inventory_id', $item->id)->first();

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryAddToManyLocations()
    {
        $newStock = $this->newInventoryStock();

        $item = Inventory::find($newStock->inventory_id);

        $location = Location::find($newStock->location_id);

        $item->addToManyLocations(10, [$location]);

        $stock = InventoryStock::where('inventory_id', $item->id)->first();

        $this->assertEquals(30, $stock->quantity);
    }

    public function testInventoryMoveItemStock()
    {
        $newStock = $this->newInventoryStock();

        $locationFrom = Location::find($newStock->location_id);

        $locationTo = $this->newLocation();

        $item = Inventory::find($newStock->inventory_id);

        $item->moveStock($locationFrom, $locationTo);

        $stock = InventoryStock::where('inventory_id', $item->id)->first();

        $this->assertEquals($locationTo->id, $stock->location_id);
    }

    public function testInventoryGetTotalStock()
    {
        $stock = $this->newInventoryStock();

        $item = Inventory::find($stock->inventory_id);

        $this->assertEquals(20, $item->getTotalStock());
    }

    public function testInventoryInvalidLocationException()
    {
        $stock = $this->newInventoryStock();

        $item = Inventory::find($stock->inventory_id);

        Lang::shouldReceive('get')->once();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidLocationException');

        $item->getStockFromLocation('testing');
    }

    public function testInventoryStockNewTransaction()
    {
        $stock = $this->newInventoryStock();

        $transaction = $stock->newTransaction();

        $this->assertInstanceOf('Stevebauman\Inventory\Interfaces\StateableInterface', $transaction);
    }

    public function testRollbackStockMovement() {
        $stock = $this->newInventoryStock();
        $initialQuantity = $stock->quantity;

        $txn = $stock->newTransaction();

        $stock->add(20, 'fresh inventory', 50);

        $this->assertEquals($initialQuantity + 20, $stock->quantity);

        $stock->rollback();

        $this->assertEquals($initialQuantity, $stock->quantity);

        $stock->remove(10, 'removing inventory cheaper', 40);

        $this->assertEquals($initialQuantity - 10, $stock->quantity);

        $stock->rollback($stock->getLastMovement());

        $this->assertEquals($initialQuantity, $stock->quantity);

        $stock->add(10, 'adding inventory cheaper', 40);

        $this->assertEquals($initialQuantity + 10, $stock->quantity);

        $stock->rollbackMovement($stock->getLastMovement());

        $this->assertEquals($initialQuantity, $stock->quantity);

        $stock->add(10, 'adding inventory even cheaper', 30);

        $this->assertEquals($initialQuantity + 10, $stock->quantity);

        $lastMovement = $stock->getLastMovement();

        $stock->rollbackMovement($lastMovement->id);

        $this->assertEquals($initialQuantity, $stock->quantity);

        $stock->add(10, 'adding inventory even cheaperer', 25);

        $this->assertEquals($initialQuantity + 10, $stock->quantity);

        $lastMovement = $stock->getLastMovement();
        $stock->add(10, 'adding inventory even cheaper again', 20);
        $stock->add(10, 'adding inventory even cheaper for a third time', 15);

        $rollbacks = $stock->rollback($lastMovement->id, true);

        $this->assertEquals($initialQuantity, $stock->quantity);
    }
}
