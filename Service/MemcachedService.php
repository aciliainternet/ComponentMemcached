<?php
namespace Acilia\Component\Memcached\Service;

use Memcached;
use DateTime;
use Exception;

class MemcachedService
{
    protected $instance;
    protected $enabled;

    public function __construct($servers, $environment, $debug = false, $prefix = 'acilia-cb', $enabled = true)
    {
        if (!class_exists('Memcached')) {
            $this->enabled = false;

            if ($debug == true) {
                throw new Exception('Class "Memcached" is not defined. Please verify that PHP Memcached Module is enabled!');
            }
        } else {
            $this->enabled = $enabled;
            $this->instance = new Memcached('storage_pool');

            $this->instance->setOption(Memcached::OPT_COMPRESSION, true);
            $this->instance->setOption(Memcached::OPT_PREFIX_KEY, $prefix . '-' . $environment);
            $this->instance->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
            $this->instance->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);
            $this->instance->setOption(Memcached::OPT_NO_BLOCK, true);

            $this->instance->addServers($servers);
        }
    }

    public function add($key, $value, $expiration = 0)
    {
        if ($this->enabled === false) {
            return false;
        }

        return $this->instance->add($key, $value, $this->calculateTime($expiration));
    }

    public function set($key, $value, $expiration = 0)
    {
        if ($this->enabled === false) {
            return false;
        }

        return $this->instance->set($key, $value, $this->calculateTime($expiration));
    }

    public function get($key)
    {
        if ($this->enabled === false) {
            return null;
        }

        return $this->instance->get($key);
    }

    public function increment($key, $offset = 1)
    {
        if ($this->enabled === false) {
            return false;
        }

        $this->instance->add($key, 0, 0);
        return $this->instance->increment($key, $offset);
    }

    public function notFound()
    {
        if ($this->enabled === false) {
            return true;
        }

        return ($this->instance->getResultCode() !== Memcached::RES_SUCCESS);
    }

    public function delete($key)
    {
        if ($this->enabled === false) {
            return false;
        }

        return $this->instance->delete($key, 0);
    }

    protected function calculateTime($expiration = 0)
    {
        if ($expiration == 0) {
            return 0;
        } elseif (($expiration * 60) >= 2592000) {
            return 0;
        }

        return ($expiration * 60);
    }
}
