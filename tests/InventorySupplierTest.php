<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Supplier;
use Stevebauman\Inventory\Models\Inventory;

class InventorySupplierTest extends FunctionalTestCase
{
    
    public function testInventorySupplierAttach()
    {
        $item = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item->addSupplier($newSupplier);

        $supplier = $item->suppliers()->first();

        $this->assertEquals('Supplier', $supplier->name);
    }

    public function testInventorySupplierDetach()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item = Inventory::find(1);

        $this->assertTrue($item->removeSupplier(1));
    }

    public function testInventorySupplierDetachAll()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item2 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item2->addSupplier($newSupplier);

        $item = Inventory::find(1);

        $item->removeAllSuppliers();

        $this->assertEquals(0, $item->suppliers()->count());
    }

    public function testSupplierAttachItem()
    {
        $item = $this->newInventory();

        $supplier = $this->newSupplier();

        $item->addSupplier($supplier);

        $this->assertEquals($supplier->name, $item->suppliers()->first()->name);
    }

    public function testSupplierDetachItem()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item = Inventory::find(1);

        $item->removeSupplier(1);

        $this->assertEquals(0, $item->suppliers()->count());
    }

    public function testSupplierInvalidSupplierException()
    {
        $item1 = $this->newInventory();

        $newSupplier = $this->newSupplier();

        $item1->addSupplier($newSupplier);

        $item = Inventory::find(1);

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidSupplierException');

        $item->addSupplier('testing');
        $item->removeSupplier('testing');
    }
}
