<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

use Zend\View\Assets\Exception;
use Zend\Stdlib\ArrayUtils;

class AbstractCollection extends AbstractAsset implements \Iterator, \Countable
{
    protected $assets = [];
    
    protected $aliases = [];

    public function setAssets($assets)
    {
        foreach($assets as $key => $options) {
            if (is_numeric($key)) {
                $this->setAsset($options);
                continue;
            }
            $this->setAsset($key, $options);
        }
        return $this;
    }

    public function setAsset($key, $asset = null)
    {
        if (is_string($key) && $asset === null) { // link
            $key = self::normalizeName($key);
            $this->assets[$key] = $key;
            return $this;
        }

        if ($key instanceof Asset) {
            $asset = $key;
            $key = $key->getSource();
        }
        $key = self::normalizeName($key);
        if ($asset instanceof Asset) {
            $asset->setCollection($this);
            $assetSource = $asset->getSource();
        } elseif (is_array($asset)) {
            $assetSource = isset($asset['source'])
                ? self::normalizeName($asset['source'])
                : $key;
            $asset['source'] = $assetSource;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$options" parameter an %s, %s or %s, received "%s"',
                __METHOD__,
                'null',
                'array',
                Asset::class,
                (is_object($asset) ? get_class($asset) : gettype($asset))
            ));
        }

        $this->assets[$key] = $asset;
        if ($assetSource && $key != $assetSource) {
            $this->aliases[$assetSource] = $key;
        }
        return $this;
    }

    public function getAsset($key)
    {
        $key = $this->resolveKeyAlias($key);
        if (!isset($this->assets[$key])) {
            return;
        }
        $asset = $this->assets[$key];
        if (!$asset instanceof AbstractAsset) {
            $asset = $this->factoryAsset($key, $asset);
        }
        return $asset;
    }

    protected function factoryAsset($key, $asset)
    {
        if (is_string($asset)) {
            return $this->collection ? $this->collection->getAsset($asset) : $asset;
        }
        if (is_array($asset)) {
            $asset = new Asset($asset);
            $this->assets[$key] = $asset->setCollection($this);
        }
        return $asset;
    }

    public function hasAsset($key)
    {
        return isset($this->assets[$this->resolveKeyAlias($key)]);
    }

    public function getFilters($asset = null)
    {
        if (!$asset) {
            return $this->filters;
        }
        if ($asset instanceof Asset) {
            $source = $asset->getSource();
        } elseif (is_string($asset)) {
            $source = $asset;
        } else {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$asset" parameter an %s or %s, received "%s"',
                __METHOD__,
                Asset::class,
                'string',
                (is_object($asset) ? get_class($asset) : gettype($asset))
            ));
        }
        $filters = [];
        foreach ($this->filters as $rule => $ruleFilters) {
            if ($rule == '*' || preg_match('(^' . $rule . '$)', $source)) {
                $filters = ArrayUtils::merge($filters, $ruleFilters);
            }
        }
        return $filters;
    }

    protected function resolveKeyAlias($key)
    {
        $key = self::normalizeName($key);
        return isset($this->aliases[$key]) ? $this->aliases[$key] : $key;
    }

    public function clear()
    {
        $this->assets = [];
        return $this;
    }

    public function current()
    {
        return $this->factoryAsset(key($this->assets), current($this->assets));
    }

    public function key()
    {
        return key($this->assets);
    }

    public function next()
    {
        next($this->assets);
    }

    public function rewind()
    {
        reset($this->assets);
    }

    public function valid()
    {
        return current($this->assets) !== false;
    }

    public function count()
    {
        return count($this->assets);
    }
}
