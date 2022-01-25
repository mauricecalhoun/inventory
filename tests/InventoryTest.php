<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Lang;
// use Illuminate\Database\Eloquent\Model as Eloquent;

class InventoryTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Eloquent::unguard();
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

        $this->expectException('Stevebauman\Inventory\Exceptions\StockAlreadyExistsException');

        $item->newStockOnLocation($location);
    }

    public function testInventoryInvalidQuantityException()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        Lang::shouldReceive('get')->once();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

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
