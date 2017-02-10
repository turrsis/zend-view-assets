<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

use Zend\View\Assets\AssetsManager;

class Alias extends AbstractAsset implements \Iterator, \Countable
{
    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    protected $assets = [];

    public function __construct($name, $options, AssetsManager $assetsManager)
    {
        parent::__construct($name, $options);

        $this->assetsManager = $assetsManager;

        if (isset($options['assets'])) {
            if (is_string($options['assets'])) {
                $options['assets'] = [$options['assets']];
            }
            foreach ($options['assets'] as $sourceName => $sourceOptions) {
                if (is_numeric($sourceName)) {
                    $this->set($sourceOptions, []);
                } else {
                    $this->set($sourceName, $sourceOptions);
                }
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->assets[self::normalizeName($name)]);
    }

    /**
     * @param string $name
     * @return Asset|Alias
     */
    public function get($name)
    {
        $name = self::normalizeName($name);
        if (!isset($this->assets[$name])) {
            return;
        }
        return $this->resolveAsset($name, $this->assets[$name]);
    }

    /**
     * @param string $name
     * @param array $source
     * @return self
     */
    public function set($name, $source)
    {
        $name = self::normalizeName($name);
        $this->assets[$name] = $source;
        return $this;
    }

    /**
     * @param type $name
     * @param type $source
     * @return Asset
     */
    protected function resolveAsset($name, $source)
    {
        if ($source instanceof AbstractAsset) {
            return $source;
        }
        if ($this->assetsManager->has($name)) {
            $source = $this->assetsManager->get($name);
        } else {
            $source = new Asset($name, $source);
        }
        $this->assets[$name] = $source;
        return $source;
    }

    public function current()
    {
        return $this->resolveAsset(
            key($this->assets),
            current($this->assets)
        );
    }

    public function key()
    {
        return key($this->assets);
    }

    public function next()
    {
        $next = next($this->assets);
        if (!$next) {
            return false;
        }
        return $this->resolveAsset(
            key($this->assets),
            $next
        );
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
