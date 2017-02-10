<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Filter;

use Countable;
use Zend\Filter\AbstractFilter;
use Zend\Stdlib\PriorityQueue;
use Zend\Filter\FilterPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\View\Assets\Asset;
use Zend\View\Assets\Exception;

class FilterChain extends AbstractFilter implements Countable
{
    protected $defaultPriority = 1000;

    /**
     * @var FilterPluginManager
     */
    protected $plugins;

    /**
     * @var PriorityQueue
     */
    protected $filters;

    public function __construct($options = null)
    {
        $this->filters = new PriorityQueue();

        if (null !== $options) {
            $this->setOptions($options);
        }        
    }

    public function setFilters($filters)
    {
        foreach ($filters as $key => $value) {
            $this->setFilter($value);
        }
        return $this;
    }

    public function setFilter($spec, $priority = null)
    {
        $priority = isset($spec['priority'])
                ? $spec['priority']
                : $priority ?: $this->defaultPriority;
        $this->filters->insert($spec, $priority);
        return $this;
    }

    protected function resolveFilter($filter)
    {
        if (isset($filter['callback'])) {
            $filter = $filter['callback'];
        } elseif (isset($filter['name'])) {
            $filter = $this->getPluginManager()->get(
                $filter['name'],
                isset($filter['options']) ? $filter['options'] : []
            );
        }
        return $filter;
    }

    public function filter($asset)
    {
        if (!$asset instanceof Asset\Asset) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s: expects "$asset" parameter an %s, received "%s"',
                __METHOD__,
                Asset\Asset::class,
                (is_object($asset) ? get_class($asset) : gettype($asset))
            ));
        }
        $valueFiltered = $asset->getSourceContent();

        foreach ($this->filters as $filter) {
            $filter = $this->resolveFilter($filter);
            $valueFiltered = call_user_func($filter, $valueFiltered, $asset);
        }

        return $valueFiltered;
    }

    /**
     * @return FilterPluginManager
     */
    public function getPluginManager()
    {
        if (!$this->plugins) {
            $this->setPluginManager(new FilterPluginManager(new ServiceManager()));
        }
        return $this->plugins;
    }

    /**
     * @param  FilterPluginManager $plugins
     * @return self
     */
    public function setPluginManager(FilterPluginManager $plugins)
    {
        $this->plugins = $plugins;
        return $this;
    }

    public function count()
    {
        return $this->filters->count();
    }
}
