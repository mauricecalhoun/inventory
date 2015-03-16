<?php

use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionBackOrderTest extends InventoryTransactionTest
{
    public function testInventoryTransactionBackOrder()
    {
        $transaction = $this->newTransaction();

        $transaction->backOrder(500);

        $this->assertEquals(500, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_BACK_ORDERED, $transaction->state);
    }

    public function testInventoryTransactionBackOrderSufficientStockException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\StockIsSufficientException');

        $transaction->backOrder(1);
    }

    public function testInventoryTransactionBackOrderInvalidQuantityException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->backOrder('40s');
    }

    public function testInventoryTransactionBackOrderInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->checkout(5)->backOrder(3);
    }
}