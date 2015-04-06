<?php

use Stevebauman\Inventory\Models\InventoryTransaction;
use Illuminate\Support\Facades\Lang;

class InventoryTransactionReturnedTest extends InventoryTransactionTest
{
    public function testInventoryTransactionReturnedAfterSold()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returned(5, 'Returned', 25);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('Returned', $stock->reason);
        $this->assertEquals(25, $stock->cost);
    }

    public function testInventoryTransactionReturnedAllAfterSold()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returnedAll();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionReturnedPartialAfterSold()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returned(3);

        $this->assertEquals(2, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionReturnedAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->returned();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionReturnedAllAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->returnedAll();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionReturnedPartialAfterCheckoutRollback()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->returned('testing');

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);
    }

    public function testInventoryTransactionReturnedPartialAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->returned(2);

        $this->assertEquals(3, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);
    }

    public function testInventoryTransactionReturnedPartialAfterSoldRollBack()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returned('testing');

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionReturnedInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->returned(5);
    }

    public function testInventoryTransactionReturnedDefaultReason()
    {
        $transaction = $this->newTransaction();

        Lang::shouldReceive('get')->twice()->andReturn('test');

        $transaction->sold(5)->returned(5);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('test', $stock->reason);
    }
}