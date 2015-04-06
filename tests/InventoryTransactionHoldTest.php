<?php

use Stevebauman\Inventory\Models\InventoryTransaction;
use Illuminate\Support\Facades\Lang;

class InventoryTransactionHoldTest extends InventoryTransactionTest
{
    public function testInventoryTransactionHold()
    {
        $transaction = $this->newTransaction();

        $transaction->hold(10, 'Holding', 25);

        $this->assertEquals(10, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_INVENTORY_ON_HOLD, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('Holding', $stock->reason);
        $this->assertEquals(25, $stock->cost);
    }

    public function testInventoryTransactionHoldInvalidQuantityException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->hold('40a');
    }

    public function testInventoryTransactionHoldInvalidTransactionStateException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->ordered(5)->hold(5);
    }

    public function testInventoryTransactionHoldNotEnoughStockException()
    {
        $transaction = $this->newTransaction();

        $this->setExpectedException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->hold(500);
    }

    public function testInventoryTransactionHoldDefaultReason()
    {
        $transaction = $this->newTransaction();

        Lang::shouldReceive('get')->once()->andReturn('test');

        $transaction->hold(5);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('test', $stock->reason);
    }
}