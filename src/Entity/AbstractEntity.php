<?php

/**
 * Db\Entity
 * 
 * PHP version 8
 */

declare(strict_types=1);

namespace Cathedral\Db\Entity;

use Exception;
use Laminas\Db\RowGateway\AbstractRowGateway;

use function in_array;
use function array_keys;
use function array_merge;
use function str_replace;
use function ucwords;

/**
 * AbstractEntity
 * 
 * @version 1.0.0
 * 
 * @package Cathedral\Db
 */
class AbstractEntity extends AbstractRowGateway {
    /**
     * Convert a column name to a user friendly method name.
     *
     * @param string $property
     * @param string $prepend
     * 
     * @return string
     */
    protected function parseMethodName(string $property, string $prepend = 'get'): string {
        // return $prepend . str_replace(' ', '', ucwords(str_replace('_', ' ', $property)));
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
     * magic method: _wakeup
     */
    public function _wakeup() {
    }

    /**
     * magic method: __get
     *
     * @param string $property
     * 
     * @return mixed
     */
    public function __get($property) {
        if (!in_array($property, array_keys($this->data))) throw new Exception("Invalid Property:\n\t{$this::class} has no property: {$property}");
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
        if (!in_array($property, array_keys($this->data))) throw new Exception("Invalid Property:\n\t{$this::class} has no property: {$property}");
        $method = $this->parseMethodName($property, 'set');
        $this->$method($value);
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
        return $data;
    }
}
