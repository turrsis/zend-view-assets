<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Asset;

use Zend\View\Assets\Asset\AbstractAsset;
use Zend\View\Assets\Exception;

/**
 * @group      Zend_View
 */
class AbstractAssetTest extends \PHPUnit_Framework_TestCase
{
    public function testNormalizeName()
    {
        $this->assertEquals(
            'foo/bar/baz.css',
            AbstractAsset::normalizeName('\foo/bar\baz.css')
        );
        $this->assertEquals(
            'http://foo/bar/baz.css',
            AbstractAsset::normalizeName('http://foo\bar\baz.css')
        );
        $this->assertEquals(
            'namespace::foo/bar/baz.css',
            AbstractAsset::normalizeName('namespace::/foo\bar\baz.css')
        );
        $this->assertEquals(
            'namespace::foo/bar/baz.css',
            AbstractAsset::normalizeName(['namespace', '\foo\bar/baz.css'])
        );
        $this->assertEquals(
            'foo/bar/baz.css',
            AbstractAsset::normalizeName([null, '\foo\bar/baz.css'])
        );
    }

    public function testSetOptions()
    {
        $asset = new AbstractAsset();
        $asset->setOptions([
            'attributes' => ['foo' => 'bar'],
            'baz' => 'bat',
        ]);
        $this->assertEquals(['baz' => 'bat'], $asset->getOptions());
        $this->assertEquals(['foo' => 'bar'], $asset->getAttributes());
    }

    public function testSetTraversableOptions()
    {
        $asset = new AbstractAsset();
        $asset->setOptions(new \ArrayObject([
            'attributes' => ['foo' => 'bar'],
            'baz' => 'bat',
        ]));
        $this->assertEquals(['baz' => 'bat'], $asset->getOptions());
        $this->assertEquals(['foo' => 'bar'], $asset->getAttributes());
    }

    public function testSetWrongOptions()
    {
        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            '"Zend\View\Assets\Asset\AbstractAsset::setOptions" expects an array or Traversable; received "stdClass"'
        );
        $asset = new AbstractAsset();
        $asset->setOptions(new \stdClass());
    }

    public function testSetAttributes()
    {
        $asset = new AbstractAsset();
        $asset->setAttributes(['foo' => 'bar']);
        $asset->setAttribute('baz', 'bat');
        $this->assertEquals([
            'foo' => 'bar',
            'baz' => 'bat',
        ], $asset->getAttributes());

        $this->assertEquals('bar', $asset->getAttributes('foo'));
        $this->assertEquals('bat', $asset->getAttributes('baz'));
    }

    public function testSetTraversableAttributes()
    {
        $asset = new AbstractAsset();
        $asset->setAttributes(new \ArrayObject(['foo' => 'bar']));
        $this->assertEquals(['foo' => 'bar'], $asset->getAttributes());
    }

    public function testSetWrongAttributes()
    {
        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            '"Zend\View\Assets\Asset\AbstractAsset::setAttributes" expects an array or Traversable; received "stdClass"'
        );
        $asset = new AbstractAsset();
        $asset->setAttributes(new \stdClass());
    }

    public function testSetFilters()
    {
        $asset = new AbstractAsset();
        $this->assertFalse($asset->hasFilters());
        $asset->setFilters(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar',], $asset->getFilters());
        $this->assertTrue($asset->hasFilters());
    }

    public function testSetTraversableFilters()
    {
        $asset = new AbstractAsset();
        $asset->setFilters(new \ArrayObject(['foo', 'bar']));
        $this->assertEquals(['foo', 'bar'], $asset->getFilters());
    }

    public function testSetWrongFilters()
    {
        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            '"Zend\View\Assets\Asset\AbstractAsset::setFilters" expects an array or Traversable; received "stdClass"'
        );
        $asset = new AbstractAsset();
        $asset->setFilters(new \stdClass());
    }
}
