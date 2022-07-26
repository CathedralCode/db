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
 * @license MIT
 * @license https://raw.githubusercontent.com/CathedralCode/Builder/develop/LICENSE MIT License
 *
 * @copyright 2013-2022 Philip Michael Raab <peep@inane.co.za>
 *
 * @version $Id$
 * $Date$
 */

declare(strict_types=1);

namespace Cathedral\Db\Adapter;

// use Laminas\Db\Adapter\AdapterServiceFactory as LaminasDbAdapterServiceFactory;

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
 * @version 1.0.0
 */
// class AdapterServiceFactory extends LaminasDbAdapterServiceFactory {
class AdapterServiceFactory implements FactoryInterface {
    /**
     * Create db adapter service
     *
     * @param string $requestedName
     * @param array $options
     * @return Adapter
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->get('config');
        return new Adapter($config['db']);
    }

    /**
     * Create db adapter service (v2)
     *
     * @return Adapter
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, Adapter::class);
    }
}

