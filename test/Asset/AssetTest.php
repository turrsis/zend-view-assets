<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Asset;

use Zend\View\Assets\Asset\Asset;
use Zend\View\Assets\AssetsManager;
use Zend\ServiceManager\ServiceManager;

/**
 * @group      Zend_View
 */
class AssetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    public function setUp()
    {
        $this->assetsManager = new AssetsManager(new ServiceManager);
    }
    
    public function testConstructor()
    {
        $asset = new Asset([
            'source'     => 'foo.css',
            'attributes' => ['foo' => 'bar'],
            'filters'    => ['baz'],
        ]);
        $this->assertEquals('foo.css', $asset->getSource());
        $this->assertEquals(null,      $asset->getSourceNs());
        $this->assertEquals('foo.css', $asset->getSourceName());
        $this->assertFalse($asset->isExternal());
        $this->assertEquals(['foo' => 'bar'], $asset->getAttributes());
        $this->assertEquals(['baz'], $asset->getFilters());

        $asset = new Asset('http://foo.css');
        $this->assertEquals('http://foo.css', $asset->getSource());
        $this->assertEquals(null,             $asset->getSourceNs());
        $this->assertEquals('http://foo.css', $asset->getSourceName());
        $this->assertTrue($asset->isExternal());
        $this->assertEquals([], $asset->getAttributes());
        $this->assertEquals([], $asset->getFilters());

        $asset = new Asset('module::/foo.css');
        $this->assertEquals('module::foo.css', $asset->getSource());
        $this->assertEquals('module',          $asset->getSourceNs());
        $this->assertEquals('foo.css',         $asset->getSourceName());
        $this->assertFalse($asset->isExternal());
    }

    public function testSetGetSource()
    {
        $asset = new Asset([
            'source'    => 'foo.less',
        ]);
        $this->assertEquals('foo.less', $asset->getSource());
        $this->assertEquals('',         $asset->getSourceNs());
        $this->assertEquals('foo.less', $asset->getSourceName());

        $asset->setSource('pre::css/foo.css');
        $this->assertEquals('pre::css/foo.css', $asset->getSource());
        $this->assertEquals('pre',              $asset->getSourceNs());
        $this->assertEquals('css/foo.css',      $asset->getSourceName());
    }
}
