<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

use Zend\View\Resolver\ResolverInterface;
use Zend\View\Assets\Asset;
use Zend\View\Assets\Exception;
use Zend\Cache\Storage\StorageInterface;
use Zend\Cache\StorageFactory;
use Zend\ServiceManager\PluginManagerInterface;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Assets\Filter\FilterChain;
use Interop\Container\ContainerInterface;

class AssetsManager
{
    protected $collectionName = 'default';

    /**
     * @var array
     */
    protected $collections;

    /**
     * @var array
     */
    protected $filters = [];

    /**
     * @var AssetsRouter
     */
    protected $assetsRouter;

    /**
     * @var ResolverInterface
     */
    protected $mimeResolver;

    /**
     * @var ResolverInterface
     */
    protected $assetsResolver;

    /**
     * @var array|StorageInterface
     */
    protected $cacheAdapter;

    /**
     * @var PluginManagerInterface
     */
    protected $filterManager;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container, array $options = [])
    {
        $this->container = $container;

        if (isset($options['collections'])) {
            foreach($options['collections'] as $collectionName => $collectionAssets) {
                $this->setCollection($collectionName, $collectionAssets);
            }
        }
        if (!$this->hasCollection($this->collectionName)) {
            $this->setCollection($this->collectionName, []);
        }
        if (isset($options['cache_adapter'])) {
            $this->setCacheAdapter($options['cache_adapter']);
        }
        if (isset($options['filters'])) {
            $this->filters = $options['filters'];
        }
    }

    /**
     * @param array $match
     * @param string $target
     * @return Asset\Asset
     * @throws Exception\NotFoundException
     */
    public function getPreparedAsset($alias, $ns, $name, $targetUri = null)
    {
        $asset = $this->getAsset($alias, $ns, $name, $targetUri);

        $cacheAdapter = $this->getCacheAdapter();
        if (!$cacheAdapter) {
            $this->filterAsset($asset);
            return $asset;
        }

        $cacheKey = $asset->getTargetUri();
        if ($cacheAdapter->hasItem($cacheKey)) {
            $cacheItem = $cacheAdapter->getItem($cacheKey);
            $asset->setTargetContent($cacheItem);
            return $asset;
        }

        $this->filterAsset($asset);
        $cacheItem = $asset->getTargetContent(true);
        $cacheAdapter->setItem($cacheKey, $cacheItem);

        return $asset;
    }

    /**
     * @param array|Asset\Asset $match
     * @return Asset\Asset
     */
    protected function getAsset($alias, $ns, $name)
    {
        $collection = $this->getCollection();
        $name  = [$ns, $name];
        $asset = $collection->getAsset($alias, $name);
        if (!$asset) {
            if ($alias) {
                $tmpCollection = $collection->getAsset($alias);
                if (!$tmpCollection) {
                    throw new Exception\NotFoundException(sprintf(
                        'collection "%s" not found',
                        $alias
                    ));
                }
            } else {
                $tmpCollection = $collection;
            }
            $asset = $tmpCollection->setAsset($name, [])->getAsset($name);
        }
        $this->initAsset($asset);
        return $asset;
    }

    public function initAsset($asset)
    {
        if ($asset instanceof Asset\Asset) {
            if (!$asset->getSourceUri()) {
                $asset->setSourceUri($this->getAssetsResolver()->resolve($asset->getSource()));
            }
            if (!$asset->getMimeType()) {
                $asset->setMimeType($this->getMimeResolver()->resolve($asset->getSource()));
            }
            if (!$asset->getTargetUri()) {
                $asset->setTargetUri($this->getAssetsRouter()->assemble(['source' => $asset]));
            }
        }
        if ($asset instanceof Asset\AggregateCollection) {
            if (!$asset->getMimeType()) {
                $asset->setMimeType($this->getMimeResolver()->resolve($asset->getName()));
            }
            if (!$asset->getTargetUri()) {
                $asset->setTargetUri($this->getAssetsRouter()->assemble(['source' => $asset]));
            }
        }
        return $this;
    }

    public function getFilters($asset = null)
    {
        if (!$asset) {
            return $this->filters;
        }
        if ($asset instanceof Asset\AbstractAsset) {
            $filters = $asset->getFilters();

            if ($asset->getCollection()) {
                $filters = ArrayUtils::merge(
                    $filters,
                    $asset->getCollection()->getFilters($asset)
                );
            }
            if ($asset->getCollection() != $this->getCollection()) {
                $filters = ArrayUtils::merge(
                    $filters, 
                    $this->getCollection()->getFilters($asset)
                );
            }
            $source = $asset->getSource();
        } elseif (is_string($asset)) {
            $filters = $this->getCollection()->getFilters($asset);
            $source = $asset;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$asset" parameter an %s or %s, received "%s"',
                __METHOD__,
                Asset\AbstractAsset::class,
                'string',
                (is_object($asset) ? get_class($asset) : gettype($asset))
            ));
        }
        
        foreach ($this->filters as $rule => $ruleFilters) {
            if ($rule == '*' || preg_match('(^' . $rule . '$)', $source)) {
                $filters = ArrayUtils::merge($filters, $ruleFilters);
            }
        }
        return $filters;
    }

    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param Asset\Asset $asset
     * @return boolean
     */
    public function hasFilters($asset = null)
    {
        if (!$asset) {
            return !empty($this->filters);
        }
        return !empty($this->getFilters($asset));
    }

    /**
     * @param Asset\AbstractAsset $asset
     * @return self
     */
    protected function filterAsset(Asset\AbstractAsset $asset)
    {
        if ($asset instanceof Asset\AggregateCollection) {
            return $this->filterCollection($asset);
        }

        $filters = $this->getFilters($asset);

        if (empty($filters)) {
            return;
        }

        $filter = (new FilterChain([
            'plugin_manager' => $this->getFilterManager(),
            'filters'        => $filters,
        ]));
        $result = $filter->filter($asset);
        $asset->setTargetContent($result);
        return $this;
    }

    protected function filterCollection(Asset\AggregateCollection $collection)
    {
        $result = '';
        foreach($collection as $key => $asset) {
            $this
                ->initAsset($asset)
                ->filterAsset($asset);
            $result .= $asset->getTargetContent(true) . "\n";
        }
        if ($result) {
            $collection->setTargetContent($result);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collectionName;
    }

    /**
     * @param string $collectionName
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setCollectionName($collectionName)
    {
        if (!$collectionName) {
            throw new Exception\InvalidArgumentException('Current group should be not empty string');
        }
        if (!isset($this->collections[$collectionName])) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Collection "%s" not exist',
                $collectionName
            ));
        }
        $this->collectionName = strtolower($collectionName);
        return $this;
    }

    /**
     * @param string $name
     * @param array|AssetsCollection $collection
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setCollection($name, $collection)
    {
        if (is_array($collection)) {
            $collection = new AssetsCollection($collection);
        }

        if (!$collection instanceof AssetsCollection) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$collection" parameter an %s or %s, received "%s"',
                __METHOD__,
                'array',
                AssetsCollection::class,
                (is_object($collection) ? get_class($collection) : gettype($collection))
            ));
        }
        $this->collections[strtolower($name)] = $collection;
        return $this;
    }
    
    /**
     * @param string $name
     * @return null|AssetsCollection
     */
    public function getCollection($name = null)
    {
        $name = strtolower($name ?: $this->collectionName);

        if (!isset($this->collections[$name])) {
            return;
        }
        return $this->collections[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCollection($name)
    {
        return isset($this->collections[strtolower($name)]);
    }

    /**
     * @param ResolverInterface $mimeResolver
     * @return self
     */
    public function setMimeResolver(ResolverInterface $mimeResolver)
    {
        $this->mimeResolver = $mimeResolver;
        return $this;
    }

    /**
     * @return ResolverInterface
     */
    public function getMimeResolver()
    {
        if (!$this->mimeResolver) {
            $this->mimeResolver = $this->container->get('MimeResolver');
        }
        return $this->mimeResolver;
    }

    /**
     * @return ResolverInterface
     */
    public function getAssetsResolver()
    {
        if (!$this->assetsResolver) {
            $this->assetsResolver = $this->container->get('ViewAssetsResolver');
        }
        return $this->assetsResolver;
    }

    /**
     * @param ResolverInterface $resolver
     * @return self
     */
    public function setAssetsResolver(ResolverInterface $resolver)
    {
        $this->assetsResolver = $resolver;
        return $this;
    }

    /**
     * @param array|StorageInterface $cacheAdapter
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setCacheAdapter($cacheAdapter)
    {
        if (!($cacheAdapter || is_array($cacheAdapter) || $cacheAdapter instanceof StorageInterface)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$cacheAdapter" parameter an %s, %s or %s, received "%s"',
                __METHOD__,
                'null',
                'array',
                StorageInterface::class,
                (is_object($cacheAdapter) ? get_class($cacheAdapter) : gettype($cacheAdapter))
            ));
        }
        $this->cacheAdapter = $cacheAdapter;
        return $this;
    }

    /**
     * @return StorageInterface
     */
    public function getCacheAdapter()
    {
        if (!$this->cacheAdapter) {
            return;
        }
        if (is_array($this->cacheAdapter)) {
            $this->cacheAdapter = StorageFactory::factory($this->cacheAdapter);
        }
        return $this->cacheAdapter;
    }

    /**
     * @return PluginManagerInterface
     */
    public function getFilterManager()
    {
        if (!$this->filterManager) {
            $this->filterManager = $this->container->get('FilterManager');
        }
        return $this->filterManager;
    }

    /**
     * @param PluginManagerInterface $filterManager
     * @return self
     */
    public function setFilterManager(PluginManagerInterface $filterManager)
    {
        $this->filterManager = $filterManager;
        return $this;
    }

    /**
     * @return AssetsRouter
     */
    public function getAssetsRouter()
    {
        if (!$this->assetsRouter) {
            $this->assetsRouter = $this->container->get('AssetsRouter');
        }
        return $this->assetsRouter;
    }

    /**
     * @param AssetsRouter $assetsRouter
     * @return self
     */
    public function setAssetsRouter(AssetsRouter $assetsRouter)
    {
        $this->assetsRouter = $assetsRouter;
        return $this;
    }
}
