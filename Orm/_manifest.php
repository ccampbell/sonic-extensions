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
    const VERSION = "1.0.1";
    protected $_dependencies = array('Cache');
    protected $_instructions = "Check out http://sonicframework.com/tutorial/orm for help getting started";
}
