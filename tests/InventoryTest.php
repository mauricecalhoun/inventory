<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Lang;

class InventoryTest extends FunctionalTestCase
{
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

        $newStock = $item->createStockOnLocation(10, $location);

        $stock = InventoryStock::find($newStock->id);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryNewStockOnLocation()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = $item->newStockOnLocation($location);

        $this->assertEquals($item->id, $stock->inventory_id);
        $this->assertEquals($location->id, $stock->location_id);
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
        $newItem = $this->newInventory();

        $item = Inventory::find($newItem->id);
        $item->category_id = null;
        $item->save();

        $this->assertFalse($item->hasCategory());
    }
}
