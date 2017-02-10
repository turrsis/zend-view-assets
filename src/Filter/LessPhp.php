<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Filter;

use Zend\View\Assets\Asset\AbstractAsset;
use Zend\View\Assets\Exception;

class LessPhp extends AbstractFilter
{
    protected $compiler = 'lessc';
    
    protected $validExtentions = ['less'];

    public function filter($value, AbstractAsset $asset = null)
    {
        return $this->getCompiler()->compile($value);
    }

    protected function getCompiler()
    {
        if (is_string($this->compiler)) {
            if (!class_exists($this->compiler)) {
                throw new Exception\NotFoundException(sprintf(
                    'compiler class "%s" not found',
                    $this->compiler
                ));
            }
            $this->compiler = new $this->compiler;
        }
        return $this->compiler;
    }

    public function canFilter($file)
    {
        $ext = strripos($file, '.');
        if ($ext === false) {
            return false;
        }
        $ext = substr($file, -1 * $ext);
        return false !== array_search($ext, $this->validExtentions);
    }
}
