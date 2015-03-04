<?php

use Stevebauman\Inventory\Models\Location;
use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\InventoryStockMovement;
use Stevebauman\Inventory\Models\InventoryStock;
use Stevebauman\Inventory\Models\InventorySku;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\Config;
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

    public function testInventoryHasMetric()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);

        $this->assertTrue($item->hasMetric());
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

    public function testTakeFromManyLocations()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        $item->takeFromManyLocations(10, array($location));

        $stock = InventoryStock::find(1);

        $this->assertEquals(10, $stock->quantity);
    }

    public function testAddToManyLocations()
    {
        $this->testInventoryStockCreation();

        $item = Inventory::find(1);

        $location = Location::find(1);

        $item->addToManyLocations(10, array($location));

        $stock = InventoryStock::find(1);

        $this->assertEquals(30, $stock->quantity);
    }

    public function testMoveItemStock()
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

    public function testInventorySkuGeneration()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(5);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();

        $item->generateSku();

        $sku = InventorySku::first();

        $this->assertEquals($sku->inventory_id, 1);
        $this->assertEquals($sku->prefix, 'DRI');
        $this->assertEquals($sku->code, '00001');
    }

    public function testInventorySkuGenerationForSmallCategoryName()
    {
        $this->testInventoryCreation();

        $category = Category::find(1);

        $update = array(
            'name' => 'D',
        );

        $category->update($update);

        $item = Inventory::find(1);

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(5);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        /*
         * Generate the SKU
         */
        $item->generateSku();

        $this->assertEquals('D00001', $item->getSku());
    }

    public function testInventorySkuRegeneration()
    {
        $this->testInventorySkuGeneration();

        $item = Inventory::find(1);

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(5);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();

        $item->regenerateSku();

        $sku = InventorySku::first();

        $this->assertEquals($sku->id, 2);
    }

    public function testInventoryHasSku()
    {
        $this->testInventorySkuGeneration();

        $item = Inventory::find(1);

        $this->assertTrue($item->hasSku());
    }

    public function testInventoryDoesNotHaveSku()
    {
        $this->testInventorySkuGeneration();

        $item = Inventory::find(1);
        $item->sku()->delete();

        $this->assertFalse($item->hasSku());
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

    public function testInventorySkuGenerationFalse()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);

        $item->category_id = NULL;
        $item->save();

        $this->assertFalse($item->generateSku());
    }

    public function testInventoryGetSku()
    {
        $this->testInventorySkuGeneration();

        $item = Inventory::find(1);

        $expected = 'DRI00001';

        $this->assertEquals($expected, $item->sku);
        $this->assertEquals($expected, $item->getSku());
    }

    public function testInventoryFindBySku()
    {
        $this->testInventorySkuGeneration();

        /*
         * Prefix length
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        /*
         * Code length
         */
        Config::shouldReceive('get')->once()->andReturn(5);

        $item = Inventory::findBySku('DRI00001');

        $this->assertEquals('Milk', $item->name);
    }

}