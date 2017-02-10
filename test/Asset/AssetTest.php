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
        $this->assetsManager = new AssetsManager();
    }

    public function testConstructor()
    {
        $asset = new Asset('foo.css', [
            'attributes' => ['foo' => 'bar'],
            'filters'    => ['baz'],
        ]);
        $this->assertEquals('foo.css', $asset->getName());
        $this->assertFalse($asset->isExternal());
        $this->assertNull($asset->getPrefix());
        $this->assertEquals(['foo' => 'bar'], $asset->getAttributes());
        $this->assertEquals(['baz'], $asset->getFilters());

        $asset = new Asset('http://foo.css');
        $this->assertEquals('http://foo.css', $asset->getName());
        $this->assertTrue($asset->isExternal());
        $this->assertNull($asset->getPrefix());

        $asset = new Asset('module::/foo.css');
        $this->assertEquals('module::foo.css', $asset->getName());
        $this->assertFalse($asset->isExternal());
        $this->assertEquals('module', $asset->getPrefix());
        $this->assertEquals('module::foo.css', $asset->getSource());
    }

    public function testSourceAndTarget()
    {
        $asset = new Asset('foo.css', [
            'source'    => 'foo.less',
        ]);
        $this->assertEquals('foo.css', $asset->getName());
        $this->assertEquals('', $asset->getPrefix());
        $this->assertEquals('foo.less', $asset->getSource());
        $this->assertEquals('foo.css', $asset->getTarget());

        $asset = new Asset('pre::foo.css', [
            'source'    => 'pre::foo.less',
        ]);
        $this->assertEquals('pre::foo.css', $asset->getName());
        $this->assertEquals('pre', $asset->getPrefix());
        $this->assertEquals('pre::foo.less', $asset->getSource());
        $this->assertEquals('foo.css', $asset->getTarget());

        $asset = new Asset('foo.css', [
            'source'    => 'pre::foo.less',
        ]);
        $this->assertEquals('foo.css', $asset->getName());
        $this->assertEquals('', $asset->getPrefix());
        $this->assertEquals('pre::foo.less', $asset->getSource());
        $this->assertEquals('foo.css', $asset->getTarget());

        $asset = new Asset('foo.css', 'pre::foo.less');
        $this->assertEquals('foo.css', $asset->getName());
        $this->assertEquals('', $asset->getPrefix());
        $this->assertEquals('pre::foo.less', $asset->getSource());
        $this->assertEquals('foo.css', $asset->getTarget());
    }
}
