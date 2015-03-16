<?php

use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionOrderedTest extends InventoryTransactionTest
{
    public function testInventoryTransactionOrdered()
    {
        $transaction = $this->newTransaction();

        $transaction->ordered(5);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_ORDERED_PENDING, $transaction->state);
    }

    public function testInventoryTransactionOrderedReceived()
    {
        $transaction = $this->newTransaction();

        $transaction->ordered(5)->received();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_ORDERED_RECEIVED, $transaction->state);
    }

    public function testInventoryTransactionOrderedReceivedPartial()
    {
        $transaction = $this->newTransaction();

        $transaction->ordered(5)->received(2);

        $this->assertEquals(3, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_ORDERED_PENDING, $transaction->state);
    }

    public function testInventoryTransactionOrderedReceivedPartialAll()
    {
        $transaction = $this->newTransaction();

        $transaction->ordered(5)->received(10);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_ORDERED_RECEIVED, $transaction->state);
    }
}