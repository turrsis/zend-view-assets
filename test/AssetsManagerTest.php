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
use Zend\View\Assets\Asset\Asset;
use Zend\View\Assets\Asset\Alias;
use Zend\View\Exception;

/**
 * @group      Zend_View
 */
class AssetsManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    public function setUp()
    {
        $this->assetsManager = new AssetsManager();
    }

    public function testSetGetHas()
    {
        $this->assetsManager
                ->set('/string-asset.css', '/string.less')
                ->set('/string-asset.css', '/string.less')
                ->set('/array-asset.css', [
                    'source' => '/foo.less',
                    'attributes' => [],
                ])
                ->set('/array-asset-no-source.css', [
                    'attributes' => [],
                ])
                ->set('pre::/prefix-string-assets.css', [
                    'assets' => '/css/bar.less',
                    'attributes' => [],
                ])
                ->set('array-assets', [
                    'assets' => [
                        '/string-asset.css',
                        'pre::/prefix-string-assets.css',
                        'array-asset.css' => [
                            'source' => 'baz.less',
                            'attributes' => [],
                        ],
                        '/string-source.css',
                    ],
                    'attributes' => [],
                ]);

        $stringAssetCss = $this->assetsManager->get('string-asset.css');
        $this->assertTrue($this->assetsManager->has('string-asset.css'));
        $this->assertInstanceOf(Asset::class, $stringAssetCss);

        $arrayAssetCss = $this->assetsManager->get('array-asset.css');
        $this->assertTrue($this->assetsManager->has('array-asset.css'));
        $this->assertInstanceOf(Asset::class, $arrayAssetCss);
        $this->assertEquals('foo.less', $arrayAssetCss->getSource());

        $arrayAssetNoSourceCss = $this->assetsManager->get('array-asset-no-source.css');
        $this->assertTrue($this->assetsManager->has('array-asset-no-source.css'));
        $this->assertInstanceOf(Asset::class, $arrayAssetNoSourceCss);

        $prefixStringAssetsCss = $this->assetsManager->get('pre::/prefix-string-assets.css');
        $this->assertTrue($this->assetsManager->has('pre::/prefix-string-assets.css'));
        $this->assertInstanceOf(Alias::class, $prefixStringAssetsCss);

        $arrayAssets = $this->assetsManager->get('array-assets');
        $this->assertTrue($this->assetsManager->has('array-assets'));
        $this->assertInstanceOf(Alias::class, $arrayAssets);

        $source1 = $arrayAssets->get('string-asset.css');
        $this->assertTrue($arrayAssets->has('string-asset.css'));
        $this->assertSame($stringAssetCss, $source1);

        $source2 = $arrayAssets->get('pre::/prefix-string-assets.css');
        $this->assertTrue($arrayAssets->has('pre::/prefix-string-assets.css'));
        $this->assertSame($prefixStringAssetsCss, $source2);

        $source3 = $arrayAssets->get('array-asset.css');
        $this->assertTrue($arrayAssets->has('array-asset.css'));
        $this->assertInstanceOf(Asset::class, $source3);

        $source4 = $arrayAssets->get('string-source.css');
        $this->assertTrue($arrayAssets->has('string-source.css'));
        $this->assertInstanceOf(Asset::class, $source4);

        $this->assertFalse($arrayAssets->has('notExist'));
        $this->assertFalse($this->assetsManager->has('notExist'));
    }

    public function testSetInvalidAlias()
    {
        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            'Zend\View\Assets\AssetsManager::set: expects "$asset" parameter an Zend\View\Assets\Asset, Zend\View\Assets\Assets, string or array, received "stdClass"'
        );
        $this->assetsManager->set('a1', new \stdClass());
    }

    public function testCurrentGroup()
    {
        $this->assertEquals('default', $this->assetsManager->getCurrentGroup());

        $this->assetsManager->setCurrentGroup('group-1')
                ->set('foo.css', []);
        $this->assetsManager->setCurrentGroup('group-2')
                ->set('bar.css', []);

        $this->assetsManager->setCurrentGroup('group-1');
        $this->assertEquals('group-1', $this->assetsManager->getCurrentGroup());
        $this->assertTrue($this->assetsManager->has('foo.css'));
        $this->assertFalse($this->assetsManager->has('bar.css'));

        $this->assetsManager->setCurrentGroup('group-2');
        $this->assertEquals('group-2', $this->assetsManager->getCurrentGroup());
        $this->assertFalse($this->assetsManager->has('foo.css'));
        $this->assertTrue($this->assetsManager->has('bar.css'));
    }

    public function testSetInvalidCurrentGroup()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'Current group should be not empty string');
        $this->assetsManager->setCurrentGroup('');
    }

    public function testFilter()
    {
        $this->assetsManager
                ->set('bothFilter.less', [
                    'filters' => ['filter3'],
                ])
                ->set('systemFilter.less', 'filters.less')
                ->set('assetFilter.css', [
                    'filters' => ['filter4'],
                ])
                ->set('noFilter.css', 'filters.css');
        $this->assetsManager->setFilter('\S*.less', 'less_filter');

        $asset = $this->assetsManager->get('bothFilter.less');
        $this->assertEquals(
            ['less_filter', 'filter3'],
            $this->assetsManager->getAssetFilters($asset)
        );
        $this->assertTrue($this->assetsManager->hasAssetFilters($asset));

        $asset = $this->assetsManager->get('systemFilter.less');
        $this->assertEquals(
            ['less_filter'],
            $this->assetsManager->getAssetFilters($asset)
        );
        $this->assertTrue($this->assetsManager->hasAssetFilters($asset));

        $asset = $this->assetsManager->get('assetFilter.css');
        $this->assertEquals(
            ['filter4'],
            $this->assetsManager->getAssetFilters($asset)
        );
        $this->assertTrue($this->assetsManager->hasAssetFilters($asset));
    }

    public function testPublicFolder()
    {
        $this->assetsManager->setPublicFolder('foo');
        $this->assertEquals('./foo', $this->assetsManager->getPublicFolder());
    }

    public function testSetInvalidPublicFolder()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class, 'Public folder should be not empty string');
        $this->assetsManager->setPublicFolder('');
    }

    public function testPassOptionsViaConstructor()
    {
        $this->assetsManager = new AssetsManager([
            'assets' => [
                'group' => [
                    'bat.css' => [],
                ],
            ],
            'filters' => ['\S*.css' => 'f1'],
            'public_folder' => 'bar',
        ]);

        $this->assetsManager->setCurrentGroup('group');
        $asset = $this->assetsManager->get('bat.css');

        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals(['f1'], $this->assetsManager->getAssetFilters($asset));
        $this->assertEquals('./bar', $this->assetsManager->getPublicFolder());
    }

    public function testPassAssetsViaConstructor()
    {
        $this->assetsManager = new AssetsManager([
            'assets' => [
                'default' => [
                    'foo.css' => [],
                    'several_assets' => [
                        'assets' => [
                            'bar.css',
                            'bat.js' => [
                                'attributes' => [
                                    'charset' => 'UTF-8',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $foo = $this->assetsManager->get('foo.css');
        $this->assertInstanceOf(Asset::class, $foo);

        $severalAssets = $this->assetsManager->get('several_assets');
        $this->assertInstanceOf(Alias::class, $severalAssets);

        $asset = $severalAssets->get('bar.css');
        $this->assertInstanceOf(Asset::class, $asset);

        $asset = $severalAssets->get('bat.js');
        $this->assertInstanceOf(Asset::class, $asset);
    }
}
