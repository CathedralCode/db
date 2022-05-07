<?php

/**
 * Project: DBLayer
 *
 * PHP Templates and structures.
 *
 * PHP version 8.1
 *
 * @package DBLayer
 * @author Philip Michael Raab<peep@inane.co.za>
 *
 * @license UNLICENSE
 * @license https://github.com/inanepain/event/raw/develop/UNLICENSE UNLICENSE
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Db\Adapter;

use Laminas\Db\Adapter\Adapter as LaminasDbAdapter;

use function str_starts_with;
use function strtoupper;

/**
 * Adapter
 *
 * Customised Db Adapter
 *
 * @package DBLayer
 *
 * @version 1.0.0
 */
class Adapter extends LaminasDbAdapter {
    /**
     * execute() is a convenience SELECT query function using QUERY_MODE_EXECUTE
     *
     * @param string $sql
     *
     * @throws \Inane\Stdlib\Exception\InvalidArgumentException
     * @throws \Laminas\Db\Adapter\Exception\InvalidArgumentException
     *
     * @return \Laminas\Db\ResultSet\ResultSet
     */
    public function execute(string $sql): \Laminas\Db\ResultSet\ResultSet {
        if (!str_starts_with(strtoupper($sql), 'SELECT'))
            throw new \Inane\Stdlib\Exception\InvalidArgumentException('Only `SELECT` statements accepted.');

        return parent::query($sql, static::QUERY_MODE_EXECUTE);
    }
}
