<?php
/**
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_statistics extends DokuWiki_Action_Plugin {

    /**
     * register the eventhandlers and initialize some options
     */
    function register(&$controller){

        $controller->register_hook('TPL_METAHEADER_OUTPUT',
                                   'BEFORE',
                                   $this,
                                   'handle_metaheaders',
                                   array());
        $controller->register_hook('IO_WIKIPAGE_WRITE',
                                   'BEFORE',
                                   $this,
                                   'logedits',
                                   array());
    }

    /**
     * Extend the meta headers
     */
    function handle_metaheaders(&$event, $param){
        global $ACT;
        global $ID;
        if($ACT != 'show') return; //only log page views for now

        $page = rawurlencode($ID);
        $data = "plugin_statistics.init('$page');";
        $event->data['script'][] = array( 'type'=>'text/javascript', 'charset'=>'utf-8', '_data'=>$data);
    }

    /**
     * @fixme call this in the webbug call
     */
    function putpixel(){
        global $ID;
        $url = DOKU_BASE.'lib/plugins/statistics/log.php?p='.rawurlencode($ID).
               '&amp;r='.rawurlencode($_SERVER['HTTP_REFERER']).'&rnd='.time();

        echo '<noscript><img src="'.$url.'" width="1" height="1" /></noscript>';
    }


    /**
     * Log edits and creates
     *
     * @fixme handle deletions
     */
    function logedits(&$event, $param){
        if($event->data[3]) return; // no revision

        if(file_exists($event->data[0][0])){
            if($event->data[0][1] == ''){
                $type = 'D';
            }else{
                $type = 'E';
            }
        }else{
            $type = 'C';
        }
        $hlp = plugin_load('helper','statistics');
        $hlp->Logger()->log_edit(cleanID($event->data[1].':'.$event->data[2]), $type);
    }
}

