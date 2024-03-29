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

namespace Cathedral\Db\Adapter;

use Laminas\Db\Adapter\Adapter as LaminasAdapter;

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
class Adapter extends LaminasAdapter {
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
