<?php

/**
 * Cathedral Db
 *
 * PHP version 8
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Db;

/**
 * ValueType
 *
 * Mostly useful for matching MySQL field types to php variable types.
 *
 * @version 1.0.0
 * @package Inane\Data
 */
enum ValueType: string {
    case ARRAY = 'array';
    case BIT = 'bit';
    case BOOL = 'bool';
    case BOOLEAN = 'boolean';
    case CONSTANT = 'constant';
    case DECIMAL = 'decimal';
    case DOUBLE = 'double';
    case FLOAT = 'float';
    case INT = 'int';
    case INTEGER = 'integer';
    case JSON = 'json';
    case NULL = 'null';
    case OBJECT = 'object';
    case OTHER = 'other';
    case STRING = 'string';

    /**
     * The value type for parameters or returns
     *
     * @return string
     */
    public function type(): string {
        return match ($this) {
            static::BIT => 'int',
            static::BOOLEAN => 'bool',
            static::DECIMAL => 'float',
            static::DOUBLE => 'float',
            static::INTEGER => 'int',
            static::JSON => 'array',
            static::OTHER => 'mixed',
            default => $this->value,
        };
    }
}

// A quick loop printing info for each ValueType
// foreach (ValueType::cases() as $type) {
//     static $i = 0;
//     $i++;
//     echo <<<INFO
// item\t: $i
// name\t: {$type->name}
// value\t: {$type->value}
// type\t: {$type->type()}
// \n
// INFO;
// }
