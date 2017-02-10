<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Filter;

use Zend\View\Assets\Asset\Asset;
use Zend\View\Assets\Asset\AbstractAsset;
use Zend\View\Assets\AssetsRouter;

class CssImports extends AbstractFilter
{
    /**
     * @var AssetsRouter
     */
    protected $assetsRouter;

    protected $patterns = [
        '/url\((["\']?)(?P<url>.*?)(\\1)\)/',
        '/@import (?!url\()(\'|"|)(?P<url>[^\'"\)\n\r]*)\1;?/',
        '/src=(["\']?)(?P<url>.*?)\\1/',
    ];

    public function filter($value, AbstractAsset $asset = null)
    {
        return preg_replace_callback($this->patterns, function ($matches) use ($asset) {
            return str_replace(
                $matches['url'],
                $this->filterUrl($matches['url'], $asset),
                $matches[0]
            );
        }, $value, -1);
    }

    protected function filterUrl($sourceUrl, Asset $asset)
    {
        if (stripos($sourceUrl, '/') !== 0) {
            return $sourceUrl; // relative or external path
        }

        return $this->assetsRouter->assemble([
            'collection' => $asset->getCollectionName(),
            'ns'         => $asset->getSourceNs(),
            'source'     => $sourceUrl
        ]);
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
