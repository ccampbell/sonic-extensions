<?php
namespace Sonic;

class Session
{
    const MEMCACHE = 'memcache';
    const MEMCACHED = 'memcached';
    const FILES = 'files';

    protected static $_instance;
    protected $_started = false;

    private function __construct() {}

    public function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new Session();
        }
        return self::$_instance;
    }

    public function setHandler($handler = self::FILES, $servers = array())
    {
        if ($handler == self::FILES) {
            return;
        }

        switch ($handler) {
            case self::MEMCACHE:
                ini_set('session.save_handler', self::MEMCACHE);
                break;
            case self::MEMCACHED:
                ini_set('session.save_handler', self::MEMCACHED);
                break;
        }

        if (count($servers) == 0) {
            throw new Exception('you must set at least one server if you are using ' . $handler . ' for session storage');
        }

        ini_set('session.save_path', implode(',', $servers));
    }

    public function setName($name)
    {
        if ($this->_started) {
            return;
        }
        session_name($name);
    }

    public function setLifetime($lifetime)
    {
        ini_set('session.gc_maxlifetime', Util::toSeconds($lifetime));
    }

    /**
     * sets cookie parameters
     *
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     * @param int $lifetime
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $http_only
     * @return void
     */
    public function setCookieParams($lifetime = 0, $path = '/', $domain = '', $secure = false, $http_only = false)
    {
        $lifetime = Util::toSeconds($lifetime);
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);
    }

    public function start()
    {
        if ($this->_started) {
            return;
        }

        session_start();

        if (mt_rand(1, 10) == 1) {
            $this->_setCookie('30 days');
        }

        $this->_started = true;
    }

    public function get($key)
    {
        $this->start();

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

    public function set($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function getId()
    {
        $this->start();
        return session_id();
    }

    protected function _setCookie($expiration)
    {
        $expiration = Util::toSeconds($expiration);
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            session_id(),
            time() + $expiration,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    protected function _eatCookie()
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), null, 1, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    public function destroy()
    {
        if (!$this->_started) {
            $this->start();
        }

        session_destroy();
        $this->_eatCookie();
    }
}
