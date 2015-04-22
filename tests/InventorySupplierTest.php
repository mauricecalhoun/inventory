<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Supplier;
use Stevebauman\Inventory\Models\Inventory;

class InventorySupplierTest extends InventoryTest
{
    protected function newSupplier()
    {
        return Supplier::create([
            'name' => 'Supplier',
            'address' => '123 Fake St',
            'postal_code' => '12345',
            'zip_code' => '12345',
            'region' => 'ON',
            'city' => 'Toronto',
            'country' => 'Canada',
            'contact_title' => 'Manager',
            'contact_name' => 'John Doe',
            'contact_phone' => '555 555 5555',
            'contact_fax' => '555 555 5555',
            'contact_email' => 'john.doe@email.com',
        ]);
    }

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

        $supplier = $this->newSupplier();

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

    public function testSupplierInvalidSupplierException()
    {
        $this->testSupplierAttachItem();

        $item = Inventory::find(1);

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidSupplierException');

        $item->addSupplier('testing');
        $item->removeSupplier('testing');
    }
}
