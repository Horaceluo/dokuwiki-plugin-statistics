<?php
/**
 * statistics plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'admin.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_statistics extends DokuWiki_Admin_Plugin {
    var $dblink = null;
    var $opt    = '';
    var $from   = '';
    var $to     = '';
    var $start  = '';
    var $tlimit = '';

    /**
     * return some info
     */
    function getInfo(){
        return confToHash(dirname(__FILE__).'/info.txt');
    }

    /**
     * Access for managers allowed
     */
    function forAdminOnly(){
        return false;
    }

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 150;
    }

    /**
     * handle user request
     */
    function handle() {
        $this->opt = preg_replace('/[^a-z]+/','',$_REQUEST['opt']);

        $this->start = (int) $_REQUEST['s'];

        // fixme add better sanity checking here:
        $this->from = preg_replace('/[^\d\-]+/','',$_REQUEST['f']);
        $this->to = preg_replace('/[^\d\-]+/','',$_REQUEST['t']);
        if(!$this->from) $this->from = date('Y-m-d');
        if(!$this->to) $this->to     = date('Y-m-d');

        //setup limit clause
        if($this->from != $this->to){
            $this->tlimit = "DATE(A.dt) >= DATE('".$this->from."') AND DATE(A.dt) <= DATE('".$this->to."')";
        }else{
            $this->tlimit = "DATE(A.dt) = DATE('".$this->from."')";
        }
    }

    /**
     * fixme build statistics here
     */
    function html() {
        $this->html_toc();
        echo '<h1>Access Statistics</h1>';
        $this->html_timeselect();

        switch($this->opt){
            case 'country':
                $this->html_country();
                break;
            case 'page':
                $this->html_page();
                break;
            case 'browser':
                $this->html_browser();
                break;
            case 'os':
                $this->html_os();
                break;
            case 'referer':
                $this->html_referer();
                break;
            case 'newreferer':
                $this->html_newreferer();
                break;
            default:
                $this->html_dashboard();
        }
    }

    function html_toc(){
        echo '<div class="toc">';
        echo '<div class="tocheader toctoggle" id="toc__header">';
        echo 'Detailed Statistics';
        echo '</div>';
        echo '<div id="toc__inside">';
        echo '<ul class="toc">';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">Dashboard</a>';
        echo '</div></li>';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=page&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">Pages</a>';
        echo '</div></li>';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=referer&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">Incoming Links</a>';
        echo '</div></li>';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=newreferer&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">New Incoming Links</a>';
        echo '</div></li>';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=browser&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">Browsers</a>';
        echo '</div></li>';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=os&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">Operating Systems</a>';
        echo '</div></li>';

        echo '<li><div class="li">';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt=country&amp;f='.$this->from.'&amp;t='.$this->to.'&amp;s='.$this->start.'">Countries</a>';
        echo '</div></li>';

        echo '</ul>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Print the time selection menu
     */
    function html_timeselect(){
        $now   = date('Y-m-d');
        $yday  = date('Y-m-d',time()-(60*60*24));
        $week  = date('Y-m-d',time()-(60*60*24*7));
        $month = date('Y-m-d',time()-(60*60*24*30));

        echo '<div class="plg_stats_timeselect">';
        echo '<span>Select the timeframe:</span>';
        echo '<ul>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$now.'&amp;t='.$now.'&amp;s='.$this->start.'">';
        echo 'today';
        echo '</a>';
        echo '</li>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$yday.'&amp;t='.$yday.'&amp;s='.$this->start.'">';
        echo 'yesterday';
        echo '</a>';
        echo '</li>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$week.'&amp;t='.$now.'&amp;s='.$this->start.'">';
        echo 'last 7 days';
        echo '</a>';
        echo '</li>';

        echo '<li>';
        echo '<a href="?do=admin&amp;page=statistics&amp;opt='.$this->opt.'&amp;f='.$month.'&amp;t='.$now.'&amp;s='.$this->start.'">';
        echo 'last 30 days';
        echo '</a>';
        echo '</li>';

        echo '</ul>';


        echo '<form action="" method="get">';
        echo '<input type="hidden" name="do" value="admin" />';
        echo '<input type="hidden" name="page" value="statistics" />';
        echo '<input type="hidden" name="opt" value="'.$this->opt.'" />';
        echo '<input type="hidden" name="s" value="'.$this->start.'" />';
        echo '<input type="text" name="f" value="'.$this->from.'" class="edit" />';
        echo '<input type="text" name="t" value="'.$this->to.'" class="edit" />';
        echo '<input type="submit" value="go" class="button" />';
        echo '</form>';

        echo '</div>';
    }


    /**
     * Print an introductionary screen
     */
    function html_dashboard(){
        echo '<p>This page gives you a quick overview on what is happening in your Wiki. For detailed lists
              choose a topic from the list.</p>';


        echo '<div class="plg_stats_dashboard">';

        // general info
        echo '<div class="plg_stats_top">';
        $result = $this->sql_aggregate($this->tlimit);
        echo '<ul>';
        echo '<li><span>'.$result['pageviews'].'</span> page views</li>';
        echo '<li><span>'.$result['sessions'].'</span> visitors (sessions)</li>';
        echo '<li><span>'.$result['users'].'</span> logged in users</li>';

        echo '</ul>';
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=trend&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        echo '</div>';


        // top pages today
        echo '<div>';
        echo '<h2>Most popular pages</h2>';
        $result = $this->sql_pages($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '</div>';

        // top referer today
        echo '<div>';
        echo '<h2>Newest incoming links</h2>';
        $result = $this->sql_newreferer($this->tlimit,$this->start,15);
        $this->html_resulttable($result);
        echo '</div>';

        // top countries today
        echo '<div>';
        echo '<h2>Visitor\'s top countries</h2>';
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=country&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
//        $result = $this->sql_countries($this->tlimit,$this->start,15);
//        $this->html_resulttable($result,array('','Countries','Count'));
        echo '</div>';

        echo '</div>';
    }

    function html_country(){
        echo '<div class="plg_stats_full">';
        echo '<h2>Visitor\'s Countries</h2>';
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=country&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        $result = $this->sql_countries($this->tlimit,$this->start,150);
        $this->html_resulttable($result);
        echo '</div>';
    }

    function html_page(){
        echo '<div class="plg_stats_full">';
        echo '<h2>Popular Pages</h2>';
        $result = $this->sql_pages($this->tlimit,$this->start,150);
        $this->html_resulttable($result);
        echo '</div>';
    }

    function html_browser(){
        echo '<div class="plg_stats_full">';
        echo '<h2>Browser Shootout</h2>';
        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=browser&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        $result = $this->sql_browsers($this->tlimit,$this->start,150,true);
        $this->html_resulttable($result);
        echo '</div>';
    }

    function html_os(){
        echo '<div class="plg_stats_full">';
        echo '<h2>Operating Systems</h2>';
//        echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/img.php?img=browser&amp;f='.$this->from.'&amp;t='.$this->to.'" />';
        $result = $this->sql_os($this->tlimit,$this->start,150,true);
        $this->html_resulttable($result);
        echo '</div>';
    }

    function html_referer(){
        echo '<div class="plg_stats_full">';
        echo '<h2>Incoming Links</h2>';
        $result = $this->sql_aggregate($this->tlimit);

        $all    = $result['search']+$result['external']+$result['direct'];

        printf("<p>Of all %d external visits, %d (%.1f%%) were bookmarked (direct) accesses,
                %d (%.1f%%) came from search engines and %d (%.1f%%) were referred through
                links from other pages.</p>",$all,$result['direct'],(100*$result['direct']/$all),
                $result['search'],(100*$result['search']/$all),$result['external'],
                (100*$result['external']/$all));

        $result = $this->sql_referer($this->tlimit,$this->start,150);
        $this->html_resulttable($result);
        echo '</div>';
    }

    function html_newreferer(){
        echo '<div class="plg_stats_full">';
        echo '<h2>New Incoming Links</h2>';
        echo '<p>The following incoming links where first logged in the selected time frame,
              and have never been seen before.</p>';

        $result = $this->sql_newreferer($this->tlimit,$this->start,150);
        $this->html_resulttable($result);
        echo '</div>';
    }



    /**
     * Display a result in a HTML table
     */
    function html_resulttable($result,$header=''){
        echo '<table>';
        if(is_array($header)){
            echo '<tr>';
            foreach($header as $h){
                echo '<th>'.hsc($h).'</th>';
            }
            echo '</tr>';
        }

        foreach($result as $row){
            echo '<tr>';
            foreach($row as $k => $v){
                echo '<td class="plg_stats_X'.$k.'">';
                if($k == 'page'){
                    echo '<a href="'.wl($v).'" class="wikilink1">';
                    echo hsc($v);
                    echo '</a>';
                }elseif($k == 'url'){
                    $url = hsc($v);
                    if(strlen($url) > 45){
                        $url = substr($url,0,30).' &hellip; '.substr($url,-15);
                    }
                    echo '<a href="'.$v.'" class="urlextern">';
                    echo $url;
                    echo '</a>';
                }elseif($k == 'browser'){
                    include_once(dirname(__FILE__).'/inc/browsers.php');
                    echo $BrowsersHashIDLib[$v];
                }elseif($k == 'bflag'){
                    include_once(dirname(__FILE__).'/inc/browsers.php');
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/browser/'.$BrowsersHashIcon[$v].'.png" alt="'.hsc($v).'" />';
                }elseif($k == 'os'){
                    if(empty($v)){
                        echo 'unknown';
                    }else{
                        include_once(dirname(__FILE__).'/inc/operating_systems.php');
                        echo $OSHashLib[$v];
                    }
                }elseif($k == 'osflag'){
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/os/'.hsc($v).'.png" alt="'.hsc($v).'" />';
                }elseif($k == 'cflag'){
                    echo '<img src="'.DOKU_BASE.'lib/plugins/statistics/ico/flags/'.hsc($v).'.png" alt="'.hsc($v).'" width="18" height="12" />';
                }elseif($k == 'html'){
                    echo $v;
                }else{
                    echo hsc($v);
                }
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    }

    /**
     * Create an image
     */
    function img_build($img){
        include(dirname(__FILE__).'/inc/AGC.class.php');

        switch($img){
            case 'country':
                // build top countries + other
                $result = $this->sql_countries($this->tlimit,$this->start,0);
                $data = array();
                $top = 0;
                foreach($result as $row){
                    if($top < 7){
                        $data[$row['country']] = $row['cnt'];
                    }else{
                        $data['other'] += $row['cnt'];
                    }
                    $top++;
                }
                $pie = new AGC(300, 200);
                $pie->setProp("showkey",true);
                $pie->setProp("showval",false);
                $pie->setProp("showgrid",false);
                $pie->setProp("type","pie");
                $pie->setProp("keyinfo",1);
                $pie->setProp("keysize",8);
                $pie->setProp("keywidspc",-50);
                $pie->setProp("key",array_keys($data));
                $pie->addBulkPoints(array_values($data));
                @$pie->graph();
                $pie->showGraph();
                break;
            case 'browser':
                // build top browsers + other
                include_once(dirname(__FILE__).'/inc/browsers.php');

                $result = $this->sql_browsers($this->tlimit,$this->start,0,false);
                $data = array();
                $top = 0;
                foreach($result as $row){
                    if($top < 5){
                        $data[strip_tags($BrowsersHashIDLib[$row['ua_info']])] = $row['cnt'];
                    }else{
                        $data['other'] += $row['cnt'];
                    }
                    $top++;
                }
                $pie = new AGC(300, 200);
                $pie->setProp("showkey",true);
                $pie->setProp("showval",false);
                $pie->setProp("showgrid",false);
                $pie->setProp("type","pie");
                $pie->setProp("keyinfo",1);
                $pie->setProp("keysize",8);
                $pie->setProp("keywidspc",-50);
                $pie->setProp("key",array_keys($data));
                $pie->addBulkPoints(array_values($data));
                @$pie->graph();
                $pie->showGraph();
                break;
            case 'trend':
                $hours  = ($this->from == $this->to);
                $result = $this->sql_trend($this->tlimit,$hours);
                $data1   = array();
                $data2   = array();

                $graph = new AGC(400, 150);
                $graph->setProp("type","bar");
                $graph->setProp("showgrid",false);
                $graph->setProp("barwidth",.8);

                $graph->setColor('color',0,'blue');
                $graph->setColor('color',1,'red');

                if($hours){
                    //preset $hours
                    for($i=0;$i<24;$i++){
                        $data1[$i] = 0;
                        $data2[$i] = 0;
                        $graph->setProp("scale",array(' 0h','   4h','   8h','    12h','    16h','    20h','    24h'));
                    }
                }else{
                    $graph->setProp("scale",array(next(array_keys($data1)),$this->to));
                }

                foreach($result as $row){
                    $data1[$row['time']] = $row['pageviews'];
                    $data2[$row['time']] = $row['sessions'];
                }

                foreach($data1 as $key => $val){
                    $graph->addPoint($val,$key,0);
                }
                foreach($data2 as $key => $val){
                    $graph->addPoint($val,$key,1);
                }

                @$graph->graph();
                $graph->showGraph();

            default:
                $this->sendGIF();
        }
    }


    /**
     * Return some aggregated statistics
     */
    function sql_aggregate($tlimit){
        $data = array();

        $sql = "SELECT ref_type, COUNT(*) as cnt
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY ref_type";
        $result = $this->runSQL($sql);

        foreach($result as $row){
            if($row['ref_type'] == 'search')   $data['search']   = $row['cnt'];
            if($row['ref_type'] == 'external') $data['external'] = $row['cnt'];
            if($row['ref_type'] == 'internal') $data['internal'] = $row['cnt'];
            if($row['ref_type'] == '')         $data['direct']   = $row['cnt'];
        }

        $sql = "SELECT COUNT(DISTINCT session) as sessions,
                       COUNT(session) as views,
                       COUNT(DISTINCT user) as users
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'";
        $result = $this->runSQL($sql);

        $data['users']     = max($result[0]['users'] - 1,0); // subtract empty user
        $data['sessions']  = $result[0]['sessions'];
        $data['pageviews'] = $result[0]['views'];

        $sql = "SELECT COUNT(id) as robots
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'robot'";
        $result = $this->runSQL($sql);
        $data['robots'] = $result[0]['robots'];

        return $data;
    }

    /**
     * standard statistics follow, only accesses made by browsers are counted
     * for general stats like browser or OS only visitors not pageviews are counted
     */
    function sql_trend($tlimit,$hours=false){
        if($hours){
            $sql = "SELECT HOUR(dt) as time,
                           COUNT(DISTINCT session) as sessions,
                           COUNT(session) as pageviews
                      FROM ".$this->getConf('db_prefix')."access as A
                     WHERE $tlimit
                       AND ua_type = 'browser'
                  GROUP BY HOUR(dt)
                  ORDER BY time";
        }else{
            $sql = "SELECT DATE(dt) as time,
                           COUNT(DISTINCT session) as sessions,
                           COUNT(session) as pageviews
                      FROM ".$this->getConf('db_prefix')."access as A
                     WHERE $tlimit
                       AND ua_type = 'browser'
                  GROUP BY DATE(dt)
                  ORDER BY time";
        }
        return $this->runSQL($sql);
    }

    function sql_pages($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, page
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY page
              ORDER BY cnt DESC, page".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_referer($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, ref as url
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
                   AND ref_type = 'external'
              GROUP BY ref_md5
              ORDER BY cnt DESC, url".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_newreferer($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(*) as cnt, ref as url
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE ua_type = 'browser'
                   AND ref_type = 'external'
              GROUP BY ref_md5
                HAVING DATE(MIN(dt)) >= DATE('".$this->from."')
                   AND DATE(MIN(dt)) <= DATE('".$this->to."')
              ORDER BY cnt DESC, url".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_countries($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, B.code AS cflag, B.country
                  FROM ".$this->getConf('db_prefix')."access as A,
                       ".$this->getConf('db_prefix')."iplocation as B
                 WHERE $tlimit
                   AND A.ip = B.ip
              GROUP BY B.country
              ORDER BY cnt DESC, B.country".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_browsers($tlimit,$start=0,$limit=20,$ext=true){
        if($ext){
            $sel = 'ua_info as bflag, ua_info as browser, ua_ver';
            $grp = 'ua_info, ua_ver';
        }else{
            $grp = 'ua_info';
            $sel = 'ua_info';
        }

        $sql = "SELECT COUNT(DISTINCT session) as cnt, $sel
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY $grp
              ORDER BY cnt DESC, ua_info".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }

    function sql_os($tlimit,$start=0,$limit=20){
        $sql = "SELECT COUNT(DISTINCT session) as cnt, os as osflag, os
                  FROM ".$this->getConf('db_prefix')."access as A
                 WHERE $tlimit
                   AND ua_type = 'browser'
              GROUP BY os
              ORDER BY cnt DESC, os".
              $this->sql_limit($start,$limit);
        return $this->runSQL($sql);
    }


    /**
     * Builds a limit clause
     */
    function sql_limit($start,$limit){
        $start = (int) $start;
        $limit = (int) $limit;
        if($limit){
            return " LIMIT $start,$limit";
        }elseif($start){
            return " OFFSET $start";
        }
        return '';
    }

    /**
     * Return a link to the DB, opening the connection if needed
     */
    function dbLink(){
        // connect to DB if needed
        if(!$this->dblink){
            $this->dblink = mysql_connect($this->getConf('db_server'),
                                          $this->getConf('db_user'),
                                          $this->getConf('db_password'));
            if(!$this->dblink){
                msg('DB Error: connection failed',-1);
                return null;
            }
            // set utf-8
            if(!mysql_db_query($this->getConf('db_database'),'set names utf8',$this->dblink)){
                msg('DB Error: could not set UTF-8 ('.mysql_error($this->dblink).')',-1);
                return null;
            }
        }
        return $this->dblink;
    }

    /**
     * Simple function to run a DB query
     */
    function runSQL($sql_string) {
        $link = $this->dbLink();

        $result = mysql_db_query($this->conf['db_database'],$sql_string,$link);
        if(!$result){
            msg('DB Error: '.mysql_error($link).' '.hsc($sql_string),-1);
            return null;
        }

        $resultarray = array();

        //mysql_db_query returns 1 on a insert statement -> no need to ask for results
        if ($result != 1) {
            for($i=0; $i< mysql_num_rows($result); $i++) {
                $temparray = mysql_fetch_assoc($result);
                $resultarray[]=$temparray;
            }
            mysql_free_result($result);
        }

        if (mysql_insert_id($link)) {
            $resultarray = mysql_insert_id($link); //give back ID on insert
        }

        return $resultarray;
    }

    /**
     * Returns a short name for a User Agent and sets type, version and os info
     */
    function ua_info($ua,&$type,&$ver,&$os){
        $ua = strtr($ua,' +','__');
        $ua = strtolower($ua);

        // common browsers
        $regvermsie     = '/msie([+_ ]|)([\d\.]*)/i';
        $regvernetscape = '/netscape.?\/([\d\.]*)/i';
        $regverfirefox  = '/firefox\/([\d\.]*)/i';
        $regversvn      = '/svn\/([\d\.]*)/i';
        $regvermozilla  = '/mozilla(\/|)([\d\.]*)/i';
        $regnotie       = '/webtv|omniweb|opera/i';
        $regnotnetscape = '/gecko|compatible|opera|galeon|safari/i';

        $name = '';
        # IE ?
        if(preg_match($regvermsie,$ua,$m) && !preg_match($regnotie,$ua)){
            $type = 'browser';
            $ver  = $m[2];
            $name = 'msie';
        }
        # Firefox ?
        elseif (preg_match($regverfirefox,$ua,$m)){
            $type = 'browser';
            $ver  = $m[1];
            $name = 'firefox';
        }
        # Subversion ?
        elseif (preg_match($regversvn,$ua,$m)){
            $type = 'rcs';
            $ver  = $m[1];
            $name = 'svn';
        }
        # Netscape 6.x, 7.x ... ?
        elseif (preg_match($regvernetscape,$ua,$m)){
            $type = 'browser';
            $ver  = $m[1];
            $name = 'netscape';
        }
        # Netscape 3.x, 4.x ... ?
        elseif(preg_match($regvermozilla,$ua,$m) && !preg_match($regnotnetscape,$ua)){
            $type = 'browser';
            $ver  = $m[2];
            $name = 'netscape';
        }else{
            include(dirname(__FILE__).'/inc/browsers.php');
            foreach($BrowsersSearchIDOrder as $regex){
                if(preg_match('/'.$regex.'/',$ua)){
                    // it's a browser!
                    $type = 'browser';
                    $name = strtolower($regex);
                    break;
                }
            }
        }

        // check versions for Safari and Opera
        if($name == 'safari'){
            if(preg_match('/safari\/([\d\.]*)/i',$ua,$match)){
                $ver = $BrowsersSafariBuildToVersionHash[$match[1]];
            }
        }elseif($name == 'opera'){
            if(preg_match('/opera[\/ ]([\d\.]*)/i',$ua,$match)){
                $ver = $match[1];
            }
        }


        // check OS for browsers
        if($type == 'browser'){
            include(dirname(__FILE__).'/inc/operating_systems.php');
            foreach($OSSearchIDOrder as $regex){
                if(preg_match('/'.$regex.'/',$ua)){
                    $os = $OSHashID[$regex];
                    break;
                }
            }

        }

        // are we done now?
        if($name) return $name;

        include(dirname(__FILE__).'/inc/robots.php');
        foreach($RobotsSearchIDOrder as $regex){
            if(preg_match('/'.$regex.'/',$ua)){
                    // it's a robot!
                    $type = 'robot';
                    return strtolower($regex);
            }
        }

        // dunno
        return '';
    }

    /**
     *
     * @fixme: put search engine queries in seperate table here
     */
    function log_search($referer,&$type){
        $referer = strtr($referer,' +','__');
        $referer = strtolower($referer);

        include(dirname(__FILE__).'/inc/search_engines.php');

        foreach($SearchEnginesSearchIDOrder as $regex){
            if(preg_match('/'.$regex.'/',$referer)){
                if(!$NotSearchEnginesKeys[$regex] ||
                   !preg_match('/'.$NotSearchEnginesKeys[$regex].'/',$referer)){
                    // it's a search engine!
                    $type = 'search';
                    break;
                }
            }
        }
        if($type != 'search') return; // we're done here

        #fixme now do the keyword magic!
    }

    /**
     * Resolve IP to country/city
     */
    function log_ip($ip){
        // check if IP already known and up-to-date
        $sql = "SELECT ip
                  FROM ".$this->getConf('db_prefix')."iplocation
                 WHERE ip ='".addslashes($ip)."'
                   AND lastupd > DATE_SUB(CURDATE(),INTERVAL 30 DAY)";
        $result = $this->runSQL($sql);
        if($result[0]['ip']) return;

        $http = new DokuHTTPClient();
        $http->timeout = 10;
        $data = $http->get('http://api.hostip.info/get_html.php?ip='.$ip);

        if(preg_match('/^Country: (.*?) \((.*?)\)\nCity: (.*?)$/s',$data,$match)){
            $country = addslashes(trim($match[1]));
            $code    = addslashes(strtolower(trim($match[2])));
            $city    = addslashes(trim($match[3]));
            $host    = addslashes(gethostbyaddr($ip));
            $ip      = addslashes($ip);

            $sql = "REPLACE INTO ".$this->getConf('db_prefix')."iplocation
                        SET ip = '$ip',
                            country = '$country',
                            code    = '$code',
                            city    = '$city',
                            host    = '$host'";
            $this->runSQL($sql);
        }
    }

    /**
     * log a page access
     *
     * called from log.php
     */
    function log_access(){
        if(!$_REQUEST['p']) return;

        # FIXME check referer against blacklist and drop logging for bad boys

        // handle referer
        $referer = trim($_REQUEST['r']);
        if($referer){
            $ref     = addslashes($referer);
            $ref_md5 = ($ref) ? md5($referer) : '';
            if(strpos($referer,DOKU_URL) === 0){
                $ref_type = 'internal';
            }else{
                $ref_type = 'external';
                $this->log_search($referer,$ref_type);
            }
        }else{
            $ref      = '';
            $ref_md5  = '';
            $ref_type = '';
        }

        // handle user agent
        $agent   = trim($_SERVER['HTTP_USER_AGENT']);

        $ua      = addslashes($agent);
        $ua_type = '';
        $ua_ver  = '';
        $os      = '';
        $ua_info = addslashes($this->ua_info($agent,$ua_type,$ua_ver,$os));

        $page    = addslashes($_REQUEST['p']);
        $ip      = addslashes($_SERVER['REMOTE_ADDR']);
        $sx      = (int) $_REQUEST['sx'];
        $sy      = (int) $_REQUEST['sy'];
        $vx      = (int) $_REQUEST['vx'];
        $vy      = (int) $_REQUEST['vy'];
        $js      = (int) $_REQUEST['js'];
        $user    = addslashes($_SERVER['REMOTE_USER']);
        $session = addslashes(session_id());

        $sql  = "INSERT DELAYED INTO ".$this->getConf('db_prefix')."access
                    SET dt       = NOW(),
                        page     = '$page',
                        ip       = '$ip',
                        ua       = '$ua',
                        ua_info  = '$ua_info',
                        ua_type  = '$ua_type',
                        ua_ver   = '$ua_ver',
                        os       = '$os',
                        ref      = '$ref',
                        ref_md5  = '$ref_md5',
                        ref_type = '$ref_type',
                        screen_x = '$sx',
                        screen_y = '$sy',
                        view_x   = '$vx',
                        view_y   = '$vy',
                        js       = '$js',
                        user     = '$user',
                        session  = '$session'";
        $ok = $this->runSQL($sql);
        if(is_null($ok)){
            global $MSG;
            print_r($MSG);
        }

        // resolve the IP
        $this->log_ip($_SERVER['REMOTE_ADDR']);
    }

    /**
     * Just send a 1x1 pixel blank gif to the browser
     *
     * @called from log.php
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @author Harry Fuecks <fuecks@gmail.com>
     */
    function sendGIF(){
        $img = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAEALAAAAAABAAEAAAIBTAA7');
        header('Content-Type: image/gif');
        header('Content-Length: '.strlen($img));
        header('Connection: Close');
        print $img;
        flush();
        // Browser should drop connection after this
        // Thinks it's got the whole image
    }

}
