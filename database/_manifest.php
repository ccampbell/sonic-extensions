<?php
namespace Sonic\Extension;

/**
 * Database Manifest
 *
 * @category Sonic
 * @package Extension
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Database extends Manifest
{
    const VERSION = "1.0.1";
    protected $_has_config = true;
    protected $_config_defaults = array(
        'default_schema' => 'database_name',
        'database_name.user' => 'user',
        'database_name.password' => 'password',
        'database_name[]' => 'host=localhost;type=master'
    );
}
