<?php
namespace Sonic;

/**
 * Cookie
 *
 * @category Sonic
 * @package Cookie
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Cookie
{
    /**
     * @var array
     */
    protected static $_cookies = array();

    /**
     * @var array
     */
    protected static $_deleted_cookies = array();

    /**
     * sets a cookie
     *
     * @param string $name
     * @param string $value
     * @param mixed $ttl
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $http_only
     * @return bool
     */
    public static function set($name, $value, $ttl = '1 day', $path = '/', $domain = '', $secure = false, $http_only = false)
    {
        self::$_cookies[$name] = $value;
        $result = setcookie($name, $value, time() + Util::toSeconds($ttl), $path, $domain, $secure, $http_only);

        if (isset(self::$_deleted_cookies[$name])) {
            unset(self::$_deleted_cookies[$name]);
        }

        return $result;
    }

    /**
     * gets a cookie
     *
     * @param string $name
     * @return mixed
     */
    public static function get($name)
    {
        if (isset(self::$_deleted_cookies[$name])) {
            return null;
        }

        if (array_key_exists($name, self::$_cookies)) {
            return self::$_cookies[$name];
        }

        $value = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
        self::$_cookies[$name] = $value;

        return $value;
    }

    /**
     * deletes a cookie
     *
     * @param string $name
     * @return bool
     */
    public static function eat($name, $path = '/', $domain = '', $secure = false, $http_only = false)
    {
        $success = self::set($name, null, -10, $path, $domain, $secure, $http_only);
        self::$_deleted_cookies[$name] = $name;

        if (isset(self::$_cookies[$name])) {
            unset(self::$_cookies[$name]);
        }

        return $success;
    }

    /**
     * gets a cookie and eats it
     *
     * @param string $name
     * @return mixed
     */
    public static function getAndEat($name, $path = '/', $domain = '', $secure = false, $http_only = false)
    {
        $value = self::get($name);
        self::eat($name, $path, $domain, $secure, $http_only);

        return $value;
    }
}
