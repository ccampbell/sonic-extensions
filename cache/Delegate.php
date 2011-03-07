<?php
namespace Sonic\Cache;
use Sonic\Extension, Sonic\App;

/**
 * Delegate
 *
 * @category Sonic
 * @package Cache
 * @author Craig Campbell
 */
class Delegate extends Extension\Delegate
{
    public function extensionFinishedLoading()
    {
        $this->_addStaticAppMethod('getMemcache', function($pool = 'default') {
            return Factory::getMemcache($pool);
        });

        $this->_addStaticAppMethod('getFileCache', function($path = 'caches') {
            $path = App::getInstance()->getPath($path);
            return new File($path);
        });
    }
}
