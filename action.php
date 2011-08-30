<?php
/**
* DokuMicroBugTracker Plugin: allows to create simple bugtracker
*
* @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @author     Benoît HERVIER <khertan@khertan.net>  
*/

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_dokumicrobugtracker extends DokuWiki_Action_Plugin {

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/INFO.txt');
    }

    /*
     * plugin should use this method to register its handlers with the dokuwiki's event controller
     */
    function register(&$controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'init_css');
    }

    /*
     * Add Js & Css after template is displayed
     */
    function init_css (&$event, $param)  {
       $event->data["link"][] = array ("type" => "text/css",
	  "src" => DOKU_BASE."lib/plugins/dokumicrobugtracker/style.css"
				          );
    }

}
