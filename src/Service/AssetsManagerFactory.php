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
use Zend\View\Assets\AssetsManager;

class AssetsManagerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');
        $managerConfig = isset($config['assets_manager']) ? $config['assets_manager'] : [];
        $instance = new AssetsManager($managerConfig);
        if ($container->has('MimeResolver')) {
            $mimeResolver = $container->get('MimeResolver');
        } else {
            $factory = new MimeResolverFactory;
            $mimeResolver = $factory($container, 'MimeResolver');
        }
        $instance->setMimeResolver($mimeResolver);
        return $instance;
    }

    public function createService(ServiceLocatorInterface $serviceLocator, $rName = null, $cName = null)
    {
        return $this($serviceLocator, $cName);
    }
}
