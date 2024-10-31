<?php
/*
Plugin Name: PopStats
Plugin URI: http://hyanetworks.com/wordpress/2010/03/03/mi-version-del-popstats/
Description: Simple blog stats panel that shows popularity and relevant data about visits. Shows only human visits, hiding every kind of bot
Author: Víctor Martínez(oxigen)
Version: 3.0
Author URI: http://hyanetworks.com/wordpress/
*/

/***** Config (only edit this part) *****/
// User level over wich you can see stats
$ps_user_level = 1; // 1 = Everyone with panel access - 10 = Top Administrator
// Type translation, leave blank the ones you don't want translated
/*$ps_tr = array (
	'Last Update' => 'Última Actualización',
	'Visits' => 'Visitas',
	'Hits' => 'Hits',
	'Day' => 'Día',
	'Today' => 'Hoy',
	'First Day' => 'Primer Día',
	'People Online' => 'Visitas Online',
	'IP' => 'IP',
	'URL' => 'URL',
	'Stats' => 'Stats',
	'Average' => 'Media',
	'Max' => 'Max',
	'Total' => 'Total',
	'Last N days' => 'Últimos N días',
	'Max Online' => 'Max Online',
	'Top Browsers' => 'Top Navegadores',
	'View Extended' => 'Ver Extendido',
	'View General' => 'Ver General',
	'Crawler/Search Engine' => 'Bots/Busqueda',
	'Top Platforms' => 'Top Plataformas',
	'Last N Search Terms' => 'Últimas N Busquedas',
	'Last N Referers' => 'Últimos N Referers',
	'Animate Stats' => 'Animar Estadísticas',
	'Stop Animation' => 'Parar Animación',
	'Reset Stats' => 'Resetear Stats',
	'You are about to delete all data and reset stats. OK to delete, Cancel to stop' => 'Vas a eliminar y resetear las estádisticas. OK para aceptar, Cancel para cancelar.'
);*/
$ps_tr = array (
	'Last Update' => 'Last Update',
	'Visits' => 'Visits',
	'Hits' => 'Hits',
	'Day' => 'Day',
	'Today' => 'Today',
	'First Day' => 'First Day',
	'People Online' => 'People Online',
	'IP' => 'IP',
	'URL' => 'URL',
	'Stats' => 'Stats',
	'Average' => 'Averange',
	'Max' => 'Max',
	'Total' => 'Total',
	'Last N days' => 'Last N Cays',
	'Max Online' => 'Max Online',
	'Top Browsers' => 'Top Browsers',
	'View Extended' => 'View Extended',
	'View General' => 'View General',
	'Crawler/Search Engine' => 'Crawler/Search Engine',
	'Top Platforms' => 'Top Platforms',
	'Last N Search Terms' => 'Last N Search Terms',
	'Last N Referers' => 'Last N Referers',
	'Animate Stats' => 'Animate Stats',
	'Stop Animation' => 'Stop Animación',
	'Reset Stats' => 'Reset Stats',
	'You are about to delete all data and reset stats. OK to delete, Cancel to stop' => 'You are about to delete all data and reset stats. OK to delete, Cancel to stop.'
);


// How many days do you want to show in the table of visits and hits
$ps_days = 35;
// How many seconds do you want your session to last (this is the appropiate time, so don't change it if you are not sure of what you're doing)
$ps_session_timeout = 3600; // 3600 seconds = 1 hour
// How many seconds do you want your visits to be considered online (this is the appropiate time, so don't change it if you are not sure of what you're doing)
$ps_online_timeout = 300; // 300 seconds = 5 minutes
// How many referers do you want to be shown
$ps_num_referers = 50;
// How many search terms do you want to be shown
$ps_num_search_terms = 30;
// Type the IPs that you don't want to track (like yours). Example: $ps_ips_not_tracked = array('156.45.689.23','197.469.934.574');
$ps_ips_not_tracked = array('88.2.72.39');
// How many seconds do you want the Ajax engine to refresh the stats
$ps_ajax_refresh_time = 10;
// Display visits graphic? (1: Yes, 0: No)
$ps_vgraphic = 1;
// // Display hits graphic? (1: Yes, 0: No)
$ps_hgraphic = 1;
/***** Config End (stop editing) *****/

/***** Constant declaration *****/
define('PS_VERSION','3.1'); 
define('PS_DAY',60*60*24);
/*****/

/***** Variables declaration *****/
$ps_time = ps_time();
$ps_ip = ps_getIP();
$ps_data_table = $table_prefix."ps_data";
$ps_visits_table = $table_prefix."ps_visits";
$ps_stats = array(
	'time_begin' => '',
	'num_days' => '',
	'visits' => array(),
	'hits' => array(),
	'online' => array(),
	'stats' => array(
		'visits_online' => '',
		'visits_avg' => '',
		'visits_max' => '',
		'visits_max_time' => '',
		'visits_total' => '',
		'hits_avg' => '',
		'hits_max' => '',
		'hits_max_time' => '',
		'hits_total' => '',
		'max_online' => '',
		'max_online_time' => ''
	),
	'platforms' => array(),
	'browsers' => array(),
	'search' => array(),
	'referers' => array()
);
/*****/

/***** Function library *****/
function ps_createDB() {
	global $wpdb,$ps_time,$ps_data_table,$ps_visits_table;

	$data_query = "CREATE TABLE ".$ps_data_table." (
					time_install int(11) NOT NULL default '0',
					version varchar(12) NOT NULL default '',
					max_visits mediumint(8) unsigned NOT NULL default '0',
					max_visits_time int(11) NOT NULL default '0',
					max_hits mediumint(8) unsigned NOT NULL default '0',
					max_hits_time int(11) NOT NULL default '0',
					max_online mediumint(8) unsigned NOT NULL default '0',
					max_online_time int(11) NOT NULL default '0'
				) TYPE=MyISAM;";
	$visits_query = "CREATE TABLE ".$ps_visits_table." (
					visit_id mediumint(8) unsigned NOT NULL auto_increment,
					ip varchar(20) NOT NULL default '',
					url varchar(255) NOT NULL default '',
					referer varchar(255) NOT NULL default '',
					platform varchar(50) NOT NULL default '',
					browser varchar(50) NOT NULL default '',
					version varchar(15) NOT NULL default '',
					search_terms varchar(255) NOT NULL default '',
					time_begin int(11) NOT NULL default '0',
					time_last int(11) NOT NULL default '0',
					hits mediumint(8) unsigned NOT NULL default '0',
					PRIMARY KEY (visit_id)
				) TYPE=MyISAM;";
	require_once (ABSPATH.'/wp-admin/upgrade-functions.php');
	maybe_create_table($ps_data_table,$data_query);
	maybe_create_table($ps_visits_table,$visits_query);
	
	$count_data = $wpdb->get_var("SELECT count(*) FROM ".$ps_data_table);
	if ($count_data==0)
		$wpdb->query("INSERT INTO ".$ps_data_table." (time_install,version,max_visits,max_visits_time,max_hits,max_hits_time) VALUES (".$ps_time.",'".PS_VERSION."',0,".$ps_time.",0,".$ps_time.")");
}

function ps_resetDB() {
	global $wpdb,$ps_data_table,$ps_visits_table;

	$wpdb->get_var("DROP TABLE ".$ps_data_table.",".$ps_visits_table);
	ps_createDB();
}

function ps_updateVersion() {
	global $wpdb,$table_prefix,$ps_data_table,$ps_visits_table;
	
	/***** Version 2.0.0 *****/
	// Reset DB if right installed
	$create_dll = "ALTER TABLE ".$ps_data_table." ADD version varchar(5) NOT NULL default '' AFTER time_install";
	maybe_add_column($ps_data_table,'version',$create_dll);
	$version = $wpdb->get_var("SELECT version FROM ".$ps_data_table);
	if ($version=='') {
		$hits_table = $table_prefix."ps_hits";
		$wpdb->get_var("DROP TABLE ".$ps_data_table.",".$ps_visits_table.",".$hits_table);
		ps_createDB();
	}
	/*****/

	// Update version
	$wpdb->query("UPDATE ".$ps_data_table." SET version = '".PS_VERSION."'");
}

function ps_tr($s) {
	global $ps_tr;
	
	return ($ps_tr[$s]!='') ? $ps_tr[$s] : $s;
}

function ps_getIP() {
	global $_SERVER;
	
	$ip = '';
	
	if (isset($_SERVER['HTTP_CLIENT_IP']))
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	else if (isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	else
		$ip = $_SERVER['REMOTE_HOST'];
	
	if (!ps_checkIP($ip))
		$ip = "UNKNOWN";
	
	return $ip;
}

function ps_checkIP($ip) {
	// XSS prevent
	$check = ip2long($ip);
	return (!($check == -1 || $check === false));
}

function ps_time() {
	return (date('U') - date('Z') + (get_settings('gmt_offset') * 3600));
}

function ps_getMidnight($time) {
	return date('U',mktime(0,0,0,1,date('z',$time)+1,date('y',$time)));
}

function ps_parseUserAgent($ua) {
	$array = array(
		'platform' => '',
		'browser'  => '',
		'version'  => ''
	);
	
	//Asignando la plataforma del navegador.
		 if (preg_match("/win/i", $ua)) 	$array['platform'] = "Windows";
	else if (preg_match("/mac/i", $ua)) 	
            {
                 if (preg_match("/Iphone/i", $ua)) $array['platform'] = "Iphone";
            else if (preg_match("/Ipad/i", $ua)) $array['platform'] = "Ipad";
            else $array['platform'] = "MacOS";
            }
	else if (preg_match("/linux/i", $ua)) 	$array['platform'] = "Linux";
	else if (preg_match("/unix/i", $ua)) 	$array['platform'] = "Unix";
	else if (preg_match("/bsd/i", $ua)) 	$array['platform'] = "BSD";
	else if (preg_match("/webOS/i", $ua)) 	$array['platform'] = "PALM";
	else if (preg_match("/android/i", $ua)) 	$array['platform'] = "Android";
	else if (preg_match("/SonyEricsson/i", $ua)) $array['platform'] = "Sony Ericsson Phone";
	else if (preg_match("/Symbian/i", $ua)) 	{
                  if (preg_match("/nokia/i", $ua)) $array['platform'] = "Symbian Nokia";
            else if (preg_match("/lg/i", $ua)) $array['platform'] = "Symbian LG";
            }

	//Buscando Navegador Netscape
	if (eregi('Mozilla/4',$ua) && !eregi('compatible',$ua)) {
		$array['browser'] = "Netscape";
		eregi('Mozilla/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Mozilla
	if (eregi('Mozilla/5',$ua) || eregi('Gecko',$ua)) {
		$array['browser'] = "Mozilla";
		eregi('rv(:| )([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[2];
	}
	
	//Buscando Navegador Safari
	if (eregi('Safari',$ua) && !eregi('Chrome',$ua)) {
		$array['browser'] = "Safari";
		eregi('Safari/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
		
		if (eregi('125',$array['version'])) {
			$array['version'] = "1.2";
		}
		else if (eregi('100',$array['version'])) {
			$array['version'] = "1.1";
		}
		else if (eregi('85',$array['version'])) {
			$array['version'] = "1.0";
		}
		else if ($array['version']<85)
			$array['version'] = "Pre-1.0 Beta";
	}
	
	//Buscando Navegador Chrome
	if (eregi('Chrome',$ua)) {
		$array['browser'] = "Chrome";
		eregi('Chrome/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando iCab
	if (eregi('iCab',$ua)) {
		$array['browser'] = "iCab";
		eregi('iCab/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
		}
		
	//Buscando Navegador Firefox
	if (eregi('Firefox',$ua)) {
		$array['browser'] = "Firefox";
		eregi('Firefox/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Flock
	if (eregi('Flock',$ua)) {
		$array['browser'] = "Flock";
		eregi('Flock/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Firebird
	if (eregi('Firebird',$ua)) {
		$array['browser'] = "Firebird";
		eregi('Firebird/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Phoenix
	if (eregi('Phoenix',$ua)) {
		$array['browser'] = "Phoenix";
		eregi('Phoenix/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Camino
	if (eregi('Camino',$ua)) {
		$array['browser'] = "Camino";
		eregi('Camino/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Chimera
	if (eregi('Chimera',$ua)) {
		$array['browser'] = "Chimera";
		eregi('Chimera/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Netscape
	if (eregi('Netscape',$ua)) {
		$array['browser'] = "Netscape";
		eregi('Netscape[0-9]?/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Explorer
	if (eregi('MSIE',$ua)) {
		$array['browser'] = "Internet Explorer";
		eregi('MSIE ([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Opera
	if (eregi('Opera',$ua)) {
		$array['browser'] = "Opera";
		eregi('Opera( |/)([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[2];
	}
	
	//Buscando Omniweb
	if (eregi('OmniWeb',$ua)) {
		$array['browser'] = "OmniWeb";
		eregi('OmniWeb/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador konkeror
	if (eregi('Konqueror',$ua)) {
		$array['browser'] = "Konqueror";
		eregi('Konqueror/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Navegador Lynxs
	if (eregi('Lynx',$ua)) {
		$array['browser'] = "Lynx";
		eregi('Lynx/([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando Links
	if (eregi('Links',$ua)) {
		$array['browser'] = "Links";
		eregi('\(([[:digit:]\.]+)',$ua,$b);
		$array['version'] = $b[1];
	}
	
	//Buscando bots, spiders o cualquier search engine.
	if (eregi('Crawl',$ua) || eregi('bot',$ua) || eregi('slurp',$ua) || eregi('spider',$ua)) {
		$array['browser'] = "Crawler/Search Engine";
		$array['version'] = '';
	}
	return $array;
}

function ps_getSearchTerms($url) {
	$url = parse_url($url);
	if (!empty($url['query']))
		parse_str($url['query'],$q);
	
	if (!empty($q)) {
		if (preg_match("/google\./",$url['host']))
			return $q['q'];
		else if (preg_match("/alltheweb\./",$url['host']))
			return $q['q'];
		else if (preg_match("/yahoo\./",$url['host']))
			return $q['p'];
		else if (preg_match("/search\.aol\./",$url['host']))
			return $q['query'];
		else if (preg_match("/searcph\.msn\./",$url['host']))
			return $q['q'];
		else if (preg_match("/terra\./",$url['host']))
			return $q['query'];
		else if (preg_match("/search\.live\./",$url['host']))
			return $q['q'];
	}
	
	return '';
}
/*****/

/***** PopStats functions *****/
function ps_setStats() {
	global $wpdb,$ps_time,$ps_ip,$ps_data_table,$ps_visits_table,$ps_days,$ps_session_timeout,$ps_online_timeout,$ps_ips_not_tracked,$userdata,$ps_user_level,$_SERVER;
	
	// Don't track plugin scripts
	if (eregi('wp-content/plugins/',$_SERVER['PHP_SELF']))
		return;
	
	//No contar visitas al popstats
	if (preg_match("/popstats/i", $_SERVER['HTTP_REFERER']))
		return;
	
	// Don't track IPs selected by user
	if(in_array($ps_ip,$ps_ips_not_tracked))
		return;
	
	$is_admin = ($wpdb->is_admin || eregi('wp-admin/',$_SERVER['PHP_SELF']) || $userdata->user_level>=$ps_user_level);
	
	// Only authorized users could trigger this action (to avoid performance problems)
	if ($is_admin) {
		// Delete all data before user predefined day
		$time_delete = ps_getMidnight($ps_time-(PS_DAY*$ps_days));
		$wpdb->query("DELETE FROM ".$ps_visits_table." WHERE time_begin < ".$time_delete);
	}
	
	/* Get visitor data */
	$url = wp_specialchars($_SERVER['REQUEST_URI'],true);
	$ua = ps_parseUserAgent($_SERVER['HTTP_USER_AGENT']);
	$referer = preg_replace('/[\r\n]/','',stripslashes($_SERVER['HTTP_REFERER']));
	if (!empty($referer)) {
		// Avoid referers like javascript:alert("XSS")
		if (!preg_match('|^https?://|i',$referer))
			$referer = 'http://'.$referer;
		// Avoid problems when search string contains "quoted texts"
		$search_terms = wp_specialchars(ps_getSearchTerms($referer),true);
		$referer = wp_specialchars($referer,true);
	}
	else
		$search_terms = '';
	/**/
	
	// Don't track feeds and bots visits
	if (preg_match("/feed|rss|atom|xml|rdf|trackback|pingback/",$url) || $ua['browser']=='Crawler/Search Engine')
		return;
		
	$time_insert_visit = $ps_time - $ps_session_timeout;
	
	// Don't track admin visits to the control panel, but do update data if session exists
	if ($is_admin) {
		$sessions = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE ip='".$ps_ip."' AND time_last > ".$time_insert_visit);
		if ($sessions > 0)
			$wpdb->query("UPDATE ".$ps_visits_table." SET time_last=".$ps_time.",url='".$url."' WHERE ip='".$ps_ip."' AND time_last > ".$time_insert_visit);
		return;
	}
	
	// Insert new visits (update existing ones) and hits
	$ip_time_query = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE ip='".$ps_ip."' AND time_last > ".$time_insert_visit);
	if ($ip_time_query==0)		
		$wpdb->query("INSERT INTO ".$ps_visits_table." (ip,url,referer,platform,browser,version,search_terms,time_begin,time_last,hits) VALUES ('".$ps_ip."','".$url."','".$referer."','".$ua['platform']."','".$ua['browser']."','".$ua['version']."','".$search_terms."',".$ps_time.",".$ps_time.",1)");
	else {
		$hits = $wpdb->get_var("SELECT hits FROM ".$ps_visits_table." WHERE ip='".$ps_ip."' AND time_last > ".$time_insert_visit);
		$hits++;
		$wpdb->query("UPDATE ".$ps_visits_table." SET time_last=".$ps_time.",url='".$url."',hits=".$hits." WHERE ip='".$ps_ip."' AND time_last > ".$time_insert_visit);
	}
	//
	
	// Check reached maximums
	$time_visit = $wpdb->get_var("SELECT time_begin FROM ".$ps_visits_table." WHERE ip='".$ps_ip."' AND time_last > ".$time_insert_visit);
	$time_start = ps_getMidnight($time_visit);
	$time_end = $time_start + PS_DAY;
	
	// Check if max visits has been reached
	$count_visits_day = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
	$max_visits = $wpdb->get_var("SELECT max_visits FROM ".$ps_data_table);
	if ($count_visits_day>=$max_visits)
		$wpdb->query("UPDATE ".$ps_data_table." SET max_visits = ".$count_visits_day.",max_visits_time = ".$time_visit);		
	
	// Check if max hits has been reached
	$count_hits_day = $wpdb->get_var("SELECT sum(hits) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
	$max_hits = $wpdb->get_var("SELECT max_hits FROM ".$ps_data_table);
	if ($count_hits_day>=$max_hits)
		$wpdb->query("UPDATE ".$ps_data_table." SET max_hits = ".$count_hits_day.",max_hits_time = ".$time_visit);
	
	// Check if max online visits has been reached
	$time_visits_online = $ps_time - $ps_online_timeout;
	$count_online = $wpdb->get_var("SELECT COUNT(*) FROM ".$ps_visits_table." WHERE time_last > ".$time_visits_online);
	$max_online = $wpdb->get_var("SELECT max_online FROM ".$ps_data_table);
	if ($count_online>=$max_online && $count_online<($max_online+100)) // Greater than previous but preventing server time error
		$wpdb->query("UPDATE ".$ps_data_table." SET max_online = ".$count_online.",max_online_time = ".$ps_time);
	//
}

function ps_getStats() {
	global $wpdb,$ps_stats,$ps_time,$ps_data_table,$ps_visits_table,$ps_days,$ps_online_timeout,$ps_num_search_terms,$ps_num_referers;
	
	/* Get NumDays & TimeBegin */
	$time_begin = ps_getMidnight($wpdb->get_var("SELECT time_install FROM ".$ps_data_table));
	$num_days = ceil(($ps_time-$time_begin)/PS_DAY);
	if ($num_days>$ps_days)
		$num_days = $ps_days + 1;
	
	$ps_stats['time_begin'] = $time_begin;
	$ps_stats['num_days'] = $num_days;
	/**/
	
	/* Get Day Arrays & Total visits/hits for averages */
	$visits_array = $hits_array = array();
	$visits_total = $hits_total = 0;
	
	for ($i=0; $i<$ps_stats['num_days']; $i++) {
		$day_time = $ps_time - ($i * PS_DAY);
		$time_start = ps_getMidnight($day_time);
		$time_end = $time_start + PS_DAY;
		$count_visits_day = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
		$count_hits_day = $wpdb->get_var("SELECT sum(hits) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
		if ($count_hits_day=='')
			$count_hits_day = 0;
		$visits_array[$day_time] = $count_visits_day;
		$hits_array[$day_time] = $count_hits_day;
		if ($i!=0 && $time_start!=$time_begin) {
			$visits_total += $count_visits_day;
			$hits_total += $count_hits_day;
		}
	}
	
	$ps_stats['visits'] = $visits_array;
	$ps_stats['hits'] = $hits_array;
	/**/
	
	/* Get Online Visits */
	$time_visits_online = $ps_time - $ps_online_timeout;
	$online_query = $wpdb->get_results("SELECT ip,url FROM ".$ps_visits_table." WHERE time_last > ".$time_visits_online." ORDER BY time_last DESC");
	$online = array();
	if ($online_query) {
		foreach ($online_query as $visit) {
			$o = array();
			$o['ip'] = (ps_checkIP($visit->ip)) ? $visit->ip : "UNKNOWN";
			$o['url'] = wp_specialchars($visit->url,true);
			$online[] = $o;
		}
	}
	
	$ps_stats['online'] = $online;
	/**/
	
	/* Get General Stats */
	$ps_stats['stats']['visits_online'] = count($ps_stats['online']);
	
	$ps_stats['stats']['visits_avg'] = ($ps_stats['num_days']>2) ? ceil($visits_total/($ps_stats['num_days']-2)) : '0';
	$ps_stats['stats']['hits_avg'] = ($ps_stats['num_days']>2) ? ceil($hits_total/($ps_stats['num_days']-2)) : '0';
	
	$ps_stats['stats']['visits_max'] = $wpdb->get_var("SELECT max_visits FROM ".$ps_data_table);
	$ps_stats['stats']['hits_max'] = $wpdb->get_var("SELECT max_hits FROM ".$ps_data_table);
	$ps_stats['stats']['visits_max_time'] = date('j M y',$wpdb->get_var("SELECT max_visits_time FROM ".$ps_data_table));
	$ps_stats['stats']['hits_max_time'] = date('j M y',$wpdb->get_var("SELECT max_hits_time FROM ".$ps_data_table));
	
	$ps_stats['stats']['visits_total'] = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table);
	$ps_stats['stats']['hits_total'] = $wpdb->get_var("SELECT sum(hits) FROM ".$ps_visits_table);
	if ($ps_stats['stats']['hits_total']=='')
			$ps_stats['stats']['hits_total'] = 0;
	
	$ps_stats['stats']['max_online'] = $wpdb->get_var("SELECT max_online FROM ".$ps_data_table);
	$ps_stats['stats']['max_online_time'] = date('j M y H:i',$wpdb->get_var("SELECT max_online_time FROM ".$ps_data_table));
	/**/
	
	/* Get Platforms */
	$platforms_total = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE platform <> ''");
	$platforms_query = $wpdb->get_results("SELECT platform,COUNT(platform) AS 'total' FROM ".$ps_visits_table." WHERE platform <> '' GROUP BY platform ORDER BY total DESC");
	$platforms = array();
	if ($platforms_query) {
		foreach ($platforms_query as $platform) {
			$p = array();
			$p['name'] = $platform->platform;
			$p['num'] = round(($platform->total/$platforms_total)*100);
			if ($p['num'] < 1)
				$p['num'] = '&lt;1';
			
			$platforms[] = $p;
		}
	}
	
	$ps_stats['platforms'] = $platforms;
	/**/
	
	/* Get Browsers */
	$browsers_total = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE browser <> ''");
	$browsers_query = $wpdb->get_results("SELECT browser,COUNT(browser) AS 'total' FROM ".$ps_visits_table." WHERE browser <> '' AND browser <> 'Crawler/Search Engine' GROUP BY browser ORDER BY total DESC");
	$browsers = array();
	if ($browsers_query) {
		foreach ($browsers_query as $browser) {
			$b = array();
			$b['name'] = $browser->browser;
			$b['num'] = round(($browser->total/$browsers_total)*100);
			if ($b['num']<1)
				$b['num'] = '&lt;1';
			$versions_total = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE browser = '".$browser->browser."' AND version <> ''");
			$versions_query = $wpdb->get_results("SELECT version,COUNT(version) AS 'total' FROM ".$ps_visits_table." WHERE browser = '".$browser->browser."' AND version <> '' GROUP BY version ORDER BY total DESC");
			$versions = array();
			if ($versions_query) {
				foreach ($versions_query as $version) {
					$v = array();
					$v['name'] = $version->version;
					$v['num'] = round(($version->total/$versions_total)*100);
					if ($v['num'] < 1)
						$v['num'] = '&lt;1';
					
					$versions[] = $v;
				}
			}
			$b['versions'] = $versions;
		
			$browsers[] = $b;
		}
	}
	
	$ps_stats['browsers'] = $browsers;
	/**/
	
	/* Get Last Search Terms */
	$search_terms_query = $wpdb->get_results("SELECT referer,search_terms FROM ".$ps_visits_table." WHERE search_terms <> '' ORDER BY time_begin DESC LIMIT 0,".$ps_num_search_terms);
	$search_terms = array();
	if ($search_terms_query) {
		foreach ($search_terms_query as $search) {
			$s = array();
			$s['url'] = wp_specialchars($search->referer,true);
			$s['str'] = wp_specialchars($search->search_terms,true);
			
			$search_terms[] = $s;
		}
	}
	
	$ps_stats['search'] = $search_terms;
	/**/
	
	/* Get Last Referers */
	$r = $wpdb->get_col("SELECT referer FROM ".$ps_visits_table." WHERE referer NOT LIKE '%".get_settings('home')."%' AND referer <> '' AND search_terms = '' ORDER BY time_begin DESC LIMIT 0,".$ps_num_referers);
	for ($i=0; $i<count($r); $i++)
		$r[$i] = wp_specialchars($r[$i],true);
	
	$ps_stats['referers'] = $r;
	/**/
}

function ps_getStatsAjax($f,$d) {
	global $wpdb,$ps_stats,$ps_time,$ps_visits_table;
	
	header("Content-Type: text/xml");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	header("Last-Modified: ".gmdate("D, d M Y H:i:s")."GMT"); 
	header("Cache-Control: no-cache, must-revalidate"); 
	header("Pragma: no-cache");
	
	echo '<?xml version="1.0" encoding="'.get_settings('blog_charset').'" standalone="yes"?>'."\n";
	echo "<response>\n";
	
	$events = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE time_last > ".$d);
	if ($events>0 || $f==1) {
		ps_getStats();
		
		$visits = $ps_stats['visits'];
		$hits = $ps_stats['hits'];
		
		echo "<update>1</update>\n";
		echo "<d>".$ps_time."</d>\n";
		
		krsort($visits);
		echo "<table_days>\n";
		foreach ($visits as $day=>$num) {
			echo "<row>\n";
			if (date('j M Y',$day) == date('j M Y',$ps_time))
				echo "<day>".ps_tr('Today')."</day>\n";
			else if (date('j M Y',$day) == date('j M Y',$ps_stats['time_begin']))
				echo "<day>".ps_tr('First Day')."</day>\n";
			else
				echo "<day>".date('j M',$day)."</day>\n";
			echo "<gday>".date('j',$day)."</gday>\n";
			echo "<visits>".$num."</visits>\n";
			echo "<hits>".$hits[$day]."</hits>\n";
			echo "<vgheight>".round(100*($num/$ps_stats['stats']['visits_max']))."</vgheight>\n";
			echo "<hgheight>".round(100*($hits[$day]/$ps_stats['stats']['hits_max']))."</hgheight>\n";
			echo "</row>\n";
		}
		echo "</table_days>\n";
		
		echo "<people_online>".$ps_stats['stats']['visits_online']."</people_online>\n";
		echo "<online_visits>\n";
		foreach ($ps_stats['online'] as $i) {
			echo "<online>\n";
			echo "<ip>".$i['ip']."</ip>\n";
			echo "<url><![CDATA[".$i['url']."]]></url>\n";
			echo "</online>\n";
		}
		echo "</online_visits>\n";
		
		echo "<average_visits>".$ps_stats['stats']['visits_avg']."</average_visits>\n";
		echo "<average_hits>".$ps_stats['stats']['hits_avg']."</average_hits>\n";
		echo "<average_vgheight>".round(100*($ps_stats['stats']['visits_avg']/$ps_stats['stats']['visits_max']))."</average_vgheight>\n";
		echo "<average_hgheight>".round(100*($ps_stats['stats']['hits_avg']/$ps_stats['stats']['hits_max']))."</average_hgheight>\n";
		echo "<max_visits>".$ps_stats['stats']['visits_max']."</max_visits>\n";
		echo "<max_hits>".$ps_stats['stats']['hits_max']."</max_hits>\n";
		echo "<max_visits_time>".$ps_stats['stats']['visits_max_time']."</max_visits_time>\n";
		echo "<max_hits_time>".$ps_stats['stats']['hits_max_time']."</max_hits_time>\n";
		echo "<total_visits>".$ps_stats['stats']['visits_total']."</total_visits>\n";
		echo "<total_hits>".$ps_stats['stats']['hits_total']."</total_hits>\n";
		echo "<max_online>".$ps_stats['stats']['max_online']."</max_online>\n";
		echo "<max_online_time>".$ps_stats['stats']['max_online_time']."</max_online_time>\n";
		
		echo "<browsers>\n";
		foreach ($ps_stats['browsers'] as $i) {
			echo "<browser>\n";
			echo "<name>".$i['name']."</name>\n";
			echo "<num>".$i['num']."</num>\n";
			echo "<versions>\n";
			foreach ($i['versions'] as $j) {
				echo "<version>\n";
				echo "<name>".$j['name']."</name>\n";
				echo "<num>".$j['num']."</num>\n";
				echo "</version>\n";
			}
			echo "</versions>\n";
			echo "</browser>\n";
		}
		echo "</browsers>\n";
		
		echo "<platforms>\n";
		foreach ($ps_stats['platforms'] as $i) {
			echo "<platform>\n";
			echo "<name>".$i['name']."</name>\n";
			echo "<num>".$i['num']."</num>\n";
			echo "</platform>\n";
		}
		echo "</platforms>\n";
		
		echo "<search_terms>\n";
		foreach ($ps_stats['search'] as $i) {
			$url = $i['url'];
			$str = (strlen($i['str']) > 80) ? substr_replace($i['string'],"...",80) : $i['str'];
			
			echo "<terms_url><![CDATA[".$url."]]></terms_url>\n";
			echo "<terms_str><![CDATA[".$str."]]></terms_str>\n";
		}
		echo "</search_terms>\n";
		
		echo "<referers>\n";
		foreach ($ps_stats['referers'] as $i) {
			$url = $i;
			$str = (strlen($url) > 80) ? substr_replace($url,"...",80) : $url;
			
			echo "<referer_url><![CDATA[".$url."]]></referer_url>\n";
			echo "<referer_str><![CDATA[".$str."]]></referer_str>\n";
		}
		echo "</referers>\n";
	}
	else
		echo "<update>0</update>\n";
	
	echo "<time>".date("H:i:s",$ps_time)."</time>\n";
	echo "</response>";
}

function ps_getVar($key) {
	global $wpdb,$ps_stats,$ps_time,$ps_visits_table,$ps_data_table,$ps_online_timeout,$ps_days;
	
	if ($key == 'visits_online') {
		$time_visits_online = $ps_time - $ps_online_timeout;
		echo $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE time_last > ".$time_visits_online);
		return;
	}
	else if ($key == 'visits_avg') {
		// Get NumDays
		$time_begin = ps_getMidnight($wpdb->get_var("SELECT time_install FROM ".$ps_data_table));
		$num_days = ceil(($ps_time-$time_begin)/PS_DAY);
		if ($num_days>$ps_days)
			$num_days = $ps_days + 1;
		
		$time_start = $time_begin + PS_DAY;
		$time_end = ps_getMidnight($ps_time);
		$visits_total = $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
		
		echo ($num_days>2) ? round($visits_total/($num_days-2)) : '-';
		return;
	}
	else if ($key == 'hits_avg') {
		// Get NumDays
		$time_begin = ps_getMidnight($wpdb->get_var("SELECT time_install FROM ".$ps_data_table));
		$num_days = ceil(($ps_time-$time_begin)/PS_DAY);
		if ($num_days>$ps_days)
			$num_days = $ps_days + 1;
		
		$time_start = $time_begin + PS_DAY;
		$time_end = ps_getMidnight($ps_time);
		$hits_total = $wpdb->get_var("SELECT sum(hits) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
			if ($hits_total=='')
				$hits_total = 0;
		
		echo ($num_days>2) ? round($hits_total/($num_days-2)) : '-';
		return;
	}
	else if ($key == 'visits_today') {
		$time_start = ps_getMidnight($ps_time);
		$time_end = $time_start + PS_DAY;
		echo $wpdb->get_var("SELECT count(*) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
		return;
	}
	else if ($key == 'hits_today') {
		$time_start = ps_getMidnight($ps_time);
		$time_end = $time_start + PS_DAY;
		echo $wpdb->get_var("SELECT sum(hits) FROM ".$ps_visits_table." WHERE time_begin >= ".$time_start." AND time_begin < ".$time_end);
		return;
	}
	else
		echo 'null';
}
/*****/

/***** Visualization functions *****/
function ps_displayStats() {
	global $ps_stats,$ps_time,$ps_vgraphic,$ps_hgraphic,$ps_num_search_terms,$ps_num_referers;
	
	ps_getStats();
	
	$visits = $ps_stats['visits'];
	$hits = $ps_stats['hits'];
?>

	<div class="wrap">
	
	<div id="ps_popstats">
	<h2>PopStats</h2>

	<div id="ps_time" align="center"><strong><?php echo ps_tr('Last Update'); ?>:</strong> <?php echo date("H:i:s",$ps_time); ?></div>
	
	<!-- Graphics -->
	
<?php
	if ($ps_vgraphic==1 || $ps_hgraphic==1) {
		// Set graphics padding
		$ps_pad = ($ps_vgraphic==1 && $ps_hgraphic==1) ? 10 : 0;
?>
		<center>
		<table align="center" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
		<tr>
			
<?php
		if ($ps_vgraphic == 1) {
?>
			
			<td valign="bottom" style="padding-right: <?php echo $ps_pad; ?>px;">
			
			<div id="ps_vgraphic">
			<table class="ps_table" cellpadding="4" cellspacing="1">
			<tr>
			<th><?php echo ps_tr('Visits'); ?></th>
			</tr>
			
			<tr>
			<td>
				
			<table cellpadding="1" cellspacing="0" style="height: 120px; background-color:#FFF;">
			<tr>
		
<?php
			ksort($visits);
			foreach ($visits as $day=>$num) {
?>
			
				<td valign="bottom"><div class="ps_statbar" title="<?php echo date('j M',$day).": ".$num; ?>" style="width: 10px; height: <?php echo round(100*($num/$ps_stats['stats']['visits_max'])); ?>px;"></div></td>
				
<?php
			}
?>
		
			<td valign="bottom" style="padding-left: 11px;"><div class="ps_statbar_av" title="<?php echo ps_tr('Average').": ".$ps_stats['stats']['visits_avg']; ?>" style="width: 10px; height: <?php echo round(100*($ps_stats['stats']['visits_avg']/$ps_stats['stats']['visits_max'])); ?>px;"></div></td>
			</tr>
			
			</table>
			
			</td>
			</tr>
			</table>
			
			</div>
			
			</td>
		
<?php
		}
		if ($ps_hgraphic == 1) {
?>	
		
			<td valign="bottom" style="padding-left: <?php echo $ps_pad ?>px;">
			
			<div id="ps_hgraphic">
			<table class="ps_table" cellpadding="4" cellspacing="1">
			<tr>
			<th><?php echo ps_tr('Hits'); ?></th>
			</tr>
			
			<tr>
			<td>
				
			<table cellpadding="1" cellspacing="0" style="height: 120px; background-color:#FFF;">
			<tr>
		
<?php
			foreach ($visits as $day=>$num) {
?>
			
				<td valign="bottom"><div class="ps_statbar" title="<?php echo date('j M',$day).": ".$hits[$day]; ?>" style="width: 10px; height: <?php echo round(100*($hits[$day]/$ps_stats['stats']['hits_max'])); ?>px;"></div></td>
				
<?php
			}
?>
		
			<td valign="bottom" style="padding-left: 11px;"><div class="ps_statbar_av" title="<?php echo ps_tr('Average').": ".$ps_stats['stats']['hits_avg']; ?>" style="width: 10px; height: <?php echo round(100*($ps_stats['stats']['hits_avg']/$ps_stats['stats']['hits_max'])); ?>px;"></div></td>
			</tr>
			
			</table>
			
			</td>
			</tr>
			</table>
			
			</div>
			
			</td>
			
<?php
		}
?>
			
	</tr>
	</table>
	
<?php
	}
?>

	<!-- End Graphics -->
	
	<!-- Data -->
	<table align="center" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
	<tr>
	<td valign="top" >
	
	<div id="ps_table_days">
	<table class="ps_table" cellpadding="4" cellspacing="1">
	<tr>
	<th width="100"><?php echo ps_tr('Day'); ?></th>
	<th width="80"><?php echo ps_tr('Visits'); ?></th>
	<th width="80"><?php echo ps_tr('Hits'); ?></th>
	</tr>
	
<?php
	krsort($visits);
	foreach ($visits as $day=>$num) {
		if (date('j M Y',$day) == date('j M Y',$ps_time))
			$day_s = ps_tr('Today');
		else if (date('j M Y',$day) == date('j M Y',$ps_stats['time_begin']))
			$day_s = ps_tr('First Day');
		else
			$day_s = date('j M',$day);
?>

		<tr>
		<td align="center"><strong><?php echo $day_s; ?></strong></td>
		<td align="center"><?php echo $num; ?></td>
		<td align="center"><?php echo $hits[$day]; ?></td>
		</tr>
			
<?php
	}
?>
	
	</table>
	</div>
	
	</td>
	<td valign="top" style="padding-left: 20px;">
		
	<table class="ps_table" cellpadding="4" cellspacing="1">
	<tr>
	<th width="278"><?php echo ps_tr('People Online'); ?></th>
	</tr>
	
	<tr>
	<td align="center"><strong><span id="ps_people_online"><?php echo $ps_stats['stats']['visits_online']; ?></span></strong></td>
	</tr>
	
	<tr>
	<td colspan="2" align="center" class="ps_td_alt"><a href="javascript: ps_changeView('online');"><span id="ps_extended_online"><?php echo ps_tr('View Extended'); ?></span></a></td>
	</tr>
			
	</table>

	<div id="ps_online"></div>
	
	<p align="center"><input id="ps_b_ajax" type="button" value="<?php echo ps_tr('Animate Stats'); ?>" onclick="ps_changeAjaxStatus();" /></p>
	
	<div id="ps_stats">
	<table class="ps_table" cellpadding="4" cellspacing="1">
	<tr>
	<th width="100"><?php echo ps_tr('Stats'); ?></th>
	<th width="80"><?php echo ps_tr('Visits'); ?></th>
	<th width="80"><?php echo ps_tr('Hits'); ?></th>
	</tr>
	
	<tr>
	<td align="center"><strong><?php echo ps_tr('Average'); ?></strong></td>
	<td align="center"><span id="ps_average_visits"><?php echo ($ps_stats['stats']['visits_avg']!=0) ? $ps_stats['stats']['visits_avg'] : '-'; ?></span></td>
	<td align="center"><span id="ps_average_hits"><?php echo ($ps_stats['stats']['hits_avg']!=0) ? $ps_stats['stats']['hits_avg'] : '-'; ?></span></td>
	</tr>
	
	<tr>
	<td align="center"><strong><?php echo ps_tr('Max'); ?></strong></td>
	<td align="center"><span id="ps_max_visits"><?php echo $ps_stats['stats']['visits_max']; ?></span><br />(<span id="ps_max_visits_time"><?php echo $ps_stats['stats']['visits_max_time'] ?></span>)</td>
	<td align="center"><span id="ps_max_hits"><?php echo $ps_stats['stats']['hits_max']; ?></span><br />(<span id="ps_max_hits_time"><?php echo $ps_stats['stats']['hits_max_time'] ?></span>)</td>
	</tr>
	
	<tr>
	<td align="center"><strong><?php echo ps_tr('Total'); ?></strong><br />(<?php echo str_replace('N',$ps_stats['num_days'],ps_tr('Last N days')); ?>)</td>
	<td align="center"><span id="ps_total_visits"><?php echo $ps_stats['stats']['visits_total']; ?></span></td>
	<td align="center"><span id="ps_total_hits"><?php echo $ps_stats['stats']['hits_total']; ?></span></td>
	</tr>
	
	<tr>
	<td align="center"><strong><?php echo ps_tr('Max Online'); ?></strong></td>
	<td align="center" colspan="2"><span id="ps_max_online"><?php echo $ps_stats['stats']['max_online'] ?></span><br />(<span id="ps_max_online_time"><?php echo $ps_stats['stats']['max_online_time'] ?></span>)</td>
	</tr>
	
	</table>
	</div>
	
	</td>
	<td valign="top" style="padding-left: 20px;">
	
	<div id="ps_browsers">
	<table class="ps_table" cellpadding="4" cellspacing="1">
	<tr>
	<th width="209"><?php echo ps_tr('Top Browsers'); ?></th>
	<th width="60">%</th>
	</tr>
	
<?php
	foreach ($ps_stats['browsers'] as $b) {
?>

		<tr>
		<td><?php echo $b['name']; ?></td>
		<td align="center"><?php echo $b['num']; ?></td>
		</tr>

<?php
	}
?>
		
	<tr>
	<td colspan="2" align="center" class="ps_td_alt"><a href="javascript: ps_changeView('browsers');"><span id="ps_extended_browsers"><?php echo ps_tr('View Extended'); ?></span></a></td>
	</tr>
	
	</table>	
	</div>
	
	<div id="ps_platforms">
	<table class="ps_table" cellpadding="4" cellspacing="1" style="margin-top: 20px;">
	<tr>
	<th width="209"><?php echo ps_tr('Top Platforms'); ?></th>
	<th width="60">%</th>
	</tr>
	
<?php
	foreach ($ps_stats['platforms'] as $p) {
?>

		<tr>
		<td><?php echo $p['name']; ?></td>
		<td align="center"><?php echo $p['num']; ?></td>
		</tr>
			
<?php
	}
?>
	
	</table>
	</div>
	
	</td>
	</tr>
	</table>
	
	<div id="ps_search">
	<table class="ps_table" cellpadding="4" cellspacing="1" align="center" style="margin-top: 30px;">
	<tr>
	<th><?php echo str_replace('N',$ps_num_search_terms,ps_tr('Last N Search Terms')); ?></th>
	</tr>
	
<?php
	if ($ps_stats['search']) {
?>
	
		<tr>
		<td>
		<table class="ps_subtable" cellpadding="4" cellspacing="0">
	
<?php
		foreach ($ps_stats['search'] as $search_term) {
			$search_term_url = $search_term['url'];
			$search_term_str = (strlen($search_term['str']) > 80) ? substr_replace($search_term['str'],"...",80) : $search_term['str'];
?>

			<tr>
			<td><a href="<?php echo $search_term_url; ?>"><?php echo $search_term_str; ?></a></td>
			</tr>
			
<?php
		}
?>
	
	</table>
	</td>
	</tr>
	
<?php
	}
?>
	
	</table>
	</div>
	
	<div id="ps_referers">
	<table class="ps_table" cellpadding="4" cellspacing="1" align="center" style="margin-top: 30px;">
	<tr>
	<th><?php echo str_replace('N',$ps_num_referers,ps_tr('Last N Referers')); ?></th>
	</tr>
	
<?php
	if ($ps_stats['referers']) {
?>
	
		<tr>
		<td>
		<table class="ps_subtable" cellpadding="4" cellspacing="0">
	
<?php
		foreach ($ps_stats['referers'] as $referer) {
			$referer_url = $referer;
			$referer_str = (strlen($referer_url) > 80) ? substr_replace($referer_url,"...",80) : $referer_url;
?>

			<tr>
			<td><a href="<?php echo $referer_url; ?>"><?php echo $referer_str; ?></a></td>
			</tr>
			
<?php
		}
?>
	
	</table>
	</td>
	</tr>
	
<?php
	}
?>
	
	</table>
    </center>
	</div>
	<!-- End Data -->
	
	<p align="center" style="margin-top: 40px;"><a href="<?php echo wp_nonce_url('index.php?page='.basename(__FILE__).'&amp;ps_action=reset','reset-stats'); ?>" onclick="return confirm('<?php echo ps_tr('You are about to delete all data and reset stats. OK to delete, Cancel to stop'); ?>');">&gt;&gt; <?php echo ps_tr('Reset Stats'); ?> &lt;&lt;</a></p>
	
	<p align="center" style="margin-top: 30px;">
	<a href="http://hyanetworks.com/wordpress/2010/03/03/mi-version-del-popstats/">PopStats <?php echo PS_VERSION; ?></a>
	<br /><a href="http://hyanetworks.com/">Víctor Martinez(oxigen)</a>
	</p>
		
	</div>
	
	</div>

<?php
}

function ps_addAdminMenu() {
	global $ps_user_level;
	
	add_submenu_page('index.php','PopStats','PopStats',$ps_user_level,__FILE__,'ps_displayStats');
}

function ps_javascript() {
	global $ps_ajax_refresh_time,$ps_vgraphic,$ps_hgraphic,$ps_num_search_terms,$ps_num_referers;
?>

	<script type="text/javascript">
	var ajax_enabled = false;
	var display_online = false;
	var display_browsers = false;
	var display_vgraphic = <?php echo ($ps_vgraphic==1) ? "true" : "false"; ?>;
	var display_hgraphic = <?php echo ($ps_hgraphic==1) ? "true" : "false"; ?>;
	
	/* Window OnLoad Event */
	function windowLoad() {
		document.getElementById('ps_online').innerHTML = '';
	}
	
	if (window.addEventListener)
		window.addEventListener("load",windowLoad,true);
	else if (window.attachEvent)
		window.attachEvent("onload",windowLoad);
	else
		window.onload = windowLoad;
	/**/
	
	function ps_writeStats(response) {
		update = response.getElementsByTagName('update')[0].firstChild.data;
		if (update == 1) {
			document.getElementById('ps_b_ajax').style.color = '#00FF00';

			// Last refresh time
			d = response.getElementsByTagName('d')[0].firstChild.data;
			
			// Visits/Hits
			buffer = '';
			buffer += '<table class="ps_table" cellpadding="4" cellspacing="1">';
			buffer += '<tr>';
			buffer += '<th width="100"><?php echo ps_tr('Day'); ?></th>';
			buffer += '<th width="80"><?php echo ps_tr('Visits'); ?></th>';
			buffer += '<th width="80"><?php echo ps_tr('Hits'); ?></th>';
			buffer += '</tr>';
			rows = response.getElementsByTagName('table_days')[0].getElementsByTagName('row');
			for (i=0; i<rows.length; i++) {
				buffer += '<tr>';
				buffer += '<td align="center"><strong>' + rows[i].getElementsByTagName('day')[0].firstChild.data + '</strong></td>';
				buffer += '<td align="center">' + rows[i].getElementsByTagName('visits')[0].firstChild.data + '</td>';
				buffer += '<td align="center">' + rows[i].getElementsByTagName('hits')[0].firstChild.data + '</td>';
				buffer += '</tr>';
			}
			buffer += '</table>';
			document.getElementById('ps_table_days').innerHTML = buffer;
			
			// Stats
			document.getElementById('ps_average_visits').innerHTML = response.getElementsByTagName('average_visits')[0].firstChild.data;
			document.getElementById('ps_average_hits').innerHTML = response.getElementsByTagName('average_hits')[0].firstChild.data;
			document.getElementById('ps_max_visits').innerHTML = response.getElementsByTagName('max_visits')[0].firstChild.data;
			document.getElementById('ps_max_hits').innerHTML = response.getElementsByTagName('max_hits')[0].firstChild.data;
			document.getElementById('ps_max_visits_time').innerHTML = response.getElementsByTagName('max_visits_time')[0].firstChild.data;
			document.getElementById('ps_max_hits_time').innerHTML = response.getElementsByTagName('max_hits_time')[0].firstChild.data;
			document.getElementById('ps_total_visits').innerHTML = response.getElementsByTagName('total_visits')[0].firstChild.data;
			document.getElementById('ps_total_hits').innerHTML = response.getElementsByTagName('total_hits')[0].firstChild.data;
			document.getElementById('ps_max_online').innerHTML = response.getElementsByTagName('max_online')[0].firstChild.data;
			document.getElementById('ps_max_online_time').innerHTML = response.getElementsByTagName('max_online_time')[0].firstChild.data;
			
			// Online
			document.getElementById('ps_people_online').innerHTML = response.getElementsByTagName('people_online')[0].firstChild.data;
			if (display_online) {
				buffer = '';
				buffer += '<table class="ps_table" cellpadding="4" cellspacing="1" style="margin-top: 20px;">';
				buffer += '<tr>';
				buffer += '<th width="200"><?php echo ps_tr('IP'); ?></th>';
				buffer += '<th width="69"><?php echo ps_tr('URL'); ?></th>';
				buffer += '</tr>';
				online_visits = response.getElementsByTagName('online_visits')[0].getElementsByTagName('online');
				for (i=0; i<online_visits.length; i++) {
					buffer += '<tr>';
					if (online_visits[i].getElementsByTagName('url')[0].firstChild.data.indexOf('wp-admin')!=-1)
						buffer += '<td><strong>' + online_visits[i].getElementsByTagName('ip')[0].firstChild.data + '</strong></td>';
					else
						buffer += '<td>' + online_visits[i].getElementsByTagName('ip')[0].firstChild.data + '</td>';
					buffer += '<td align="center"><a href="' + online_visits[i].getElementsByTagName('url')[0].firstChild.data + '">&gt;&gt;</a></td>';
					buffer += '</tr>';
				}
				buffer += '</table>';
				document.getElementById('ps_online').innerHTML = buffer;
			}
			else
				document.getElementById('ps_online').innerHTML = '';
				
			// Graphics
			if (display_vgraphic) {
				buffer = '';
				buffer += '<table class="ps_table" cellpadding="4" cellspacing="1">';
				buffer += '<tr>';
				buffer += '<th><?php echo ps_tr('Visits'); ?></th>';
				buffer += '</tr>';
				buffer += '<tr>';
				buffer += '<td>';
				buffer += '<table cellpadding="1" cellspacing="0" style="height: 120px;">';
				buffer += '<tr>';
				rows = response.getElementsByTagName('table_days')[0].getElementsByTagName('row');
				for (i=rows.length-1; i>=0; i--)
					buffer += '<td valign="bottom"><div class="ps_statbar" title="' + rows[i].getElementsByTagName('day')[0].firstChild.data + ': ' + rows[i].getElementsByTagName('visits')[0].firstChild.data + '" style="width: 10px; height: ' + rows[i].getElementsByTagName('vgheight')[0].firstChild.data + 'px;">&nbsp;</div></td>';
				buffer += '<td valign="bottom" style="padding-left: 10px;"><div class="ps_statbar_av" title="<?php echo ps_tr('Average'); ?>: ' + response.getElementsByTagName('average_visits')[0].firstChild.data + '" style="width: 10px; height: ' + response.getElementsByTagName('average_vgheight')[0].firstChild.data + 'px;">&nbsp;</div></td>';
				buffer += '</tr>';
				buffer += '</table>';
				buffer += '</td>';
				buffer += '</tr>';
				buffer += '</table>';
				document.getElementById('ps_vgraphic').innerHTML = buffer;
			}
			if (display_hgraphic) {
				buffer = '';
				buffer += '<table class="ps_table" cellpadding="4" cellspacing="1">';
				buffer += '<tr>';
				buffer += '<th><?php echo ps_tr('Hits'); ?></th>';
				buffer += '</tr>';
				buffer += '<tr>';
				buffer += '<td>';
				buffer += '<table cellpadding="1" cellspacing="0" style="height: 120px;">';
				buffer += '<tr>';
				rows = response.getElementsByTagName('table_days')[0].getElementsByTagName('row');
				for (i=rows.length-1; i>=0; i--)
					buffer += '<td valign="bottom"><div class="ps_statbar" title="' + rows[i].getElementsByTagName('day')[0].firstChild.data + ': ' + rows[i].getElementsByTagName('hits')[0].firstChild.data + '" style="width: 10px; height: ' + rows[i].getElementsByTagName('hgheight')[0].firstChild.data + 'px;">&nbsp;</div></td>';
				buffer += '<td valign="bottom" style="padding-left: 10px;"><div class="ps_statbar_av" title="<?php echo ps_tr('Average'); ?>: ' + response.getElementsByTagName('average_hits')[0].firstChild.data + '" style="width: 10px; height: ' + response.getElementsByTagName('average_hgheight')[0].firstChild.data + 'px;">&nbsp;</div></td>';
				buffer += '</tr>';
				buffer += '</table>';
				buffer += '</td>';
				buffer += '</tr>';
				buffer += '</table>';
				document.getElementById('ps_hgraphic').innerHTML = buffer;
			}
			
			// Browsers
			buffer = '';
			buffer += '<table class="ps_table" cellpadding="4" cellspacing="1">';
			buffer += '<tr>';
			buffer += '<th width="209"><?php echo ps_tr('Top Browsers'); ?></th>';
			buffer += '<th width="60">%</th>';
			buffer += '</tr>';
			browsers = response.getElementsByTagName('browsers')[0].getElementsByTagName('browser');
			for (i=0; i<browsers.length; i++) {
				if (display_browsers) {
					buffer += '<tr class="ps_tr_alt">';
					buffer += '<td><strong>' + browsers[i].getElementsByTagName('name')[0].firstChild.data + '</strong></td>';
					buffer += '<td align="center"><strong>' + browsers[i].getElementsByTagName('num')[0].firstChild.data + '</strong></td>';
				}
				else {
					buffer += '<tr>';
					buffer += '<td>' + browsers[i].getElementsByTagName('name')[0].firstChild.data + '</td>';
					buffer += '<td align="center">' + browsers[i].getElementsByTagName('num')[0].firstChild.data + '</td>';
				}
				buffer += '</tr>';
				versions = browsers[i].getElementsByTagName('version');
				if (display_browsers && versions.length>0) {
					buffer += '<tr>';
					buffer += '<td colspan="2">';
					buffer += '<table class="ps_subtable" cellpadding="2" cellspacing="0" style="margin-left: 20px;">';
					for (j=0; j<versions.length; j++) {
						buffer += '<tr>';
						buffer += '<td width="200">' + versions[j].getElementsByTagName('name')[0].firstChild.data + '</td>';
						buffer += '<td width="44" align="center">' + versions[j].getElementsByTagName('num')[0].firstChild.data + '</td>';
						buffer += '</tr>';
					}
					buffer += '</table>';
					buffer += '</td>';
					buffer += '</tr>';
				}
			}
			buffer += '<tr>';
			buffer += '<td colspan="2" align="center" class="ps_td_alt"><a href="javascript: ps_changeView(\'browsers\');"><span id="ps_extended_browsers">' + document.getElementById('ps_extended_browsers').innerHTML + '</span></a></td>';
			buffer += '</tr>';
			buffer += '</table>';
			document.getElementById('ps_browsers').innerHTML = buffer;
			
			// Platforms
			buffer = '';
			buffer += '<table class="ps_table" cellpadding="4" cellspacing="1" style="margin-top: 20px;">';
			buffer += '<tr>';
			buffer += '<th width="209"><?php echo ps_tr('Top Platforms'); ?></th>';
			buffer += '<th width="60">%</th>';
			buffer += '</tr>';
			platforms = response.getElementsByTagName('platforms')[0].getElementsByTagName('platform');
			for (i=0; i<platforms.length; i++) {
				buffer += '<tr>';
				buffer += '<td>' + platforms[i].getElementsByTagName('name')[0].firstChild.data + '</td>';
				buffer += '<td align="center">' + platforms[i].getElementsByTagName('num')[0].firstChild.data + '</td>';
				buffer += '</tr>';
			}
			// ?¿?
			buffer += '<tr>';
			buffer += '<td colspan="2" class="ps_td_alt">&nbsp;</td>';
			buffer += '</tr>';
			
			buffer += '</table>';
			document.getElementById('ps_platforms').innerHTML = buffer;
			
			// Search Terms
			buffer = '';
			buffer += '<table class="ps_table" cellpadding="4" cellspacing="1" align="center" style="margin-top: 20px;">';
			buffer += '<tr>';
			buffer += '<th width="278"><?php echo str_replace('N',$ps_num_search_terms,ps_tr('Last N Search Terms')); ?></th>';
			buffer += '</tr>';
			
			terms_url = response.getElementsByTagName('search_terms')[0].getElementsByTagName('terms_url');
			terms_str = response.getElementsByTagName('search_terms')[0].getElementsByTagName('terms_str');
			if (terms_url.length>0) {
				buffer += '<tr>';
				buffer += '<td>';
				buffer += '<table class="ps_subtable" cellpadding="4" cellspacing="0">';
				for (i=0; i<terms_url.length; i++) {
					buffer += '<tr>';
					buffer += '<td><a href="' + terms_url[i].firstChild.data + '">' + terms_str[i].firstChild.data + '</a></td>';
					buffer += '</tr>';
				}
				buffer += '</table>';
				buffer += '</td>';
				buffer += '</tr>';
			}
			
			buffer += '</table>';
			document.getElementById('ps_search').innerHTML = buffer;
			
			// Referers
			buffer = '';
			buffer += '<table class="ps_table" cellpadding="4" cellspacing="1" align="center" style="margin-top: 20px;">';
			buffer += '<tr>';
			buffer += '<th width="278"><?php echo str_replace('N',$ps_num_referers,ps_tr('Last N Referers')); ?></th>';
			buffer += '</tr>';
			
			referers_url = response.getElementsByTagName('referers')[0].getElementsByTagName('referer_url');
			referers_str = response.getElementsByTagName('referers')[0].getElementsByTagName('referer_str');
			if (referers_url.length>0) {
				buffer += '<tr>';
				buffer += '<td>';
				buffer += '<table class="ps_subtable" cellpadding="4" cellspacing="0">';
				for (i=0; i<referers_url.length; i++) {
					buffer += '<tr>';
					buffer += '<td><a href="' + referers_url[i].firstChild.data + '">' + referers_str[i].firstChild.data + '</a></td>';
					buffer += '</tr>';
				}
				buffer += '</table>';
				buffer += '</td>';
				buffer += '</tr>';
			}
			
			buffer += '</table>';
			document.getElementById('ps_referers').innerHTML = buffer;
		}

		document.getElementById('ps_time').innerHTML = '<strong><?php echo ps_tr('Last Update'); ?>:</strong> ' + response.getElementsByTagName('time')[0].firstChild.data;
		
		document.getElementById('ps_b_ajax').style.color = '#000000';
	}
	
	var ps_req;
	var d = <?php echo ps_time(); ?>;
	var timeout;

	function ps_loadXMLDoc(url,method) {
		// branch for native XMLHttpRequest object
		if (window.XMLHttpRequest) {
			ps_req = new XMLHttpRequest();
			ps_req.onreadystatechange = ps_processReqChange;
			ps_req.open(method,url,true);
			ps_req.send(null);
		}
		// branch for IE/Windows ActiveX version
		else if (window.ActiveXObject) {
			ps_req = new ActiveXObject("Microsoft.XMLHTTP");
			if (ps_req) {
				ps_req.onreadystatechange = ps_processReqChange;
				ps_req.open(method,url,true);
				ps_req.send();
			}
		}
	}
	
	function ps_processReqChange() {
		if (ps_req.readyState == 4) {
			if (ps_req.status == 200) {
				response = ps_req.responseXML.documentElement;
				ps_writeStats(response);
			}
		}
	}
	
	function ps_reqData(f) {
		if (!ps_req || ps_req.readyState==4) { // If Object is not running
			document.getElementById('ps_b_ajax').style.color = '#FF0000';
		
			url = 'index.php?page=wp-popstats.php&ps_action=ajax_all&f=' + f + '&d=' + d;
			ps_loadXMLDoc(url,'GET');
		}
	
		if (ajax_enabled && f==0)
			timeout = setTimeout('ps_reqData(0)',<?php echo $ps_ajax_refresh_time; ?>000);
	}
	
	function ps_changeAjaxStatus() {
		if (ajax_enabled) {
			ajax_enabled = false;
			document.getElementById('ps_b_ajax').value = '<?php echo ps_tr('Animate Stats'); ?>';
			clearTimeout(timeout);
		}
		else {
			ajax_enabled = true;
			ps_reqData(0);
			document.getElementById('ps_b_ajax').value = '<?php echo ps_tr('Stop Animation'); ?>';
		}
	}
	
	function ps_changeView(src) {
		ps_reqData(1);
		
		if (src == 'online') {
			if (display_online) {
				display_online = false;
				document.getElementById('ps_extended_online').innerHTML = '<?php echo ps_tr('View Extended'); ?>';
			}
			else {
				display_online = true;
				document.getElementById('ps_extended_online').innerHTML = '<?php echo ps_tr('View General'); ?>';
			}
		}
		if (src == 'browsers') {
			if (display_browsers) {
				display_browsers = false;
				document.getElementById('ps_extended_browsers').innerHTML = '<?php echo ps_tr('View Extended'); ?>';
			}
			else {
				display_browsers = true;
				document.getElementById('ps_extended_browsers').innerHTML = '<?php echo ps_tr('View General'); ?>';
			}
		}
	}
	</script>

<?php
}

function ps_cssAdmin() {
?>
	
	<style type="text/css">
	#ps_popstats {
		font-size: 13px;
		color: #000;
		font-family:"Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
	}
	#ps_popstats th {
		background:url(images/fav.png) repeat-x;
		height:32px;
		text-shadow:0 -1px 0 rgba(0, 0, 0, 0.4);
		
	}
	#ps_popstats td {
		
	}
	#ps_popstats a {
		border-width: 0px;
		color: #333;
		text-decoration: none;
	}	
	#ps_popstats a:hover {
		border-width: 0px;
		color: #333;
		text-decoration: underline;
	}
	#ps_time {
		padding-top: 10px;
	}
	
	.ps_table {
		background-color: #666;
	}
	.ps_table td {
		background-color: #FFF;
	}
	.ps_table th {
		background-color: #5292CC;
		color: #FFF;
	}
	.ps_table .center {
		text-align: center;
	}
	.ps_table .ps_td_alt {
		background-color: #E3E3E3;
	}
	.ps_table .ps_tr_alt td {
		background-color: #C3C3C3;
	}
	.ps_subtable {
		background-color: #FFF;
	}
	.ps_subtable td {
		background-color: #FFF;
	}
	
	.ps_statbar {
		margin: 0;
		padding: 0;
		background-color: #336CA1;
		border-bottom: 1px solid #336CA1;
		font-size: 0px;
		line-height: 0px;
	}
	.ps_statbar_av {
		margin: 0;
		padding: 0;
		background-color: #5292CC;
		border-bottom: 1px solid #5292CC;
		font-size: 0px;
		line-height: 0px;
	}
	</style>
	
<?php
}
/*****/

/***** Program Flow *****/
/* Create tables in the data base or upgrade if necessary (installation) */
ps_createDB();
ps_updateVersion();
/**/

/* Process Special Requests */
if (eregi('wp-popstats.php',$_GET["page"])) {
	if ($_GET['ps_action'] == 'ajax_all') {
		ps_getStatsAjax($_GET['f'],$_GET['d']);
		exit;
	}
	if ($_GET['ps_action'] == 'reset') {
		if (file_exists(ABSPATH.'wp-includes/pluggable.php'))
			require_once (ABSPATH.'wp-includes/pluggable.php');
		else
			require_once (ABSPATH.'wp-includes/pluggable-functions.php');
		
		check_admin_referer('reset-stats');
		ps_resetDB();
	}
}
/**/

/* Set Functions */
add_action('init','ps_setStats');
add_action('admin_menu','ps_addAdminMenu');
if (eregi('wp-popstats.php',$_GET["page"])) {
	add_action('admin_head','ps_javascript');
	add_action('admin_head','ps_cssAdmin');
}
/**/
/*****/
?>