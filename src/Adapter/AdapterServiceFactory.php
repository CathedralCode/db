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
