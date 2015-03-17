<?php

use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionReservedTest extends InventoryTransactionTest
{
    public function testInventoryTransactionReserved()
    {
        $transaction = $this->newTransaction();

        $transaction->reserved(10);

        $this->assertEquals(10, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RESERVED, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals(10, $stock->quantity);
    }

    public function testInventoryTransactionReservedNotEnoughStockException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->reserved(100);
    }

    public function testInventoryTransactionReservedInvalidQuantityException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->reserved('40a');
    }

    public function testInventoryTransactionReservedInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->hold(5)->reserved(20);
    }

    public function testInventoryTransactionReservedCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved();

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RESERVED, $transaction->state);
    }
}