<?php

declare(strict_types=1);

namespace Jasny\Validation;

use Jasny\Meta\MetaClass;
use Jasny\Meta\MetaProperty;
use Jasny\Entity\Entity;
use Jasny\ValidationResult;
use function Jasny\expect_type;

/**
 * Validate using meta
 */
class Validation
{
    /**
     * Meta data of given class
     * @var MetaClass
     **/
    protected $meta;

    /**
     * Create class instance
     *
     * @param MetaClass $meta
     */
    public function __construct(MetaClass $meta)
    {
        $this->meta = $meta;
    }

    /**
     * Validate entity
     *
     * @return ValidationResult
     */
    public function validate($object): ValidationResult
    {
        expect_type($object, 'object');

        $validation = $this->getValidationResult();
        $properties = $this->meta->getProperties();

        foreach ($properties as $prop => $meta) {
            $validation->add($this->validateProperty($object, $prop, $meta));
        }

        return $validation;
    }

    /**
     * Validate a property
     *
     * @param object      $object
     * @param string      $prop
     * @param MetaProperty $meta
     * @return ValidationResult
     */
    public function validateProperty($object, string $prop, MetaProperty $meta): ValidationResult
    {
        expect_type($object, 'object');

        $validation = $this->getValidationResult();

        if ($meta->is('required') && !isset($object->$prop)) {
            $validation->addError("%s is required", $prop);
        }

        if (!isset($object->$prop)) {
            return $validation;
        }

        if ($meta->is('unique')) {
            $unique = $meta->get('unique');
            $uniqueGroup = is_string($unique) ? $unique : null;

            if (!method_exists($object, 'hasUnique')) {
                $validation->addError("%s can't check if it has a unique %s", get_class($object), $prop);
                return $validation;
            } elseif (!$object->hasUnique($prop, $uniqueGroup)) {
                $validation->addError("There is already a %s with this %s", get_class($object), $prop);
                return $validation;
            }
        }

        if ($meta->is('immutable')) {
            if (!$object instanceof Entity) {
                $validation->addError(get_class($object) . " is not Entity, can't check if %s has changed", $prop);
                return $validation;
            } elseif (!$object->isNew()) {
                $validation->addError("%s shouldn't be modified", $prop);
                return $validation;
            }
        }

        $validation->add($this->validateBasics($object, $prop, $meta));

        return $validation;
    }

    /**
     * Perform basic validation on property
     *
     * @param object       $object
     * @param string       $prop
     * @param MetaProperty $meta
     * @return ValidationResult
     */
    protected function validateBasics($object, string $prop, MetaProperty $meta): ValidationResult
    {
        $validation = $this->getValidationResult();

        if ($meta->has('min') && $object->$prop < $meta->get('min')) {
            $validation->addError("%s should be at least %s", $prop, $meta->get('min'));
        }

        if ($meta->has('max') && $object->$prop > $meta->get('max')) {
            $validation->addError("%s should no at most %s", $prop, $meta->get('max'));
        }

        if ($meta->has('minLength') && strlen($object->$prop) < $meta->get('minLength')) {
            $validation->addError("%s should be at least %d characters", $prop, $meta->get('minLength'));
        }

        if ($meta->has('maxLength') && strlen($object->$prop) > $meta->get('maxLength')) {
            $validation->addError("%s should be at most %d characters", $prop, $meta->get('maxLength'));
        }

        if ($meta->has('options')) {
            $options = array_map('trim', explode(',', $meta->get('options')));
            if (!in_array($object->$prop, $options)) {
                $validation->addError("%s should be one of: %s", $prop, $meta->get('options'));
            }
        }

        if ($meta->has('type') && !$this->validateType($object, $prop, $meta->get('type'))) {
            $validation->addError("%s isn't a valid %s", $prop, $meta->get('type'));
        }

        if ($meta->has('pattern') && !$this->validatePattern($object, $prop, $meta->get('pattern'))) {
            $validation->addError("%s isn't valid", $prop);
        }

        return $validation;
    }

    /**
     * Validate for a property type
     *
     * @param object $object
     * @param string $prop
     * @param string $type
     * @return boolean
     */
    protected function validateType($object, string $prop, string $type): bool
    {
        $value = $object->$prop;

        switch ($type) {
            case 'color':
                return strlen($value) === 7 && $value[0] === '#' && ctype_xdigit(substr($value, 1));
            case 'number':
                return is_int($value) || ctype_digit((string)$value);
            case 'range':
                return is_numeric($value);
            case 'url':
                $pos = strpos($value, '://');
                return $pos !== false && ctype_alpha(substr($value, 0, $pos));
            case 'email':
                return (bool)preg_match('/^[\w\-\.\+]+@[\w\-\.]*\w$/', $value);

            default:
                return false;
        }
    }

    /**
     * Validate the value of the control against a regex pattern.
     *
     * @param object $object
     * @param string $prop
     * @param string $pattern
     * @return boolean
     */
    protected function validatePattern($object, string $prop, string $pattern): bool
    {
        return (bool)preg_match('/^(?:' . str_replace('/', '\/', $pattern) . ')$/', $object->$prop);
    }

    /**
     * Instantiate validation result
     *
     * @return ValidationResult
     */
    protected function getValidationResult()
    {
        return new ValidationResult();
    }
}
