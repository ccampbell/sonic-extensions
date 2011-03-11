<?php
namespace Sonic;
use Sonic\App;

/**
 * Session
 *
 * @category Sonic
 * @package Session
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Session
{
    /**
     * @var string
     */
    const MEMCACHE = 'memcache';

    /**
     * @var string
     */
    const MEMCACHED = 'memcached';

    /**
     * @var string
     */
    const FILES = 'files';

    /**
     * @var Session
     */
    protected static $_instance;

    /**
     * @var bool
     */
    protected $_started = false;

    /**
     * @var mixed
     */
    protected $_lifetime;

    /**
     * constructor
     *
     * @return void
     */
    private function __construct() {}

    /**
     * gets instance of Session class
     *
     * @return Session
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new Session();
        }
        return self::$_instance;
    }

    /**
     * sets session handler
     *
     * @param string $handler
     * @param array $servers
     * @return void
     */
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
            throw new \Exception('you must set at least one server if you are using ' . $handler . ' for session storage');
        }

        $this->setSavePath(implode(',', $servers));
    }

    /**
     * sets save path for the session
     *
     * @param string $path
     * @return mixed
     */
    public function setSavePath($path)
    {
        return ini_set('session.save_path', $path);
    }

    /**
     * sets the session name
     *
     * @param string $name
     * @return mixed
     */
    public function setName($name)
    {
        if ($this->_started) {
            return;
        }
        return session_name($name);
    }

    /**
     * sets session lifetime
     *
     * @param int $lifetime (accepts a string such as '2 weeks' - see Sonic\Util)
     * @return mixed
     */
    public function setLifetime($lifetime)
    {
        $this->_lifetime = $lifetime;
        App::getInstance()->includeFile('Sonic/Util.php');
        return ini_set('session.gc_maxlifetime', Util::toSeconds($lifetime));
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
        $this->_lifetime = $lifetime;
        App::getInstance()->includeFile('Sonic/Util.php');
        $lifetime = Util::toSeconds($lifetime);
        session_set_cookie_params($lifetime, $path, $domain, $secure, $http_only);
    }

    /**
     * starts the session
     *
     * @return void
     */
    public function start()
    {
        if ($this->_started) {
            return;
        }

        session_start();

        // if the session has a lifetime that is not the default php does not
        // reset the session cookie expiration on each request
        // this resets the cookie on one out of every 10 requests
        if ($this->_lifetime && mt_rand(1, 10) == 1) {
            $this->_setCookie($this->_lifetime);
        }

        $this->_started = true;
    }

    /**
     * gets a session variable by name
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $this->start();

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return null;
    }

    /**
     * sets a session variable
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    /**
     * gets the session id
     *
     * @return string
     */
    public function getId()
    {
        $this->start();
        return session_id();
    }

    /**
     * sets the session cookie
     *
     * @param mixed $expiration
     * @return void
     */
    protected function _setCookie($expiration)
    {
        App::getInstance()->includeFile('Sonic/Util.php');
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

    /**
     * deletes the session cookie
     *
     * @return void
     */
    protected function _eatCookie()
    {
        $params = session_get_cookie_params();
        setcookie(session_name(), null, 1, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    /**
     * destroys the current session
     *
     * @return void
     */
    public function destroy()
    {
        if (!$this->_started) {
            $this->start();
        }

        session_destroy();
        $this->_eatCookie();
    }
}
