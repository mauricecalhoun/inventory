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

        $part = $this->newInventory([
            'name' => 'Child Part',
            'category_id' => $item->category_id,
            'metric_id' => $item->metric_id,
        ]);

        $item->addAssemblyItem($part);

        $this->assertEquals(1, $item->getAssemblyItems()->count());
    }

    public function testGetAssemblyItemsNested()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $part = $this->newInventory([
            'name' => 'Child Part',
            'category_id' => $item->category_id,
            'metric_id' => $item->metric_id,
        ]);

        $item->addAssemblyItem($part, 5);

        $part2 = $this->newInventory([
            'name' => 'Child Part 2',
            'category_id' => $item->category_id,
            'metric_id' => $item->metric_id,
        ]);

        $part3 = $this->newInventory([
            'name' => 'Child Part 3',
            'category_id' => $item->category_id,
            'metric_id' => $item->metric_id,
        ]);

        $part->addAssemblyItem($part2);
        $part->addAssemblyItem($part3);

        $items = $item->getAssemblyItems();

        $this->assertEquals('Child Part', $items->get(0)->get('part')->name);
        $this->assertEquals($part->id, $items->get(0)->get('part')->id);
        $this->assertEquals(5, $items->get(0)->get('part')->quantity);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $items->get(0)->get('assembly'));
        $this->assertEquals('Child Part 2', $items->get(0)->get('assembly')->get(0)->name);
        $this->assertEquals('Child Part 3', $items->get(0)->get('assembly')->get(1)->name);
    }

    public function testRemoveAssemblyById()
    {
        $item = $this->newInventory();

        $part = $this->newInventory([
            'name' => 'Part',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $item->addAssemblyItem($part);

        $assembly = $item->assemblies()->first();

        $this->assertEquals(1, $item->removeAssembly($assembly->id));
    }

    public function testRemoveAssemblyByObject()
    {
        $item = $this->newInventory();

        $part = $this->newInventory([
            'name' => 'Part',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $item->addAssemblyItem($part);

        $assembly = $item->assemblies()->first();

        $this->assertEquals(1, $item->removeAssembly($assembly));
    }
}