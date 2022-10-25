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

use Interop\Container\ContainerInterface;
use Laminas\Db\Adapter\AdapterAbstractServiceFactory as LaminasAdapterAbstractServiceFactory;

/**
 * Adapter Abstract Service Factory
 *
 * Customised Adapter Abstract Service Factory
 *
 * @package DBLayer
 *
 * @version 0.1.0
 */
class AdapterAbstractServiceFactory extends LaminasAdapterAbstractServiceFactory {
	/**
	 * Invoke Adapter
	 * 
	 * @param \Interop\Container\ContainerInterface $container container
	 * @param string $requestedName request name
	 * @param array $options options
	 * 
	 * @return \Laminas\Db\Adapter\Adapter 
	 */
	public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null) {
		$config = $this->getConfig($container);
		return new Adapter($config[$requestedName]);
	}
}
