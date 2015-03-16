<?php

use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionCancelledTest extends InventoryTransactionTest
{
    public function testInventoryTransactionCancelAfterReserved()
    {
        $transaction = $this->newTransaction();

        $transaction->reserved(5)->cancel();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_CANCELLED, $transaction->state);
    }

    public function testInventoryTransactionCancelAfterOnHold()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->cancel();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_CANCELLED, $transaction->state);
    }

    public function testInventoryTransactionCancelAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->cancel();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_CANCELLED, $transaction->state);
    }

    public function testInventoryTransactionCancelAfterBackOrder()
    {
        $transaction = $this->newTransaction();

        $transaction->backOrder(500)->cancel();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_CANCELLED, $transaction->state);
    }

    public function testInventoryTransactionCancelAfterOrdered()
    {
        $transaction = $this->newTransaction();

        $transaction->ordered(500)->cancel();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_CANCELLED, $transaction->state);
    }

    public function testInventoryTransactionCancelAfterOpened()
    {
        $transaction = $this->newTransaction();

        $transaction->cancel();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_CANCELLED, $transaction->state);
    }

    public function testInventoryTransactionCancelAfterCancelFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->cancel()->cancel();
    }
}