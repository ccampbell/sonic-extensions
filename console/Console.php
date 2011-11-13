<?php
namespace Sonic;
use \FB, \ChromePhp;

/**
 * Console
 *
 * @category Sonic
 * @package Console
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Console
{
    /**
     * @var string
     */
    const CHROMEPHP = 'chromephp';

    /**
     * @var string
     */
    const FIREPHP = 'firephp';

    /**
     * @var string
     */
    const NOTHING = 'nothing';

    /**
     * @var string
     */
    const LOG = 'log';

    /**
     * @var string
     */
    const ERROR = 'error';

    /**
     * @var string
     */
    const WARN = 'warn';

    /**
     * @var string
     */
    const INFO = 'info';

    /**
     * @var bool
     */
    protected static $_use = false;

    /**
     * initializes logging
     *
     * @return void
     */
    protected static function _init()
    {
        if (self::$_use) {
            return;
        }

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false) {
            self::$_use = self::FIREPHP;
            return;
        }

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) {
            self::$_use = self::CHROMEPHP;
            ChromePhp::getInstance()->addSetting(ChromePhp::BACKTRACE_LEVEL, 2);
            return;
        }

        self::$_use = self::NOTHING;
    }

    /**
     * logs data
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    protected static function _log($key, $value = null, $type = self::LOG)
    {
        self::_init();

        if ($value === null) {
            $value = $key;
            $key = null;
        }

        switch (self::$_use) {
            case self::FIREPHP:
                return FB::$type($value, $key);
                break;
            case self::CHROMEPHP:
                return ChromePhp::$type($key, $value);
                break;
        }
    }

    /**
     * log
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public static function log($key, $value = null)
    {
        return self::_log($key, $value);
    }

    /**
     * warn
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public static function warn($key, $value = null)
    {
        return self::_log($key, $value, self::WARN);
    }

    /**
     * error
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public static function error($key, $value = null)
    {
        return self::_log($key, $value, self::ERROR);
    }

    /**
     * info
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public static function info($key, $value = null)
    {
        return self::_log($key, $value, self::INFO);
    }
}
