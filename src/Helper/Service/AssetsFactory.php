<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Helper\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Assets\Service\AssetsManagerFactory;
use Zend\View\Assets\Helper\Assets;

class AssetsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config = $container->get('config');
        $config = isset($config['assets_manager']) ? $config['assets_manager'] : [];

        $instance = new Assets();
        if ($container->has('AssetsManager')) {
            $assetsManager = $container->get('AssetsManager');
        } else {
            $factory = new AssetsManagerFactory;
            $assetsManager = $factory($container, 'AssetsManager');
        }
        $instance->setAssetsManager($assetsManager);
        $instance->setRouteName(isset($config['router_name']) ? $config['router_name'] : null);
        $instance->setMimeAttributes(isset($config['mime_attributes']) ? $config['mime_attributes'] : []);

        if ($container->has('Request')) {
            $request = $container->get('Request');
            if (is_callable([$request, 'getBasePath'])) {
                $instance->setBasePath($request->getBasePath());
            }
        }
        return $instance;
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator, $rName = null, $cName = null)
    {
        return $this($serviceLocator, $cName);
    }
}
