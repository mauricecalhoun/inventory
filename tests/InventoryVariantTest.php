<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Inventory;

/**
 * Inventory Variant Test
 * 
 * @coversDefaultClass \Stevebauman\Inventory\Traits\InventoryVariantTrait
 */
class InventoryVariantTest extends FunctionalTestCase
{
    /**
     * Test new variant
     *  
     * @covers ::newVariant
     * 
     * @return void
     */
    public function testNewVariant()
    {
        $item = $this->newInventory();

        $milk = Inventory::find($item->id);

        $chocolateMilk = $milk->newVariant();

        $chocolateMilk->name = 'Chocolate Milk';

        $chocolateMilk->save();

        $this->assertEquals($chocolateMilk->parent_id, $milk->id);
        $this->assertEquals($chocolateMilk->category_id, $milk->category_id);
        $this->assertEquals($chocolateMilk->metric_id, $milk->metric_id);
    }

    /**
     * Test create variant
     * 
     * @covers ::createVariant
     * @covers ::isVariant
     *
     * @return void
     */
    public function testCreateVariant()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $name = 'Cherry Coke';
        $description = 'Delicious Cherry Coke';

        $cherryCoke = $coke->createVariant($name, $description);

        $this->assertTrue($cherryCoke->isVariant());
        $this->assertEquals($coke->id, $cherryCoke->parent_id);
        $this->assertEquals($name, $cherryCoke->name);
        $this->assertEquals($description, $cherryCoke->description);
        $this->assertEquals($category->id, $cherryCoke->category_id);
        $this->assertEquals($metric->id, $cherryCoke->metric_id);
    }

    /**
     * Test make variant
     * 
     * @covers ::makeVariantOf
     *
     * @return void
     */
    public function testMakeVariant()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = Inventory::create([
            'name' => 'Cherry Coke',
            'description' => 'Delicious Cherry Coke',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke->makeVariantOf($coke);

        $this->assertEquals($cherryCoke->parent_id, $coke->id);
    }

    /**
     * Test is variant
     *
     * @covers ::isVariant
     * 
     * @return void
     */
    public function testIsVariant()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $isCokeVariant = $coke->isVariant();
        $isCherryVariant = $cherryCoke->isVariant();

        $this->assertFalse($isCokeVariant);
        $this->assertTrue($isCherryVariant);
    }

    /**
     * Test get variants
     * 
     * @covers ::getVariants
     *
     * @return void
     */
    public function testGetVariants()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $variants = $coke->getVariants();

        $this->assertInstanceOf('Illuminate\Support\Collection', $variants);
        $this->assertEquals(1, $variants->count());
    }

    /**
     * Test get parent
     * 
     * @covers ::getParent
     *
     * @return void
     */
    public function testGetParent()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = Inventory::create([
            'name' => 'Cherry Coke',
            'description' => 'Delicious Cherry Coke',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke->makeVariantOf($coke);

        $parent = $cherryCoke->getParent();

        $this->assertEquals('Coke', $parent->name);
        $this->assertEquals(null, $parent->parent_id);
    }

    /**
     * Test get total variant stock
     * 
     * @covers ::getTotalVariantStock
     *
     * @return void
     */
    public function testGetTotalVariantStock()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $vanillaCherryCoke = $coke->createVariant('Vanilla Cherry Coke');

        $vanillaCherryCoke->makeVariantOf($coke);

        $location = $this->newLocation();

        $vanillaCherryCoke->createStockOnLocation(40, $location);

        $this->assertEquals(40, $coke->getTotalVariantStock());
        $this->assertEquals(0, $coke->getTotalStock());
    }

    /**
     * Test parents cannot be variants
     * 
     * @covers ::makeVariantOf
     * @covers \Stevebauman\Inventory\Exceptions\InvalidVariantException
     *
     * @return void
     */
    public function testParentsCannotBeVariants()
    {
        $category = $this->newCategory();
        $metric = $this->newMetric();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidVariantException');

        $vanillaCherryCoke = $cherryCoke->createVariant('Vanilla Cherry Coke');

        $vanillaCherryCoke->makeVariantOf($cherryCoke);
    }

    /**
     * Test parents cannot have location
     * 
     * @covers \Stevebauman\Inventory\Traits\InventoryTrait::createStockOnLocation
     *
     * @return void
     */
    public function testParentsCannotHaveLocation() 
    {
        $location = $this->newLocation();

        $metric = $this->newMetric();

        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Delicious Pop',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->createStockOnLocation(10, $location);
    }

    /**
     * Test cannot add supplier to parent
     * 
     * @covers \Stevebauman\Inventory\Traits\InventoryTrait::addSupplier
     *
     * @return void
     */
    public function testCannotAddSupplierToParent() 
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $supplier = $this->newSupplier();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Actually coke is kinda gross',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->addSupplier($supplier);
    }

    /**
     * Test cannot add suppliers to parent
     * 
     * @covers \Stevebauman\Inventory\Traits\InventoryTrait::addSuppliers
     *
     * @return void
     */
    public function testCannotAddSuppliersToParent() 
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $supplier1 = $this->newSupplier();

        $supplier2 = $this->newSupplier();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Actually coke is kinda gross',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->addSuppliers([$supplier1, $supplier2]);
    }

    /**
     * Test parents cannot create stock on location
     * 
     * @covers \Stevebauman\Inventory\Traits\InventoryTrait::createStockOnLocation
     *
     * @return void
     */
    public function testParentsCannotCreateStockOnLocation()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'No really, I\'m getting sick of it',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $location = $this->newLocation();

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->createStockOnLocation(10, $location);
    }

    /**
     * Test parents cannot have new stock on location
     * 
     * @covers \Stevebauman\Inventory\Traits\InventoryTrait::newStockOnLocation
     *
     * @return void
     */
    public function testParentsCannotHaveNewStockOnLocation()
    {
        $metric = $this->newMetric();

        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'No really, I\'m getting sick of it',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $location = $this->newLocation();

        $this->expectException('Stevebauman\Inventory\Exceptions\IsParentException');

        $coke->newStockOnLocation($location);
    }

    /**
     * Test inventory becomes parent when variant added
     * 
     * @covers ::makeVariantOf
     *
     * @return void
     */
    public function testInventoryBecomesParentWhenVariantAdded() {
        $metric = $this->newMetric();
        
        $category = $this->newCategory();

        $coke = Inventory::create([
            'name' => 'Coke',
            'description' => 'Honestly why drink brown fizzy garbage water',
            'metric_id' => $metric->id,
            'category_id' => $category->id,
        ]);

        $cherryCoke = $coke->createVariant('Cherry Coke');

        $cherryCoke->makeVariantOf($coke);

        $this->assertTrue($coke->is_parent);
    }
}
