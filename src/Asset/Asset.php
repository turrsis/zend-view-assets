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
use Zend\Stdlib\ErrorHandler;

class Asset extends AbstractAsset implements TargetInterface
{
    use TargetTrait;

    protected $source;

    protected $sourceNs;

    protected $sourceName;

    protected $sourceUri;

    protected $sourceContent;

    protected $isExternal;

    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->setSource($options);
        } else {
            $this->setOptions($options);
        }
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setSource($source)
    {
        $this->sourceNs = null;
        $this->sourceName = null;
        $this->isExternal = null;
        $this->source = self::normalizeName($source);
        
        $source = explode(self::NS_DELIMITER, $this->source, 2);
        if (count($source) == 1) {
            $this->sourceNs = null;
            $this->sourceName = $source[0];
        } else {
            $this->sourceNs = $source[0];
            $this->sourceName = $source[1];
        }
        return $this;
    }

    public function getSourceNs()
    {
        return $this->sourceNs;
    }

    public function getSourceName()
    {
        return $this->sourceName;
    }

    public function getSourceUri()
    {
        return $this->sourceUri;
    }

    public function setSourceUri($sourceUri)
    {
        $this->sourceUri = $sourceUri;
        return $this;
    }

    public function getSourceContent()
    {
        if ($this->sourceContent === null) {
            ErrorHandler::start();
            $this->sourceContent = file_get_contents($this->sourceUri);
            if ($err = ErrorHandler::stop()) {
                throw new Exception\RuntimeException(
                    "Error get source content: '$this->sourceUri'", 0, $err
                );
            }
        }
        return $this->sourceContent;
    }

    public function setSourceContent($content)
    {
        $this->sourceContent = $content;
        return $this;
    }

    public function getTargetContent($forceLoad = false)
    {
        if ($this->targetContent === null && $forceLoad) {
            $this->targetContent = $this->getSourceContent();
        }
        return $this->targetContent;
    }

    public function isExternal()
    {
        if ($this->isExternal === null) {
            $this->isExternal = (stripos($this->getSource(), 'http') === 0);
        }
        return $this->isExternal;
    }

    /**
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collection instanceof AssetCollection
                ? $this->collection->getName()
                : null;
    }
}
