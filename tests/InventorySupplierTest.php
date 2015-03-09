<?php

use Stevebauman\Inventory\Models\Supplier;
use Stevebauman\Inventory\Models\Inventory;

class InventorySupplierTest extends InventoryTest
{
    public function testInventorySupplierAttach()
    {
        $this->testInventoryCreation();

        $item = Inventory::find(1);

        $newSupplier = new Supplier;
        $newSupplier->name = 'Supplier';
        $newSupplier->save();

        $item->addSupplier($newSupplier);

        $supplier = $item->suppliers()->first();

        $this->assertEquals('Supplier', $supplier->name);
    }

    public function testInventorySupplierDetach()
    {
        $this->testInventorySupplierAttach();

        $item = Inventory::find(1);

        $this->assertTrue($item->removeSupplier(1));
    }

    public function testInventorySupplierDetachAll()
    {
        $this->testInventorySupplierAttach();

        $this->testInventorySupplierAttach();

        $item = Inventory::find(1);

        $item->removeAllSuppliers();

        $this->assertEquals(0, $item->suppliers()->count());
    }

    public function testSupplierAttachItem()
    {
        $this->testInventoryCreation();

        $supplier = new Supplier;
        $supplier->name = 'Test';
        $supplier->save();

        $item = Inventory::find(1);

        $item->addSupplier($supplier);

        $this->assertEquals($supplier->name, $item->suppliers()->first()->name);
    }

    public function testSupplierDetachItem()
    {
        $this->testSupplierAttachItem();

        $item = Inventory::find(1);

        $item->removeSupplier(1);

        $this->assertEquals(0, $item->suppliers()->count());
    }
}