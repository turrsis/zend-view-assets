<?php
/**
 * @link      http://github.com/zendframework/zend-form for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

use Zend\ServiceManager\Factory\InvokableFactory;

class ConfigProvider
{
    /**
     * Return general-purpose zend-i18n configuration.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'view_helpers' => $this->getViewHelperConfig(),
        ];
    }

    /**
     * Return application-level dependency configuration.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'aliases' => [
                'AssetsListener' => 'Zend\View\Assets\AssetsListener',
            ],
            'factories'  => [
                'AssetsManager'                   => 'Zend\View\Assets\Service\AssetsManagerFactory',
                'MimeResolver'                    => 'Zend\View\Assets\Resolver\Service\MimeResolverFactory',
                'ViewAssetsResolver'              => 'Zend\View\Assets\Resolver\Service\AssetsResolverFactory',
                'Zend\View\Assets\AssetsListener' => 'Zend\View\Assets\Service\AssetsListenerFactory',
            ],
        ];
    }

    /**
     * Return zend-form helper configuration.
     *
     * Obsoletes View\HelperConfig.
     *
     * @return array
     */
    public function getViewHelperConfig()
    {
        //F:\aDev\SVN\turrsis_cms\app\vendor\zendframework\zend-view-assets\src\Helper\Service
        //F:\aDev\SVN\turrsis_cms\app\vendor\zendframework\zend-view-assets\src\Hepler\Service
        return [
            'aliases' => [
                'Assets'              => Helper\Assets::class,
                'assets'              => Helper\Assets::class,
            ],
            'factories' => [
                Helper\Assets::class              => Helper\Service\AssetsFactory::class,

                // v2 canonical FQCNs
                'zendviewhelperassets'            => Helper\Service\AssetsFactory::class,
            ],
        ];
    }
}
