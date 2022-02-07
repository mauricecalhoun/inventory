<?php

namespace Stevebauman\Inventory\Tests;

class CustomAttributeTest extends FunctionalTestCase
{
    public function testCanAddCustomStringAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('string', 'Property');
        
        $this->assertTrue($item->hasCustomAttribute('property'));
        $this->assertEquals('string', $item->getCustomAttribute('property')->value_type);
        $this->assertEquals('Property', $item->getCustomAttribute('property')->display_name);
    }

    public function testCanAddCustomDropdownAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('dropdown', 'Dropdown Property');
        
        $this->assertTrue($item->hasCustomAttribute('dropdown_property'));
        $this->assertEquals('string', $item->getCustomAttribute('dropdown_property')->value_type);
        $this->assertEquals('Dropdown Property', $item->getCustomAttribute('dropdown_property')->display_name);
    }

    public function testCanAddCustomIntegerAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('integer', 'Integer Property');

        $this->assertTrue($item->hasCustomAttribute('integer_property'));
        $this->assertEquals('num', $item->getCustomAttribute('integer_property')->value_type);
        $this->assertEquals('Integer Property', $item->getCustomAttribute('integer_property')->display_name);
    }

    public function testCanAddCustomDecimalAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('decimal', 'Decimal Property');

        $this->assertTrue($item->hasCustomAttribute('decimal_property'));
        $this->assertEquals('num', $item->getCustomAttribute('decimal_property')->value_type);
        $this->assertEquals('Decimal Property', $item->getCustomAttribute('decimal_property')->display_name);
    }

    public function testCanAddCustomCurrencyAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('currency', 'Currency Property');

        $this->assertTrue($item->hasCustomAttribute('currency_property'));
        $this->assertEquals('num', $item->getCustomAttribute('currency_property')->value_type);
        $this->assertEquals('Currency Property', $item->getCustomAttribute('currency_property')->display_name);
    }

    public function testCanAddCustomDateAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('date', 'Date Property');

        $this->assertTrue($item->hasCustomAttribute('date_property'));
        $this->assertEquals('date', $item->getCustomAttribute('date_property')->value_type);
        $this->assertEquals('Date Property', $item->getCustomAttribute('date_property')->display_name);
    }

    public function testCanAddCustomTimeAttribute()
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('time', 'Time Property');

        $this->assertTrue($item->hasCustomAttribute('time_property'));
        $this->assertEquals('date', $item->getCustomAttribute('time_property')->value_type);
        $this->assertEquals('Time Property', $item->getCustomAttribute('time_property')->display_name);
    }
    
    public function testCanSetStringCustomAttributeValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('string', 'Property');

        $item->setCustomAttribute($attr->id, 'string', 'this is a value');

        $this->assertEquals('this is a value', $item->getCustomAttributeValue('property'));
    }

    public function testCanSetNumberCustomAttributeValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('integer', 'Number Property');

        $item->setCustomAttribute($attr->id, 'num', 42);

        $this->assertEquals(42, $item->getCustomAttributeValue('number_property'));
    }

    public function testCanSetDateCustomAttributeValue() 
    {
        $item = $this->newInventory();
        
        $attr = $item->addCustomAttribute('date', 'Date Property');

        $date = getdate();

        $dateFormatted = $date['mon'].'-'.$date['mday'].'-'.$date['year'];

        $item->setCustomAttribute($attr->id, 'date', $dateFormatted);

        $this->assertEquals($dateFormatted, $item->getCustomAttributeValue('date_property'));
    }

    public function testCanRemoveCustomAttribute() 
    {
        $item = $this->newInventory();

        $item->addCustomAttribute('string', 'Property');

        $item->removeCustomAttribute('property');

        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');

        $item->getCustomAttributeValue('property');
    }

    public function testCannotAddSameAttributeTwice() {
        $item = $this->newInventory();

        $attr1 = $item->addCustomAttribute('string', 'String Property');
        
        $this->expectException('\Stevebauman\Inventory\Exceptions\InvalidCustomAttributeException');
        
        $attr2 = $item->addCustomAttribute('string', 'String Property');
    }

    public function testCanAddExistingAttributeToItem() {
        $item1 = $this->newInventory();
        $item2 = $this->newInventory();

        $attr1 = $item1->addCustomAttribute('string', 'String Property');
        $attr2 = $item1->addCustomAttribute('integer', 'Integer Property');

        $attr3 = $item2->addCustomAttribute('string', 'String Property');

        $this->assertEquals('String Property', $item2->getCustomAttribute('string_property')->display_name);
        $this->assertNotEquals($attr1->id, $attr2->id);
        $this->assertEquals($attr1->id, $attr3->id);
    }

    public function testCanAddCustomAttributeWithDefault() 
    {
        $item = $this->newInventory();
    
        $item->addCustomAttribute('string', 'Property', 'default value');
    
        $this->assertEquals('default value', $item->getCustomAttributeValue('property'));
    }
}
