<?php

namespace Jasny\Validation\Tests;

use Jasny\Validation\Validation;
use Jasny\Meta\MetaClass;
use Jasny\Meta\MetaProperty;
use Jasny\ValidationResult;
use PHPUnit\Framework\TestCase;

/**
 * @covers Jasny\Validation\Validation
 */
class FactoryTest extends TestCase
{
    use \Jasny\TestHelper;

    /**
     * Test 'validate' method
     */
    public function testValidate()
    {
        $object = new \stdClass();

        $meta = $this->createMock(MetaClass::class);
        $validationResult = $this->createMock(ValidationResult::class);

        $property1 = $this->createMock(MetaProperty::class);
        $property2 = $this->createMock(MetaProperty::class);
        $validationResult1 = $this->createMock(validationResult::class);
        $validationResult2 = $this->createMock(validationResult::class);

        $properties = ['foo' => $property1, 'bar' => $property2];

        $validation = $this->createPartialMock(Validation::class, ['getValidationResult', 'validateProperty']);
        $this->setPrivateProperty($validation, 'meta', $meta);

        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);
        $meta->expects($this->once())->method('getProperties')->willReturn($properties);

        $validation->expects($this->exactly(2))->method('validateProperty')->will($this->returnValueMap([
            [$object, 'foo', $property1, $validationResult1],
            [$object, 'bar', $property2, $validationResult2]
        ]));

        $validationResult->expects($this->exactly(2))->method('add')->withConsecutive(
            [$validationResult1],
            [$validationResult2]
        );

        $result = $validation->validate($object);

        $this->assertSame($validationResult, $result);
    }

    /**
     * Test 'validateProperty' method for 'required' notation
     */
    public function testValidatePropertyRequired()
    {
        $object = (object)['foo' => 'bar'];

        $meta = $this->createMock(MetaProperty::class);
        $validationResult = $this->createMock(ValidationResult::class);
        $validation = $this->createPartialMock(Validation::class, ['getValidationResult']);

        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);
        $meta->expects($this->once())->method('is')->with('required')->willReturn(true);
        $validationResult->expects($this->once())->method('addError')->with('%s is required', 'zoo');

        $result = $validation->validateProperty($object, 'zoo', $meta);

        $this->assertSame($validationResult, $result);
    }

    /**
     * Test 'validateProperty' method for 'required' notation, if property is set
     */
    public function testValidatePropertyRequiredIsSet()
    {
        $object = (object)['foo' => 'bar', 'zoo' => 'baz'];

        $meta = $this->createMock(MetaProperty::class);
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult1 = $this->createMock(ValidationResult::class);
        $validation = $this->createPartialMock(Validation::class, ['getValidationResult', 'validateBasics']);

        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);
        $meta->expects($this->exactly(3))->method('is')->will($this->returnCallback(function($name) {
            return $name === 'required';
        }));

        $validationResult->expects($this->never())->method('addError');
        $validation->expects($this->once())->method('validateBasics')->with($object, 'zoo', $meta)->willReturn($validationResult1);
        $validationResult->expects($this->once())->method('add')->with($validationResult1);

        $result = $validation->validateProperty($object, 'zoo', $meta);

        $this->assertSame($validationResult, $result);
    }

    /**
     * Test 'validateProperty' method, if property is not set
     */
    public function testValidatePropertyNotSet()
    {
        $object = (object)['foo' => 'bar'];

        $meta = $this->createMock(MetaProperty::class);
        $validationResult = $this->createMock(ValidationResult::class);
        $validation = $this->createPartialMock(Validation::class, ['getValidationResult']);

        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);
        $meta->expects($this->once())->method('is')->with('required')->willReturn(false);
        $validationResult->expects($this->never())->method('addError');

        $result = $validation->validateProperty($object, 'zoo', $meta);

        $this->assertSame($validationResult, $result);
    }

    /**
     * Test 'validateProperty' method for 'unique' notation, if method 'hasUnique' does not exist
     */
    public function testValidatePropertyUniqueMethodNotExist()
    {
        $object = (object)['foo' => 'bar', 'zoo' => 'baz'];

        $meta = $this->createMock(MetaProperty::class);
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult1 = $this->createMock(ValidationResult::class);
        $validation = $this->createPartialMock(Validation::class, ['getValidationResult', 'validateBasics']);

        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);
        $meta->expects($this->exactly(2))->method('is')->will($this->returnCallback(function($name) {
            return $name === 'unique';
        }));

        $validationResult->expects($this->once())->method('addError')->with("%s can't check if it has a unique %s", \stdClass::class, 'zoo');
        $validation->expects($this->never())->method('validateBasics');

        $result = $validation->validateProperty($object, 'zoo', $meta);

        $this->assertSame($validationResult, $result);
    }

    /**
     * Provide data for testing 'validateProperty' method for 'unique' notation
     *
     * @return array
     */
    public function validatePropertyUniqueNotUniqueProvider()
    {
        return [
            [true, null],
            ['some_group', 'some_group']
        ];
    }

    /**
     * Test 'validateProperty' method for 'unique' notation
     *
     * @dataProvider validatePropertyUniqueNotUniqueProvider
     */
    public function testValidatePropertyUniqueNotUnique($notationValue, $group)
    {
        $tester = $this;

        $object = new class($tester, $group) {
            public $zoo = 'baz';
            public $group;
            public $tester;

            public function __construct($tester, $group) {
                $this->tester = $tester;
                $this->group = $group;
            }

            public function hasUnique($name, $group) {
                $this->tester->assertSame('zoo', $name);
                $this->tester->assertSame('zoo', $name);
                return false;
            }
        };

        $meta = $this->createMock(MetaProperty::class);
        $validationResult = $this->createMock(ValidationResult::class);
        $validationResult1 = $this->createMock(ValidationResult::class);
        $validation = $this->createPartialMock(Validation::class, ['getValidationResult', 'validateBasics']);

        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);
        $meta->expects($this->exactly(2))->method('is')->will($this->returnCallback(function($name) use ($notationValue) {
            return $name === 'unique' ? $notationValue : false;
        }));

        $validationResult->expects($this->once())->method('addError')->with("There is already a %s with this %s", get_class($object), 'zoo');
        $validation->expects($this->never())->method('validateBasics');

        $result = $validation->validateProperty($object, 'zoo', $meta);

        $this->assertSame($validationResult, $result);
    }

    /**
     * Provide data for testing 'validateBasics' method
     *
     * @return array
     */
    public function validateBasicsProvider()
    {
        return [
            ['min', 4, 10, '%s should be at least %s'],
            ['min', 10, 10, null],
            ['min', 12, 10, null],
            ['min', '12', '10', null],
            ['max', 11, 10, '%s should no at most %s'],
            ['max', 10, 10, null],
            ['max', 7, 10, null],
            ['max', '7', '10', null],
            ['minLength', 'test', 5, '%s should be at least %d characters'],
            ['minLength', 'tests', 5, null],
            ['minLength', 'tests-rests', 5, null],
            ['minLength', 'tests-rests', '5', null],
            ['minLength', '', 0, null],
            ['maxLength', 'test', 3, '%s should be at most %d characters'],
            ['maxLength', 'test', 4, null],
            ['maxLength', 'test', '4', null],
            ['maxLength', 'tes', '4', null],
            ['maxLength', '', 0, null],
            ['options', 'test', 'foo,bar,baz', '%s should be one of: %s'],
            ['options', 'test', 'foo,bar,baz,test', null],
            ['options', 6, '3,4,6', null],
            ['type', 'rgb(255,255,255)', 'color', '%s isn\'t a valid %s'],
            ['type', '#afafag', 'color', '%s isn\'t a valid %s'],
            ['type', '#afafaf', 'color', null],
            ['type', 'foo', 'number', '%s isn\'t a valid %s'],
            ['type', '10f', 'number', '%s isn\'t a valid %s'],
            ['type', '10', 'number', null],
            ['type', 10, 'number', null],
            ['type', 10., 'number', null],
            ['type', 'foo', 'range', '%s isn\'t a valid %s'],
            ['type', '10f', 'range', '%s isn\'t a valid %s'],
            ['type', '10', 'range', null],
            ['type', 10, 'range', null],
            ['type', 10., 'range', null],
            ['type', 'www.foo.com', 'url', '%s isn\'t a valid %s'],
            ['type', 'http:www.foo.com', 'url', '%s isn\'t a valid %s'],
            ['type', 'http://www.foo.com', 'url', null],
            ['type', 'foo', 'email', '%s isn\'t a valid %s'],
            ['type', 'foo@', 'email', '%s isn\'t a valid %s'],
            ['type', 'foo@com', 'email', null],
            ['type', 'foo@email.com', 'email', null],
            ['type', 'test', 'unknown-type', '%s isn\'t a valid %s'],
            ['pattern', '24 45 35a', '^\d{2} \d{2} \d{2}$', '%s isn\'t valid'],
            ['pattern', '24 45 35a', '^\d{2} \d{2} \d{2}[a-z]$', null],
        ];
    }

    /**
     * Test 'validateBasics' method
     *
     * @dataProvider validateBasicsProvider
     */
    public function testValidateBasics($notation, $value, $notationValue, $error)
    {
        $object = (object)['zoo' => $value];

        $meta = $this->createMock(MetaProperty::class);
        $validationResult = $this->createMock(ValidationResult::class);

        $validation = $this->createPartialMock(Validation::class, ['getValidationResult']);
        $validation->expects($this->once())->method('getValidationResult')->willReturn($validationResult);

        $meta->expects($this->any())->method('has')->will($this->returnCallback(function($name) use ($notation) {
            return $notation === $name;
        }));

        $meta->expects($this->any())->method('get')->with($notation)->willReturn($notationValue);

        if ($error) {
            $notation === 'pattern' ?
                $validationResult->expects($this->once())->method('addError')->with($error, 'zoo') :
                $validationResult->expects($this->once())->method('addError')->with($error, 'zoo', $notationValue);
        } else {
            $validationResult->expects($this->never())->method('addError');
        }

        $result = $this->callPrivateMethod($validation, 'validateBasics', [$object, 'zoo', $meta]);
    }
}
