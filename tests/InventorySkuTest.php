<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\Inventory\Models\InventorySku;

/**
 * Inventory SKU Test
 * 
 * @coversDefaultClass \Stevebauman\Inventory\Traits\InventoryTrait
 */
class InventorySkuTest extends FunctionalTestCase
{
    public function testInventorySkuGeneration()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        /*
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('');

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();

        Event::shouldReceive('dispatch')->once();

        $sku = $this->newInventorySku();

        $item = Inventory::where('id', $sku->inventory_id)->first();

        $this->assertEquals($item->id, $sku->inventory_id);
        $this->assertMatchesRegularExpression('/DRI\d{6}/', $sku->code);
    }

    public function testInventorySkuGenerationForSmallCategoryName()
    {
        $item = $this->newInventory();

        $category = $this->newCategory();

        $update = [
            'name' => 'D',
        ];

        $category->update($update);

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        /*
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('');

        /*
         * Generate the SKU
         */
        $inventorySku = $item->generateSku();

        /*
         * Get the sku code
         */
        $sku = $item->sku()->first()->code;

        $this->assertEquals($inventorySku->code, $sku);
    }

    public function testInventorySkuRegeneration()
    {
        $newSku = $this->newInventorySku();

        $item = Inventory::find($newSku->inventory_id);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();

        $item->regenerateSku();

        $sku = InventorySku::where('inventory_id', $newSku->inventory_id)->first();

        $this->assertEquals($sku->id, $newSku->id + 1);
    }

    public function testInventoryHasSku()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);
        
        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $sku = $this->newInventorySku();

        $item = Inventory::find($sku->inventory_id);

        $this->assertTrue($item->hasSku());
    }

    public function testInventoryDoesNotHaveSku()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);
        
        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $newSku = $this->newInventorySku();

        $inventoryID = $newSku->inventory_id;

        $sku = InventorySku::where('inventory_id', $inventoryID)->first();
        $sku->delete();

        $item = Inventory::find($inventoryID);

        $this->assertFalse($item->hasSku());
    }

    public function testInventorySkuGenerationFalse()
    {
        $item = $this->newInventory();

        $item->category_id = null;
        $item->save();

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);
        $this->assertFalse($item->generateSku());
    }

    public function testInventoryGetSku()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $newSku = $this->newInventorySku();

        $inventoryID = $newSku->inventory_id;

        $item = Inventory::find($inventoryID);

        $this->assertEquals($item->getSku(), $item->sku->code);
        $this->assertMatchesRegularExpression('/DRI\d{6}/', $item->sku->code);
    }

    public function testInventoryFindBySku()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);
        
        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $newSku = $this->newInventorySku();

        $newSkuCode = $newSku->code;

        $item = Inventory::findBySku($newSkuCode);

        $this->assertEquals('Milk', $item->name);
    }

    public function testInventorySkuBlankCategoryName()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);
        
        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $newSku = $this->newInventorySku();
        
        $category = $this->newCategory();

        $category->update(['name' => '     ']);

        $item = Inventory::find($newSku->inventory_id);

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        /*
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('');

        $sku = $item->regenerateSku();

        /*
         * SKU generation will fail and the previous will be restored
         * with new ID
         */
        $this->assertEquals($newSku->id + 1, $sku->id);
        $this->assertMatchesRegularExpression('/DRI\d{6}/', $sku->code);
    }

    public function testInventorySkuSeparator()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);
        
        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $newSku = $this->newInventorySku();
        
        $inventoryID = $newSku->inventory_id;

        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);

        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        /*
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('-');

        $item = Inventory::find($inventoryID);

        $sku = $item->regenerateSku();

        $this->assertEquals($newSku->id + 1, $sku->id);
        $this->assertMatchesRegularExpression('/DRI-\d{6}/', $sku->code);
    }

    public function testInventorySkuCreateSku()
    {
        $item = $this->newInventory();

        $sku = $item->createSku('TESTING');

        $this->assertEquals($item->id, $sku->inventory_id);
        $this->assertEquals('TESTING', $sku->code);
    }

    public function testInventorySkuCreateSkuOverwrite()
    {
        $item = $this->newInventory();

        $firstSku = $item->createSku('TESTING2');

        $newSku = $item->createSku('TESTING-RESTORE', true);

        $this->assertEquals($firstSku->id, $newSku->id);
        $this->assertEquals($item->id, $newSku->inventory_id);
        $this->assertEquals('TESTING-RESTORE', $newSku->code);
    }

    public function testsInventorySkuUpdate()
    {
        $item = $this->newInventory();

        $firstSku = $item->createSku('TESTING3');

        $sku = $item->updateSku('TESTING-UPDATE');

        $this->assertEquals($firstSku->id, $sku->id);
        $this->assertEquals($firstSku->inventory_id, $sku->inventory_id);
        $this->assertEquals('TESTING-UPDATE', $sku->code);
    }

    public function testInventorySkuUpdateCreate()
    {
        $item = $this->newInventory();

        $sku = $item->updateSku('TESTING-UPDATE-CREATE');

        $this->assertTrue(is_numeric($sku->id));
        $this->assertEquals($item->id, $sku->inventory_id);
        $this->assertEquals('TESTING-UPDATE-CREATE', $sku->code);
    }

    public function testInventorySkuCreateSkuAlreadyExistsException()
    {
        /*
         * SKU generation is enabled
         */
        Config::shouldReceive('get')->once()->andReturn(true);
        
        /*
         * SKU code limit
         */
        Config::shouldReceive('get')->once()->andReturn(6);

        /*
         * SKU prefix limit
         */
        Config::shouldReceive('get')->once()->andReturn(3);

        $newSku = $this->newInventorySku();

        $item = Inventory::find($newSku->inventory_id);

        Lang::shouldReceive('get')->once()->andReturn('Failed');

        $this->expectException('Stevebauman\Inventory\Exceptions\SkuAlreadyExistsException');

        $item->createSku('test');
    }
}
