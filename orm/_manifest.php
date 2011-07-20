<?php
namespace Sonic\Extension;

/**
 * ORM Manifest
 *
 * @category Sonic
 * @package Extension
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class Orm extends Manifest
{
    const VERSION = "1.0.4";
    protected $_dependencies = array('Database', 'Cache');
    protected $_keep_on_upgrade = array(
        'configs/definitions.php'
    );
    protected $_instructions = "Check out http://sonicframework.com/tutorial/orm for help getting started";
}
