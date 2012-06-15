<?php
/**
 * Info Plugin: Displays information about various DokuWiki internals without "start" page
 * Based on: nslist plugin by Andreas Gohr <andi@splitbrain.org>
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Zaher Dirkey <zaherdirkey@yahoo.com>
 */


if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
require_once(DOKU_INC.'inc/search.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_pglist extends DokuWiki_Syntax_Plugin {

  function getInfo(){
        return array(
            'author' => 'Zaher Dirkey',
            'email'  => 'zaherdirkey@yahoo.com',
            'date'   => '2012-06-4',
            'name'   => 'Page List Plugin',
            'desc'   => 'List pages of namespace based on nslist.',
            'url'    => 'http://dokuwiki.org/plugin:pglist',
        );
    }
    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'substition';
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 302;
    }


    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('{{pglist>[^}]*}}',$mode,'plugin_pglist');
    }


    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler){
        global $ID;
        $match = substr($match,9,-2); //strip {{pglist> from start and }} from end

        $conf = array(
            'ns'    => getNS($ID),
            'depth' => 1,
            'dirs' => 0,
            'files' => 0,
            'me' => 0,
            'nostart' => 0,
            'date'  => 0,
            'fsort' => 0,
            'dsort' => 0
        );

        list($ns,$params) = explode(' ',$match,2);
        $ns = cleanID($ns);

        if(preg_match('/\bdirs\b/i',$params)) $conf['dirs'] = 1;
        if(preg_match('/\bfiles\b/i',$params)) $conf['files'] = 1;
        if(preg_match('/\bme\b/i',$params)) $conf['me'] = 1;
        if(preg_match('/\bnostart\b/i',$params))  $conf['nostart'] = 1;
        if(preg_match('/\bdate\b/i',$params)) $conf['date'] = 1;
        if(preg_match('/\bfsort\b/i',$params)) $conf['fsort'] = 1;
        if(preg_match('/\bdsort\b/i',$params)) $conf['dsort'] = 1;
        if(preg_match('/\b(\d+)\b/i',$params,$m)) $conf['depth'] = $m[1];

        if ($ns) $conf['ns'] = $ns;

        $conf['dir'] = str_replace(':','/',$conf['ns']);

        // prepare data
        return $conf;
    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        global $conf;
        global $lang;
        global $ID;
        if($format != 'xhtml') return false;


        $opts = array(
            'depth'     => $data['depth'],
            'listfiles' => true,
            'listdirs'  => false,
            'pagesonly' => true,
            'firsthead' => true,
            'meta'      => true
        );

        if($data['dirs']) {
         	$opts['listdirs'] = true;
	        if ($data['files'])
	          $opts['listfiles'] = true;
          else
          	$opts['listfiles'] = false;
        }

        // read the directory
        $result = array();
        search($result,$conf['datadir'],'search_universal',$opts,$data['dir']);

        if($data['fsort']){
            usort($result,array($this,'_sort_file'));
        }elseif($data['dsort']){
            usort($result,array($this,'_sort_date'));
        }else{
            usort($result,array($this,'_sort_page'));
        }

        $R->listu_open();
        foreach($result as $item) {
        	$skip_it = false;
          if ($data['nostart'] and ($item['file'] == $conf['start'].'.txt'))
	        	$skip_it = true;

          if (!$data['me'] and ($item['id'] == $ID))
	        	$skip_it = true;

          if ($item['type']=='d') {
            $P = resolve_id($item['id'], $conf['start'], false);
            if(!page_exists($P))
		          $skip_it = true;
          } else
	          $P = ':'.$item['id'];

        	if (!$skip_it)
          {
            $R->listitem_open(1);
            $R->listcontent_open();
            $R->internallink($P);
            if($data['date'])
            	$R->cdata(' '.dformat($item['mtime']));
            $R->listcontent_close();
            $R->listitem_close();
          }
        }
        $R->listu_close();

        return true;
    }

    function _sort_page($a,$b){
			$r = strcmp($a['type'],$b['type']);
      if ($r<>0)
        return $r;
      else
        return strcmp($a['id'],$b['id']);
    }

    function _sort_file($a,$b){
			$r = strcmp($a['type'],$b['type']);
      if ($r<>0)
        return $r;
      else
        return strcmp($a['file'],$b['file']);
    }

    function _sort_date($a,$b) {
        if ($b['mtime'] < $a['mtime']) {
            return -1;
        } elseif($b['mtime'] > $a['mtime']) {
            return 1;
        } else {
            return strcmp($a['id'],$b['id']);
        }
    }

}

//Setup VIM: ex: et ts=4 enc=utf-8 :