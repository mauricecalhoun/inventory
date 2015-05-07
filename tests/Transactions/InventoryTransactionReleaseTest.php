<?php

namespace Stevebauman\Inventory\tests\Transactions;

use Illuminate\Support\Facades\Lang;
use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionReleaseTest extends InventoryTransactionTest
{
    public function testInventoryTransactionRelease()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->release(null, 'Released', 25);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_RELEASED, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('Released', $stock->reason);
        $this->assertEquals(25, $stock->cost);
    }

    public function testInventoryTransactionReleasePartial()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->release(2);

        $this->assertEquals(3, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_ON_HOLD, $transaction->state);
    }

    public function testInventoryTransactionReleaseAll()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->releaseAll();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_RELEASED, $transaction->state);
    }

    public function testInventoryTransactionReleasePartialAll()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->release(10);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_RELEASED, $transaction->state);
    }

    public function testInventoryTransactionReleaseInvalidQuantity()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->release('asddsa');

        $this->assertEquals(5, $transaction->quantity);
    }

    public function testInventoryTransactionReleaseInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->ordered(5)->release();
    }

    public function testInventoryTransactionReleaseDefaultReason()
    {
        $transaction = $this->newTransaction();

        Lang::shouldReceive('get')->twice()->andReturn('test');

        $transaction->hold(5)->release(3);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('test', $stock->reason);
    }
}
