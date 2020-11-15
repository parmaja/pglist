<?php
/**
 * DokuWiki Plugin pglist (Action Component)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Zaher Dirkey <zaherdirkey@yahoo.com>
 */
 
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_pglist extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook("TOOLBAR_DEFINE", "AFTER", $this, "insert_button", array ());
    }

    /**
    * Inserts a toolbar button
    */
    public function insert_button(&$event, $param) {
        $event->data[] = array (
            'type' => 'insert',
            'title' => 'PGList Plugin',
            'icon' => '../../plugins/pglist/images/pglist.png',
            'insert' => '{{pglist>selected_namespace files dirs me nostart fsort dsort}}'
        );
    }
}
