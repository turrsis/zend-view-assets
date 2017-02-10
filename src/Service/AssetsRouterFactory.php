<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Service;

use Zend\ServiceManager\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Assets\AssetsRouter;

class AssetsRouterFactory implements FactoryInterface
{
    protected $assetsClass = AssetsRouter::class;

    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $router = new $this->assetsClass();
        $router->setBasePath($container->get('Request')->getBasePath());
        $router->setAssetsManager($container->get('AssetsManager'));
        $config = $container->get('config');
        if (isset($config['assets_manager']['router_prefix'])) {
            $router->setPrefix($config['assets_manager']['router_prefix']);
        }

        return $router;
    }

    public function createService(ServiceLocatorInterface $serviceLocator, $rName = null, $cName = null)
    {
        return $this($serviceLocator, $cName);
    }
}
