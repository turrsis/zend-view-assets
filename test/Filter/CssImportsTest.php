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
use Zend\View\Assets\Filter\CssImports;
use Zend\View\Assets\AbstractRouter;
use Zend\ServiceManager\ServiceManager;

class CssImportsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CssImports
     */
    protected $filter;

    /**
     * @var AbstractRouter
     */
    protected $router;

    public function setUp()
    {
        $this->router = new AssetsRouter;
        $this->router->setAssetsManager(new AssetsManager(new ServiceManager));
        $this->router->setPrefix('assets-cache');

        $this->filter = new CssImports();        
        $this->filter->setAssetsRouter($this->router);
    }

    public function testFilterUrl()
    {
        $asset = new Asset('module-1::/foo.css');
        $value = implode(PHP_EOL, [
            'foo { background : url(../img/pic1.png) ; color : red;}',
            'foo { background : url(/img/pic2.png) ; color : red;}',
            'foo { background : url(http://foo.com/img/pic3.png) ; color : red;}',
        ]);

        $actual = explode(PHP_EOL, $this->filter->filter($value, $asset));
        $this->assertEquals([
            'foo { background : url(../img/pic1.png) ; color : red;}',
            'foo { background : url(/assets-cache/ns-module-1/img/pic2.png) ; color : red;}',
            'foo { background : url(http://foo.com/img/pic3.png) ; color : red;}',
        ], $actual);
    }
}
