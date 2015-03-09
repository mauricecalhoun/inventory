<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\Inventory;
use Stevebauman\Inventory\Models\InventorySku;

class InventorySkuTest extends InventoryTest
{
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

        /*
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('');

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();

        Event::shouldReceive('fire')->once();

        $item->generateSku();

        $sku = InventorySku::first();

        $this->assertEquals($sku->inventory_id, 1);
        $this->assertEquals($sku->code, 'DRI00001');
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
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('');

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

        $item = Inventory::findBySku('DRI00001');

        $this->assertEquals('Milk', $item->name);
    }

    public function testInventorySkuBlankCategoryName()
    {
        $this->testInventorySkuGeneration();

        $category = Category::find(1);

        $category->update(array('name' => '     '));

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
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('');

        $item = Inventory::find(1);

        $item->regenerateSku();

        /*
         * SKU generation will fail and the previous will be restored
         */
        $this->assertEquals('DRI00001', $item->sku);
    }

    public function testInventorySkuSeparator()
    {
        $this->testInventorySkuGeneration();

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
         * SKU separator
         */
        Config::shouldReceive('get')->once()->andReturn('-');

        $item = Inventory::find(1);

        $item->regenerateSku();

        $this->assertEquals($item->getSku(), 'DRI-00001');
    }
}