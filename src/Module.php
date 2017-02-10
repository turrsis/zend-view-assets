<?php
/**
 * @link      http://github.com/zendframework/zend-form for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

use Zend\Loader\AutoloaderFactory;
use Zend\Loader\StandardAutoloader;
use Zend\Stdlib\ArrayUtils;

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
        $mvcProvider = new Mvc\ConfigProvider();
        return [
            'service_manager' => ArrayUtils::merge(
                $provider->getDependencyConfig(),
                $mvcProvider->getDependencyConfig()
            ),
            'view_helpers'    => $provider->getViewHelperConfig(),
            'assets_manager'  => $provider->getAssetsManagerConfig(),
            'filters'         => $provider->getFiltersConfig(),
            'listeners'       => $mvcProvider->getListenersConfig(),
        ];
    }
}
