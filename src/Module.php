<?php
/**
 * @link      http://github.com/zendframework/zend-form for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

use Zend\Loader\AutoloaderFactory;
use Zend\Loader\StandardAutoloader;
use Zend\Mvc\MvcEvent;

class Module
{
    public function getAutoloaderConfig()
    {
        return array(
            AutoloaderFactory::STANDARD_AUTOLOADER => array(
                StandardAutoloader::LOAD_NS => array(
                    __NAMESPACE__ => __DIR__,
                ),
            ),
        );
    }

    public function getConfig()
    {
        $provider = new ConfigProvider();
        return [
            'service_manager' => $provider->getDependencyConfig(),
            'view_helpers'    => $provider->getViewHelperConfig(),
        ];
    }

    public function onBootstrap(MvcEvent $event)
    {
        $assetsListener = $event->getApplication()->getServiceManager()->get('AssetsListener');
        $assetsListener->attach($event->getApplication()->getEventManager());
        $assetsListener->injectRouter($event);
    }
}
