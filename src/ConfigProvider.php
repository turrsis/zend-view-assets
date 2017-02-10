<?php
/**
 * @link      http://github.com/zendframework/zend-form for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

class ConfigProvider
{
    /**
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
            'factories'  => [
                'AssetsRouter'        => Service\AssetsRouterFactory::class,
                'AssetsManager'       => Service\AssetsManagerFactory::class,
                'MimeResolver'        => Service\MimeResolverFactory::class,
                'ViewAssetsResolver'  => Service\AssetsResolverFactory::class,
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

    /**
     * @return array
     */
    public function getAssetsManagerConfig()
    {
        return [
            'router_prefix' => 'assets',
            'public_folder' => './public',
            'cache_adapter' => [
                'adapter' => Cache\DefaultAdapter::class,
                'options' => [
                    'cache_dir' => './public',
                ],
            ],
            'filters' => [
                '\S*::\S*.css' => [ // fix url paths
                    ['name' => 'CssImports'],
                ],
                '\S*.less' => [
                    ['name' => 'LessPhpFilter'],
                    ['name' => 'CssImports'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFiltersConfig()
    {
        return [
            'aliases' => [
                'CssImports' => Filter\CssImports::class,
                'LessPhpFilter' => Filter\LessPhp::class,
            ],
            'factories' => [
                Filter\CssImports::class => Filter\Service\CssImportsFactory::class,
            ],
            'invokables' => [
                Filter\LessPhp::class,
            ],
        ];
    }
}
