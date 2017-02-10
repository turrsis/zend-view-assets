<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Filter;

use Zend\View\Assets\Asset\Asset;
use Zend\View\Assets\AssetsRouter;
use Zend\View\Assets\AssetsManager;
use Zend\View\Assets\Filter\FilterChain;
use Zend\View\Assets\AbstractRouter;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Assets\Exception;

class FilterChainTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FilterChain
     */
    protected $filter;

    public function setUp()
    {
        $this->filter = new FilterChain();
    }

    public function testFilter()
    {
        $this->filter->setFilters([
            [
                'callback' => function ($value, $asset = null) {
                    return 'a=' . $value . '=z';
                }
            ],
            [
                'name' => 'StringToUpper'
            ],
        ]);

        $this->assertEquals(2, $this->filter->count());

        $asset = new Asset([
            'source' => 'foo.css',
            'source_content' => 'content',
        ]);
        $this->assertEquals('A=CONTENT=Z', $this->filter->filter($asset));
    }

    public function testFilterWrongArgument()
    {
        $this->setExpectedException(
            Exception\InvalidArgumentException::class,
            'Zend\View\Assets\Filter\FilterChain::filter: '
                . 'expects "$asset" parameter an Zend\View\Assets\Asset\Asset, '
                . 'received "stdClass"'
        );
        $this->filter->filter(new \stdClass());
    }
}
