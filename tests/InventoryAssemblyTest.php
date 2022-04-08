<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

/**
 * Inventory Assembly Test
 * 
 * @coversDefaultClass \Stevebauman\Inventory\Traits\AssemblyTrait
 */
class InventoryAssemblyTest extends FunctionalTestCase
{
    public function testMakeAssembly()
    {
        $item = $this->newInventory();

        // DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        // DB::shouldReceive('commit')->once()->andReturn(true);

        // Event::shouldReceive('dispatch')->once()->andReturn(true);

        $item->makeAssembly();

        $this->assertTrue($item->is_assembly);
    }

    public function testAddAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        // Cache::shouldReceive('forget')->once()->andReturn(true);

        // Event::shouldReceive('dispatch')->once()->andReturn(true);

        $item->addAssemblyItem($childItem, 10);

        $items = $item->assemblies;

        $this->assertEquals('Child Item', $items->first()->name);
        $this->assertEquals(10, $items->first()->pivot->quantity);
    }

    public function testAddAssemblyItems()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $childItem2 = $this->newInventory([
            'name' => 'Child Item 2',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        // Cache::shouldReceive('forget')->twice()->andReturn(true);

        $item->addAssemblyItems([$childItem, $childItem2], 10);

        $items = $item->assemblies;

        $this->assertEquals('Child Item', $items->get(0)->name);
        $this->assertEquals(10, $items->get(0)->pivot->quantity);

        $this->assertEquals('Child Item 2', $items->get(1)->name);
        $this->assertEquals(10, $items->get(1)->pivot->quantity);
    }

    public function testAddSameAssemblyItems()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        // Cache::shouldReceive('forget')->twice()->andReturn(true);

        $item->addAssemblyItems([$childItem, $childItem]);

        // Cache::shouldReceive('has')->once()->andReturn(false);
        // Cache::shouldReceive('forever')->once()->andReturn(true);

        $this->assertEquals(2, $item->getAssemblyItems()->count());
    }

    // public function testAddAssemblyItemExtraAttributes()
    // {
    //     $item = $this->newInventory();

    //     $childItem = $this->newInventory([
    //         'name' => 'Child Item',
    //         'metric_id' => $item->metric_id,
    //         'category_id' => $item->category_id,
    //     ]);

    //     Cache::shouldReceive('forget')->once()->andReturn(true);

    //     Event::shouldReceive('dispatch')->once()->andReturn(true);

    //     $item->addAssemblyItem($childItem, 10, ['extra' => 'testing']);

    //     /*
    //      * Tests that the extra array is merged
    //      * and updated successfully with the quantity
    //      */
    //     $this->assertEquals(10, $item->assemblies()->first()->pivot->quantity);
    // }

    public function testAddInvalidAssemblyItem()
    {
        $item = $this->newInventory();

        try {
            $this->expectError('TypeError');
            $item->addAssemblyItem('invalid item');

            $passes = false;
        } catch (\Exception $e) {
            $passes = true;
        }

        $this->assertTrue($passes);
    }

    public function testAddInvalidQuantityWithAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Lang::shouldReceive('get')->once()->andReturn('Invalid Quantity');

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->addAssemblyItem($childItem, 'invalid quantity');
    }

    public function testUpdateAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        // Cache::shouldReceive('forget')->times(3)->andReturn(true);

        $item->addAssemblyItem($childItem);

        $item->updateAssemblyItem($childItem, 5);

        $this->assertEquals(5, $item->assemblies()->first()->pivot->quantity);

        $item->updateAssemblyItem($childItem->id, 10);

        $this->assertEquals(10, $item->assemblies()->first()->pivot->quantity);
    }

    public function testUpdateAssemblyItems()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $childItem2 = $this->newInventory([
            'name' => 'Child Item 2',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        // Cache::shouldReceive('forget')->times(4)->andReturn(true);

        $item->addAssemblyItem($childItem);
        $item->addAssemblyItem($childItem2);

        $item->updateAssemblyItems([$childItem, $childItem2], 10);

        $items = $item->assemblies()->get();

        $this->assertEquals(10, $items->get(0)->pivot->quantity);
        $this->assertEquals(10, $items->get(1)->pivot->quantity);
    }

    public function testUpdateInvalidQuantityWithAssemblyItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        // Lang::shouldReceive('get')->once();

        $item->addAssemblyItem($childItem, 'invalid quantity');
    }

    public function testUpdateAssemblyItemWhenItemIsNotAnAssembly()
    {
        $item = $this->newInventory();

        $this->assertFalse($item->updateAssemblyItem(1, 5));
    }

    public function testUpdateAssemblyItemsWhenItemIsNotAnAssembly()
    {
        $item = $this->newInventory();

        $this->assertEquals(0, $item->updateAssemblyItems([1, 2], 5));
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

        // Cache::shouldReceive('forget')->twice()->andReturn(true);

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

        // Cache::shouldReceive('forget')->times(4)->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        // Cache::shouldReceive('has')->once()->andReturn(false);
        // Cache::shouldReceive('forever')->once()->andReturn(true);

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

    // public function testGetAssemblyItemsCached()
    // {
    //     $item = $this->newInventory();

    //     $subItem = $this->newInventory();

    //     $item->addAssemblyItem($subItem);

    //     // Cache::shouldReceive('has')->once()->andReturn(true);
    //     // Cache::shouldReceive('get')->once()->andReturn('cached items');

    //     $cachedItems = $item->getAssemblyItems();

    //     $this->assertEquals('cached items', $item->getAssemblyItems());
    // }

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

        // Cache::shouldReceive('forget')->twice()->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);

        $this->assertTrue($table->removeAssemblyItem($tableTop));

        $this->assertNull($table->assemblies->first());
    }

    public function testRemoveAssemblyItemWhenItemIsNotAnAssembly()
    {
        $item = $this->newInventory();

        $this->assertFalse($item->removeAssemblyItem(1));
    }

    public function testRemoveAssemblyItemsWhenItemIsNotAnAssembly()
    {
        $item = $this->newInventory();

        $this->assertEquals(0, $item->removeAssemblyItems([1, 2]));
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

        // Cache::shouldReceive('forget')->times(7)->andReturn(true);
        // Event::shouldReceive('dispatch')->times(7)->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $screws->addAssemblyItem($metal, 5);

        $metal->addAssemblyItem($ore, 10);
        $metal->addAssemblyItem($flux, 5);

        // Cache::shouldReceive('has')->once()->andReturn(false);
        // Cache::shouldReceive('forever')->once()->andReturn(true);

        $list = $table->getAssemblyItemsList();

        $this->assertEquals('Table Top', $list[0]['name']);
        $this->assertEquals('Table Legs', $list[1]['name']);

        // Validate Table Top / Table Leg Depth
        $this->assertEquals(1, $list[0]['depth']);
        $this->assertEquals(1, $list[1]['depth']);

        $this->assertEquals('Screws', $list[0]['parts'][0]['name']);
        $this->assertEquals('Screws', $list[1]['parts'][0]['name']);

        // Validate Screw Depth
        $this->assertEquals(2, $list[0]['parts'][0]['depth']);
        $this->assertEquals(2, $list[1]['parts'][0]['depth']);

        $this->assertEquals('Metal', $list[0]['parts'][0]['parts'][0]['name']);
        $this->assertEquals('Metal', $list[1]['parts'][0]['parts'][0]['name']);

        // Validate Metal Depth
        $this->assertEquals(3, $list[0]['parts'][0]['parts'][0]['depth']);
        $this->assertEquals(3, $list[1]['parts'][0]['parts'][0]['depth']);

        $this->assertEquals('Ore', $list[0]['parts'][0]['parts'][0]['parts'][0]['name']);
        $this->assertEquals('Ore', $list[1]['parts'][0]['parts'][0]['parts'][0]['name']);

        // Validate Ore Depth
        $this->assertEquals(4, $list[0]['parts'][0]['parts'][0]['parts'][0]['depth']);
        $this->assertEquals(4, $list[1]['parts'][0]['parts'][0]['parts'][0]['depth']);
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

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidPartException');

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

        // Cache::shouldReceive('forget')->times(5)->andReturn(true);

        // Event::shouldReceive('dispatch')->times(5)->andReturn(true);

        $table->addAssemblyItem($tableTop, 1);
        $table->addAssemblyItem($tableLegs, 4);

        $tableTop->addAssemblyItem($screws, 1);
        $tableLegs->addAssemblyItem($screws, 2);

        $screws->addAssemblyItem($metal, 5);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidPartException');

        /*
         * Since metal is already apart of screws,
         * adding table as an assembly item of metal
         * would cause an infinite recursive query
         */
        $metal->addAssemblyItem($table);
    }

    public function testHasCachedAssemblyItems()
    {
        $item = $this->newInventory();

        // Cache::shouldReceive('has')->once()->andReturn(false);

        $this->assertFalse($item->hasCachedAssemblyItems());
    }

    public function testGetCachedAssemblyItems()
    {
        $item = $this->newInventory();

        // Cache::shouldReceive('has')->once()->andReturn(true);
        // Cache::shouldReceive('get')->once()->andReturn('cached items');

        $this->assertEquals(false, $item->getCachedAssemblyItems());
    }

    public function testForgetCachedAssemblyItems()
    {
        $item = $this->newInventory();

        $subItem = $this->newInventory();

        $item->addAssemblyItem($subItem);

        $item->getAssemblyItems();

        // Cache::shouldReceive('forget')->once()->andReturn(true);

        $this->assertTrue($item->forgetCachedAssemblyItems());
    }
}