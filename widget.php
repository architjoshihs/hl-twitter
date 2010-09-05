<?php if(!HL_TWITTER_LOADED) die('Direct script access denied.');

class hl_twitter_widget extends WP_Widget {
	
	
	function widget($args, $instance) {		
		global $wpdb;
		extract($args);
		
		$num_tweets = intval($instance['num_tweets']);
		if($num_tweets<=0) $num_tweets = 5;
		
		$user_id = intval($instance['user_id']);
		if($user_id>0) {
			$single_user = true;
			$user = $wpdb->get_row($wpdb->prepare('
				SELECT twitter_user_id, screen_name, name, num_friends, num_followers, num_tweets, registered, url, description, location, avatar
				FROM '.HL_TWITTER_DB_PREFIX.'users 
				WHERE twitter_user_id=%d 
				LIMIT 1
			', $user_id));
			$sql = $wpdb->prepare('
				SELECT
					t.twitter_tweet_id, t.tweet, t.lat, t.lon, t.created, t.reply_tweet_id, t.reply_screen_name, t.source,
					u.screen_name, u.name, u.avatar
				FROM '.HL_TWITTER_DB_PREFIX.'tweets AS t
				JOIN '.HL_TWITTER_DB_PREFIX.'users AS u ON t.twitter_user_id = u.twitter_user_id
				WHERE t.twitter_user_id=%d
				ORDER BY t.created DESC
				LIMIT 0, %d
			', $user_id, $num_tweets);
		} else {
			$single_user = false;
			$sql = $wpdb->prepare('
				SELECT
					t.twitter_tweet_id, t.tweet, t.lat, t.lon, t.created, t.reply_tweet_id, t.reply_screen_name, t.source,
					u.screen_name, u.name, u.avatar
				FROM '.HL_TWITTER_DB_PREFIX.'tweets AS t
				JOIN '.HL_TWITTER_DB_PREFIX.'users AS u ON t.twitter_user_id = u.twitter_user_id
				ORDER BY t.created DESC
				LIMIT 0, %d
			', $num_tweets);
		}
		
		$tweets = $wpdb->get_results($sql);
		$num_tweets = $wpdb->num_rows;
		
		$current_template_directory = get_template_directory();
		if(file_exists($current_template_directory.'/hl_twitter_widget.php')) {
			include $current_template_directory.'/hl_twitter_widget.php';
		} else {
			include HL_TWITTER_DIR.'/hl_twitter_widget.php';
		}
		
	} // end func: widget
	
	
	function __construct() {
		parent::__construct(false, $name = 'Recent Tweets', array('description'=>'Shows a list of recent tweets on your website.'));	
	} // end func: __construct
	
	
	function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		$instance['num_tweets'] = intval($new_instance['num_tweets']);
		$instance['user_id'] = intval($new_instance['user_id']);
		return $instance;
	} // end func: update
	
	
	function form($instance) {
		global $wpdb;
		$users = $wpdb->get_results('SELECT twitter_user_id, screen_name FROM '.HL_TWITTER_DB_PREFIX.'users ORDER BY screen_name ASC');
		$poss_num_tweets = range(1,10);
		
		$user_id = intval(esc_attr($instance['user_id']));
		$num_tweets = intval(esc_attr($instance['num_tweets']));
		?>
		
		<p>
			<label for="<?php echo $this->get_field_id('user_id'); ?>"><?php _e('User'); ?></label><br />
			<select id="<?php echo $this->get_field_id('user_id'); ?>" name="<?php echo $this->get_field_name('user_id'); ?>">
				<option value="0">All users</option>
				<?php foreach($users as $user): ?>
					<option value="<?php echo $user->twitter_user_id; ?>" <?php if($user->twitter_user_id==$user_id) echo 'selected="selected"'; ?>><?php echo hl_e($user->screen_name); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('num_tweets'); ?>"><?php _e('Tweets to show'); ?></label><br />
			<select id="<?php echo $this->get_field_id('num_tweets'); ?>" name="<?php echo $this->get_field_name('num_tweets'); ?>">
				<?php foreach($poss_num_tweets as $num): ?>
					<option value="<?php echo $num; ?>" <?php if($num_tweets==$num) echo 'selected="selected"'; ?>><?php echo $num; ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		
		<?php 
	} // end func: form
	
} // end class: hl_twitter_widget