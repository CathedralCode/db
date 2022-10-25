<?php

/**
 * This file is part of the Cathedral package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP version 8.1
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @package Cathedral\Db
 *
 * @license UNLICENSE
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/UNLICENSE UNLICENSE
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 *
 * @version $Id: 0.2.1-4-g4197440$
 * $Date: Tue Jul 26 22:32:33 2022 +0200$
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

// $b = [];
// $b['vt'] = ValueType::FLOAT;
// echo $b['vt']->type() . PHP_EOL;
