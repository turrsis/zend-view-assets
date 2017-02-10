<?php
/**
 * @link      http://github.com/zendframework/zend-form for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\View\Assets\Cache;

use Traversable;
use Zend\Cache\Storage\Adapter\AbstractAdapter;
use Zend\Stdlib\ErrorHandler;
use Zend\Cache\Exception;

/**
 * @method DefaultOptions getOptions()
 */
class DefaultAdapter extends AbstractAdapter
{
    protected $lastFileSpec;

    /**
     * Set options.
     *
     * @param  array|Traversable|DefaultOptions $options
     * @return AbstractAdapter
     * @see    getOptions()
     */
    public function setOptions($options)
    {
        if (! $options instanceof DefaultOptions) {
            $options = new DefaultOptions($options);
        }
        return parent::setOptions($options);
    }

    protected function internalSetItem(& $normalizedKey, & $value)
    {
        $filespec = $this->getFileSpec($normalizedKey);
        $this->prepareDirectoryStructure($filespec);
        $this->putFileContent($filespec, $value);
        return true;
    }

    protected function internalHasItem(& $normalizedKey)
    {
        $file = $this->getFileSpec($normalizedKey);
        if (! file_exists($file)) {
            return false;
        }

        $ttl = $this->getOptions()->getTtl();
        if ($ttl) {
            ErrorHandler::start();
            $mtime = filemtime($file);
            $error = ErrorHandler::stop();
            if (! $mtime) {
                throw new Exception\RuntimeException("Error getting mtime of file '{$file}'", 0, $error);
            }

            if (time() >= ($mtime + $ttl)) {
                return false;
            }
        }

        return true;
    }

    protected function internalGetItem(& $normalizedKey, & $success = null, & $casToken = null)
    {
        if (! $this->internalHasItem($normalizedKey)) {
            $success = false;
            return;
        }

        try {
            $filespec = $this->getFileSpec($normalizedKey);
            $data     = $this->getOptions()->isReturnResouce()
                    ? $this->getFileResource($filespec)
                    : $this->getFileContent($filespec);

            // use filemtime + filesize as CAS token
            if (func_num_args() > 2) {
                $casToken = filemtime($filespec) . filesize($filespec);
            }
            $success  = true;
            return $data;
        } catch (BaseException $e) {
            $success = false;
            throw $e;
        }
    }

    protected function internalRemoveItem(&$normalizedKey)
    {
        $filespec = $this->getFileSpec($normalizedKey);
        if (! file_exists($filespec)) {
            return false;
        } else {
            $this->unlink($filespec);
        }
        return true;
    }

    protected function getFileSpec($normalizedKey)
    {
        $fileSpec = $this->getOptions()->getCacheDir() . DIRECTORY_SEPARATOR . ltrim($normalizedKey, '\\/');
        if ($this->lastFileSpec !== $fileSpec) {
            $this->lastFileSpec = $fileSpec;
        }
        return $this->lastFileSpec;
    }

    protected function prepareDirectoryStructure($file)
    {
        $dir  = dirname($file);
        if (!file_exists($dir) && @mkdir($dir, 0777, true) === false) {
            throw new Exception\RuntimeException('can not create "'.$dir.'" directory for caching asset');
        }
    }

    protected function putFileContent($file, $data)
    {
        ErrorHandler::start();
        if (is_resource($data)) {
            fseek($data, 0);
        }
        $res = file_put_contents($file, $data);
        if (is_resource($data)) {
            fseek($data, 0);
        }
        if (!$res) {
            $err = ErrorHandler::stop();
            throw new Exception\RuntimeException("Error writing file '{$file}'", 0, $err);
        }
        ErrorHandler::stop();
    }

    protected function getFileContent($file)
    {
        return file_get_contents($file);
    }

    protected function getFileResource($file)
    {
        return fopen($file, 'r');
    }

    protected function unlink($file)
    {
        ErrorHandler::start();
        $res = unlink($file);
        $err = ErrorHandler::stop();

        // only throw exception if file still exists after deleting
        if (! $res && file_exists($file)) {
            throw new Exception\RuntimeException(
                "Error unlinking file '{$file}'; file still exists",
                0,
                $err
            );
        }
    }
}
