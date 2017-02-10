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
use Zend\View\Assets\Exception;
use Zend\View\Assets\Asset;
use Zend\View\Helper\AbstractHelper;

/**
 * @method self add($asset, $options = [])
 * @method string render()
 */
class Assets extends AbstractHelper
{
    protected $defaultGroup = 'default';

    protected $groups = [];

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
        $asset = Asset\Asset::normalizeName($asset);
        if ($list->get($asset) === null) {
            $list->insert($asset, $options, $priority);
        }
        return $this;
    }

    protected function renderGroup($group)
    {
        $group = strtolower($group);
        if (isset($this->rendered[$group]) || !isset($this->groups[$group])) {
            return '';
        }

        $result = '';
        $collection = $this->assetsManager->getCollection();
        foreach ($this->groups[$group] as $assetName => $assetAttributes) {
            $asset = $collection->getAsset($assetName);
            if (!$asset) {
                $collection->setAsset($assetName, $assetAttributes);
                $asset = $collection->getAsset($assetName);
            }
            $this->assetsManager->initAsset($asset);
            if ($asset instanceof Asset\AssetCollection) {
                $result .= $this->renderCollection($asset, $assetAttributes);
            } else {
                $result .= $this->renderAsset($asset, $assetAttributes);
            }
        }
        $this->rendered[$group] = true;
        return trim($result, $this->EOL) . $this->EOL;
    }

    protected function renderCollection(Asset\AssetCollection $collection, $attributes = [])
    {
        if ($collection instanceof Asset\AggregateCollection) {
            return $this->renderLink(
                $collection->getTargetUri(),
                $attributes,
                $collection->getMimeType()
            ) . $this->EOL;
        }

        $result = '';
        foreach ($collection as $asset) {
            if (!$asset) {
                continue;
            }
            $this->assetsManager->initAsset($asset);
            if ($asset instanceof Asset\AssetCollection) {
                $result .= $this->renderCollection($asset, $attributes);
            } else {
                $result .= $this->renderAsset($asset, $attributes);
            }
        }
        return trim($result, $this->EOL) . $this->EOL;
    }

    protected function renderAsset(Asset\Asset $asset, $attributes = [])
    {
        $href = $asset->getTargetUri();
        if (isset($this->rendered[$href])) {
            return '';
        }
        $this->rendered[$href] = true;

        $mimeType = $asset->getMimeType();

        $attributes = ArrayUtils::merge(
            ArrayUtils::merge($this->getMimeAttributes($mimeType), $asset->getAttributes()),
            $attributes
        );

        return $this->renderLink($href, $attributes, $mimeType) . $this->EOL;
    }

    protected function renderLink($href, $attributes, $mimeType)
    {
        $renderer = $this->getMimeRenderer($mimeType);

        if ($mimeType == 'application/javascript') {
            $attributes['src'] = $href;
            return $renderer->itemToString((object)[
                'type'       => $mimeType,
                'attributes' => $attributes,
            ], null, null, null) . $this->EOL;
        }

        $attributes['href'] = $href;
        $attributes['type'] = $mimeType;

        if (isset($attributes['conditional'])) {
            $attributes['conditionalStylesheet'] = $attributes['conditional'];
            unset($attributes['conditional']);
        }

        return $renderer->itemToString((object)$attributes, '');
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
