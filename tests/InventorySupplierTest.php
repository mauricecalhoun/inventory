<?php

namespace Stevebauman\Inventory\Tests;

use Stevebauman\Inventory\Models\Supplier;
use Stevebauman\Inventory\Models\Inventory;
use Illuminate\Support\Facades\DB;

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
     * @return void
     */
    public function testInventorySupplierAttach()
    {
        $item = $this->newInventory();

        $newSupplier = $this->newSupplier();

        // Sometimes we get "facade root not set" errors, so we have to do
        // this on the first test.  Why?  ...  Laravel I guess is why.
        DB::shouldReceive('beginTransaction')->once()->andReturn(true);
        DB::shouldReceive('commit')->once()->andReturn(true);

        $item->addSupplier($newSupplier);

        $supplier = $item->suppliers()->first();

        $this->assertEquals('Supplier', $supplier->name);
    }

    /**
     * Test inventory supplier detach
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

    public function testInventorySupplierAddMany()
    {
        $item = $this->newInventory();

        $supp1 = $this->newSupplier();

        $supp2 = $this->newSupplier();
    
        $supp3 = $this->newSupplier();

        $item->addSuppliers([$supp1, $supp2, $supp3]);

        $this->assertEquals(3, $item->suppliers()->count());
    }

    public function testInventorySupplierRemoveMany()
    {
        $item = $this->newInventory();

        $supp1 = $this->newSupplier();

        $supp2 = $this->newSupplier();
    
        $supp3 = $this->newSupplier();

        $item->addSuppliers([$supp1, $supp2, $supp3]);

        $item->removeSuppliers([$supp1, $supp2, $supp3]);

        $this->assertEquals(0, $item->suppliers()->count());
    }

    public function testInventorySupplierRemoveSubset()
    {
        $item = $this->newInventory();

        $newSupplier1 = $this->newSupplier();

        $newSupplier2 = $this->newSupplier();
        
        $item->addSupplier($newSupplier1);

        $item->addSupplier($newSupplier2);

        $item->removeSuppliers([$newSupplier1, $newSupplier2]);

        $this->assertEquals(0, $item->suppliers()->count());
    }

    /**
     * Test supplier attach item
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

    public function testInventoryAddSupplierSKU() 
    {
        $item = $this->newInventory();

        $supplier = $this->newSupplier();

        $item->addSupplier($supplier);

        $item->addSupplierSKU($supplier, 'test123');

        $this->assertEquals('test123', $item->getSupplierSKU($supplier->id));
    }

    public function testInventoryUpdateSupplierSKU()
    {
        $item = $this->newInventory();

        $supplier = $this->newSupplier();

        $item->addSupplier($supplier);

        $item->addSupplierSKU($supplier, 'test234');

        $item->updateSupplierSKU($supplier->code, 'test345');

        $this->assertEquals('test345', $item->getSupplierSKU($supplier->code));
    }
}
