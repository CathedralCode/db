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

// use Laminas\Db\Adapter\AdapterServiceFactory as LaminasDbAdapterServiceFactory;

use Laminas\Db\Adapter\AdapterInterface;
use Psr\Container\ContainerInterface;

use Laminas\ServiceManager\{
	Factory\FactoryInterface,
	ServiceLocatorInterface
};

/**
 * AdapterServiceFactory
 *
 * Customised Db AdapterServiceFactory
 *
 * @package DBLayer
 *
 * @version 1.1.0
 */
class AdapterServiceFactory implements FactoryInterface {
	/**
	 * Create db adapter service
	 * 
	 * @param \Psr\Container\ContainerInterface $container 
	 * @param string $requestedName 
	 * @param null|mixed[] $options 
	 * 
	 * @return \Cathedral\Db\Adapter\Adapter|\Laminas\Db\Adapter\AdapterInterface 
	 * 
	 * @throws \Psr\Container\NotFoundExceptionInterface 
	 * @throws \Psr\Container\ContainerExceptionInterface 
	 */
	public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): Adapter|AdapterInterface {
		$config = $container->get('config');
		return new Adapter($config['db']);
	}

	/**
	 * Create db adapter service (v2)
	 * 
	 * @param \Laminas\ServiceManager\ServiceLocatorInterface $container 
	 * 
	 * @return \Cathedral\Db\Adapter\Adapter|\Laminas\Db\Adapter\AdapterInterface 
	 */
	public function createService(ServiceLocatorInterface $container): Adapter|AdapterInterface {
		return $this($container, Adapter::class);
	}
}
