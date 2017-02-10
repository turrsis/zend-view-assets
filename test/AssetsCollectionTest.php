<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets;

use Zend\View\Assets\Asset;
use Zend\View\Assets\AssetsCollection;
use Zend\View\Assets\Exception;

class AssetsCollectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Asset\AssetsCollection
     */
    protected $сollection;

    public function setUp()
    {
        $this->сollection = new AssetsCollection;
    }

    public function testSetAssets()
    {
        $a5List = new Asset\AssetCollection();
        $this->сollection->setAssets([
            'a0.css' => [],                         // asset with same name and source
            'a1'     => ['source' => 'css/a1.css'], // asset
            'a2.css' => ['source' => 'a2.css'],     // asset with same name and source
            'a3'     => ['source' => 'p5::css/a1.css'], // asset with namespace
            'a4List' => ['assets' => [
                'a40.css' => [],
                'a2.css', // link to "a2"
                'a4Null', // link to not exists element
            ]],
            'a5List' => $a5List,
        ]);
        
        $a0Css = $this->сollection->getAsset('a0.css');
        $this->assertInstanceOf(Asset\Asset::class, $a0Css);
        $this->assertEquals('a0.css', $a0Css->getSource());
        $this->assertEquals('',       $a0Css->getSourceNs());
        $this->assertEquals('a0.css', $a0Css->getSourceName());
        
        $a1 = $this->сollection->getAsset('a1');
        $this->assertInstanceOf(Asset\Asset::class, $a1);
        $this->assertSame($a1, $this->сollection->getAsset('css/a1.css'));
        $this->assertEquals('css/a1.css', $a1->getSource());
        $this->assertEquals('',           $a1->getSourceNs());
        $this->assertEquals('css/a1.css', $a1->getSourceName());

        $a2 = $this->сollection->getAsset('a2.css');
        $this->assertInstanceOf(Asset\Asset::class, $a2);
        $this->assertEquals('a2.css', $a2->getSource());
        $this->assertEquals('',       $a2->getSourceNs());
        $this->assertEquals('a2.css', $a2->getSourceName());

        $a3 = $this->сollection->getAsset('a3');
        $this->assertInstanceOf(Asset\Asset::class, $a3);
        $this->assertEquals('p5::css/a1.css', $a3->getSource());
        $this->assertEquals('p5',             $a3->getSourceNs());
        $this->assertEquals('css/a1.css',     $a3->getSourceName());

        $a4List = $this->сollection->getAsset('a4List');
        $this->assertInstanceOf(Asset\AssetCollection::class, $a4List);
        
        $a4List_a40Css = $this->сollection->getAsset('a4List', 'a40.css');
        $this->assertInstanceOf(Asset\Asset::class, $a4List_a40Css);
        $this->assertEquals('a40.css', $a4List_a40Css->getSource());
        $this->assertEquals('',        $a4List_a40Css->getSourceNs());
        $this->assertEquals('a40.css', $a4List_a40Css->getSourceName());

        $a4List_a2Css = $this->сollection->getAsset('a4List', 'a2.css');
        $this->assertSame($a2, $a4List_a2Css);
        
        $a4List_a4Null = $this->сollection->getAsset('a4List', 'a4Null');
        $this->assertNull($a4List_a4Null);

        $this->assertSame($a5List, $this->сollection->getAsset('a5List'));
    }

    public function testSetAssetsLink()
    {
        $this->setExpectedException(Exception\InvalidArgumentException::class);
        $this->сollection->setAsset('link');
    }

    public function testIterator()
    {
        $aFoo = new Asset\Asset('foo.css');
        $aBar = new Asset\Asset('bar.css');
        $this->сollection->setAssets([
            $aFoo,
            'child' => ['assets' => [
                $aBar,
                'foo.css',
            ]],
        ]);

        $this->assertSame(['assets' => [
            'foo.css' => $aFoo,
            'child'   => $this->сollection->getAsset('child'),
        ]],
        $this->collectionToArray($this->сollection));

        $this->assertSame(['assets' => [
            'bar.css' => $aBar,
            'foo.css' => $aFoo,
        ]],
        $this->collectionToArray($this->сollection->getAsset('child')));
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
