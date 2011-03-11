<?php
namespace Nijikodo;
use Sonic\Extension, Sonic\App;

/**
 * Delegate
 *
 * @category Sonic
 * @package Nijikodo
 * @author Craig Campbell
 */
class Delegate extends Extension\Delegate
{
    protected $_language;
    protected $_height;

    public function extensionFinishedLoading()
    {
        App::getInstance()->includeFile('Nijikodo/Parser.php');

        $delegate = $this;
        $this->_addViewMethod('highlightStart', function($lang, $height = null) use ($delegate) {
            ob_start();
            $delegate->setLanguage($lang);
            $delegate->setHeight($height);
        });

        $this->_addViewMethod('highlightEnd', function() use ($delegate) {
            $code = ob_get_contents();
            ob_end_clean();
            echo \Nijikodo\Parser::toHtml($code, $delegate->getLanguage(), $delegate->getHeight());
        });
    }

    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    public function getLanguage()
    {
        return $this->_language;
    }

    public function setHeight($height)
    {
        $this->_height = $height;
    }

    public function getHeight()
    {
        return $this->_height;
    }
}
