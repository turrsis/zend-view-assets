<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Service;

use Zend\ServiceManager\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Resolver as ViewResolver;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Assets\Exception;

class AssetsResolverFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config');

        if (!isset($config['assets_manager'])) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "assets_manager" key in Config',
                __METHOD__
            ));
        }

        $publicFolder = isset($config['assets_manager']['public_folder'])
                ? $config['assets_manager']['public_folder']
                : './public';

        $resolverConfig = isset($config['assets_manager']['template_resolver'])
                ? $config['assets_manager']['template_resolver']
                : [];

        if (isset($resolverConfig['path_resolver']['script_paths'])) {
            $resolverConfig['path_resolver']['script_paths'] = ArrayUtils::merge(
                [$publicFolder],
                $resolverConfig['path_resolver']['script_paths']
            );
        } else {
            $resolverConfig['path_resolver']['script_paths'] = [
                $publicFolder,
            ];
        }

        $resolverConfig['prefix_resolver']['public::'] = $publicFolder;

        $resolver = new ViewResolver\AggregateResolver();
        foreach ($resolverConfig as $name => $options) {
            if (!$options) {
                continue;
            }
            switch ($name) {
                case 'prefix_resolver' :
                    $resolver->attach(new ViewResolver\PrefixPathStackResolver($options));
                    break;
                case 'map_resolver' :
                    $resolver->attach(new ViewResolver\TemplateMapResolver($options));
                    break;
                case 'path_resolver' :
                    $resolver->attach(new ViewResolver\TemplatePathStack($options));
                    break;
            }
        }
        return $resolver;
    }

    public function createService(ServiceLocatorInterface $serviceLocator, $rName = null, $cName = null)
    {
        return $this($serviceLocator, $cName);
    }
}
