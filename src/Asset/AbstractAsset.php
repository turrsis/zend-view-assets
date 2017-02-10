<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

use Traversable;
use Zend\View\Assets\Exception;

class AbstractAsset
{
    const NS_DELIMITER = '::';

    protected $options = [];
    
    protected $attributes = [];

    protected $filters = [];

    /**
     * @var AbstractCollection
     */
    protected $collection;

    public function __construct($options = [])
    {
        $this->setOptions($options);
    }

    public static function normalizeName($name)
    {
        if (is_array($name)) {
            $ns = $name[0];
            $name = $name[1];
        } else {
            $ns = null;
        }

        return ($ns ? $ns . self::NS_DELIMITER : '')
            . str_replace(self::NS_DELIMITER . '/', self::NS_DELIMITER, trim(str_replace('\\', '/', $name), '/'));
    }

    public function setOptions($options)
    {
        if (!is_array($options) && !$options instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($options) ? get_class($options) : gettype($options))
            ));
        }

        foreach ($options as $key => $value) {
            $setter = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $setter)) {
                $this->{$setter}($value);
            } else {
                $this->options[$key] = $value;
            }
        }
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
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

    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    public function setAttributes($attributes)
    {
        if ($attributes instanceof Traversable) {
            $this->attributes = iterator_to_array($attributes);
            return $this;
        }
        if (!is_array($attributes)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($attributes) ? get_class($attributes) : gettype($attributes))
            ));
        }
        $this->attributes = $attributes;
        return $this;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function setFilters($filters)
    {
        if ($filters instanceof Traversable) {
            $this->filters = iterator_to_array($filters);
            return $this;
        }
        if (!is_array($filters)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($filters) ? get_class($filters) : gettype($filters))
            ));
        }
        $this->filters = $filters;
        return $this;
    }

    public function hasFilters()
    {
        return !empty($this->filters);
    }

    /**
     * @return AbstractCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param AbstractCollection $collection
     * @return self
     */
    public function setCollection(AbstractCollection $collection)
    {
        $this->collection = $collection;
        return $this;
    }
}
