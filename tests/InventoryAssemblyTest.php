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

    public function testAddAssemblyItemsNested()
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

        $part2 = $this->newInventory([
            'name' => 'Child 2 Part',
            'category_id' => $item->category_id,
            'metric_id' => $item->metric_id,
        ]);

        $item->addAssemblyItem($part);
        $item->addAssemblyItem($part2);

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('fire')->once()->andReturn(true);

        $nestedPart = $this->newInventory([
            'name' => 'Nested Part',
            'category_id' => $part->category_id,
            'metric_id' => $part->metric_id,
        ]);

        $part->makeAssembly();

        $part->addAssemblyItem($nestedPart);

        $nestedNestedPart = $this->newInventory([
            'name' => 'Nested Nested Part',
            'category_id' => $nestedPart->category_id,
            'metric_id' => $nestedPart->metric_id,
        ]);

        $nestedPart->makeAssembly();

        $nestedPart->addAssemblyItem($nestedNestedPart);

        $items = $item->getAssemblyItems();

        $this->assertEquals(2, $items->count());
        $this->assertEquals(2, $items[1]->count());

        $partItems = $part->getAssemblyItems();

        $this->assertEquals(2, $partItems->count());
        $this->assertEquals(1, $partItems[1]->count());

        $nestedPartItems = $nestedPart->getAssemblyItems();

        $this->assertEquals(1, $nestedPartItems->count());
        $this->assertNull($nestedNestedPart[1]);

        $nestedNestedPartItems = $nestedNestedPart->getAssemblyItems();

        $this->assertEquals(0, $nestedNestedPartItems->count());
    }

    public function testGetAssemblyItemsList()
    {
        $item = $this->newInventory();

        $part1 = $this->newInventory([
            'name' => 'Part 1',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $part2 = $this->newInventory([
            'name' => 'Part 2',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $part3 = $this->newInventory([
            'name' => 'Part 3',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $item->addAssemblyItem($part1);

        $part1->addAssemblyItem($part2);

        $part2->addAssemblyItem($part3);

        $items = $item->getAssemblyItemsList();

        $this->assertEquals(3, $items->count());
        $this->assertEquals('Part 1', $items->get(0)->name);
        $this->assertEquals('Part 2', $items->get(1)->name);
        $this->assertEquals('Part 3', $items->get(2)->name);
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