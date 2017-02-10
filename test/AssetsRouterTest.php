<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets;

use Zend\View\Assets\AssetsRouter;
use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\Asset;
use Zend\ServiceManager\ServiceManager;

/**
 * @group      Zend_View
 */
class AssetsRouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsRouter
     */
    protected $router;

    public function setUp()
    {
        $this->router = new AssetsRouter();
        $this->router->setAssetsManager(new AssetsManager(new ServiceManager));
    }

    public function testMatch()
    {
        $this->assertEquals([
            'collection' => 'foo',
            'ns'         => 'bar',
            'source'     => 'css/baz.css',
        ], $this->router->match('/assets/collection-foo/ns-bar/css/baz.css'));

        $this->assertEquals([
            'collection' => null,
            'ns'         => 'bar',
            'source'     => 'css/baz.css',
        ], $this->router->match('/assets/ns-bar/css/baz.css'));

        $this->assertEquals([
            'collection' => 'foo',
            'ns'         => null,
            'source'     => 'css/baz.css',
        ], $this->router->match('/assets/collection-foo/css/baz.css'));

        $this->assertEquals([
            'collection' => null,
            'ns'         => null,
            'source'     => 'css/baz.css',
        ], $this->router->match('/assets/css/baz.css'));

        $this->assertNull($this->router->match('/other_path'));
    }

    public function testAssemble()
    {
        $this->assertEquals(
            'http://com.com/css/bar.css',
            $this->router->assemble(['source' => new Asset\Asset('http://com.com/css/bar.css')])
        );

        $this->assertEquals(
            '/css/bar.css',
            $this->router->assemble(['source' => new Asset\Asset('css/bar.css')])
        );

        $this->assertEquals(
            '/assets/ns-foo/css/bar.css',
            $this->router->assemble(['source' => new Asset\Asset('foo::css/bar.css')])
        );
        
        $collection = new Asset\AssetCollection([
            'name' => 'foo',
            'assets' => [
                'asset-1' => ['source' => 'css/bar.css'],
                'asset-2' => ['source' => 'bar::css/bat.css'],
            ],
        ]);

        $this->assertEquals(
            '/assets/collection-foo/css/bar.css',
            $this->router->assemble(['source' => $collection->getAsset('asset-1')])
        );

        $this->assertEquals(
            '/assets/collection-foo/ns-bar/css/bat.css',
            $this->router->assemble(['source' => $collection->getAsset('asset-2')])
        );
    }
}
