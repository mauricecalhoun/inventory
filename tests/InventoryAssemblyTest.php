<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use Stevebauman\Inventory\Models\InventoryAssembly;

class InventoryAssemblyTest extends InventoryTest
{
    public function testMakeAssembly()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $item->makeAssembly();

        $this->assertTrue($item->is_assembly);

        $assembly = InventoryAssembly::first();

        $this->assertEquals(1, $assembly->inventory_id);
        $this->assertEquals(1, $assembly->part_id);
        $this->assertEquals(0, $assembly->depth);
    }

    public function testMakeAssemblyReturned()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $assembly = $item->makeAssembly(true);

        $this->assertInstanceOf('Stevebauman\Inventory\Models\InventoryAssembly', $assembly);
    }

    public function testAddAssemblyItem()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $item->makeAssembly();

        $part = $this->newInventory([
            'name' => 'Child Part',
            'category_id' => $item->category_id,
            'metric_id' => $item->metric_id,
        ]);

        $item->addAssemblyItem($part);

        $this->assertEquals(1, $item->getAssemblyItems()->count());
    }

    public function testGetAssemblyItemsNone()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $item->makeAssembly();

        $this->assertEquals(0, $item->getAssemblyItems()->count());
    }
}