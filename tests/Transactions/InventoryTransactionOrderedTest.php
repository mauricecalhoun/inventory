<?php

namespace Stevebauman\Inventory\tests\Transactions;

use Illuminate\Support\Facades\Lang;
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

        $transaction->ordered(5)->received(5, 'Received Order', 25);

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_ORDERED_RECEIVED, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('Received Order', $stock->reason);
        $this->assertEquals(25, $stock->cost);
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

    public function testInventoryTransactionOrderedInvalidQuantityException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->ordered('40a');
    }

    public function testInventoryTransactionOrderedInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->reserved(5)->ordered(10);
    }

    public function testInventoryTransactionReceivedDefaultReason()
    {
        $transaction = $this->newTransaction();

        Lang::shouldReceive('get')->once()->andReturn('test');

        $transaction->ordered(5)->received(5);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('test', $stock->reason);
    }
}
