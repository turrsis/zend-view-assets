<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\Helper\Service;

use Zend\View\Assets\Helper\Service\AssetsFactory;
use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use Zend\View\Assets\Service;
use Zend\View\Assets\Helper\Assets;
use Zend\View\Assets\AssetsManager;

/**
 * @group      Zend_View
 */
class AssetsFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $config
     * @return ServiceManager
     */
    protected function getServiceManager($config)
    {
        $config = new Config($config);
        $serviceManager = new ServiceManager();
        $config->configureServiceManager($serviceManager);
        return $serviceManager;
    }

    public function testFactory()
    {
        $factory = new AssetsFactory();
        $helper = $factory($this->getServiceManager([
            'services' => [
                'config' => ['assets_manager' => [
                    'mime_attributes' => [
                        'text/css' => ['foo' => 'bar'],
                    ],
                ]],
            ],
            'factories'  => [
                'AssetsManager'       => Service\AssetsManagerFactory::class,
            ],
        ]), '');

        $this->assertInstanceOf(Assets::class, $helper);
        $this->assertInstanceOf(AssetsManager::class, $helper->getAssetsManager());
        $this->assertEquals(
            ['text/css' => ['foo' => 'bar']],
            $helper->getMimeAttributes()
        );
    }
}
