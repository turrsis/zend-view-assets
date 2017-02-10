<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Asset;

use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\Asset\Alias;
use Zend\View\Assets\Asset\Asset;

/**
 * @group      Zend_View
 */
class AssetsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    public function setUp()
    {
        $this->assetsManager = new AssetsManager();
    }

    public function testConstructor()
    {
        // empty sources
        $alias = new Alias('foo', [], $this->assetsManager);
        $this->assertEquals(0, $alias->count());

        // string sources
        $alias = new Alias('foo', ['assets' => 'bar.css'], $this->assetsManager);
        $asset = $alias->get('bar.css');
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('bar.css', $asset->getName());

        // array sources
        $alias = new Alias('foo', ['assets' => ['bar.css', 'baz.css']], $this->assetsManager);
        $asset = $alias->get('bar.css');
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('bar.css', $asset->getName());

        $asset = $alias->get('baz.css');
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('baz.css', $asset->getName());

        // array with attributes
        $alias = new Alias('foo', ['assets' => [
            'bar.css' => [
                'attributes' => ['k' => 'v'],
            ],
        ]], $this->assetsManager);
        $asset = $alias->get('bar.css');
        $this->assertInstanceOf(Asset::class, $asset);
        $this->assertEquals('bar.css', $asset->getName());
        $this->assertEquals(['k' => 'v'], $asset->getAttributes());
    }

    public function testSet()
    {
        $alias = new Alias('foo', [], $this->assetsManager);
        $alias
            ->set('foo.css', [])
            ->set('bar.css', [
                    'attributes' => ['k' => 'v'],
            ]);
        $this->assertInstanceOf(Asset::class, $alias->get('foo.css'));
        $this->assertInstanceOf(Asset::class, $alias->get('bar.css'));
        $this->assertEquals(['k' => 'v'], $alias->get('bar.css')->getAttributes());
    }

    public function testHas()
    {
        $alias = new Alias('foo', [
            'assets' => ['/bar\baz.css'],
        ], $this->assetsManager);

        $this->assertTrue($alias->has('\bar\baz.css'));
        $this->assertTrue($alias->has('bar/baz.css'));
        $this->assertTrue($alias->has('/bar\baz.css'));
        $this->assertFalse($alias->has('notExist'));
    }

    public function testIterator()
    {
        $alias = new Alias('foo', [], $this->assetsManager);
        $alias
            ->set('bar.css', 'bar.less')
            ->set('bat.css', 'bat.less');

        $array = [];
        foreach ($alias as $k => $v) {
            $array[$k] = $v;
        }
        $this->assertEquals([
            'bar.css' => new Asset('bar.css', 'bar.less'),
            'bat.css' => new Asset('bat.css', 'bat.less'),
        ], $array);
    }
}
