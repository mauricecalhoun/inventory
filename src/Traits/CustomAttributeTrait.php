<?php

namespace Stevebauman\Inventory\Traits;

use Illuminate\Database\Eloquent\Model;
use Stevebauman\Inventory\Models\CustomAttribute;
use Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException;
use Stevebauman\Inventory\Exceptions\RequiredCustomAttributeException;

/**
 * Class CustomAttributeTrait.
 */
trait CustomAttributeTrait
{
    use DatabaseTransactionTrait;

    /**
     * The hasMany customAttributeValues relationship.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    abstract public function customAttributeValues();

    /**
     * Returns true if item has given custom attribute, or 
     * false otherwise.
     * 
     * @param int|string $attr
     * 
     * @return boolean
     */
    public function hasCustomAttribute($attr) 
    {
        return (boolean) $this->getCustomAttribute($attr);
    }

    /**
     * Returns a given custom attribute by name or numeric id
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
        } else {
            $res = $this->customAttributes()->where('custom_attributes.name', $attr)->orWhere('custom_attributes.id', $attr)->get()->first();
        }

        return $res ? $res : false;
    }

    /**
     * Resolves a customAttributeObject given an id, name, or Model
     * - for retrieving a custom attribute object that may or may not
     * be attached to $this inventory model
     *
     * @param int|string|Model $attr
     * 
     * @return Model
     * 
     * @throws InvalidCustomAttributeException
     */
    public function resolveCustomAttributeObject($attr) 
    {
        if ($attr instanceof Model) return $attr;

        $attrObj = $this->hasCustomAttribute($attr) ? 
            $this->getCustomAttribute($attr) :
            CustomAttribute::where('id', $attr)->orWhere('name', $attr)->first();

        if (!$attrObj) throw new InvalidCustomAttributeException('Could not find custom attribute with key "'.$attr.'"');

        return $attrObj;
    }

    /**
     * Returns the list of all custom attribute models
     * for this inventory item
     * 
     * @return Collection
     */
    public function getCustomAttributes() 
    {
        return $this->customAttributes()->get();
    }

    /**
     * Returns a formatted list of all custom attribute 
     * values for this inventory item with the format
     * [
     *   attribute_name => value,
     *   ...,
     * ]
     * 
     * 
     * @return Collection
     */
    public function getCustomAttributeValues() 
    {
        $attrVals = $this->customAttributes()->get()->reduce(function($carry, $item) {
            if (!$carry) return [$item->name => $item->values[$item->value_type.'_val']];
            else $carry[$item->name] = $item->values[$item->value_type.'_val'];
            return $carry;
        });

        return $attrVals;
    }

    /**
     * Adds a new custom attribute to this inventory item
     *
     * @param string $type
     * @param string $displayName
     * @param mixed $defaultValue
     * @param boolean $required
     * 
     * @return Model|boolean
     * 
     * @throws InvalidCustomAttributeException
     */
    public function addCustomAttribute($type, $displayName, $defaultValue = null, $required = false, $rule = null, $ruleDesc = null)
    {
        // If we're given an invalid regex rule, that's easy to check and pop
        // out an exception, so we do so here.  We will not do validation on
        // the set value before inserting into the database, as this field is 
        // primarily for front-end form field validation.
        if (!is_null($rule)) {
            $testString = '1234567890abcdefghijklmnopqrstuvwxyz';
            if ($type == 'longText') {
                throw new InvalidCustomAttributeException('Cannot create longText attribute with regular expression rule');
            }
            else if (@preg_match($rule, $testString) === false) {
                throw new InvalidCustomAttributeException('Cannot create custom attribute - invalid regular expression provided');
            } 
            else if (is_null($ruleDesc)) {
                throw new InvalidCustomAttributeException('Cannot create custom attribute with regex rule but no description');
            }
        }
        
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

            case 'longText':
                $rawType = 'string';
                break;

            default:
                $message = '"' . $type . '" is an invalid custom attribute type';
                throw new InvalidCustomAttributeException($message);
                break;
        }

        // Format snake-case "name" attribute from display name
        $name = strtolower($displayName);
        $name = preg_replace('/\s+/', '_', $name);
        
        // TODO: deal with all this default logic:
        $defaultIsNull = is_null($defaultValue);
        
        $hasDefault = $defaultIsNull ? false : true;
        
        // Validate default value matches type
        if ($hasDefault) {
            $this->validateAttribute($defaultValue, $rawType);
        }
        
        $defaultValue = $defaultIsNull ? null : $defaultValue;

        // Check if existing attribute of that name
        $existingAttr = $this->getCustomAttribute($name);
        
        // If the customAttribute exists on this item, check if it's of the correct type
        if ($existingAttr) {
            throw new InvalidCustomAttributeException('Cannot add same attribute "'.$displayName.'" twice');
        } else {
            // Check that the attribute exists at all - not just on this item.
            $anyExistingAttr = CustomAttribute::where('name', $name)->where('value_type', $rawType)->first();

            if ($anyExistingAttr) {
                //If the attribute exists, we initialize a linked 
                // customAttributeValue by setting a null value
                $this->setCustomAttribute($anyExistingAttr->id, $defaultValue, $rawType);

                if ($hasDefault) {
                    $this->setCustomAttributeDefault($anyExistingAttr, $defaultValue);
                }

                return $anyExistingAttr;
            } else {
                // If no existing customAttribute found, create a new customAttribute
                $createdCustomAttribute = $this->createCustomAttribute($name, $displayName, $rawType, $type, $hasDefault, $defaultValue, $required, $rule, $ruleDesc);
                
                if ($hasDefault) {
                    $this->setCustomAttributeDefault($createdCustomAttribute, $defaultValue);
                }

                return $createdCustomAttribute;
            }
        }

        return false;
    }

    /**
     * Validates the input attribute against the given type and
     * throws an InvalidCustomAttributeException if invalid
     *
     * @param mixed $value
     * @param string $type
     * 
     * @return void
     * 
     * @throws InvalidCustomAttributeException
     */
    private function validateAttribute($value, $type) {
        // We allow null values
        if(!is_null($value)) {
            if ($type == 'num' && !is_numeric($value)) {
                $message = '"'.$value.'" is an invalid number value';
                throw new InvalidCustomAttributeException($message);
            } else if ($type == 'date' && (!strtotime($value) && !is_numeric($value))) {
                $message = '"'.$value.'" is an invalid date value';
                throw new InvalidCustomAttributeException($message);
            }
        }
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
     * @param mixed $defaultValue
     * @param boolean $required
     * @param string $rule
     * 
     * @return boolean
     */
    private function createCustomAttribute($name, $displayName, $rawType, $type, $hasDefault, $defaultValue = null, $required = false, $rule = null, $ruleDesc = null)
    {
        // TODO: put default value in this object
        $newCustomAttribute = [
            'name' => $name,
            'display_name' => $displayName,
            'value_type' => $rawType,
            'reserved' => false,
            'display_type' => $type,
            'has_default' => $hasDefault,
            'default_value' => $defaultValue,
            'required' => $required,
            'rule' => $rule,
            'rule_desc' => $ruleDesc,
        ];

        $createdAttr = $this->customAttributes()->create($newCustomAttribute);

        /*
         * Then we need to initialize the new custom attribute with an
         * empty value to link it with the current inventory item
         */
        $this->setCustomAttribute($createdAttr->id, $defaultValue, $rawType);

        return $createdAttr;
    }

    /**
     * Sets an customAttributeValue with the given customAttribute name
     * and value
     * 
     * @param int|string|Model $id
     * @param mixed $value
     * @param string $type default = null
     * 
     * @return mixed
     */
    public function setCustomAttribute($attr, $value, $type = null) 
    {
        try {
            if ($attr instanceof Model) {
                // do nothing
            } else if ($this->hasCustomAttribute($attr)) {
                $attr = $this->getCustomAttribute($attr);
            } else {
                $attr = $this->resolveCustomAttributeObject($attr);
            }

            if (is_null($value) && $attr->required) {
                throw new RequiredCustomAttributeException('Cannot set required attribute to null');
            }

            $existingAttrValObj = $this->getCustomAttributeValueObj($attr);

            if (!$type) {
                $type = $attr->value_type;
            }
            
            if (!$attr || !$existingAttrValObj) {
                throw new \Exception();
            }

            $this->validateAttribute($value, $type);

            if ($type == 'date') {
                $value = $this->formatDateValue($value);
            }

            $valKey = $type . '_val';
    
            $existingAttrValObj->$valKey = $value;
    
            return $existingAttrValObj->save();
        } catch (RequiredCustomAttributeException $e) {
            throw $e;
        } catch (\Exception $e) {
            if (!$type) throw new InvalidCustomAttributeException('Could not find attribute "'.$attr.'", and can not create without a type');

            $this->validateAttribute($value, $type);

            $itemKey = $this->getKey();
            
            $attrVal = [
                'custom_attribute_id' => $attr->id,
                'inventory_id' => $itemKey,
                'string_val' => $type == 'string' ? $value : null,
                'num_val' => $type == 'num' ? $value : null,
                'date_val' => $type == 'date' ? $this->formatDateValue($value) : null,
            ];
    
            return $this->customAttributeValues()->create($attrVal);
        }
    }

    /**
     * Formats a date value to be consumed by database
     *
     * @param string|int $value
     * 
     * @return string
     */
    private function formatDateValue($value) {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', $value);
        } else {
            return date('Y-m-d H:i:s', strtotime($value));
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
            throw new InvalidCustomAttributeException('Could not get custom attribute value with key "'.$attr.'"');
        }

        return false;
    }

    /**
     * Gets a custom attribute default from the custom attribute's model
     *
     * @param int|string $attr
     * 
     * @return mixed
     * 
     * @throws InvalidCustomAttributeException
     */
    public function getCustomAttributeDefault($attr) 
    {
        try {
            $attrObj = $this->getCustomAttribute($attr);

            if (is_numeric($attrObj->default_value)) return filter_var($attrObj->default_value, FILTER_SANITIZE_NUMBER_FLOAT);
            else return $attrObj->default_value;
        } catch (\Exception $e) {
            throw new InvalidCustomAttributeException('Could not get custom attribute with key "'.$attr.'"');
        }

        return false;
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
    public function getCustomAttributeValueObj($attr) 
    {
        // If called with no attribute to find, don't bother looking
        // if (!$attr) return false;

        try {
            $attrObj = $this->resolveCustomAttributeObject($attr);

            $attrValObj = $this->customAttributeValues()
                ->where('custom_attribute_id', $attrObj->getKey())
                ->get()->first();

            return $attrValObj; 
        } catch (\Exception $e) {
            throw new InvalidCustomAttributeException('Could not get custom attribute value object with key "'.$attr.'"');
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
            throw new InvalidCustomAttributeException('Could not remove custom attribute value object with key "'.$attr.'"');
        }
    }

    /** 
     * Sets the given custom attribute default for this item
     *
     * @param string|int|Model $attr
     * @param string|int|date $value
     * 
     * @return Model
     */
    public function setCustomAttributeDefault($attr, $value) {
        $attrObj = $this->getCustomAttribute($attr);

        $attrObj->default_value = $value;

        if (!$attrObj->has_default) $attrObj->has_default = true;

        $attrObj->save();

        return $attrObj;
    }

    /** 
     * Sets the given custom attribute default for this item
     *
     * @param string|int|Model $attr
     * 
     * @return Model
     */
    public function removeCustomAttributeDefault($attr) {
        $attrObj = $this->getCustomAttribute($attr);

        $attrObj->default_value = null;

        if ($attrObj->has_default) $attrObj->has_default = false;

        $attrObj->save();

        return $attrObj;
    }
}
