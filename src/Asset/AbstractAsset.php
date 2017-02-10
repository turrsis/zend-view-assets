<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

abstract class AbstractAsset
{
    const PREFIX_DELIMITER = '::';

    protected $name;

    protected $target;

    protected $attributes = [];

    protected $filters = [];

    public static function normalizeName($name)
    {
        $prefix = null;
        if (is_array($name)) {
            $prefix = $name[0];
            $name = $name[1];
        }

        $name = str_replace(self::PREFIX_DELIMITER . '/', self::PREFIX_DELIMITER, trim(str_replace('\\', '/', $name), '/'));
        if (!$prefix) {
            return $name;
        }
        return $prefix . self::PREFIX_DELIMITER . $name;
    }

    public static function factory($name, $options, $assetsManager = null)
    {
        if (is_string($options)) {
            $options = ['source' => $options];
        }
        if (isset($options['assets'])) {
            return new Alias($name, $options, $assetsManager);
        }

        return new Asset($name, $options);
    }

    public function __construct($name, $options)
    {
        $this->name = self::normalizeName($name);

        if (isset($options['target'])) {
            $this->target = self::normalizeName($options['target']);
        }

        if (isset($options['attributes'])) {
            $this->attributes = $options['attributes'];
        }
        if (isset($options['filters'])) {
            $this->filters = $options['filters'];
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getAttributes($key = null)
    {
        if ($key == null) {
            return $this->attributes;
        }
        return isset($this->attributes[$key])
            ? $this->attributes[$key]
            : null;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function hasFilters()
    {
        return !empty($this->filters);
    }
}
