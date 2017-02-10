<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Service;

use Zend\View\Assets\Service\AssetsResolverFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use Zend\View\Assets\Exception;
use Zend\View\Resolver\AggregateResolver;

/**
 * @group      Zend_View
 */
class AssetsResolverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $config
     * @return ServiceManager
     */
    protected function getServiceManager($config)
    {
        $config = new Config($config);
        $serviceManager = new ServiceManager();
        $config->configureServiceManager($serviceManager);
        return $serviceManager;
    }

    public function testFactory()
    {
        $factory = new AssetsResolverFactory();
        $resolver = $factory($this->getServiceManager(['services' => [
            'config' => ['assets_manager' => [
                'public_folder' => 'foo',
                'template_resolver' => [
                    'path_resolver' => [
                        'script_paths' => [
                            'scriptPath'
                        ]
                    ],
                    'map_resolver' => [
                        'map' => 'map_value'
                    ],
                    'prefix_resolver' => [
                        'prefix' => 'prefix_path',
                    ],
                ],
            ]],
        ]]), '');

        $this->assertInstanceOf(AggregateResolver::class, $resolver);

        list($path, $map, $prefix) = $resolver->getIterator()->toArray();

        $this->assertEquals([
            'prefix' => 'prefix_path',
            'public::' => 'foo'
        ], $this->readAttribute($prefix, 'prefixes'));

        $this->assertEquals([
            'scriptPath' . DIRECTORY_SEPARATOR,
            'foo' . DIRECTORY_SEPARATOR,
        ], $path->getPaths()->toArray());
        $this->assertEquals(['map' => 'map_value'], $map->getMap());
    }

    public function testFactoryWithEmptyConfig()
    {
        $factory = new AssetsResolverFactory();
        $resolver = $factory($this->getServiceManager(['services' => [
            'config' => ['assets_manager'=>[]],
        ]]), '');

        $this->assertInstanceOf('Zend\View\Resolver\AggregateResolver', $resolver);

        list($path) = $resolver->getIterator()->toArray();

        $this->assertEquals(['./public' . DIRECTORY_SEPARATOR], $path->getPaths()->toArray());
    }

    public function testFactoryWithInvalidConfig()
    {
        $factory = new AssetsResolverFactory();
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'Zend\View\Assets\Service\AssetsResolverFactory::__invoke: expects "assets_manager" key in Config');
        $factory($this->getServiceManager(['services' => ['config' => []]]), '');
    }
}
