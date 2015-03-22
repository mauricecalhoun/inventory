<?php

use Stevebauman\Inventory\Models\Metric;
use Stevebauman\Inventory\Models\Category;
use Stevebauman\Inventory\Models\InventoryAssembly;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class InventoryAssemblyTest extends InventoryTest
{
    public function testInventoryAssemblyCreation()
    {
        $this->newInventory();

        $metric = Metric::find(1);
        $category = Category::find(1);

        $cmilk = Inventory::create(array(
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'name' => 'Chocolate Milk',
            'description' => 'Chocolate Milk',
        ));

        DB::shouldReceive('beginTransaction')->once()->shouldReceive('commit')->once();
        Event::shouldReceive('fire')->once();

        $cmilk->makeAssembly();

        $this->assertTrue($cmilk->isAssembly());
    }

    public function testInventoryAddAssemblyItem()
    {
        $this->testInventoryAssemblyCreation();

        $metric = Metric::find(1);
        $category = Category::find(1);

        $milk = Inventory::find(1);

        $cmilk = Inventory::find(2);

        $quik = Inventory::create(array(
            'metric_id' => $metric->id,
            'category_id' => $category->id,
            'name' => 'Nes Quik',
            'description' => 'Cocoa Powder',
        ));

        $cmilk->addAssemblyItem($milk);

        $cmilk->addAssemblyItem($quik, null, 2);

        $items = $cmilk->getAssemblyItems();

        $this->assertEquals(2, $items->count());

        $firstItem = $items->get(0);
        $secondItem = $items->get(1);

        $this->assertEquals($firstItem->name, 'Milk');
        $this->assertEquals($secondItem->name, 'Nes Quik');
    }
}