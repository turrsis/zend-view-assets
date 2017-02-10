<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Filter;

use Traversable;
use Zend\View\Assets\Asset\AbstractAsset;
use Zend\Filter\AbstractFilter as BaseAbstractFilter;

abstract class AbstractFilter extends BaseAbstractFilter
{
    public function __construct($options = null)
    {
        if (is_array($options) || $options instanceof Traversable) {
            $this->setOptions($options);
        }
    }

    abstract public function filter($value, AbstractAsset $asset = null);

    public function __invoke($value, AbstractAsset $asset = null)
    {
        return $this->filter($value, $asset);
    }
}
