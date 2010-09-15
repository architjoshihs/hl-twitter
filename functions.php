<?php if(!HL_TWITTER_LOADED) die('Direct script access denied.');


/*
	Run cron job
*/
function hl_twitter_init() {
	if($_GET['hl_twitter_cron']!='' and $_GET['hl_twitter_cron']==HL_TWITTER_CRON_KEY) {
		echo '<h1>HL Twitter</h1><hr />';
		$result = hl_twitter_import();
		
		if($result['status']=='success') {
			echo '<p>Import completed successfully.</p>';
		} else {
			echo '<p>One or more errors were encountered while importing.';
		}
		
		echo '<ul><li>';
			echo implode('</li><li>', $result['lines']);
		echo '</li></ul>';
		
		die();
	}
} // end func: hl_twitter_init



/*
	Returns true if User has set up Twitter OAuth connection details
*/
function hl_twitter_is_oauth_verified() {
	return (get_option(HL_TWITTER_OAUTH_TOKEN)!='')?true:false;
} // end func: hl_twitter_is_oauth_verified




/*
	Returns a single instance of the Twitter OAuth interface
*/
function hl_twitter_get_api() {
	static $hl_twitter_api;
	if(!$hl_twitter_api) {
		$token = get_option(HL_TWITTER_OAUTH_TOKEN);
		if(!$token) return false;
		$token_secret = get_option(HL_TWITTER_OAUTH_TOKEN_SECRET);
		$hl_twitter_api = new EpiTwitter(HL_TWITTER_OAUTH_CONSUMER_KEY, HL_TWITTER_OAUTH_CONSUMER_SECRET, $token, $token_secret);
	}
	return $hl_twitter_api;
} // end func: hl_twitter_get_api




/*
	Posts a new Tweet to twitter
*/
function hl_twitter_tweet($tweet) {
	$tweet = stripslashes(trim($tweet));
	if($tweet=='' or strlen($tweet)>140) return false;
	$api = hl_twitter_get_api();
	if(!$api) return false;
	$max_retries = 3;
	while($max_retries>=0) {
		try {
			$response = $api->post('/statuses/update.json', array('status'=>$tweet));
			if($response->code==200) return true;
		} catch(Exception $e) {}
		$max_retries--;
	}
	return false;
} // end func: hl_twitter_tweet



/*
	Generates the string to be genereated for a post
*/
function hl_twitter_generate_post_tweet_text($post_id) {
	
	$prior_auto_tweet = get_post_meta($post_id, HL_TWITTER_AUTO_TWEET_POSTMETA, true);
	if($prior_auto_tweet!='') return $prior_auto_tweet;
	
	$tweet_format = get_option(HL_TWITTER_TWEET_FORMAT, 'I just posted %title%, read it here: %shortlink%');
	
	$search = array('%title%', '%shortlink%', '%permalink%');
	$post_title = get_the_title($post_id);
	$post_permalink = get_permalink($post_id);
	$post_shortlink = wp_get_shortlink($post_id);
	if($post_shortlink=='') $post_shortlink = $post_permalink;
	$replace = array($post_title, $post_shortlink, $post_permalink);
	
	$tweet = str_replace($search, $replace, $tweet_format);
	
	return $tweet;
	
} // end func: hl_twitter_generate_post_tweet_text


/*
	Returns a 48x48 (max) avatar image URL and caches locally
*/
function hl_twitter_get_avatar($url) {
	if($url=='') return false;
	
	$hash = md5($url);
	$ext = substr($url, strrpos($url,'.'));
	$file = 'avatars/'.$hash.$ext;
	
	if(file_exists(HL_TWITTER_DIR.$file) and filemtime(HL_TWITTER_DIR.$file)+HL_TWITTER_AVATAR_CACHE_TTL >= time()) {
		return HL_TWITTER_URL.$file;
	}
	
	try {
		$original_file = HL_TWITTER_DIR.'avatars/'.$hash.$ext;
		
		$resp = wp_remote_get($url);
		if($resp['response']['code']!=200) return $url;
		
		$result = wp_mkdir_p(HL_TWITTER_DIR.'avatars/');
		if(!$result) return $url;
		
		$result = @file_put_contents($original_file, $resp['body']);
		if(!$result) return $url;
		
		$result = image_resize($original_file, 48, 48, false, '48', 90);
		if($result) return HL_TWITTER_URL.$file;
		
		return $url;
		
	} catch(Exception $e) {
		return $url;
	}
	
} // end func: hl_twitter_get_avatar


/*
	Returns a tweet with all links, hashtags and usernames converted to links
*/
function hl_twitter_show_tweet($tweet) {
	$tweet = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t< ]*)#", "\\1<a href=\"\\2\">\\2</a>", $tweet);
	$tweet = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r< ]*)#", "\\1<a href=\"http://\\2\">\\2</a>", $tweet);
	$tweet = preg_replace("/@(\w+)/", "<a href=\"http://www.twitter.com/\\1\">@\\1</a>", $tweet);
	$tweet = preg_replace("/#(\w+)/", "<a href=\"http://search.twitter.com/search?q=\\1\">#\\1</a>", $tweet);
	return $tweet;
} // end func: hl_twitter_show_tweet




/*
	Called by the internal WordPress Event Scheduler
*/
function hl_twitter_cron_handler() {
	hl_twitter_import();
} // end func: hl_twitter_cron_handler


/*
	Add custom time intervals
*/
function hl_twitter_cron_schedules($schedules) {
	$schedules['hl_10mins'] = array(
		'interval'=> 600,
		'display'=>  __('every 10 minutes')
	);
	$schedules['hl_15mins'] = array(
		'interval'=> 900,
		'display'=>  __('every 15 minutes')
	);
	$schedules['hl_30mins'] = array(
		'interval'=> 1800,
		'display'=>  __('every 30 minutes')
	);
	$schedules['hl_1hr'] = array(
		'interval'=> 3600,
		'display'=>  __('every hour')
	);
	$schedules['hl_3hrs'] = array(
		'interval'=> 10800,
		'display'=>  __('every 3 hours')
	);
	$schedules['hl_12hrs'] = array(
		'interval'=> 43200,
		'display'=>  __('every 12 hours')
	);
	$schedules['hl_24hrs'] = array(
		'interval'=> 86400,
		'display'=>  __('every 24 hours')
	);
	return $schedules;
} // end func: hl_twitter_cron_schedules



/*
	On install...
*/
function hl_twitter_install() {
	global $wpdb, $table_prefix;
	
	wp_schedule_event(time(), 'hl_1hr', HL_TWITTER_SCHEDULED_EVENT_ACTION); # Add cron event handler
	update_option(HL_TWITTER_UPDATE_FREQUENCY, 'hl_1hr');
	
	/* Plugin Dial Home
	 * 
	 * This is a completely anonymous remote call to the original developers server.
	 * The only data tracked is an non-reversible hash of the site URL so duplicates
	 * aren't recorded. It is so installations can be shown on the plugin website.
	 */
	wp_remote_get('http://hybridlogic.co.uk/hl-plugin-activation.php?plugin=hl_twitter&hash='.md5(get_bloginfo('url')));
	
	$sql = "
		CREATE TABLE `".$table_prefix.HL_TWITTER_DB_PREFIX."replies` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`twitter_tweet_id` bigint(20) unsigned DEFAULT NULL,
			`twitter_user_id` int(11) DEFAULT NULL,
			`twitter_user_name` varchar(40) DEFAULT NULL,
			`twitter_user_screen_name` varchar(40) DEFAULT NULL,
			`twitter_user_url` varchar(255) DEFAULT NULL,
			`twitter_user_avatar` varchar(255) DEFAULT NULL,
			`tweet` varchar(160) DEFAULT NULL,
			`lat` double DEFAULT NULL,
			`lon` double DEFAULT NULL,
			`created` datetime DEFAULT NULL,
			`reply_tweet_id` bigint(20) unsigned DEFAULT NULL,
			`reply_user_id` int(11) DEFAULT NULL,
			`reply_screen_name` varchar(40) DEFAULT NULL,
			`source` varchar(40) DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `twitter_tweet_id` (`twitter_tweet_id`),
			FULLTEXT KEY `tweet` (`tweet`)
		);
	";
	$wpdb->query($sql);
	
	$sql = "
		CREATE TABLE `".$table_prefix.HL_TWITTER_DB_PREFIX."tweets` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`twitter_tweet_id` bigint(20) unsigned DEFAULT NULL,
			`twitter_user_id` int(11) DEFAULT NULL,
			`tweet` varchar(160) DEFAULT NULL,
			`lat` double DEFAULT NULL,
			`lon` double DEFAULT NULL,
			`created` datetime DEFAULT NULL,
			`reply_tweet_id` bigint(20) unsigned DEFAULT NULL,
			`reply_user_id` int(11) DEFAULT NULL,
			`reply_screen_name` varchar(40) DEFAULT NULL,
			`source` varchar(40) DEFAULT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `twitter_tweet_id` (`twitter_tweet_id`),
			FULLTEXT KEY `tweet` (`tweet`)
		);
	";
	$wpdb->query($sql);
	
	$sql = "
		CREATE TABLE `".$table_prefix.HL_TWITTER_DB_PREFIX."users` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`twitter_user_id` int(11) DEFAULT NULL,
			`screen_name` varchar(40) DEFAULT NULL,
			`name` varchar(40) DEFAULT NULL,
			`num_friends` int(11) DEFAULT NULL,
			`num_followers` int(11) DEFAULT NULL,
			`num_tweets` int(11) DEFAULT NULL,
			`registered` datetime DEFAULT NULL,
			`url` varchar(255) DEFAULT NULL,
			`description` varchar(255) DEFAULT NULL,
			`location` varchar(40) DEFAULT NULL,
			`avatar` varchar(255) DEFAULT NULL,
			`created` datetime DEFAULT NULL,
			`last_updated` datetime DEFAULT NULL,
			`pull_in_replies` tinyint(1) DEFAULT '0',
			PRIMARY KEY (`id`)
		);
	";
	$wpdb->query($sql);
	
} // end func: hl_twitter_install



/*
	On uninstall...
*/
function hl_twitter_uninstall() {
	# Leave tables alone, just in case
	wp_clear_scheduled_hook(HL_TWITTER_SCHEDULED_EVENT_ACTION); # Remove cron
} // end func: hl_twitter_uninstall




### COMMON FUNCTIONS ########################

/*
	Display time ago, seconds, minutes etc
*/
if(!function_exists('hl_time_ago')):
	function hl_time_ago($timestamp, $now=false) {
		if(!$now) $now = date_i18n('U');
		$then = (is_integer($timestamp))?$timestamp:strtotime($timestamp);
		$seconds = abs($now-$then);
		if($seconds<60) return $seconds.' '.hl_plural($seconds,'second');
		$minutes = round($seconds/60);
		if($minutes<60) return $minutes.' '.hl_plural($minutes,'minute');
		$hours = round($seconds/3600);
		if($hours<24) return $hours.' '.hl_plural($hours,'hour');
		$days = round($seconds/86400);
		if($days<7) return $days.' '.hl_plural($days,'day');
		$weeks = round($seconds/604800);
		if($weeks<=4) return $weeks.' '.hl_plural($weeks,'week');
		$months = round($seconds/2613600);
		if($months<=12) return $months.' '.hl_plural($months,'month');
		$years = round($seconds/31557600);
		return $years.' '.hl_plural($years,'year');
	} // end func: hl_time_ago
endif;


/*
	Pluralise a word if necessary
*/
if(!function_exists('hl_plural')):
	function hl_plural($num, $single, $plural=false) {
		$num = intval($num);
		if($num==1) return $single;
		if($plural) return $plural;
		return $single.'s';
	} // end func: hl_plural
endif;


/*
	Calculates percent, divbyzero check
*/
if(!function_exists('hl_percent')) {
	function hl_percent($numerator, $denominator=100) {
		if($denominator==0) return 0;
		return round($numerator/$denominator*100);
	} // end func: hl_percent
}


/*
  print_r() wrapped in <pre> tags
*/
if(!function_exists('hl_print_r')) {
	function hl_print_r() {
		echo '<pre>';
		foreach(func_get_args() as $arg) {
			print_r($arg);
		}
		echo '</pre>';
	} // end func: hl_print_r
}


/*
  var_dump() wrapped in <pre> tags
*/
if(!function_exists('hl_var_dump')) {
	function hl_var_dump() {
		echo '<pre>';
		foreach(func_get_args() as $arg) {
			var_dump($arg);
		}
		echo '</pre>';
	} // end func: hl_var_dump
}


/*
  Escapes output via htmlspecialchars
*/
if(!function_exists('hl_e')) {
	function hl_e($str) {
		return htmlspecialchars($str);
	} // end func: hl_e
}


/*
  Converts a string to a URL slug like format
  * Does not test for uniqueness  
*/
if(!function_exists('hl_slugify')) {
	function hl_slugify($str) {
		$str = strtolower(trim($str, '-'));
		$str = preg_replace('~[^\\pL\d]+~u', '-', $str);
		$str = preg_replace('~[^-\w]+~', '', $str);
		return $str;
	} // end func: hl_slugify
}
