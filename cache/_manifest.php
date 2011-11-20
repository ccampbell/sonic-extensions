<?php
namespace Sonic\Extension;

/**
 * Cache Manifest
 *
 * @category Sonic
 * @package Extension
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Cache extends Manifest
{
    const VERSION = "1.1.1";
    protected $_has_config = true;
    protected $_config_defaults = array(
        'enabled' => 1,
        'cache.default[]' => "127.0.0.1:11211"
    );
}
