<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Supplier;
use Stevebauman\Inventory\Models\Inventory;

/**
 * Inventory Supplier Test
 * 
 * @coversDefaultClass \Stevebauman\Inventory\Traits\InventoryTrait
 */
class InventorySupplierTest extends FunctionalTestCase
{
    /**
     * Test inventory supplier attach
     * 
     * @covers ::addSupplier
     *
     * @return void
     */
    public function testInventorySupplierAttach()
    {
        $item = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item->addSupplier($newSupplier);

        $supplier = $item->suppliers()->first();

        $this->assertEquals('Supplier', $supplier->name);
    }

    /**
     * Test inventory supplier detach
     * 
     * @covers ::removeSupplier
     *
     * @return void
     */
    public function testInventorySupplierDetach()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item = Inventory::find($item1->id);

        $this->assertTrue($item->removeSupplier($newSupplier->id));
    }

    /**
     * Test inventory supplier detach all
     * 
     * @covers ::removeAllSuppliers
     *
     * @return void
     */
    public function testInventorySupplierDetachAll()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item2 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item2->addSupplier($newSupplier);

        $item = Inventory::find($item1->id);

        $item->removeAllSuppliers();

        $this->assertEquals(0, $item->suppliers()->count());
    }

    /**
     * Test supplier attach item
     * 
     * @covers ::addSupplier
     *
     * @return void
     */
    public function testSupplierAttachItem()
    {
        $item = $this->newInventory();

        $supplier = $this->newSupplier();

        $item->addSupplier($supplier);

        $this->assertEquals($supplier->name, $item->suppliers()->first()->name);
    }

    /**
     * Test supplier detach item
     * 
     * @covers ::removeSupplier
     *
     * @return void
     */
    public function testSupplierDetachItem()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item = Inventory::find($item1->id);

        $item->removeSupplier($newSupplier->id);

        $this->assertEquals(0, $item->suppliers()->count());
    }

    /**
     * Test supplier invalid supplier exception
     * 
     * @covers ::addSupplier
     * @covers ::removeSupplier
     *
     * @return void
     */
    public function testSupplierInvalidSupplierException()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item = Inventory::find($item1->id);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidSupplierException');

        $item->addSupplier('testing');
        $item->removeSupplier('testing');
    }
}
