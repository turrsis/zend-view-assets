<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets;

use Zend\View\Assets\AssetsManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\View\Assets\AssetsCollection;
use Zend\View\Assets\Exception;
use Zend\Stdlib\ArrayUtils;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Assets\Cache;

/**
 * @group      Zend_View
 */
class AssetsManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    /**
     * @param array $config
     * @return AssetsManager
     */
    protected function getAssetsManager($config = [])
    {
        $publicFolder = '_files/assets_cache';
        $moduleConfig = (new \Zend\View\Assets\Module())->getConfig();
        $moduleConfig = ArrayUtils::merge(ArrayUtils::merge($moduleConfig, [
            'assets_manager' => [
                'public_folder' => $publicFolder,
                'cache_adapter' => new ArrayUtils\MergeRemoveKey,
                'template_resolver' => [
                    'map_resolver' => [
                        'style1.css' => __DIR__ . '\TestAsset\assets\style1.css',
                        'style2.css' => __DIR__ . '\TestAsset\assets\style2.css',
                    ],
                    'prefix_resolver' => [
                        'foo::' => __DIR__ . '\TestAsset\assets'
                    ],
                    'path_resolver' => [],
                ],
                'collections' => ['default' => [ 'assets' => [
                    'style1.css' => [],
                    'styles-1' => [
                        'assets' => [
                            'style2.css' => [],
                        ],
                    ],
                    'styles-3' => [
                        'assets' => [
                            'foo::style3.css' => [],
                        ],
                    ],
                    'foo::style4.css' => [],
                    'aggregate.css' => [
                        'assets' => [
                            'style1.css' => [],
                            'style2.css' => [],
                        ],
                        'aggregate' => 'Aggregate',
                    ],
                ]]],
            ],
            'service_manager' => [
                'services' => [
                    'Request' => new Request,
                ],
                'factories' => [
                    'AssetsRouter'  => \Zend\View\Assets\Service\AssetsRouterFactory::class,
                    'FilterManager' => function ($container, $name) {
                        $options = $container->get('config');
                        $filter = new \Zend\Filter\FilterPluginManager(
                            $container,
                            isset($options['filters']) ? $options['filters'] : []
                        );
                        return $filter;
                    },
                ],
            ],
        ]), $config);
        $moduleConfig['service_manager']['services']['config'] = $moduleConfig;

        $serviceManager = new ServiceManager($moduleConfig['service_manager']);
        return $serviceManager->get('AssetsManager');
    }

    public function testGetPreparedAsset()
    {
        $am = $this->getAssetsManager();

        // exists in config
        $asset = $am->getPreparedAsset(null, null, 'style1.css');
        $this->assertNull($asset->getTargetContent());
        $this->assertEquals('.style_1 {}', $asset->getTargetContent(true));

        // exists in config
        $asset = $am->getPreparedAsset('styles-1', null, 'style2.css');
        $this->assertNull($asset->getTargetContent());
        $this->assertEquals('.style_2 {}', $asset->getTargetContent(true));

        // exists in config
        $asset = $am->getPreparedAsset(null, 'foo', 'style4.css');
        $this->assertEquals('.style_4 {}', $asset->getTargetContent());

        // exists in config
        $asset = $am->getPreparedAsset('styles-3', 'foo', 'style3.css');
        $this->assertEquals('.style_3 {}', $asset->getTargetContent());
        
        // not exists in config
        $asset = $am->getPreparedAsset(null, 'foo', 'style5.css');
        $this->assertEquals('.style_5 {}', $asset->getTargetContent());
    }

    public function testGetPreparedAssetWrong()
    {
        $this->setExpectedException(
            Exception\NotFoundException::class,
            'collection "notExistsAlias" not found'
        );
        $am = $this->getAssetsManager();
        $asset = $am->getPreparedAsset('notExistsAlias', null, 'style1.css');
    }

    public function testGetPreparedAssetAggregate()
    {
        $am = $this->getAssetsManager();
        $asset = $am->getPreparedAsset('aggregate.css', null, 'aggregate.css');
        $this->assertEquals(".style_1 {}\n.style_2 {}\n", $asset->getTargetContent());
    }

    public function testSetInvalidCollectionName()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'Current group should be not empty string');
        (new AssetsManager(new ServiceManager))->setCollectionName('');
    }

    public function testSetNotExistsCollectionName()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'Collection "NotExists" not exist');
        (new AssetsManager(new ServiceManager))->setCollectionName('NotExists');
    }

    public function testCollection()
    {
        $assetsManager = new AssetsManager(new ServiceManager);

        $this->assertEquals('default', $assetsManager->getCollectionName());
        $this->assertInstanceOf(
            AssetsCollection::class,
            $assetsManager->getCollection()
        );
        $this->assertInstanceOf(
            AssetsCollection::class,
            $assetsManager->getCollection('default')
        );

        $assetsManager->setCollection('foo', []);
        $foo = $assetsManager->getCollection('foo');
        $this->assertInstanceOf(AssetsCollection::class, $foo);
        $this->assertSame($foo,
            $assetsManager->setCollectionName('foo')->getCollection()
        );
    }

    public function testCache()
    {
        $path = '_files';
        $publicFolder = $path . '/assets_cache';
        $this->removeDirectory($path);
        $this->createDirectory($publicFolder);

        $assetsManager = $this->getAssetsManager(['assets_manager' => [
            'cache_adapter' => [
                'adapter' => Cache\DefaultAdapter::class,
                'options' => [
                    'cache_dir' => $publicFolder,
                ],
            ],
        ]]);
        $cachedFileName = $publicFolder . '/assets/ns-foo/style5.css';
        $this->assertFileNotExists($cachedFileName);
        $asset = $assetsManager->getPreparedAsset(null, 'foo', 'style5.css');
        $this->assertEquals('.style_5 {}', $asset->getTargetContent());
        $this->assertFileExists($cachedFileName);

        $this->removeDirectory($path);
    }

    public function testCacheAlreadyCached()
    {
        $path = '_files';
        $publicFolder = $path . '/assets_cache';
        $this->removeDirectory($path);
        $this->createDirectory($publicFolder . '/assets/ns-foo');

        $assetsManager = $this->getAssetsManager(['assets_manager' => [
            'cache_adapter' => [
                'adapter' => Cache\DefaultAdapter::class,
                'options' => [
                    'cache_dir' => $publicFolder,
                ],
            ],
        ]]);

        $cachedFileName = $publicFolder . '/assets/ns-foo/style5.css';
        file_put_contents($cachedFileName, '.style_5 {} cached');

        $asset = $assetsManager->getPreparedAsset(null, 'foo', 'style5.css');

        $this->assertInternalType('resource', $asset->getTargetContent());
        $this->assertEquals('.style_5 {} cached', stream_get_contents($asset->getTargetContent()));
        $this->assertFileExists($cachedFileName);

        fclose($asset->getTargetContent());
        $this->removeDirectory($path);
    }

    public function testGetFilteredContent()
    {
        $assetsManager = $this->getAssetsManager([
            'assets_manager' => [
                'collections' => ['default' => [
                    'assets' => [
                        'style1.css' => [
                            'filters' => [
                                [
                                    'name' => TestAsset\WrapFilter::class,
                                    'options' => ['template' => '#1 %s']
                                ],
                            ],
                        ],
                    ],
                    'filters' => [
                        '\S*.css' => [
                            [
                                'name' => TestAsset\WrapFilter::class,
                                'options' => ['template' => '#2 %s']
                            ],
                        ],
                    ],
                ]],
                'filters' => new ArrayUtils\MergeReplaceKey([
                    '*' => [
                        [
                            'name' => TestAsset\WrapFilter::class,
                            'options' => ['template' => '#3 %s']
                        ],
                    ],
                    '\S*.css' => [
                        ['name' => TestAsset\WrapFilter::class],
                    ]
                ]),
            ],
            'filters' => [
                'invokables' => [
                    TestAsset\WrapFilter::class,
                ],
            ],
        ]);

        $asset = $assetsManager->getPreparedAsset(null, null, 'style1.css');
        $this->assertEquals("=== #3 #2 #1 .style_1 {} ===", $asset->getTargetContent());
    }

    public function testGetFilters()
    {
        $assetsManager = $this->getAssetsManager([
            'assets_manager' => [
                'collections' => ['default' => [
                    'filters' => [
                        '\S*.css' => [
                            [
                                'name' => TestAsset\WrapFilter::class,
                                'options' => ['template' => '#2 %s']
                            ],
                        ],
                    ],
                ]],
                'filters' => new ArrayUtils\MergeReplaceKey([
                    '*' => [
                        [
                            'name' => TestAsset\WrapFilter::class,
                            'options' => ['template' => '#3 %s']
                        ],
                    ],
                ]),
            ],
            'filters' => [
                'invokables' => [
                    TestAsset\WrapFilter::class,
                ],
            ],
        ]);
        $filters = $assetsManager->getFilters('foo.css');
        $this->assertEquals([
            [
                'name' => TestAsset\WrapFilter::class,
                'options' => ['template' => '#2 %s']
            ],
            [
                'name' => TestAsset\WrapFilter::class,
                'options' => ['template' => '#3 %s']
            ],
        ], $filters);
        $this->assertTrue($assetsManager->hasFilters('foo.css'));

        $filters = $assetsManager->getFilters('foo.less');
        $this->assertEquals([
            [
                'name' => TestAsset\WrapFilter::class,
                'options' => ['template' => '#3 %s']
            ],
        ], $filters);
        $this->assertTrue($assetsManager->hasFilters('foo.less'));
    }

    protected function createDirectory($path)
    {
        if (!file_exists($path) && @mkdir($path, 0777, true) === false) {
            throw new \Exception('can not create folder for caching asset');
        }
    }

    protected function removeDirectory($path)
    {
        if (!file_exists($path)) {
            return;
        }
        foreach (glob($path . '/*') as $file) {
            is_dir($file) ? $this->removeDirectory($file) : unlink($file);
        }
        rmdir($path);
    }
}
