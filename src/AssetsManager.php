<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

use Zend\Stdlib\ArrayUtils;
use Zend\View\Exception;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Assets\Asset;

class AssetsManager
{
    protected $currentGroup = 'default';

    protected $assets = [];

    protected $filters = [];

    protected $publicFolder = '/public';

    /**
     * @var ResolverInterface
     */
    protected $mimeResolver;

    public function __construct(array $options = [])
    {
        if (isset($options['assets'])) {
            foreach ($options['assets'] as $group => $aliases) {
                foreach ($aliases as $alias => $aliasOptions) {
                    $this->set($alias, $aliasOptions, $group);
                }
            }
        }
        if (isset($options['filters'])) {
            foreach ($options['filters'] as $rule => $filters) {
                $this->setFilter($rule, $filters);
            }
        }
        if (isset($options['public_folder'])) {
            $this->setPublicFolder($options['public_folder']);
        }
    }

    /**
     * @param string $alias
     * @param string|array $assets
     * @return self
     * @throws \Exception\InvalidArgumentException
     */
    public function set($name, $asset, $group = null)
    {
        if (is_string($name) && !(is_string($asset) || is_array($asset) || $asset instanceof Asset\AbstractAsset)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$asset" parameter an %s, %s, %s or %s, received "%s"',
                __METHOD__,
                Asset::class,
                Assets::class,
                'string',
                'array',
                (is_object($asset) ? get_class($asset) : gettype($asset))
            ));
        }
        $group = $group ?: $this->currentGroup;
        if ($name instanceof Asset\AbstractAsset) {
            $name = $name->getName();
            $asset = $name;
        } else {
            $name = Asset\Asset::normalizeName($name);
        }
        $this->assets[$group][$name] = $asset;
        return $this;
    }

    /**
     * @param string $name
     * @return Asset
     */
    public function get($name)
    {
        $name = Asset\AbstractAsset::normalizeName($name);
        if (!isset($this->assets[$this->currentGroup][$name])) {
            return;
        }
        $asset = $this->assets[$this->currentGroup][$name];
        if (!$asset instanceof Asset\AbstractAsset) {
            $asset = Asset\AbstractAsset::factory($name, $asset, $this);
            $this->assets[$this->currentGroup][$name] = $asset;
        }
        return $asset;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        $name = Asset\AbstractAsset::normalizeName($name);
        return isset($this->assets[$this->currentGroup][$name]);
    }

    public function setFilter($rule, $filters)
    {
        $this->filters[$rule] = (array)$filters;
        return $this;
    }

    public function hasAssetFilters(Asset\Asset $asset)
    {
        if ($asset->hasFilters()) {
            return true;
        }

        $source = $asset->getName();
        foreach ($this->filters as $rule => $filters) {
            if (preg_match('(^' . $rule . '$)', $source)) {
                return true;
            }
        }
        return false;
    }

    public function getAssetFilters(Asset\Asset $asset)
    {
        $source = $asset->getName();
        $result = $asset->getFilters();
        foreach ($this->filters as $rule => $filters) {
            if (!preg_match('(^' . $rule . '$)', $source)) {
                continue;
            }
            $result = ArrayUtils::merge($filters, $result);
        }
        return $result;
    }

    /**
     * @param string $currentGroup
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setCurrentGroup($currentGroup)
    {
        if (!$currentGroup) {
            throw new Exception\InvalidArgumentException('Current group should be not empty string');
        }
        $this->currentGroup = strtolower($currentGroup);
        return $this;
    }

    /**
     * @return string
     */
    public function getCurrentGroup()
    {
        return $this->currentGroup;
    }

    /**
     * @param string $publicFolder
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setPublicFolder($publicFolder)
    {
        $publicFolder = trim(trim($publicFolder, '.'), '/');
        if (!$publicFolder) {
            throw new Exception\InvalidArgumentException('Public folder should be not empty string');
        }
        $this->publicFolder = './' . $publicFolder;
        return $this;
    }

    /**
     * @return string
     */
    public function getPublicFolder()
    {
        return $this->publicFolder;
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
        return $this->mimeResolver;
    }
}
