<?php
namespace Sonic;
use Sonic\App, Sonic\Cache;

/**
 * CurlCached
 *
 * @category Sonic
 * @package Curl
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class CurlCached extends Curl
{
    /**
     * @var string
     */
    protected $_cache_key;

    /**
     * @var int
     */
    protected $_ttl;

    /**
     * constructor
     *
     * @param string $url
     * @param string $cache_key
     * @param int $ttl
     */
    public function __construct($url, $cache_key, $ttl = 7200)
    {
        $app = App::getInstance();

        if (!$app->extensionLoaded('Cache')) {
            throw new Exception('CurlCached depends on the Cache extension.  It is not currently loaded.');
        }

        parent::__construct($url);
        $this->_cache_key = $cache_key;
        $this->_ttl = Util::toSeconds($ttl);
    }

    /**
     * gets cached response
     *
     * @return string || false on failure
     */
    public function getResponse()
    {
        $cache = Cache::getCache();
        $result = $cache->get($this->_cache_key);
        if ($result !== false) {
            return $result;
        }

        $response = parent::getResponse();

        if (!$response) {
            return null;
        }

        $cache->set($this->_cache_key, $response, $this->_ttl);

        return $response;
    }
}
