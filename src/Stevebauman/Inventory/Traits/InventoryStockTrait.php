<?php

namespace Stevebauman\Inventory\Traits;

use Stevebauman\Inventory\InventoryServiceProvider;
use Stevebauman\Inventory\Exceptions\NotEnoughStockException;
use Stevebauman\Inventory\Exceptions\InvalidMovementException;
use Stevebauman\Inventory\Exceptions\InvalidQuantityException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

/**
 * Trait InventoryStockTrait
 * @package Stevebauman\Inventory\Traits
 */
trait InventoryStockTrait
{
    /**
     * Used for easily grabbing a specified location
     */
    use LocationTrait;

    /**
     * Verification helper functions
     */
    use VerifyTrait;

    /**
     * Set's the models constructor method to automatically assign the
     * user_id's attribute to the current logged in user
     */
    use UserIdentificationTrait;

    /**
     * Helpers for starting database transactions
     */
    use DatabaseTransactionTrait;

    /**
     * Stores the quantity before an update
     *
     * @var int|string
     */
    private $beforeQuantity = 0;

    /**
     * Stores the reason for updating / creating a stock
     *
     * @var string
     */
    public $reason = '';

    /**
     * Stores the cost for updating a stock
     *
     * @var int|string
     */
    public $cost = 0;

    /**
     * The hasOne location relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function location();

    /**
     * The belongsTo item relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function item();

    /**
     * The hasMany movements relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function movements();

    /**
     * The hasMany transactions relationship
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function transactions();

    /**
     * Overrides the models boot function to set the user ID automatically
     * to every new record
     *
     * @return void
     */
    public static function bootInventoryStockTrait()
    {
        static::creating(function($model)
        {
            $model->user_id = $model->getCurrentUserId();

            /*
             * Check if a reason has been set, if not let's retrieve the default first entry reason
             */
            if( ! $model->reason) $model->reason = Lang::get('inventory::reasons.first_record');
        });

        static::created(function($model)
        {
            $model->postCreate();
        });

        static::updating(function($model)
        {
            /*
             * Retrieve the original quantity before it was updated,
             * so we can create generate an update with it
             */
            $model->beforeQuantity = $model->getOriginal('quantity');

            /*
             * Check if a reason has been set, if not let's retrieve the default change reason
             */
            if( ! $model->reason) $model->reason = Lang::get('inventory::reasons.change');
        });

        static::updated(function($model)
        {
            $model->postUpdate();
        });
    }

    /**
     * Generates a stock movement on the creation of a stock
     *
     * @return void
     */
    public function postCreate()
    {
        /*
         * Only create a first record movement if one isn't created already
         */
        if( ! $this->getLastMovement())
        {
            /*
             * Generate the movement
             */
            $this->generateStockMovement(0, $this->quantity, $this->reason, $this->cost);
        }
    }

    /**
     * Generates a stock movement after a stock is updated
     *
     * @return void
     */
    public function postUpdate()
    {
        $this->generateStockMovement($this->beforeQuantity, $this->quantity, $this->reason, $this->cost);
    }

    /**
     * Performs a quantity update. Automatically determining depending on the quantity entered if stock is being taken
     * or added
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return InventoryStock|InventoryStockTrait
     * @throws InvalidQuantityException
     */
    public function updateQuantity($quantity, $reason= '', $cost = 0)
    {
        if($this->isValidQuantity($quantity))
        {
            return $this->processUpdateQuantityOperation($quantity, $reason, $cost);
        }
    }

    /**
     * Removes the specified quantity from the current stock
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return $this|bool|InventoryStockTrait
     */
    public function remove($quantity, $reason= '', $cost = 0)
    {
        return $this->take($quantity, $reason, $cost);
    }

    /**
     * Processes a 'take' operation on the current stock
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return $this|bool|InventoryStockTrait
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     */
    public function take($quantity, $reason = '', $cost = 0)
    {
        if($this->isValidQuantity($quantity) && $this->hasEnoughStock($quantity))
        {
            return $this->processTakeOperation($quantity, $reason, $cost);
        }
    }

    /**
     * Alias for put function
     *
     * @param $quantity
     * @param $reason
     * @param int $cost
     * @return InventoryStockTrait
     */
    public function add($quantity, $reason = '', $cost = 0)
    {
        return $this->put($quantity, $reason, $cost);
    }

    /**
     * Processes a 'put' operation on the current stock
     *
     * @param $quantity
     * @param string $reason
     * @param int $cost
     * @return static
     * @throws InvalidQuantityException
     */
    public function put($quantity, $reason = '', $cost = 0)
    {
        if($this->isValidQuantity($quantity))
        {
            return $this->processPutOperation($quantity, $reason, $cost);
        }
    }

    /**
     * Moves a stock to the specified location
     *
     * @param $location
     * @return bool
     */
    public function moveTo($location)
    {
        $location = $this->getLocation($location);

        return $this->processMoveOperation($location);
    }

    /**
     * Rolls back the last movement, or the movement specified. If recursive is set to true,
     * it will rollback all movements leading up to the movement specified
     *
     * @param null $movement
     * @param bool $recursive
     * @return $this|bool|InventoryStockTrait
     */
    public function rollback($movement = NULL, $recursive = false)
    {
        if($movement)
        {
            return $this->rollbackMovement($movement, $recursive);
        } else
        {
            $movement = $this->getLastMovement();

            if($movement) return $this->processRollbackOperation($movement, $recursive);
        }

        return false;
    }

    /**
     * Rolls back a specific movement
     *
     * @param $movement
     * @param bool $recursive
     * @return $this|bool|InventoryStockTrait
     * @throws InvalidMovementException
     */
    public function rollbackMovement($movement, $recursive = false)
    {
        $movement = $this->getMovement($movement);

        return $this->processRollbackOperation($movement, $recursive);
    }

    /**
     * Returns true if the specified quantity is valid, throws
     * InvalidQuantityException otherwise.
     *
     * @param $quantity
     * @return bool
     * @throws InvalidQuantityException
     */
    public function isValidQuantity($quantity)
    {
        if($this->isPositive($quantity)) return true;

        $message = Lang::get('inventory::exceptions.InvalidQuantityException', [
            'quantity' => $quantity,
        ]);

        throw new InvalidQuantityException($message);
    }

    /**
     * Returns true if there is enough stock for the specified quantity being taken.
     * Throws NotEnoughStockException otherwise.
     *
     * @param int $quantity
     * @return bool
     * @throws NotEnoughStockException
     */
    public function hasEnoughStock($quantity = 0)
    {
        /**
         * Using double equals for validation of complete value only, not variable type. For example:
         * '20' (string) equals 20 (int)
         */
        if($this->quantity == $quantity || $this->quantity > $quantity) return true;

        $message = Lang::get('inventory::exceptions.NotEnoughStockException', [
            'quantity' => $quantity,
            'available' => $this->quantity,
        ]);

        throw new NotEnoughStockException($message);
    }

    /**
     * Returns the last movement on the current stock record
     *
     * @return mixed
     */
    public function getLastMovement()
    {
        $movement = $this->movements()->orderBy('created_at', 'DESC')->first();

        if($movement) return $movement;

        return false;
    }

    /**
     * Returns a movement depending on the specified argument. If an object is supplied, it is checked if it
     * is an instance of an eloquent model. If a numeric value is entered, it is retrieved by it's ID.
     *
     * @param $movement
     * @return \Illuminate\Support\Collection|null|static
     * @throws InvalidMovementException
     */
    public function getMovement($movement)
    {
        if($this->isModel($movement))
        {
            return $movement;
        } elseif(is_numeric($movement))
        {
            return $this->getMovementById($movement);
        } else
        {
            $message = Lang::get('inventory::exceptions.InvalidMovementException', [
                'movement' => $movement,
            ]);

            throw new InvalidMovementException($message);
        }
    }

    /**
     * Creates and returns a new un-saved stock transaction
     * instance with the current stock ID attached.
     *
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function newTransaction($name = '')
    {
        $transaction = $this->transactions()->getRelated();

        /*
         * Set the transaction attributes so they don't
         * need to be set manually
         */
        $transaction->stock_id = $this->id;
        $transaction->name = $name;

        return $transaction;
    }

    /**
     * Retrieves a movement by the specified ID
     *
     * @param int|string $id
     * @return \Illuminate\Support\Collection|null|static
     */
    private function getMovementById($id)
    {
        return $this->movements()->find($id);
    }

    /**
     * Processes a quantity update operation
     *
     * @param int|string $quantity
     * @param string $reason
     * @param int|string $cost
     * @return InventoryStock|InventoryStockTrait
     */
    private function processUpdateQuantityOperation($quantity, $reason = '', $cost = 0)
    {
        if($quantity > $this->quantity)
        {
            $putting = $quantity - $this->quantity;

            return $this->put($putting, $reason, $cost);
        } else
        {
            $taking = $this->quantity - $quantity;

            return $this->take($taking, $reason, $cost);
        }
    }

    /**
     * Processes removing quantity from the current stock
     *
     * @param int|string $taking
     * @param string $reason
     * @param int|string $cost
     * @return $this|bool
     */
    private function processTakeOperation($taking, $reason = '', $cost = 0)
    {
        $left = $this->quantity - $taking;

        /*
         * If the updated total and the beginning total are the same, we'll check if
         * duplicate movements are allowed. We'll return the current record if
         * they aren't.
         */
        if($left == $this->quantity && ! $this->allowDuplicateMovementsEnabled()) return $this;

        $this->quantity = $left;

        $this->setReason($reason);

        $this->setCost($cost);

        $this->dbStartTransaction();

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.taken', [
                    'stock' => $this,
                ]);

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes adding quantity to current stock
     *
     * @param int|string $putting
     * @param string $reason
     * @param int|string $cost
     * @return $this|bool|mixed
     */
    private function processPutOperation($putting, $reason = '', $cost = 0)
    {
        $before = $this->quantity;

        $total = $putting + $before;

        /*
         * If the updated total and the beginning total are the same,
         * we'll check if duplicate movements are allowed
         */
        if($total == $this->quantity && ! $this->allowDuplicateMovementsEnabled()) return $this;

        $this->quantity = $total;

        $this->setReason($reason);

        $this->setCost($cost);

        $this->dbStartTransaction();

        try
        {
            if ($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.added', [
                    'stock' => $this,
                ]);

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes the stock moving from it's current location,
     * to the specified location
     *
     * @param $location
     * @return bool
     */
    private function processMoveOperation($location)
    {
        $this->location_id = $location->id;

        $this->dbStartTransaction();

        try
        {
            if($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.moved', [
                    'stock' => $this,
                ]);

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }


    /**
     * Processes a single rollback operation
     *
     * @param $movement
     * @param bool $recursive
     * @return $this|bool
     */
    private function processRollbackOperation($movement, $recursive = false)
    {
        if($recursive) return $this->processRecursiveRollbackOperation($movement);

        $this->quantity = $movement->before;

        $reason = Lang::get('inventory::reasons.rollback', [
            'id' => $movement->getOriginal('id'),
            'date' => $movement->getOriginal('created_at'),
        ]);

        $this->setReason($reason);

        if($this->rollbackCostEnabled())
        {
            $this->setCost($movement->cost);

            $this->reverseCost();
        }

        $this->dbStartTransaction();

        try
        {
            if ($this->save())
            {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.rollback', [
                    'stock' => $this,
                ]);

                return $this;
            }
        } catch(\Exception $e)
        {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes a recursive rollback operation
     *
     * @param $movement
     * @return $this|array|bool|InventoryStockTrait
     */
    private function processRecursiveRollbackOperation($movement)
    {
        /*
         * Retrieve movements that were created after the specified movement, and order them descending
         */
        $movements = $this
            ->movements()
            ->where('created_at', '>=', $movement->getOriginal('created_at'))
            ->orderBy('created_at', 'DESC')
            ->get();

        $rollbacks = [];

        foreach($movements as $movement) $rollbacks = $this->processRollbackOperation($movement);

        return $rollbacks;
    }

    /**
     * Creates a stock movement record
     *
     * @param $before
     * @param $after
     * @param string $reason
     * @param $cost
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function generateStockMovement($before, $after, $reason = '', $cost = 0)
    {
        $insert = [
            'stock_id' => $this->id,
            'before' => $before,
            'after' => $after,
            'reason' => $reason,
            'cost' => $cost,
        ];

        return $this->movements()->create($insert);
    }

    /**
     * Sets the cost attribute
     *
     * @param int|string $cost
     */
    private function setCost($cost = 0)
    {
        $this->cost = $cost;
    }

    /**
     * Reverses the cost of a movement
     *
     * @return void
     */
    private function reverseCost()
    {
        if($this->isPositive($this->cost))
        {
            $this->setCost(-abs($this->cost));
        } else
        {
            $this->setCost(abs($this->cost));
        }
    }

    /**
     * Sets the reason attribute
     *
     * @param string $reason
     * @return void
     */
    private function setReason($reason = '')
    {
        $this->reason = $reason;
    }

    /**
     * Returns true/false from the configuration file determining
     * whether or not stock movements can have the same before and after
     * quantities
     *
     * @return bool
     */
    private function allowDuplicateMovementsEnabled()
    {
        return Config::get('inventory'. InventoryServiceProvider::$packageConfigSeparator .'allow_duplicate_movements');
    }

    /**
     * Returns true/false from the configuration file determining
     * whether or not to rollback costs when a rollback occurs on
     * a stock
     *
     * @return bool
     */
    private function rollbackCostEnabled()
    {
        return Config::get('inventory'. InventoryServiceProvider::$packageConfigSeparator .'rollback_cost');
    }

}