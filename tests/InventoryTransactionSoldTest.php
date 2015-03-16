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

    public function testInventoryTransactionSoldQuantityFailure()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->sold(5000);
    }

    public function testInventoryTransactionSoldQuantityFormatFailure()
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
}