<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Asset;

class Asset extends AbstractAsset
{
    protected $prefix;
    protected $source;
    protected $isExternal;

    public function __construct($name, $options = [])
    {
        if (is_string($options)) {
            $options = ['source' => $options];
        }
        parent::__construct($name, $options);

        if (false !== ($posName = stripos($this->name, self::PREFIX_DELIMITER))) {
            $this->prefix = substr($this->name, 0, $posName);
        }

        $this->source = isset($options['source'])
                ? self::normalizeName($options['source'])
                : $this->name;

        $this->target = $this->target ?: $this->name;
        if (false !== ($posTarget = stripos($this->target, self::PREFIX_DELIMITER))) {
            $this->target = substr($this->target, $posTarget + strlen(self::PREFIX_DELIMITER));
        }

        $this->isExternal = (stripos($this->target, 'http') === 0);
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function isExternal()
    {
        return $this->isExternal;
    }
}
