<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

class AssetsCollection extends Asset\AbstractCollection
{
    /**
     * @param string $name
     * @return Asset\Asset
     */
    public function getAsset($aliasOrAssetName, $assetName = null)
    {
        if (!$aliasOrAssetName && $assetName) {
            $aliasOrAssetName = $assetName;
            $assetName = null;
        }
        $aliasOrAssetName = static::normalizeName($aliasOrAssetName);        
        if ($assetName) {
            $assetName = static::normalizeName($assetName);
        }
        
        $result = parent::getAsset($aliasOrAssetName);
        if ($result instanceof Asset\AggregateCollection && $result->getName() == $assetName && $result->getAggregate()) {
            return $result;
        }
        if ($result && $assetName) {
            return $result->getAsset($assetName);
        }
        return $result;
    }

    protected function factoryAsset($key, $asset)
    {
        if (is_array($asset) && isset($asset['assets'])) {
            if (isset($asset['aggregate'])) {
                $asset = new Asset\AggregateCollection($asset);
            } else {
                $asset = new Asset\AssetCollection($asset);
            }
            $this->assets[$key] = $asset;
        }
        if ($asset instanceof Asset\AssetCollection) {
            $asset->setName($key);
            $asset->setCollection($this);
            return $asset;
        }

        return parent::factoryAsset($key, $asset);
    }

    public function setAsset($key, $asset = null)
    {
        if (is_string($key) && $asset === null) { // link
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: do not accept links.',
                __METHOD__
            ));
        }

        if ($asset instanceof Asset\AbstractCollection || (is_array($asset) && isset($asset['assets']))) {
            $this->assets[static::normalizeName($key)] = $asset;
            return $this;
        }

        return parent::setAsset($key, $asset);
    }
}
