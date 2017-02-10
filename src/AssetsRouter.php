<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

class AssetsRouter
{
    protected $basePath = '';

    protected $regex;

    protected $prefix = 'assets';

    protected $keyCollection = 'collection';
    protected $keyNs         = 'ns';
    protected $keySource     = 'source';

    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    public function assemble(array $params = [], array $options = [])
    {
        $collection = isset($params[$this->keyCollection])
                ? $params[$this->keyCollection]
                : null;
        $ns = isset($params[$this->keyNs])
                ? $params[$this->keyNs]
                : null;
        $source = isset($params[$this->keySource])
                ? $params[$this->keySource]
                : null;

        if ($source instanceof Asset\AggregateCollection) {
            return $this->assembleInternal($source->getName(), null, $source->getName(), false);
        }

        if ($source instanceof Asset\Asset) {
            if ($source->isExternal()) {
                return $source->getTargetUri()
                    ? $source->getTargetUri()
                    : $source->getSourceName();
            }

            $collection = $source->getCollectionName();
            $ns = $source->getSourceNs();
            $source = $source->getSourceName();
        }

        if (!is_string($source)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "source" parameter an %s, %s or %s, received "%s"',
                __METHOD__,
                Asset\AggregateCollection::class,
                Asset\AbstractAsset::class,
                'string',
                (is_object($source) ? get_class($source) : gettype($source))
            ));
        }

        $hasFilters = $this->assetsManager->hasFilters($source);
        return $this->assembleInternal($collection, $ns, $source, $hasFilters);
    }

    protected function assembleInternal($collection, $sourceNs, $sourceName, $hasFilters = false)
    {
        if (!$collection && !$sourceNs) {
            if (!$hasFilters) {
                return $this->basePath . '/' . ltrim($sourceName, '/');
            }
            $sourceNs = 'public';
        }
        $href = $this->basePath
                ? [trim($this->basePath, '/'), $this->prefix]
                : [$this->prefix];

        if ($collection) {
            $href[] = $this->keyCollection . '-' . $collection;
        }
        if ($sourceNs) {
            $href[] = $this->keyNs . '-' . $sourceNs;
        }
        if (!$sourceName) {
            throw new Exception\InvalidArgumentException('$sourceName can not be empty');
        }
        $href[] = ltrim($sourceName, '/');

        return '/' . implode('/', $href);
    }

    public function match($path, $pathOffset = null)
    {
        if (!$this->regex) {
            $this->regex = "/$this->prefix"
                . "(?:/$this->keyCollection\-(?P<$this->keyCollection>[a-zA-Z][.a-zA-Z0-9_-]*))?"
                . "(?:/$this->keyNs\-(?P<$this->keyNs>[a-zA-Z][a-zA-Z0-9_-]*))?/"
                . "(?P<$this->keySource>\S*)";
        }
        if ($pathOffset !== null) {
            $result = preg_match('(\G' . $this->regex . ')', $path, $matches, null, $pathOffset);
        } else {
            $result = preg_match('(^' . $this->regex . '$)', $path, $matches);
        }
        if (!$result) {
            return;
        }
        return [
            $this->keyCollection => $matches[$this->keyCollection] ?: null,
            $this->keyNs         => $matches[$this->keyNs] ?: null,
            $this->keySource     => $matches[$this->keySource] ?: null,
        ];
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param array $prefix
     * @return self
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param array $basePath
     * @return self
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return AssetsManager
     */
    public function getAssetsManager()
    {
        return $this->assetsManager;
    }

    /**
     * @param AssetsManager $assetsManager
     * @return self
     */
    public function setAssetsManager(AssetsManager $assetsManager)
    {
        $this->assetsManager = $assetsManager;
        return $this;
    }
}
