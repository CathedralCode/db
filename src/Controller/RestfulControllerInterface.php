<?php
/**
 * Restful Controller Interface
 *
 * PHP version 8
 *
 * @package Cathedral\Db
 * @author Philip Michael Raab <philip@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Db\Controller;

/**
 * Restful Controller Interface
 *
 * @version 0.3.0
 * @package Cathedral\Db
 */
interface RestfulControllerInterface {
    /**
     * @var integer default page size
     */
    const PAGINATION_PAGESIZE = 10;

    /**
     * @var string type int
     */
    const CONTENT_TYPE_NUMBER = 'int';

    /**
     * @since 0.3.0
     * @var string type float
     */
    const CONTENT_TYPE_FLOAT = 'float';
}
