<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets;

use Zend\ServiceManager\ServiceManager;
use Zend\Http\PhpEnvironment\Request;
use Zend\View\Assets;
use Zend\View\Resolver as ViewResolver;
use Zend\View\Assets\Module;

class ModuleTest extends \PHPUnit_Framework_TestCase
{
    public function testGetServices()
    {
        $moduleConfig = (new Module)->getConfig();
        $serviceManager = new ServiceManager($moduleConfig['service_manager']);
        $serviceManager->setService('config', $moduleConfig);
        $serviceManager->setService('Request', new Request);

        $this->assertInstanceOf(Assets\AssetsManager::class, $serviceManager->get('AssetsManager'));
        $this->assertInstanceOf(Assets\AssetsRouter::class, $serviceManager->get('AssetsRouter'));
        $this->assertInstanceOf(Assets\MimeResolver::class, $serviceManager->get('MimeResolver'));
        $this->assertInstanceOf(ViewResolver\AggregateResolver::class, $serviceManager->get('ViewAssetsResolver'));
    }
}
