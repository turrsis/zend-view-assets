<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

trait TargetTrait
{
    protected $targetUri;

    protected $targetContent;

    public function getMimeType()
    {
        if (isset($this->attributes['type'])) {
            return $this->attributes['type'];
        }
        return;
    }

    public function setMimeType($mimeType)
    {
        $this->attributes['type'] = $mimeType;
        return $this;
    }

    public function getTargetUri()
    {
        return $this->targetUri;
    }

    public function setTargetUri($targetUri)
    {
        $this->targetUri = $targetUri;
        return $this;
    }

    public function setTargetContent($content)
    {
        $this->targetContent = $content;
    }

    public function getTargetContent()
    {
        return $this->targetContent;
    }
}
