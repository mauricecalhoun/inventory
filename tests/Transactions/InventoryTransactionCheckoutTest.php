<?php

namespace Stevebauman\Inventory\Tests\Transactions;

use Illuminate\Support\Facades\Lang;
// use Illuminate\Support\Facades\DB;
use Stevebauman\Inventory\Models\InventoryTransaction;
use Stevebauman\Inventory\Tests\FunctionalTestCase;

/**
 * Inventory Transaction Checkout Test
 * 
 * @coversDefaultClass \InventoryTransaction
 */
class InventoryTransactionCheckoutTest extends FunctionalTestCase
{
    public function testInventoryTransactionCheckout()
    {
        $transaction = $this->newTransaction();

        // DB::shouldReceive('startTransaction')->once();

        // DB::shouldReceive('commit')->once();

        $transaction->checkout(5, 'Checking out', 25);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('Checking out', $stock->reason);
        $this->assertEquals(25, $stock->cost);
    }

    public function testInventoryTransactionIsCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5);

        $this->assertTrue($transaction->isCheckout());
    }

    public function testInventoryTransactionIsNotCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->reserved(5);

        $this->assertFalse($transaction->isCheckout());
    }

    public function testInventoryTransactionCheckoutFailure()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\NotEnoughStockException');

        $transaction->checkout(5000);
    }

    public function testInventoryTransactionCheckoutQuantityFailure()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidQuantityException');

        $transaction->checkout('30as');
    }

    public function testInventoryTransactionReservationAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved();

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RESERVED, $transaction->state);
    }

    /**
     * The transaction quantity stays at 5 because a partial reserve cannot be made on a checkout.
     */
    public function testInventoryTransactionReservationAfterCheckoutQuantity()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved(500);

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RESERVED, $transaction->state);
    }

    /**
     * This fails because the transaction is already set to checkout, a quantity update could simply be made
     * instead of calling checkout again.
     */
    public function testInventoryTransactionCheckoutAfterCheckoutFailure()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->checkout(5)->checkout(500);
    }

    /*
     * This works because you can always update the quantity on any transaction
     * if needed
     */
    public function testInventoryTransactionQuantityUpdateAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5);

        $transaction->quantity = 10;
        $transaction->save();

        $this->assertEquals(10, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);
    }

    public function testInventoryTransactionReturnedAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->returned();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionReturnedPartialAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->returned(2);

        $this->assertEquals(3, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);
    }

    public function testInventoryTransactionSoldAfterCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->sold();

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionSoldAmountAfterCheckoutFailure()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->checkout(5)->sold(500);
    }

    public function testInventoryTransactionSoldAfterReservationAndCheckout()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved()->sold();

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    public function testInventoryTransactionCheckoutSoldAndReturned()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved()->sold()->returned();

        $this->assertEquals(0, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_RETURNED, $transaction->state);
    }

    public function testInventoryTransactionCheckoutSoldAndReturnedPartial()
    {
        $transaction = $this->newTransaction();

        $transaction->checkout(5)->reserved()->sold()->returned(3);

        $this->assertEquals(2, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_SOLD, $transaction->state);
    }

    /**
     * This fails because when a stock is returned a new transaction must be created
     * for any functions. It's an 'end of the line' state.
     */
    public function testInventoryTransactionCheckoutSoldReturnedAndReservedFailure()
    {
        $transaction = $this->newTransaction();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidTransactionStateException');

        $transaction->checkout(5)->reserved()->sold()->returned()->reserved(5);

        $transaction->checkout(5)->reserved()->sold()->returned()->reserved();
    }

    public function testInventoryTransactionCheckoutReserved()
    {
        $transaction = $this->newTransaction();

        $transaction->reserved(5)->checkout();

        $this->assertEquals(5, $transaction->quantity);
        $this->assertEquals(InventoryTransaction::STATE_COMMERCE_CHECKOUT, $transaction->state);
    }

    public function testInventoryTransactionCheckoutDefaultReason()
    {
        $transaction = $this->newTransaction();

        Lang::shouldReceive('get')->once()->andReturn('test');

        $transaction->checkout(5);

        $stock = $transaction->getStockRecord();

        $this->assertEquals('test', $stock->reason);
    }
}
