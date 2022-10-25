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
