<?php

use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionRemoveTest extends InventoryTransactionTest
{
    public function testInventoryTransactionRemove()
    {
        $transaction = $this->newTransaction();

        $transaction->remove(5);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_REMOVED, $transaction->state);
    }

    public function testInventoryTransactionRemovePartial()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->remove(2);

        $this->assertEquals(3, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_ON_HOLD, $transaction->state);
    }


    public function testInventoryTransactionRemovePartialAll()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(5)->remove(10);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_REMOVED, $transaction->state);
    }

    public function testInventoryTransactionRemoveInvalidQuantityException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->remove('10a');
    }

    public function testInventoryTransactionRemoveBlankInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->remove();
    }

    public function testInventoryTransactionRemoveInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->ordered(5)->remove();
    }
}