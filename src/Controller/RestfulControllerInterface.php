<?php
/**
 * Restful Controller Interface
 * 
 * PHP version 7
 *
 * @package Cathedral\Db
 * @author Philip Michael Raab <philip@inane.co.za>
 */
declare(strict_types=1);

namespace Cathedral\Db\Controller;

/**
 * Restful Controller Interface
 * 
 * @package Cathedral\Db
 * @version 0.1.0
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
}
