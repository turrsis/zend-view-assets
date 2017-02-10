<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Filter\Service;

use Interop\Container\ContainerInterface;
use Zend\View\Assets\Filter\CssImports;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class CssImportsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $name
     * @param array $options
     * @return CssImports
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $cssImports = new CssImports();
        $cssImports->setAssetsRouter($container->get('AssetsRouter'));
        return $cssImports;
    }

    /**
     * @param ServiceLocatorInterface $container
     * @return CssImports
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, AssetsListener::class);
    }
}
