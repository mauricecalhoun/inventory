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
}