<?php

/**
 * Cathedral
 *
 * Db
 *
 * PHP version 8.1
 *
 * @package Owner\Project
 * @author Philip Michael Raab<peep@inane.co.za>
 *
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 */

declare(strict_types=1);

namespace Cathedral\Db\Adapter;

use Laminas\Db\Adapter\Adapter as LaminasAdapter;

class Adapter extends LaminasAdapter {
    /**
     * query() is a convenience function
     *
     * @param string $sql
     * @param string $parametersOrQueryMode
     * @param \Laminas\Db\ResultSet\ResultSetInterface|null $resultPrototype
     *
     * @return void
     */
    public function query(
        $sql,
        $parametersOrQueryMode = self::QUERY_MODE_PREPARE,
        ?\Laminas\Db\ResultSet\ResultSetInterface $resultPrototype = null
    ) {
        try {
            //code...
            return parent::query($sql, $parametersOrQueryMode, $resultPrototype);
        } catch (\Throwable $th) {
            //throw $th;
            return new \Laminas\Db\ResultSet\ResultSet();
        }
    }
}
