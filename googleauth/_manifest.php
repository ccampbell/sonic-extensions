<?php
namespace Sonic\Extension;

/**
 * GoogleAuth Manifest
 *
 * @category Sonic
 * @package Extension
 * @author Craig Campbell <iamcraigcampbell@gmail.com>
 */
class GoogleAuth extends Manifest
{
    const VERSION = "1.0";
    protected $_dependencies = array('Curl');
}
