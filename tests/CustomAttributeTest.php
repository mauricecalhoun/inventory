<?php

namespace Stevebauman\Inventory\Tests;

/**
 * Custom Attribute Test
 */
class CustomAttributeTest extends FunctionalTestCase
{
    /*
     *  "Can" tests 
     */
    public function testCanAddCustomStringAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('string', 'Property');
        
        $this->assertTrue($item->hasCustomAttribute('property'));
        $this->assertEquals('string', $item->getCustomAttribute('property')->value_type);
        $this->assertEquals('string', $item->getCustomAttribute('property')->display_type);
        $this->assertEquals('Property', $item->getCustomAttribute('property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomDropdownAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('dropdown', 'Dropdown Property');
        
        $this->assertTrue($item->hasCustomAttribute('dropdown_property'));
        $this->assertEquals('string', $item->getCustomAttribute('dropdown_property')->value_type);
        $this->assertEquals('dropdown', $item->getCustomAttribute('dropdown_property')->display_type);
        $this->assertEquals('Dropdown Property', $item->getCustomAttribute('dropdown_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomIntegerAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('integer', 'Integer Property');

        $this->assertTrue($item->hasCustomAttribute('integer_property'));
        $this->assertEquals('num', $item->getCustomAttribute('integer_property')->value_type);
        $this->assertEquals('integer', $item->getCustomAttribute('integer_property')->display_type);
        $this->assertEquals('Integer Property', $item->getCustomAttribute('integer_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomDecimalAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('decimal', 'Decimal Property');

        $this->assertTrue($item->hasCustomAttribute('decimal_property'));
        $this->assertEquals('num', $item->getCustomAttribute('decimal_property')->value_type);
        $this->assertEquals('decimal', $item->getCustomAttribute('decimal_property')->display_type);
        $this->assertEquals('Decimal Property', $item->getCustomAttribute('decimal_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomCurrencyAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('currency', 'Currency Property');

        $this->assertTrue($item->hasCustomAttribute('currency_property'));
        $this->assertEquals('num', $item->getCustomAttribute('currency_property')->value_type);
        $this->assertEquals('currency', $item->getCustomAttribute('currency_property')->display_type);
        $this->assertEquals('Currency Property', $item->getCustomAttribute('currency_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomDateAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('date', 'Date Property');

        $this->assertTrue($item->hasCustomAttribute('date_property'));
        $this->assertEquals('date', $item->getCustomAttribute('date_property')->value_type);
        $this->assertEquals('date', $item->getCustomAttribute('date_property')->display_type);
        $this->assertEquals('Date Property', $item->getCustomAttribute('date_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomTimeAttribute()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('time', 'Time Property');

        $this->assertTrue($item->hasCustomAttribute('time_property'));
        $this->assertEquals('date', $item->getCustomAttribute('time_property')->value_type);
        $this->assertEquals('Time Property', $item->getCustomAttribute('time_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCanAddCustomLongTextAttribute()
    {   
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('longText', 'Long Text Property');

        $this->assertTrue($item->hasCustomAttribute('long_text_property'));
        $this->assertEquals('string', $item->getCustomAttribute('long_text_property')->value_type);
        $this->assertEquals('longText', $item->getCustomAttribute('long_text_property')->display_type);
        $this->assertEquals('Long Text Property', $item->getCustomAttribute('long_text_property')->display_name);
        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }
    
    public function testCanSetStringCustomAttributeValueById() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('string', 'Property');

        $item->setCustomAttribute($attr->id, 'this is a value');

        $this->assertEquals('this is a value', $item->getCustomAttributeValue('property'));
    }

    public function testCanSetStringCustomAttributeValueByName() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('string', 'Property');

        $item->setCustomAttribute($attr->name, 'this is a value');

        $this->assertEquals('this is a value', $item->getCustomAttributeValue('property'));
    }

    public function testCanSetStringCustomAttributeValueByModel() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('string', 'Property');

        $item->setCustomAttribute($attr, 'this is a value');

        $this->assertEquals('this is a value', $item->getCustomAttributeValue('property'));
    }

    public function testCanSetNumberCustomAttributeValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('integer', 'Number Property');

        $item->setCustomAttribute($attr->id, 42);

        $this->assertEquals(42, $item->getCustomAttributeValue('number_property'));
    }

    public function testCanSetDateCustomAttributeValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('date', 'Date Property');

        $date = getdate();

        $dateFormatted = $date['year'].'-'.$date['mon'].'-'.$date['mday'];

        $item->setCustomAttribute($attr->id, $dateFormatted);

        $this->assertEquals(strtotime($dateFormatted), strtotime($item->getCustomAttributeValue('date_property')));
    }

    public function testCanSetDateCustomAttributeValueWithTimestamp()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('time', 'Time Property');
        
        $time = time();

        $item->setCustomAttribute($attr->id, $time);

        $this->assertEquals($time, strtotime($item->getCustomAttributeValue('time_property')));
    }

    public function testCanRemoveCustomAttribute() 
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('string', 'Property');

        $this->assertTrue($item->removeCustomAttribute('property'));

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->getCustomAttributeValue('property');
        
        $attributes = $item->getCustomAttributes();

        $this->assertFalse($attributes->contains($attr));
    }

    public function testCannotRemoveNonExistentCustomAttribute() 
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('string', 'Property');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->removeCustomAttribute('not a property');

        $this->assertTrue($item->getCustomAttributes()->contains($attr));
    }

    public function testCannotAddSameAttributeTwice() 
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('string', 'String Property');
        
        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->addCustomAttribute('string', 'String Property');
    }

    public function testCanAddExistingAttributeToItem() 
    {
        $item1 = $this->newInventory();
        $item2 = $this->newInventory();

        $attr1 = $item1->addCustomAttribute('string', 'String Property');
        $attr2 = $item1->addCustomAttribute('integer', 'Integer Property');

        $attr3 = $item2->addCustomAttribute('string', 'String Property');

        $this->assertEquals('String Property', $item2->getCustomAttribute('string_property')->display_name);
        $this->assertNotEquals($attr1->id, $attr2->id);
        $this->assertEquals($attr1->id, $attr3->id);
    }

    // TODO: modify this for custom attribute default refactor
    public function testCanAddCustomAttributeWithDefault() 
    {
        $item = $this->newInventory();
    
        $item->addCustomAttribute('string', 'Fresh Property', 'default value');
    
        $this->assertEquals('default value', $item->getCustomAttributeValue('fresh_property'));
    }

    // TODO: modify this for custom attribute default refactor
    public function testCanAddDefaultToExistingCustomAttribute()
    {
        $item = $this->newInventory();
    
        $attr = $item->addCustomAttribute('string', 'New Property');

        $this->assertFalse($attr->has_default);

        $item->setCustomAttributeDefault($attr, 'default value');

        $this->assertEquals('default value', $item->getCustomAttributeDefault($attr->id));

        $this->assertTrue($attr->has_default);
    }

    // TODO: modify this for custom attribute default refactor
    public function testCanChangeCustomAttributeValueWithDefault() 
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('date', 'Date Property', '22-2-2');

        $this->assertEquals(strtotime('22-2-2'), strtotime($item->getCustomAttributeValue($attr->id)));

        $item->setCustomAttribute($attr->id, '33-3-3');

        $this->assertEquals(strtotime('33-3-3'), strtotime($item->getCustomAttributeValue($attr->id)));
        $this->assertEquals(strtotime('22-2-2'), strtotime($item->getCustomAttributeDefault($attr->id)));
    }

    // TODO: modify this for custom attribute default refactor
    public function testCanChangeCustomAttributeDefaultValue() 
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('integer', 'Number Property', 10);

        $item->setCustomAttributeDefault('number_property', 42);

        $this->assertEquals(42, $item->getCustomAttributeDefault('number_property'));
    }

    public function testCanAddRequiredCustomAttribute()
    {
        $item = $this->newInventory();

        // fourth argument should be 'required' field
        $attr = $item->addCustomAttribute('integer', 'Required Number Property', 42, true);

        $this->assertTrue($attr->required);
    }

    public function testCanAddCustomAttributeWithRegexRule()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('string', 'Freshest String Prop', 'def', false, '/s{1}/', 'Should have one \'s\'');

        $this->assertEquals('/s{1}/', $item->getCustomAttribute($attr)->rule);

        // If given a regex rule, it should also have a human-readable description of that rule
        $this->assertEquals('Should have one \'s\'', $item->getCustomAttribute($attr)->rule_desc);
    }

    public function testCanAddExistingCustomAttributeToNewInventoryItemById()
    {
        $item1 = $this->newInventory();

        $attr = $item1->addCustomAttribute('string', 'Newest Property as of right now');

        $item2 = $this->newInventory();

        $item2->setCustomAttribute($attr->id, 'Another value');

        $this->assertEquals('Another value', $item2->getCustomAttributeValue($attr));
    }

    public function testCanAddExistingCustomAttributeToNewInventoryItemByName()
    {
        $item1 = $this->newInventory();

        $attr = $item1->addCustomAttribute('string', 'Newest Property as of right now');

        $item2 = $this->newInventory();

        $attr2 = $item2->setCustomAttribute($attr->name, 'Another value');

        $this->assertEquals('Another value', $item2->getCustomAttributeValue($attr));
    }

    public function testCanAddExistingCustomAttributeToNewInventoryItemByModel()
    {
        $item1 = $this->newInventory();

        $attr = $item1->addCustomAttribute('string', 'Newest Property as of right now');

        $item2 = $this->newInventory();

        $item2->setCustomAttribute($attr, 'Another value');

        $this->assertEquals('Another value', $item2->getCustomAttributeValue($attr));
    }

    public function testCanGetAllCustomAttributesForAnInventoryItem() {
        $item = $this->newInventory();

        $attr1 = $item->addCustomAttribute('string', 'A fresh as heck property');
        $item->setCustomAttribute($attr1, 'string value');

        $attr2 = $item->addCustomAttribute('integer', 'Another super fresh prop but numeric this time');
        $item->setCustomAttribute($attr2, 42);

        $attr3 = $item->addCustomAttribute('date', 'The time and day of these most freshest of properties');
        $item->setCustomAttribute($attr3, '3-1-2021');

        $allAttrs = $item->getCustomAttributes();
        $allAttrVals = $item->getCustomAttributeValues();

        $this->assertCount(3, $allAttrs);
        $this->assertCount(3, $allAttrVals);
        $this->assertEquals($allAttrVals['a_fresh_as_heck_property'], 'string value');
        $this->assertEquals($allAttrVals['another_super_fresh_prop_but_numeric_this_time'], 42);
        $this->assertEquals(strtotime($allAttrVals['the_time_and_day_of_these_most_freshest_of_properties']), strtotime('3-1-2021'));
    }


    
    /*
     *  "Cannot" tests
     */



    public function testCannotAddCustomAttributeWithInvalidRegexRule()
    {
        $item = $this->newInventory();

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->addCustomAttribute('string', 'Another Fresh String Prop', 'def', false, '/\/', 'description');
    }

    public function testCannotAddCustomAttributeWithRegexButNoRuleDescription()
    {
        $item = $this->newInventory();

        $this->expectException('Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->addCustomAttribute('string', 'Freshest String Around', 'default', false, '/s/');
    }

    public function testCannotGiveLongTextCustomAttributeRegexRule()
    {
        $item = $this->newInventory();

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->addCustomAttribute('longText', 'Freshest Longest Text I\'ve seen', 'default', false, '/test/', 'rule description');
    }
    
    public function testCannotCreateCustomAttributeWithInvalidType()
    {
        $item = $this->newInventory();

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->addCustomAttribute('bad type', 'Bad Property');
    }

    public function testCannotAddCustomAttributeWithSameNameAndTypeAsExisting()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('string', 'Prop');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->addCustomAttribute('string', 'Prop');
    }

    public function testCannotAddCustomAttributeWithSameNameAndDifferentTypeAsExisting()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('string', 'Prop');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->addCustomAttribute('integer', 'Prop');
    }

    public function testCannotSetRequiredCustomAttributeToNull()
    {
        $item = $this->newInventory();

        $attr = $item->addCustomAttribute('string', 'Required String Property', 'a million', true);

        $this->expectException('\Stevebauman\Inventory\Exceptions\RequiredCustomAttributeException');

        $item->setCustomAttribute($attr->id, null);
    }

    public function testCannotGetValueOfNonexistentCustomAttribute()
    {
        $item = $this->newInventory();

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->getCustomAttributeValue('not an attribute even a little');
    }

    // TODO: maybe refactor this method
    public function testCannotGetDefaultValueOfNonexistentCustomAttribute()
    {
        $item = $this->newInventory();

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->getCustomAttributeDefault('not an attribute at all');
    }

    // TODO: maybe refactor this as well
    public function testCannotCreateNumericCustomAttributeWithInvalidDefault() 
    {
        $item = $this->newInventory();
        
        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->addCustomAttribute('integer', 'Number Property', 'not a number');
    }

    // TODO: although maybe would be better to just rewrite the backend
    public function testCannotCreateDateCustomAttributeWithInvalidDefault() 
    {
        $item = $this->newInventory();
        
        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->addCustomAttribute('date', 'Date Property', 'not a date');
    }

    // TODO: so that it fits with these scenarios
    public function testCannotCreateTimeCustomAttributeWithInvalidDefault() 
    {
        $item = $this->newInventory();
        
        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->addCustomAttribute('time', 'Time Property', 'not a time');
    }

    public function testCannotSetNumericCustomAttributeWithInvalidValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('integer', 'Number Property');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->setCustomAttribute($attr->id, 'not a number');
    }

    public function testCannotSetDateCustomAttributeWithInvalidValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('date', 'Date Property');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->setCustomAttribute($attr->id, 'not a date');
    }

    public function testCannotSetTimeCustomAttributeWithInvalidValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('time', 'Time Property');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $item->setCustomAttribute($attr->id, 'not a time');
    }
}
