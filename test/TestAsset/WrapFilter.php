<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\View\Assets\TestAsset;

use Zend\View\Assets\Filter\AbstractFilter;
use Zend\View\Assets\Asset\AbstractAsset;

class WrapFilter extends AbstractFilter
{
    protected $template = '=== %s ===';

    public function filter($value, AbstractAsset $asset = null)
    {
        return sprintf($this->template, $value);
    }
    
    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function getTemplate()
    {
        return $this->template;
    }
}
