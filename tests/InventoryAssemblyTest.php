<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

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
    }

    public function testGetAssemblies()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $items = $table->assemblies;

        $this->assertEquals(2, $items->count());

        $this->assertEquals('Table Top', $items->get(0)->name);
        $this->assertEquals(1, $items->get(0)->pivot->quantity);

        $this->assertEquals('Table Legs', $items->get(1)->name);
        $this->assertEquals(4, $items->get(1)->pivot->quantity);

        $this->assertNull($items->get(2));
    }

    public function testGetAssembliesRecursive()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $screws = $this->newInventory([
            'name' => 'Screws',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $items = $table->getAssemblyItems();

        $this->assertEquals(2, $items->count());

        $this->assertEquals('Table Top', $items->get(0)->name);
        $this->assertEquals(1, $items->get(0)->pivot->quantity);

        $this->assertEquals('Table Legs', $items->get(1)->name);
        $this->assertEquals(4, $items->get(1)->pivot->quantity);

        // One screw item exists on each model
        $this->assertEquals(1, $items->get(0)->assemblies->count());
        $this->assertEquals(1, $items->get(1)->assemblies->count());

        // One screw for table top
        $this->assertEquals('Screws', $items->get(0)->assemblies->get(0)->name);
        $this->assertEquals(1, $items->get(0)->assemblies->get(0)->pivot->quantity);

        // Two screws for table legs
        $this->assertEquals('Screws', $items->get(1)->assemblies->get(0)->name);
        $this->assertEquals(2, $items->get(1)->assemblies->get(0)->pivot->quantity);
    }

    public function testRemoveAssemblyItem()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $table->addAssemblyItem($tableTop, 1);

        $table->removeAssemblyItem($tableTop);

        $this->assertNull($table->assemblies->first());
    }

    public function testGetAssemblyItemsList()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $screws = $this->newInventory([
            'name' => 'Screws',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $metal = $this->newInventory([
            'name' => 'Metal',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $ore = $this->newInventory([
            'name' => 'Ore',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $flux = $this->newInventory([
            'name' => 'Flux',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $screws->addAssemblyItem($metal, 5);

        $metal->addAssemblyItem($ore, 10);
        $metal->addAssemblyItem($flux, 5);

        $list = $table->getAssemblyItemsList();

        $this->assertEquals('Table Top', $list[0]['name']);
        $this->assertEquals('Table Legs', $list[1]['name']);

        $this->assertEquals('Screws', $list[0]['parts'][0]['name']);
        $this->assertEquals('Screws', $list[1]['parts'][0]['name']);

        $this->assertEquals('Metal', $list[0]['parts'][0]['parts'][0]['name']);
        $this->assertEquals('Metal', $list[1]['parts'][0]['parts'][0]['name']);

        $this->assertEquals('Ore', $list[0]['parts'][0]['parts'][0]['parts'][0]['name']);
        $this->assertEquals('Ore', $list[1]['parts'][0]['parts'][0]['parts'][0]['name']);
    }

    public function testInvalidPartException()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidPartException');

        $table->addAssemblyItem($table);
    }

    public function testNestedInvalidPartException()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $tableTop = $this->newInventory([
            'name' => 'Table Top',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $tableLegs = $this->newInventory([
            'name' => 'Table Legs',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $screws = $this->newInventory([
            'name' => 'Screws',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $metal = $this->newInventory([
            'name' => 'Metal',
            'metric_id' => $table->metric_id,
            'category_id' => $table->category_id,
        ]);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $screws->addAssemblyItem($metal, 5);

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidPartException');

        $metal->addAssemblyItem($screws);
    }
}