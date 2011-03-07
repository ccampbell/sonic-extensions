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
    const VERSION = "1.0";
    protected $_has_config = true;
    protected $_config_defaults = array(
        'db.default_schema' => 'database_name',
        'db.database_name.user' => 'user',
        'db.database_name.password' => 'password',
        'db.database_name[]' => 'host=localhost;type=master'
    );
}
