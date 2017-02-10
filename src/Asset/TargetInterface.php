<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

interface TargetInterface
{
    public function getMimeType();

    public function setMimeType($mimeType);

    public function getTargetUri();

    public function setTargetUri($targetUri);

    public function setTargetContent($body);

    public function getTargetContent();
}
