<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Lang;

/**
 * Inventory Test
 * 
 * @coversDefaultClass \Traits\InventoryTrait
 */
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

        $metric = Metric::find($item->metric_id);
        $metric->delete();

        $this->assertFalse($item->hasMetric());
    }

    public function testCanGetMetricSymbol()
    {
        $item = $this->newInventory();

        $metricSymbol = $item->getMetricSymbol();

        $this->assertEquals('L', $metricSymbol);
    }

    public function testCanGetNullWhenNoMetricSymbol()
    {
        $item = $this->newInventory();

        $metric = Metric::find($item->metric_id);
        $metric->delete();

        $this->assertNull($item->getMetricSymbol());
    }

    public function testInventoryCreateStockOnLocation()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

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

    public function testInventoryTakeStockFromLocation()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

        $stock = $item->createStockOnLocation(20, $location);

        $updatedItem = $item->takeFromLocation(10, $location);

        $this->assertEquals(10, $updatedItem->stocks()->first()->quantity);
    }

    public function testInventoryTakeStockFromMultipleLocations()
    {
        $item = $this->newInventory();

        $location1 = $this->newLocation();
        $location2 = $this->newLocation();

        $item->createStockOnLocation(20, $location1);
        $item->createStockOnLocation(15, $location2);

        $updatedStocks = $item->takeFromLocation(10, [$location1, $location2]);

        $this->assertEquals(10, $updatedStocks[0]->quantity);
        $this->assertEquals(5, $updatedStocks[1]->quantity);
    }

    public function testInventoryInvalidQuantityException()
    {
        $item = $this->newInventory();

        $location = $this->newLocation();

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

    public function testCanCreateInventoryAsParent() 
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();
        
        $newItem = Inventory::create([
            'name' => 'Widget',
            'description' => 'It\'s a thing that does stuff',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'is_parent' => true,
        ]);

        $newItem->save();

        $item = Inventory::find($newItem->id);

        $this->assertTrue((boolean) $item->is_parent);
    }

    public function testCannotPutStockAndLocationOnParent() 
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();
        
        $newItem = Inventory::create([
            'name' => 'Widget',
            'description' => 'It\'s a thing that does other things',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'is_parent' => true,
        ]);

        $newItem->save();

        $item = Inventory::find($newItem->id);

        $location = $this->newLocation();

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');
        
        $item->newStockOnLocation(10, $location);
    }
}
