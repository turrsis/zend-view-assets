<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Asset;

use Zend\View\Assets\Asset;
use Zend\View\Assets\Exception;

class AbstractCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Asset\AbstractCollection
     */
    protected $сollection;

    public function setUp()
    {
        $this->сollection = new Asset\AbstractCollection();
    }

    public function testSetAssets()
    {
        $fooCss = new Asset\Asset([
            'source' => 'foo.less',
        ]);
        $this->сollection->setAssets([
            'foo.css' => $fooCss, // asset
            'bar.css' => [], // asset
            'baz.css' => ['source' => 'baz.less'],  // asset with source
            'ns::bat.css' => ['source' => 'ns::bat.less'],
            'link', // link
        ]);

        $this->assertEquals(5, $this->сollection->count());

        $this->assertSame($fooCss, $this->сollection->getAsset('foo.css'));
        $this->assertSame($fooCss, $this->сollection->getAsset($fooCss->getSource()));
        $this->assertSame($this->сollection, $fooCss->getCollection());
        $this->assertEquals('foo.less', $fooCss->getSource());

        $barCss = $this->сollection->getAsset('bar.css');
        $this->assertInstanceOf(Asset\Asset::class, $barCss);
        $this->assertSame($this->сollection, $barCss->getCollection());
        $this->assertEquals('bar.css', $barCss->getSource());

        $bazCss = $this->сollection->getAsset('baz.css');
        $this->assertInstanceOf(Asset\Asset::class, $bazCss);
        $this->assertSame($bazCss, $this->сollection->getAsset($bazCss->getSource()));
        $this->assertSame($this->сollection, $bazCss->getCollection());
        $this->assertEquals('baz.less', $bazCss->getSource());

        $nsBatCss = $this->сollection->getAsset('ns::bat.css');
        $this->assertInstanceOf(Asset\Asset::class, $nsBatCss);
        $this->assertSame($this->сollection, $nsBatCss->getCollection());
        $this->assertEquals('ns::bat.less', $nsBatCss->getSource());
        $this->assertEquals('ns', $nsBatCss->getSourceNs());
        $this->assertEquals('bat.less', $nsBatCss->getSourceName());

        $link = $this->сollection->getAsset('link');
        $this->assertEquals('link', $link);
    }

    public function testSetAsset()
    {
        $this->сollection->clear()->setAsset('foo.css', new Asset\Asset(['source' => 'foo.less']));
        $this->assertEquals(
            'foo.less',
            $this->сollection->getAsset('foo.css')->getSource()
        );

        $this->сollection->clear()->setAsset(new Asset\Asset(['source' => 'bar.less']));
        $this->assertEquals(
            'bar.less',
            $this->сollection->getAsset('bar.less')->getSource()
        );

        $this->сollection->clear()->setAsset('bat.css', []);
        $this->assertEquals(
            'bat.css',
            $this->сollection->getAsset('bat.css')->getSource()
        );

        $this->сollection->clear()->setAsset('baz.css', ['source' => 'baz.less']);
        $this->assertEquals(
            'baz.less',
            $this->сollection->getAsset('baz.css')->getSource()
        );
        
        $this->сollection->clear()->setAsset('link.css');
        $this->assertEquals(
            'link.css',
            $this->сollection->getAsset('link.css')
        );
    }

    public function testHasAsset()
    {
        $this->сollection->setAssets([
            'baz.css' => ['source' => 'baz.less']
        ]);
        $this->assertTrue($this->сollection->hasAsset('baz.css'));
        $this->assertTrue($this->сollection->hasAsset('baz.less'));
        $this->assertFalse($this->сollection->hasAsset('foo.css'));
    }

    public function testSetWrongAsset()
    {
        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            'Zend\View\Assets\Asset\AbstractCollection::setAsset: '
                . 'expects "$options" parameter an null, '
                . 'array or Zend\View\Assets\Asset\Asset, received "stdClass"'
        );
        $this->сollection->setAsset('foo', new \stdClass());
    }

    public function testIterator()
    {
        $aFoo = new Asset\Asset('foo.css');
        $aBar = new Asset\Asset('bar.css');
        $this->сollection->setAssets([
            $aFoo,
            $aBar,
        ]);

        $this->assertSame(['assets' => [
            'foo.css' => $aFoo,
            'bar.css' => $aBar,
        ]],
        $this->collectionToArray($this->сollection));
    }

    protected function collectionToArray($collection)
    {
        $result = [];
        foreach($collection as $name => $item) {
            $result[$name] = $item;
        }
        return [
            'assets' => $result
        ];
    }
}
