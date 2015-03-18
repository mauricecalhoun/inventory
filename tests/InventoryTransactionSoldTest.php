<?php

use Stevebauman\Inventory\Models\InventoryTransaction;

class InventoryTransactionSoldTest extends InventoryTransactionTest
{
    public function testInventoryTransactionSold()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionSoldNotEnoughStockFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->sold(5000);
    }

    public function testInventoryTransactionSoldInvalidQuantityFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->sold('50a');
    }

    public function testInventoryTransactionSoldAndThenReturned()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returned();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionSoldAndThenReturnedAll()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returnedAll();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionSoldAndThenReturnedPartial()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returned(2);

        $this->assertEquals(3, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionSoldAndThenReturnedPartialAll()
    {
        $transaction = $this->newTransaction();

        $transaction->sold(5)->returned(5);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionSoldAmount()
    {
        $transaction = $this->newTransaction();

        $transaction->soldAmount(5);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionSoldAmountInvalidQuantityFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->soldAmount('40a');
    }

    public function testInventoryTransactionSoldAmountNotEnoughStockFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->soldAmount(5000);
    }
}