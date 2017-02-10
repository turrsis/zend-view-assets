<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets;

use Zend\View\Resolver\ResolverInterface;
use Zend\View\Renderer\RendererInterface;
use Zend\Stdlib\ArrayUtils;

class MimeResolver implements ResolverInterface
{
    protected $defaultMimeType = 'text/plain';

    protected $mimeExtensions = [
        'text/css' => 'css',
    ];

    protected $mimeTypes = [
        'css'      => 'text/css',
        'less'     => 'text/css',
        'sass'     => 'text/css',
        'scss'     => 'text/css',
        'gif'      => 'image/gif',
        'htm'      => 'text/html',
        'html'     => 'text/html',
        'ico'      => 'image/x-icon',
        'jpe'      => 'image/jpeg',
        'jpeg'     => 'image/jpeg',
        'jpg'      => 'image/jpeg',
        'js'       => 'application/javascript',
        'json'     => 'application/json',
        'pdf'      => 'application/pdf',
        'png'      => 'image/png',
        'tif'      => 'image/tiff',
        'tiff'     => 'image/tiff',
        'txt'      => 'text/plain',
        'xml'      => 'application/xml',
        'xsd'      => 'application/xml',
        'xsl'      => 'application/xml',
    ];

    public function __construct($mimeTypes = [])
    {
        if ($mimeTypes) {
            $this->mimeTypes = ArrayUtils::merge($this->mimeTypes, $mimeTypes);
        }
    }

    public function resolve($name, RendererInterface $renderer = null)
    {
        $ext = explode('.', $name);
        $ext = end($ext);
        if (isset($this->mimeTypes[$ext])) {
            return $this->mimeTypes[$ext];
        }
        return $this->defaultMimeType;
    }

    public function resolveExtension($mimeType)
    {
        if (isset($this->mimeExtensions[$mimeType])) {
            return $this->mimeExtensions[$mimeType];
        }
        return;
    }

    public function resolveWebExtension($name)
    {
        $mimeType = $this->resolve($name);
        return $this->resolveExtension($mimeType);
    }

    public function setMimeType($extension, $type)
    {
        $this->mimeTypes[$extension] = $type;
        return $this;
    }

    public function setMimeExtension($type, $extension)
    {
        $this->mimeExtensions[$type] = $extension;
        return $this;
    }
}
