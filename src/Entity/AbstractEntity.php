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
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 *
 * @version $Id$
 * $Date$
 */

declare(strict_types=1);

namespace Cathedral\Db\Entity;

use Exception;
use Laminas\Db\RowGateway\AbstractRowGateway;

use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function in_array;
use function str_replace;
use function ucwords;

/**
 * AbstractEntity
 *
 * @version 1.1.0
 * @package Cathedral\Db
 */
abstract class AbstractEntity extends AbstractRowGateway {
    /**
     * Convert a column name to a user friendly method name.
     *
     * @param string $property generated method target
     * @param string $prepend getter or setter
     *
     * @return string method name
     */
    protected function parseMethodName(string $property, string $prepend = 'get'): string {
        return $prepend . str_replace(['_', ' '], [' ', ''], ucwords($property));
    }

    /**
     * magic method: __sleep
     *
     * @return array
     */
    public function __sleep(): array {
        return ['data'];
    }

    /**
     * magic method: __wakeup
     */
    public function __wakeup() {
    }

    /**
     * magic method: __get
     *
     * @param string $property
     *
     * @return mixed
     */
    public function __get($property) {
        if (!in_array($property, array_keys($this->data))) throw new Exception("Invalid Property:\n\t" . $this::class . " has no property: {$property}");
        $method = $this->parseMethodName($property);
        return $this->$method();
    }

    /**
     * magic method: __set
     *
     * @param string $property
     * @param mixed $value
     *
     * @return static
     */
    public function __set($property, $value) {
        if (!in_array($property, array_keys($this->data))) throw new Exception("Invalid Property:\n\t" . $this::class . " has no property: {$property}");
        $method = $this->parseMethodName($property, 'set');
        try {
            $this->$method($value);
        } catch (\Throwable $th) {
            if ($th instanceof \TypeError) $this->$method((int)$value);
            else throw $th;
        }
    }

    /**
     * Array copy of object
     *
     * @param ?object $object
     * @param bool $ignorePrimaryColumn
     *
     * @return Array
     */
    public function getArrayCopy(?object $object = null, bool $ignorePrimaryColumn = false): array {
        $data = array_merge([], $this->data);
        if ($ignorePrimaryColumn) foreach ($this->primaryKeyColumn as $column) unset($data[$column]);

        return array_intersect_key($data, array_flip($this->getDataTable()->getColumns()));
    }
}

