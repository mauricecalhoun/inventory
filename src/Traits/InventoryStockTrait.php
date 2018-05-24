<?php

namespace Trexology\Inventory\Traits;

use Trexology\Inventory\Exceptions\NotEnoughStockException;
use Trexology\Inventory\Exceptions\InvalidMovementException;
use Trexology\Inventory\Exceptions\InvalidQuantityException;
use Trexology\Inventory\Helper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

trait InventoryStockTrait
{
    use CommonMethodsTrait;

    /**
     * Stores the reason for updating / creating a stock.
     *
     * @var string
     */
    public $reason = '';

    /**
     * Stores the cost for updating a stock.
     *
     * @var int|float|string
     */
    public $cost = 0;

    /**
     * Stores the receiver params.
     *
     * @var string
     */
    public $receiver_id = null;
    public $receiver_type = null;

    /**
     * Stores the quantity before an update.
     *
     * @var int|float|string
     */
    protected $beforeQuantity = 0;

    /**
     * Serial Number for stock movment
     *
     * @var Array
     */
    public $movementSerial = [];

    /**
     * The hasOne location relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    abstract public function location();

    /**
     * The belongsTo item relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    abstract public function item();

    /**
     * The hasMany movements relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function movements();

    /**
     * The hasMany transactions relationship.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function transactions();

    /**
     * Overrides the models boot function to set
     * the user ID automatically to every new record.
     */
    public static function bootInventoryStockTrait()
    {
        static::creating(function (Model $model) {
            $model->setAttribute('user_id', Helper::getCurrentUserId());

            // Check if a reason has been set, if not let's
            // retrieve the default first entry reason.
            if (!$model->reason) {
                $model->reason = Lang::get('inventory::reasons.first_record');
            }
        });

        static::created(function (Model $model) {
            $model->postCreate();
        });

        static::updating(function (Model $model) {
            // Retrieve the original quantity before it was updated,
            // so we can create generate an update with it.
            $model->beforeQuantity = $model->getOriginal('quantity');

            // Check if a reason has been set, if not let's
            // retrieve the default change reason.
            if (!$model->reason) {
                $model->reason = Lang::get('inventory::reasons.change');
            }
        });

        static::updated(function (Model $model) {
            $model->postUpdate();
        });
    }

    /**
     * Generates a stock movement after a stock is created.
     *
     * @return void
     */
    public function postCreate()
    {
        $this->generateStockMovement(0, $this->getAttribute('quantity'), $this->reason, $this->cost, $this->receiver_id, $this->receiver_type, $this->movementSerial);
    }

    /**
     * Generates a stock movement after a stock is updated.
     *
     * @return void
     */
    public function postUpdate()
    {
        $this->generateStockMovement($this->beforeQuantity, $this->getAttribute('quantity'), $this->reason, $this->cost, $this->receiver_id, $this->receiver_type, $this->movementSerial);
    }

    /**
     * Performs a quantity update. Automatically determining
     * depending on the quantity entered if stock is being taken
     * or added.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidQuantityException
     *
     * @return $this
     */
    public function updateQuantity($quantity, $reason = '', $cost = 0)
    {
        if ($this->isValidQuantity($quantity)) {
            return $this->processUpdateQuantityOperation($quantity, $reason, $cost);
        }
    }

    /**
     * Removes the specified quantity from the current stock.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this|bool
     */
    public function remove($quantity, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        return $this->take($quantity, $reason, $cost, $receiver_id, $receiver_type, $serial);
    }

    /**
     * Processes a 'take' operation on the current stock.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidQuantityException
     * @throws NotEnoughStockException
     *
     * @return $this|bool
     */
    public function take($quantity, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        return $this->processTakeOperation($quantity, $reason, $cost, $receiver_id, $receiver_type, $serial);
    }

    /**
     * Alias for put function.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this
     */
    public function add($quantity, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        return $this->put($quantity, $reason, $cost, $receiver_id, $receiver_type, $serial);
    }

    /**
     * Processes a 'put' operation on the current stock.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @throws InvalidQuantityException
     *
     * @return $this
     */
    public function put($quantity, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        return $this->processPutOperation($quantity, $reason, $cost, $receiver_id, $receiver_type,$serial);
    }

    /**
     * Moves a stock to the specified location.
     *
     * @param Model $location
     *
     * @return bool
     */
    public function moveTo(Model $location)
    {
        return $this->processMoveOperation($location);
    }

    /**
     * Rolls back the last movement, or the movement specified. If recursive is set to true,
     * it will rollback all movements leading up to the movement specified.
     *
     * @param mixed $movement
     * @param bool  $recursive
     *
     * @return $this|bool
     */
    public function rollback($movement = null, $recursive = false)
    {
        if ($movement) {
            return $this->rollbackMovement($movement, $recursive);
        } else {
            $movement = $this->getLastMovement();

            if ($movement) {
                return $this->processRollbackOperation($movement, $recursive);
            }
        }

        return false;
    }

    /**
     * Return stock assigned to an entity. Stock may be disposed of during the process of returning
     *
     * @param mixed $movement
     * @param bool  $recursive
     *
     * @return $this|bool
     */
    public function returnStock($movement, $collect_amt, $collect_reason, $collect_serial, $dispose_amt, $dispose_reason,$dispose_serial)
    {
        if (!$movement) {
            $movement = $this->getLastMovement();
        }

        $amt = $movement->getAttribute('before') - $movement->getAttribute('after');
        $balance = $amt - $dispose_amt - $collect_amt;
        if ($amt > 0 && $balance >= 0) {
          // $bal_reason = Lang::get('inventory::reasons.rollback', [
          //     'id' => $movement->getOriginal('id'),
          //     'date' => $movement->getOriginal('created_at'),
          // ]);
          $bal_reason = "Balance leftover from stock redistribution. (id: $movement->id)";

          $bal_serial = null;
          if (is_array($collect_serial) && is_array($dispose_serial)) {
            $bal_serial = array_diff($movement->serial, $collect_serial,$dispose_serial);
          }
          $this->put($amt,$collect_reason,0,$movement->receiver_id,$movement->receiver_type,$collect_serial);
          $this->take($dispose_amt,$dispose_reason,0,null,null,$dispose_serial);
          if ($balance > 0) {
            $this->take($balance,$bal_reason,0,$movement->receiver_id,$movement->receiver_type,$bal_serial);
          }

          $movement->returned = true; // Prevent duplicate return for this movements
          $movement->save();
        }

        return false;
    }

    /**
     * Rolls back a specific movement.
     *
     * @param mixed $movement
     * @param bool  $recursive
     *
     * @throws InvalidMovementException
     *
     * @return $this|bool
     */
    public function rollbackMovement($movement, $recursive = false)
    {
        $movement = $this->getMovement($movement);

        return $this->processRollbackOperation($movement, $recursive);
    }

    /**
     * Returns true if there is enough stock for the specified quantity being taken.
     * Throws NotEnoughStockException otherwise.
     *
     * @param int|float|string $quantity
     *
     * @throws NotEnoughStockException
     *
     * @return bool
     */
    public function hasEnoughStock($quantity = 0)
    {
        $available = $this->getAttribute('quantity');

        if ((float) $available === (float) $quantity || $available > $quantity) {
            return true;
        }

        $message = Lang::get('inventory::exceptions.NotEnoughStockException', [
            'quantity' => $quantity,
            'available' => $available,
        ]);

        throw new NotEnoughStockException($message);
    }

    /**
     * Returns the last movement on the current stock record.
     *
     * @return bool|Model
     */
    public function getLastMovement()
    {
        $movement = $this->movements()->orderBy('created_at', 'DESC')->first();

        if ($movement) {
            return $movement;
        }

        return false;
    }

    /**
     * Returns a movement depending on the specified argument. If an object is supplied, it is checked if it
     * is an instance of an eloquent model. If a numeric value is entered, it is retrieved by it's ID.
     *
     * @param int|string|Model $movement
     *
     * @throws InvalidMovementException
     *
     * @return mixed
     */
    public function getMovement($movement)
    {
        if ($this->isModel($movement)) {
            return $movement;
        } elseif (is_numeric($movement)) {
            return $this->getMovementById($movement);
        } else {
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
     *
     * @return Model
     */
    public function newTransaction($name = '')
    {
        $transaction = $this->transactions()->getRelated()->newInstance();

        // Set the transaction attributes so they don't need to be set manually
        $transaction->setAttribute('stock_id', $this->getKey());
        $transaction->setAttribute('name', $name);

        return $transaction;
    }

    /**
     * Retrieves a movement by the specified ID.
     *
     * @param int|string $id
     *
     * @return null|Model
     */
    protected function getMovementById($id)
    {
        return $this->movements()->find($id);
    }

    /**
     * Processes a quantity update operation.
     *
     * @param int|float|string $quantity
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this
     */
    protected function processUpdateQuantityOperation($quantity, $reason = '', $cost = 0)
    {
        $current = $this->getAttribute('quantity');

        if ($quantity > $current) {
            $putting =  $quantity - $current;

            return $this->put($putting, $reason, $cost);
        } else {
            $taking = $current - $quantity;

            return $this->take($taking, $reason, $cost);
        }
    }

    /**
     * Processes removing quantity from the current stock.
     *
     * @param int|float|string $taking
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this|bool
     */
    protected function processTakeOperation($taking, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        if($this->isValidQuantity($taking) && $this->hasEnoughStock($taking)) {
            $available = $this->getAttribute('quantity');

            $left = (float) $available - (float) $taking;

            /*
             * If the updated total and the beginning total are the same, we'll check if
             * duplicate movements are allowed. We'll return the current record if
             * they aren't.
             */
            if ((float) $left === (float) $available && !$this->allowDuplicateMovementsEnabled()) {
                return $this;
            }
            $this->setAttribute('quantity', $left);

            if (is_string($serial)) {
              $serial = preg_split("/\s*,\s*/", trim($serial), -1, PREG_SPLIT_NO_EMPTY);
            }

            if ($this->serial && is_array($serial)) {
              $this->serial = array_diff($this->serial,$serial);
            }

            $this->movementSerial = $serial;
            $this->setReason($reason);
            $this->setCost($cost);
            $this->receiver_id = $receiver_id;
            $this->receiver_type = $receiver_type;

            $this->dbStartTransaction();
            try {
                if ($this->save()) {
                    $this->dbCommitTransaction();

                    $this->fireEvent('inventory.stock.taken', [
                        'stock' => $this,
                    ]);

                    return $this;
                }
            } catch (\Exception $e) {
                $this->dbRollbackTransaction();
            }
        }

        return false;
    }

    /**
     * Processes adding quantity to current stock.
     *
     * @param int|float|string $putting
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return $this|bool
     */
    protected function processPutOperation($putting, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        if($this->isValidQuantity($putting)) {
            $current = $this->getAttribute('quantity');

            $total = (float) $putting + (float) $current;

            // If the updated total and the beginning total are the same,
            // we'll check if duplicate movements are allowed.
            if ((float) $total === (float) $current && !$this->allowDuplicateMovementsEnabled()) {
                return $this;
            }

            if (is_string($serial)) {
              $serial = preg_split("/\s*,\s*/", trim($serial), -1, PREG_SPLIT_NO_EMPTY);
            }
            if ($this->serial && is_array($serial)) {
              $this->serial = array_merge($this->serial,$serial);
            }
            elseif (!$this->serial) {
              $this->serial = $serial;
            }
            $this->quantity = $total;

            $this->movementSerial = $serial;
            $this->setReason($reason);
            $this->setCost($cost);
            $this->receiver_id = $receiver_id;
            $this->receiver_type = $receiver_type;

            $this->dbStartTransaction();

            try {
                if ($this->save()) {
                    $this->dbCommitTransaction();

                    $this->fireEvent('inventory.stock.added', [
                        'stock' => $this,
                    ]);

                    return $this;
                }
            } catch (\Exception $e) {
                $this->dbRollbackTransaction();
            }
        }

        return false;
    }

    /**
     * Processes the stock moving from it's current
     * location, to the specified location.
     *
     * @param mixed $location
     *
     * @return bool
     */
    protected function processMoveOperation(Model $location)
    {
        $this->setAttribute('location_id', $location->getKey());

        $this->dbStartTransaction();

        try {
            if ($this->save()) {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.moved', [
                    'stock' => $this,
                ]);

                return $this;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes a single rollback operation.
     *
     * @param mixed $movement
     * @param bool  $recursive
     *
     * @return $this|bool
     */
    protected function processRollbackOperation(Model $movement, $recursive = false)
    {
        if ($recursive) {
            return $this->processRecursiveRollbackOperation($movement);
        }

        $amt = $movement->getAttribute('after')- $movement->getAttribute('before');
        $this->setAttribute('quantity', $this->quantity - $amt);

        $reason = Lang::get('inventory::reasons.rollback', [
            'id' => $movement->getOriginal('id'),
            'date' => $movement->getOriginal('created_at'),
        ]);

        $this->setReason($reason);

        $this->receiver_id = $movement->receiver_id;
        $this->receiver_type = $movement->receiver_type;

        if ($this->rollbackCostEnabled()) {
            $this->setCost($movement->getAttribute('cost'));

            $this->reverseCost();
        }

        $this->dbStartTransaction();

        try {
            if ($this->save()) {
                $this->dbCommitTransaction();

                $this->fireEvent('inventory.stock.rollback', [
                    'stock' => $this,
                ]);

                return $this;
            }
        } catch (\Exception $e) {
            $this->dbRollbackTransaction();
        }

        return false;
    }

    /**
     * Processes a recursive rollback operation.
     *
     * @param mixed $movement
     *
     * @return array
     */
    protected function processRecursiveRollbackOperation(Model $movement)
    {
        /*
         * Retrieve movements that were created after
         * the specified movement, and order them descending
         */
        $movements = $this
            ->movements()
            ->where('created_at', '>=', $movement->getOriginal('created_at'))
            ->orderBy('created_at', 'DESC')
            ->get();

        $rollbacks = [];

        if ($movements->count() > 0) {
            foreach ($movements as $movement) {
                $rollbacks = $this->processRollbackOperation($movement);
            }
        }

        return $rollbacks;
    }

    /**
     * Creates a new stock movement record.
     *
     * @param int|float|string $before
     * @param int|float|string $after
     * @param string           $reason
     * @param int|float|string $cost
     *
     * @return bool|Model
     */
    protected function generateStockMovement($before, $after, $reason = '', $cost = 0, $receiver_id = null, $receiver_type = null, $serial = null)
    {
        $movement = $this->movements()->getRelated()->newInstance();

        $movement->setAttribute('stock_id', $this->getKey());
        $movement->setAttribute('before', $before);
        $movement->setAttribute('after', $after);
        if ($receiver_id && $receiver_type) {
          $movement->setAttribute('receiver_id', $receiver_id);
          $movement->setAttribute('receiver_type', $receiver_type);
        }
        $movement->setAttribute('reason', $reason);
        $movement->setAttribute('cost', $cost);
        $movement->setAttribute('serial', $serial);

        if($movement->save()) {
            return $movement;
        }

        return false;
    }

    /**
     * Sets the cost attribute.
     *
     * @param int|float|string $cost
     */
    protected function setCost($cost = 0)
    {
        $this->cost = (float) $cost;
    }

    /**
     * Reverses the cost of a movement.
     */
    protected function reverseCost()
    {
        $cost = $this->getAttribute('cost');

        if ($this->isPositive($cost)) {
            $this->setCost(-abs($cost));
        } else {
            $this->setCost(abs($cost));
        }
    }

    /**
     * Sets the reason attribute.
     *
     * @param string $reason
     */
    protected function setReason($reason = '')
    {
        $this->reason = $reason;
    }

    /**
     * Returns true/false from the configuration file determining
     * whether or not stock movements can have the same before and after
     * quantities.
     *
     * @return bool
     */
    protected function allowDuplicateMovementsEnabled()
    {
        return Config::get('inventory.allow_duplicate_movements');
    }

    /**
     * Returns true/false from the configuration file determining
     * whether or not to rollback costs when a rollback occurs on
     * a stock.
     *
     * @return bool
     */
    protected function rollbackCostEnabled()
    {
        return Config::get('inventory.rollback_cost');
    }
}
