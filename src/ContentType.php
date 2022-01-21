<?php
/**
 * Cathedral Db Content Type
 *
 * PHP version 8
 *
 * @author Philip Michael Raab <peep@cathedral.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Db;

/**
 * Enum: ContentType
 *
 * @version 1.0.0
 * @package Cathedral\Db
 */
enum ContentType: string {
    // case JSON = 'json';

    case BOOLEAN = 'boolean';
    case BOOL = 'bool';
    case INTEGER = 'int';
    case INT = 'int';
    case FLOAT = 'float';
    case DOUBLE = 'double';
    case STRING = 'string';
    case ARRAY = 'array';
    case CONSTANT = 'constant';
    case NULL = 'null';
    case OBJECT = 'object';
    case OTHER = 'other';
    case JSON = 'array';
}
