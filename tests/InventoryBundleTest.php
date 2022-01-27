<?php

namespace Stevebauman\Inventory\Tests;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

class InventoryBundleTest extends FunctionalTestCase
{
    public function testMakeBundle()
    {
        $item = $this->newInventory();

        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        Event::shouldReceive('dispatch')->once()->andReturn(true);

        $item->makeBundle();

        $this->assertTrue($item->is_bundle);
    }

    public function testAddBundleItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Cache::shouldReceive('forget')->once()->andReturn(true);

        $item->addBundleItem($childItem, 10);

        $items = $item->bundles;

        $this->assertEquals('Child Item', $items->first()->name);
        $this->assertEquals(10, $items->first()->pivot->quantity);
    }

    public function testAddBundleItems()
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

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $item->addBundleItems([$childItem, $childItem2], 10);

        $items = $item->bundles;

        $this->assertEquals('Child Item', $items->get(0)->name);
        $this->assertEquals(10, $items->get(0)->pivot->quantity);

        $this->assertEquals('Child Item 2', $items->get(1)->name);
        $this->assertEquals(10, $items->get(1)->pivot->quantity);
    }

    // TODO: this should increment the quantity of a single item
    public function testAddSameBundleItems()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $item->addBundleItems([$childItem, $childItem, $childItem, $childItem]);

        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('forever')->once()->andReturn(true);

        $this->assertEquals(1, $item->getBundleItems()->count());
        $this->assertEquals(4, $item->getBundleItems()->first()->pivot->quantity);
    }

    public function testAddBundleItemExtraAttributes()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Cache::shouldReceive('forget')->once()->andReturn(true);

        $item->addBundleItem($childItem, 10, ['extra' => 'testing']);

        /*
         * Tests that the extra array is merged
         * and updated successfully with the quantity
         */
        $this->assertEquals(10, $item->bundles()->first()->pivot->quantity);
    }

    public function testAddInvalidBundleItem()
    {
        $item = $this->newInventory();

        try {
            $this->expectError('TypeError');
            $item->addBundleItem('invalid item');

            $passes = false;
        } catch (\Exception $e) {
            $passes = true;
        }

        $this->assertTrue($passes);
    }

    public function testAddInvalidQuantityWithBundleItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Lang::shouldReceive('get')->once()->andReturn('Invalid Quantity');

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->addBundleItem($childItem, 'invalid quantity');
    }

    public function testUpdateBundleItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        Cache::shouldReceive('forget')->times(3)->andReturn(true);

        $item->addBundleItem($childItem);

        $item->updateBundleItem($childItem, 5);

        $this->assertEquals(5, $item->bundles()->first()->pivot->quantity);

        $item->updateBundleItem($childItem->id, 10);

        $this->assertEquals(10, $item->bundles()->first()->pivot->quantity);
    }

    public function testUpdateBundleItems()
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

        Cache::shouldReceive('forget')->times(4)->andReturn(true);

        $item->addBundleItem($childItem);
        $item->addBundleItem($childItem2);

        $item->updateBundleItems([$childItem, $childItem2], 10);

        $items = $item->bundles()->get();

        $this->assertEquals(10, $items->get(0)->pivot->quantity);
        $this->assertEquals(10, $items->get(1)->pivot->quantity);
    }

    public function testUpdateInvalidQuantityWithBundleItem()
    {
        $item = $this->newInventory();

        $childItem = $this->newInventory([
            'name' => 'Child Item',
            'metric_id' => $item->metric_id,
            'category_id' => $item->category_id,
        ]);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $item->addBundleItem($childItem, 'invalid quantity');
    }

    public function testUpdateBundleItemWhenItemIsNotAnBundle()
    {
        $item = $this->newInventory();

        $this->assertFalse($item->updateBundleItem(1, 5));
    }

    public function testUpdateBundleItemsWhenItemIsNotAnBundle()
    {
        $item = $this->newInventory();

        $this->assertEquals(0, $item->updateBundleItems([1, 2], 5));
    }

    public function testGetBundles()
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

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $table->addBundleItem($tableTop, 1);
        $table->addBundleItem($tableLegs, 4);

        $items = $table->bundles;

        $this->assertEquals(2, $items->count());

        $this->assertEquals('Table Top', $items->get(0)->name);
        $this->assertEquals(1, $items->get(0)->pivot->quantity);

        $this->assertEquals('Table Legs', $items->get(1)->name);
        $this->assertEquals(4, $items->get(1)->pivot->quantity);

        $this->assertNull($items->get(2));
    }

    public function testGetBundlesRecursive()
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

        Cache::shouldReceive('forget')->times(4)->andReturn(true);

        $table->addBundleItem($tableTop, 1);
        $table->addBundleItem($tableLegs, 4);

        $tableTop->addBundleItem($screws, 1);
        $tableLegs->addBundleItem($screws, 2);

        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('forever')->once()->andReturn(true);

        $items = $table->getBundleItems();

        $this->assertEquals(2, $items->count());

        $this->assertEquals('Table Top', $items->get(0)->name);
        $this->assertEquals(1, $items->get(0)->pivot->quantity);

        $this->assertEquals('Table Legs', $items->get(1)->name);
        $this->assertEquals(4, $items->get(1)->pivot->quantity);

        // One screw item exists on each model
        $this->assertEquals(1, $items->get(0)->bundles->count());
        $this->assertEquals(1, $items->get(1)->bundles->count());

        // One screw for table top
        $this->assertEquals('Screws', $items->get(0)->bundles->get(0)->name);
        $this->assertEquals(1, $items->get(0)->bundles->get(0)->pivot->quantity);

        // Two screws for table legs
        $this->assertEquals('Screws', $items->get(1)->bundles->get(0)->name);
        $this->assertEquals(2, $items->get(1)->bundles->get(0)->pivot->quantity);
    }

    public function testGetBundleItemsCached()
    {
        $item = $this->newInventory();

        Cache::shouldReceive('has')->once()->andReturn(true);
        Cache::shouldReceive('get')->once()->andReturn('cached items');

        $this->assertEquals('cached items', $item->getBundleItems());
    }

    public function testRemoveBundleItem()
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

        Cache::shouldReceive('forget')->twice()->andReturn(true);

        $table->addBundleItem($tableTop, 1);

        $this->assertTrue($table->removeBundleItem($tableTop));

        $this->assertNull($table->bundles->first());
    }

    public function testRemoveBundleItemWhenItemIsNotAnBundle()
    {
        $item = $this->newInventory();

        $this->assertFalse($item->removeBundleItem(1));
    }

    public function testRemoveBundleItemsWhenItemIsNotAnBundle()
    {
        $item = $this->newInventory();

        $this->assertEquals(0, $item->removeBundleItems([1, 2]));

        $bundleItems = $item->getBundleItems();

        $this->assertEquals(0, count($bundleItems));
    }

    public function testGetBundleItemsList()
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

        Cache::shouldReceive('forget')->times(7)->andReturn(true);

        $table->addBundleItem($tableTop, 1);
        $table->addBundleItem($tableLegs, 4);

        $tableTop->addBundleItem($screws, 1);
        $tableLegs->addBundleItem($screws, 2);

        $screws->addBundleItem($metal, 5);

        $metal->addBundleItem($ore, 10);
        $metal->addBundleItem($flux, 5);

        Cache::shouldReceive('has')->once()->andReturn(false);
        Cache::shouldReceive('forever')->once()->andReturn(true);

        $list = $table->getBundleItemsList();

        $this->assertEquals('Table Top', $list[0]['name']);
        $this->assertEquals('Table Legs', $list[1]['name']);

        // Validate Table Top / Table Leg Depth
        $this->assertEquals(1, $list[0]['depth']);
        $this->assertEquals(1, $list[1]['depth']);

        $this->assertEquals('Screws', $list[0]['components'][0]['name']);
        $this->assertEquals('Screws', $list[1]['components'][0]['name']);

        // Validate Screw Depth
        $this->assertEquals(2, $list[0]['components'][0]['depth']);
        $this->assertEquals(2, $list[1]['components'][0]['depth']);

        $this->assertEquals('Metal', $list[0]['components'][0]['components'][0]['name']);
        $this->assertEquals('Metal', $list[1]['components'][0]['components'][0]['name']);

        // Validate Metal Depth
        $this->assertEquals(3, $list[0]['components'][0]['components'][0]['depth']);
        $this->assertEquals(3, $list[1]['components'][0]['components'][0]['depth']);

        $this->assertEquals('Ore', $list[0]['components'][0]['components'][0]['components'][0]['name']);
        $this->assertEquals('Ore', $list[1]['components'][0]['components'][0]['components'][0]['name']);

        // Validate Ore Depth
        $this->assertEquals(4, $list[0]['components'][0]['components'][0]['components'][0]['depth']);
        $this->assertEquals(4, $list[1]['components'][0]['components'][0]['components'][0]['depth']);
    }

    public function testInvalidComponentException()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $table = $this->newInventory([
            'name' => 'Table',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidComponentException');

        $table->addBundleItem($table);
    }

    public function testNestedInvalidComponentException()
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

        Cache::shouldReceive('forget')->times(5)->andReturn(true);

        $table->addBundleItem($tableTop, 1);
        $table->addBundleItem($tableLegs, 4);

        $tableTop->addBundleItem($screws, 1);
        $tableLegs->addBundleItem($screws, 2);

        $screws->addBundleItem($metal, 5);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidComponentException');

        /*
         * Since metal is already apart of screws,
         * adding table as an bundle item of metal
         * would cause an infinite recursive query
         */
        $metal->addBundleItem($table);
    }

    public function testHasCachedBundleItems()
    {
        $item = $this->newInventory();

        Cache::shouldReceive('has')->once()->andReturn(false);

        $this->assertFalse($item->hasCachedBundleItems());
    }

    public function testGetCachedBundleItems()
    {
        $item = $this->newInventory();

        Cache::shouldReceive('has')->once()->andReturn(true);
        Cache::shouldReceive('get')->once()->andReturn('cached items');

        $this->assertEquals('cached items', $item->getCachedBundleItems());
    }

    public function testForgetCachedBundleItems()
    {
        $item = $this->newInventory();

        Cache::shouldReceive('forget')->once()->andReturn(true);

        $this->assertTrue($item->forgetCachedBundleItems());
    }
}