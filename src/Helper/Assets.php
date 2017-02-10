<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Helper;

use Zend\View\Assets\AssetsManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\PriorityList;
use Zend\View\Exception;
use Zend\View\Assets\Asset;
use Zend\View\Helper\AbstractHelper;

/**
 * @method self add($asset, $options = [])
 * @method string render()
 */
class Assets extends AbstractHelper
{
    protected $basePath = '';

    protected $defaultGroup = 'default';

    protected $groups = [];

    protected $routeName;

    protected $urlHelper;

    protected $rendered = [];

    protected $EOL = "\n";

    protected $mimeRenderers = [
        'default'                => 'HeadLink',
        'application/javascript' => 'HeadScript',
    ];

    protected $mimeAttributes = [
        'text/css' => [
            'rel'   => 'stylesheet',
        ]
    ];

    /**
     * @var AssetsManager
     */
    protected $assetsManager;

    /**
     * @param type $method
     * @param type $arguments
     * @return self
     * @throws Exception\BadMethodCallException
     */
    public function __call($method, $arguments)
    {
        if (stripos($method, 'add') === 0) {
            $group = substr($method, 3) ?: $this->defaultGroup;
            $asset = $arguments[0];
            $options = isset($arguments[1]) ? $arguments[1] : [];
            $priority = isset($arguments[2]) ? $arguments[2] : 1;
            return $this->addAsset($group, $asset, $options, $priority);
        }

        if (stripos($method, 'render') === 0) {
            return $this->renderGroup(substr($method, 6) ?: $this->defaultGroup);
        }

        throw new Exception\BadMethodCallException('Method "' . $method . '" does not exist');
    }

    /**
     * @param string $group
     * @param string|array|Asset $asset
     * @param array $options
     * @param int $priority
     * @return self
     */
    protected function addAsset($group, $asset, $options = [], $priority = 1)
    {
        $group = strtolower($group);
        if (!isset($this->groups[$group])) {
            $list = new PriorityList();
            $list->isLIFO(false);
            $this->groups[$group] = $list;
        } else {
            $list = $this->groups[$group];
        }
        if (is_numeric($options)) {
            $priority = $options;
            $options = [];
        }
        $asset = Asset\AbstractAsset::normalizeName($asset);
        if ($list->get($asset) === null) {
            $list->insert($asset, $options, $priority);
        }
        return $this;
    }

    protected function renderGroup($group)
    {
        $group = strtolower($group);
        if (isset($this->rendered[$group])) {
            return '';
        }

        if (!isset($this->groups[$group])) {
            return '';
        }

        $result = '';
        foreach ($this->groups[$group] as $asset => $attributes) {
            if (!$this->assetsManager->has($asset)) {
                $asset = new Asset\Asset($asset);
            } else {
                $asset = $this->assetsManager->get($asset);
            }
            $result .= $this->renderAsset($asset, $attributes);
        }
        $this->rendered[$group] = true;
        return trim($result, $this->EOL) . $this->EOL;
    }

    protected function renderAsset(Asset\AbstractAsset $asset, $attributes = [])
    {
        if ($asset instanceof Asset\Asset) {
            return $this->renderLink(null, $asset, $attributes) . $this->EOL;
        }
        if (!$asset instanceof Asset\Alias) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$asset" parameter an %s or %s, received "%s"',
                __METHOD__,
                Asset\Asset::class,
                Asset\Alias::class,
                (is_object($asset) ? get_class($asset) : gettype($asset))
            ));
        }

        $return = '';
        $attributes = ArrayUtils::merge($asset->getAttributes(), $attributes);
        foreach ($asset as $assetItem) {
            if ($assetItem instanceof Asset\Alias) {
                $return .= $this->renderAsset($assetItem, $attributes) . $this->EOL;
            } else {
                $return .= $this->renderLink($asset->getName(), $assetItem, $attributes) . $this->EOL;
            }
        }
        return $return;
    }

    protected function renderLink($alias, Asset\Asset $asset, $attributes = [])
    {
        $href = $this->assemble($alias, $asset);

        if (isset($this->rendered[$href])) {
            return '';
        }
        $this->rendered[$href] = true;

        $mimeType = $asset->getAttributes('type') ?: $this->assetsManager->getMimeResolver()->resolve($asset->getTarget());

        $attributes = ArrayUtils::merge(
            ArrayUtils::merge($this->getMimeAttributes($mimeType), $asset->getAttributes()),
            $attributes
        );

        $renderer = $this->getMimeRenderer($mimeType);

        if ($mimeType == 'application/javascript') {
            $attributes['src'] = $href;
            return $renderer->itemToString((object)[
                'type'       => $mimeType,
                'attributes' => $attributes,
            ], null, null, null);
        }

        $attributes['href'] = $href;
        $attributes['type'] = $mimeType;

        if (isset($attributes['conditional'])) {
            $attributes['conditionalStylesheet'] = $attributes['conditional'];
            unset($attributes['conditional']);
        }

        return $renderer->itemToString((object)$attributes, '');
    }

    protected function assemble($alias, Asset\Asset $asset)
    {
        if ($asset->isExternal()) {
            return $asset->getTarget();
        }
        if (!$alias && !$asset->getPrefix()) {
            return $this->basePath . '/' . $asset->getTarget();
        }

        if (!$this->routeName) {
            throw new Exception\InvalidArgumentException('Using modules assets require the valid "routeName"');
        }

        if (!$this->urlHelper) {
            $this->urlHelper = $this->getView()->plugin('url');
        }

        $href = $this->urlHelper->__invoke($this->routeName, [
            'alias'  => $alias,
            'prefix' => $asset->getPrefix(),
            'asset'  => $asset->getTarget(),
        ], [
            'reuse_query'      => false,
            'only_return_path' => true,
        ]);
        return '/' . ltrim($href, '/');
    }

    public function getMimeRenderer($contentType)
    {
        if (!isset($this->mimeRenderers[$contentType])) {
            $contentType = 'default';
        }
        if (is_string($this->mimeRenderers[$contentType])) {
            $this->mimeRenderers[$contentType] = $this->getView()->plugin($this->mimeRenderers[$contentType]);
        }
        return $this->mimeRenderers[$contentType];
    }

    public function setMimeRenderer($contentType, $renderer)
    {
        $this->mimeRenderers[$contentType] = $renderer;
        return $this;
    }

    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
        return $this;
    }

    public function getRouteName()
    {
        return $this->routeName;
    }

    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    public function getBasePath()
    {
        return $this->basePath;
    }

    public function getMimeAttributes($mime = null)
    {
        if (!$mime) {
            return $this->mimeAttributes;
        }
        return isset($this->mimeAttributes[$mime])
                ? $this->mimeAttributes[$mime]
                : [];
    }

    public function setMimeAttributes($mime, $attributes = [])
    {
        if (is_array($mime)) {
            foreach ($mime as $type => $attributes) {
                $this->mimeAttributes[$type] = $attributes;
            }
            return $this;
        }
        $this->mimeAttributes[$mime] = $attributes;
        return $this;
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

    /**
     * @return AssetsManager
     */
    public function getAssetsManager()
    {
        return $this->assetsManager;
    }

    public function __toString()
    {
        return $this->renderGroup($this->defaultGroup);
    }
}
