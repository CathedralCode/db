<?php

/**
 * Db\Entity
 *
 * PHP version 8
 *
 * @author Philip Michael Raab <peep@inane.co.za>
 * @license MIT
 *
 * @package Cathedral\Db
 */

declare(strict_types=1);

namespace Cathedral\Db\Entity;

use Exception;
use Laminas\Db\RowGateway\AbstractRowGateway;

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
class AbstractEntity extends AbstractRowGateway {
    /**
     * Convert a column name to a user friendly method name.
     *
     * @param string $property generated method target
     * @param string $prepend getter or setter
     *
     * @return string method name
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
        $columns = $this->getDataTable()->getColumns();

        if ($ignorePrimaryColumn) foreach ($this->primaryKeyColumn as $column) unset($data[$column]);
        foreach ($data as $key => $value) if (!in_array($key, $columns)) unset($data[$key]);

        return $data;
    }
}
