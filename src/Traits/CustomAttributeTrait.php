<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Inventory\Models\CustomAttribute;
use Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException;

/**
 * Class CustomAttributeTrait.
 */
trait CustomAttributeTrait
{
    use DatabaseTransactionTrait;

    /**
     * The items customAttribute cache key.
     *
     * @var string
     */
    protected $customAttributeCacheKey = 'inventory::customAttribute.';

    /**
     * The hasMany customAttributeValues relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function customAttributeValues();
    
    /**
     * The hasMany customAttributeDefaults relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function customAttributeDefaults();

    /**
     * Returns the current item's customAttribute cache key
     * 
     * @return string
     */
    private function getCustomAttributeCacheKey()
    {
        return $this->customAttributeCacheKey.$this->getKey();
    }

    /**
     * Returns boolean based on whether the current item
     * has cached customAttributes
     * 
     * @return bool
     */
    public function hasCachedCustomAttributes() 
    {
        return Cache::has($this->getCustomAttributeCacheKey());
    }

    /**
     * Puts the given customAttribute into the cache for
     * the current item
     * 
     * @return bool
     */
    public function addCachedCustomAttribute($attr) 
    {
        return Cache::forever($this->getCustomAttributeCacheKey(), $attr);
    }

    /**
     * Returns the current item's cached customAttributes if
     * they exist in the cache, or false otherwise
     * 
     * @return bool|Collection
     */
    public function getCachedCustomAttributes() 
    {
        if ($this->hasCachedCustomAttributes()) {
            return Cache::get($this->getCustomAttributeCacheKey());
        }

        return false;
    }

    /**
     * Removes the current item's customAttributes from the cache
     * 
     * @return bool
     */
    public function forgetCachedCustomAttributes()
    {
        return Cache::forget($this->getCustomAttributeCacheKey());
    }

    public function customAttributeExists($attr) {
        
    }

    /**
     * Returns true if item has given custom customAttribute, or 
     * false otherwise.
     * 
     * @param int|string $attr
     * 
     * @return boolean
     */
    public function hasCustomAttribute($attr) {
        return (boolean) $this->getCustomAttribute($attr);
    }

    /**
     * Returns a given custom customAttribute by name or numeric id
     * for this inventory item
     * 
     * @param int|string|Model $attr
     * 
     * @return Model|boolean
     */
    public function getCustomAttribute($attr) 
    {
        $res = false;

        if ($attr instanceof Model) {
            return $attr;
        } else if (is_numeric($attr)) {
            $res = $this->customAttributes()->where('custom_attributes.id', $attr)->get()->first();
        } else {
            $res = $this->customAttributes()->where('custom_attributes.name', $attr)->get()->first();
        }

        return $res ? $res : false;
    }

    /**
     * Returns the list of all custom customAttributes 
     * for this inventory item
     * 
     * @return Collection
     */
    public function getCustomAttributes() 
    {
        return $this->customAttributes()->get();
    }

    /**
     * Adds a new custom customAttribute to this inventory item
     *
     * @param string $type
     * @param string $displayName
     * @param string $name
     * @param mixed $defaultValue
     * @param enum $displayType
     * 
     * @return Model|boolean
     * 
     * @throws InvalidCustomAttributeException
     */
    public function addCustomAttribute($type, $displayName, $defaultValue = null, $name = null)
    {
        /**
         * TODO: should try to add a custom customAttribute to an inventory item.
         * 
         * If the customAttribute exists (lookup by name), attach that 
         * customAttribute to this item.
         * 
         * Otherwise, create a new customAttribute and attach it to this item. 
         * 
         * Check if defaultValue is set, and add an customAttributeDefaultValue 
         * entry after creating/using the customAttribute.
         * 
         * Check that displayType is a valid type.
         * 
         * Save everything in the database.
         */

        
        /* 
         * Infer raw type from the $type parameter.  We will
         * use this information to decide which column to store
         * the data in the customAttributeValues table.
         */

        $rawType = null;

        switch ($type) {
            case 'string':
                $rawType = 'string';
                break;
            
            case 'integer':
                $rawType = 'num';
                break;
            
            case 'decimal':
                $rawType = 'num';
                break;

            case 'currency':
                $rawType = 'num';
                break;

            case 'date':
                $rawType = 'date';
                break;

            case 'time':
                $rawType = 'date';
                break;

            case 'dropdown':
                $rawType = 'string';
                break;

            default:
                $message = $type . ' is an invalid custom attribute type';
                throw new InvalidCustomAttributeException($message);
                break;
        }

        // Infer the name field from the displayName parameter,
        // if $name is not provided.
        if (!$name) {
            $name = strtolower($displayName);
            $name = preg_replace('/\s+/', '_', $name);
        }
        
        $existingAttr = $this->getCustomAttribute($name);

        // If the customAttribute exists on this item, check if it's of the correct type
        if ($existingAttr) {
            if ($type == $existingAttr->value_type) {
                throw new InvalidCustomAttributeException('Cannot add same attribute '.$displayName.' twice');
            } else {
                $createdCustomAttribute = $this->createCustomAttribute($name, $displayName, $rawType, $type, false);
                
                return $createdCustomAttribute;
            }
        } else {
            // Check that the attribute exists at all - not just on this item.
            $anyExistingAttr = CustomAttribute::where('name', $name)->where('value_type', $rawType)->first();

            if ($anyExistingAttr) {
                //If the attribute exists, we initialize a linked 
                // customAttributeValue by setting a null value
                $this->setCustomAttribute($anyExistingAttr->id, $rawType, null);

                return $anyExistingAttr;
            } else {
                // If no similarly-typed customAttribute found, create a new customAttribute
                // TODO: set last parameter to calculated 'has_default' value
                
                $createdCustomAttribute = $this->createCustomAttribute($name, $displayName, $rawType, $type, false);
                
                // ... And set its value to null or equivalent
    
    
                // Then, if there is a default value, create that entry
    
                return $createdCustomAttribute;
            }
        }

        return false;
    }

    /**
     * Create a new customAttribute entry with the given properties,
     * and return boolean based on whether creation was successful
     *
     * @param string $name
     * @param string $displayName
     * @param string $rawType
     * @param string $type
     * @param boolean $hasDefault
     * 
     * @return boolean
     */
    private function createCustomAttribute($name, $displayName, $rawType, $type, $hasDefault)
    {
        $newCustomAttribute = [
            'name' => $name,
            'display_name' => $displayName,
            'value_type' => $rawType,
            'reserved' => false,
            'display_type' => $type,
            'has_default' => $hasDefault,
        ];

        $createdAttr = $this->customAttributes()->create($newCustomAttribute);

        /*
         * Then we need to initialize the new custom attribute with an
         * empty value to link it with the current inventory item
         */
        $this->setCustomAttribute($createdAttr->id, $rawType, null);

        return $createdAttr;
    }

    /**
     * Sets an customAttributeValue with the given customAttribute name
     * and value
     * 
     * @param int $id
     * @param string $type
     * @param mixed $value
     * 
     * @return mixed
     */
    public function setCustomAttribute($id, $type, $value) 
    {
        try {
            $existingAttrValObj = $this->getCustomAttributeValueObj($id);

            $valKey = $type . '_val';
    
            $existingAttrValObj->$valKey = $value;
    
            return $existingAttrValObj->save();
        } catch (\Exception $e) {
            $itemKey = $this->getKey();
            
            $attrVal = [
                'custom_attribute_id' => $id,
                'inventory_id' => $itemKey,
                'string_val' => $type == 'string' ? $value : null,
                'num_val' => $type == 'num' ? $value : null,
                'date_val' => $type == 'date' ? $value : null,
            ];
    
            return $this->customAttributeValues()->create($attrVal);
        }
    }

    /**
     * Gets a customAttribute value based on the customAttribute id
     * and inventory item's id
     *
     * @param int|string $attr
     * 
     * @return mixed
     * 
     * @throws InvalidCustomAttributeException
     */
    public function getCustomAttributeValue($attr) 
    {
        try {
            $attrObj = $this->getCustomAttribute($attr);

            $attrValObj = $this->getCustomAttributeValueObj($attrObj);

            $type = $attrObj->value_type;

            $key = $type . '_val';

            return $attrValObj->$key; 
        } catch (\Exception $e) {
            throw new InvalidCustomAttributeException('Could not get custom attribute with key ' . $attr);
        }

        return false;
    }

    /**
     * Resolves a customAttributeObject given an id, name, or Model
     *
     * @param int|string|Model $attr
     * 
     * @return Model
     * 
     * @throws InvalidCustomAttributeException
     */
    public function resolveCustomAttributeObject($attr) {
        $attrObj = $this->getCustomAttribute($attr);

        if (!$attrObj) throw new InvalidCustomAttributeException('Could not find custom attribute with key '.$attr);

        return $attrObj;
    }

    /**
     * Gets a customAttributeValue object by custom_attribute_id and
     * inventory_id
     * 
     * @param int|string|Model $attr
     * 
     * @return mixed
     * 
     * @throws InvalidCustomAttributeException
     */
    public function getCustomAttributeValueObj($attr) {
        try {
            $attrObj = $this->resolveCustomAttributeObject($attr);

            return $this->customAttributeValues()
                ->where('custom_attribute_id', $attrObj->getKey())
                ->where('inventory_id', $this->getKey())
                ->get()->first();
        } catch (\Exception $e) {
            throw new InvalidCustomAttributeException('Could not get custom attribute value object with key ' . $attr);
        }
    }

    /**
     * Removes a custom attribute from an inventory item, returns
     * true or throws an exception based on success of removal
     *
     * @param int|string|Model $attr
     * 
     * @return boolean
     * 
     * @throws InvalidCustomAttributeException
     */
    public function removeCustomAttribute($attr) 
    {
        try {
            $attrObj = $this->resolveCustomAttributeObject($attr);

            $this->customAttributeValues()
                ->where('custom_attribute_id', $attrObj->getKey())
                ->where('inventory_id', $this->getKey())
                ->delete();

            return true;
        } catch (\Exception $e) {
            throw new InvalidCustomAttributeException('Could not remove custom attribute value object with key ' . $attr);
        }
    }
}
