<?php
/*
Plugin Name: Cleantalk. Spam protect
Plugin URI: http://cleantalk.ru/wordpress
Description: Plugin for automoderation and spam protection. It use several tests to stop spam. Like, 1) Blacklists with over 9 billions records, 2) Compare comment with posts on blog, 3) Javascript availability, 4) Comment submit time. Cleantalk plugin dramatically reduce spam activity at your blog.
Version: 1.1.2
Author: Сleantalk team
Author URI: http://cleantalk.ru
*/

// Сleantalk team (shagimuratov@cleantalk.ru), coder Alexey Znaev (znaeff@mail.ru)

add_action('init', 'ct_init_locale');
add_action('delete_comment', 'ct_delete_comment_meta');				// param - comment ID
add_action('comment_form', 'ct_add_hidden_fields');
add_action('parse_request', 'ct_set_session');
add_filter('preprocess_comment', 'ct_check');					// param - comment data array

if(is_admin()){ 
    add_action('admin_init', 'ct_admin_init');
    add_action('admin_menu', 'ct_admin_add_page');
    add_action('admin_enqueue_scripts', 'ct_enqueue_scripts');
    add_action('comment_unapproved_to_approved', 'ct_comment_approved');	// param - comment object
    add_action('comment_unapproved_to_spam', 'ct_comment_spam');		// param - comment object
    add_action('comment_approved_to_spam', 'ct_comment_spam');			// param - comment object
    add_action('trash_comment', 'ct_comment_trash');				// param - comment ID
    add_filter('get_comment_text', 'ct_get_comment_text');			// param - current comment text
}

/**
* Inner function - Cleantalk instance
* @return 	&Cleantalk Cleantalk instance
*/
function ct_get_instance(){
    /**
    * Cleantalk instance
    */
    static $CT;

    if(!isset($CT)){
	require_once('cleantalk.class.php');

	$options = ct_get_options();

	$CT = new Cleantalk(array(
		'auth_key' 	=> $options['apikey'],
		'response_lang' => $options['language'],
		'server_url' 	=> $options['server']
	));
    }
    
    return $CT;
}

/**
* Inner function - Current Cleantalk options
* @return 	mixed[] Array of options
*/
function ct_get_options(){
    $options = get_option('cleantalk_settings');
    if(!is_array($options)) $options = array();
    return array_merge(ct_def_options(), $options);
}

/**
* Inner function - Default Cleantalk options
* @return 	mixed[] Array of default options
*/
function ct_def_options(){
    return array(
	'stopwords'	=> '1',
	'allowlinks'	=> '0',
	'language'	=> 'en',
	'server'	=> 'http://moderate.cleantalk.ru',
	'apikey'	=> __('enter key', 'cleantalk')
    );
}

/**
* Inner function - Stores ang returns cleantalk hash of current comment
* @param	string New hash or NULL
* @return 	string New hash or current hash depending on parameter
*/
function ct_hash($new_hash = ''){
    /**
    * Current hash
    */
    static $hash;
    
    if(!empty($new_hash)){
	$hash = $new_hash;
    }
    return $hash;
}

/**
* Inner function - Interface to XML RPC server
* @param	string $mehod Method name
* @param	mixed[] $params Array of XML params
* @return 	mixed[] XML RPS server response
*/
function ct_send_request($method, $params){
    /**
    * Plugin version string for server
    */
    $ENGINE = 'wordpress-112';
    
    $CT = ct_get_instance();
    $options = ct_get_options();
	    
    switch($method){
		case 'check_message':
			$sender_id = $CT->getSenderId();
			$params_rpc = array(
				$params['message'],
				$params['base_text'],
				$options['apikey'],
				$ENGINE,
				$params['user_info'],
				$options['stopwords'],
				$options['language'],
				$params['session_ip'],
				$params['user_email'],
				$params['user_name'],
				$sender_id,
				$options['allowlinks'],
				$params['submit_time'],
				$params['checkjs']
			);
			break;
		case 'send_feedback':
			$feedback = array();
			foreach($params['moderate'] as $msgFeedback)
				$feedback[] = $msgFeedback['msg_hash'] . ':' . intval($msgFeedback['is_allow']);
			
			$feedback = implode(';', $feedback);
				
			$params_rpc = array(
				$CT->config['auth_key'],
				$feedback
			);
			break;
		default:
			$params_rpc = array();
			return NULL;
    }

    $plugin_url = $CT->config['server_url'];
    $config = get_option('cleantalk_server');
    $result = NULL;

    if((isset($config['ct_work_url']) && $config['ct_work_url'] !== '') && ($config['ct_server_changed'] + $config['ct_server_ttl'] > time())) {
	$result = $CT->xmlRequest2(
		$method,
		$params_rpc,
		$config['ct_work_url']
	);
    }

    if(!isset($result) || $result->faultCode()) {
            $matches = array();
	    preg_match("#^(http://|https://)([a-z\.\-0-9]+):?(\d*)$#i", $plugin_url, $matches);
	    $url_prefix = $matches[1];
	    $pool = $matches[2];
	    $port = $matches[3];
	    if(empty($url_prefix))
	        $url_prefix = 'http://';
	    if(empty($pool)){
        	$result = array(
                        'allow'=>0,
                        'comment'=>'Can\'t connect to cleantalk.ru',
                        'blacklisted'=>0,
            	);
	    }else{
	        foreach (dns_get_record($pool, DNS_A) as $server){
		    $server_host = gethostbyaddr($server['ip']);
		    $work_url = $url_prefix . $server_host;
		    if($server['host'] === 'localhost')
				$work_url = $url_prefix . $server['host'];
			
		    $work_url = ($port !== '') ? $work_url . ':' . $port : $work_url;

		    $result = $CT->xmlRequest2(
				$method,
				$params_rpc,
				$work_url	
		    );

		    if(!$result->faultCode()){
				update_option(
				    'cleantalk_server', 
				    array(
					'ct_work_url' => $work_url, 
					'ct_server_ttl' => $server['ttl'],
					'ct_server_changed' => time()
				    )
				);
				break;
		    }
		}
		if(!$result){
		    $result = $CT->xmlRequest2(
				$method,
				$params_rpc,
				$plugin_url
		    );
		}	
	    }
    }
	
    if(is_object($result) && !$result->faultCode()){
	$result = $result->value();
		
	if($method === 'check_message' && $sender_id == '' && isset($result['sender_id']))
		$CT->setSenderId($result['sender_id']);
    }else{
                $result = array(
                            'allow'=>0,
                            'comment'=>'Can\'t connect to cleantalk.ru',
                            'blacklisted'=>0,
            		);
    }

    return $result;
}

/**
 * Inner function - Sends the results of moderation and delete cleantalk resume
 * @param 	string $hash Cleantalk comment hash
 * @param 	string $message comment_content
 * @param 	int $allow flag good comment (1) or bad (0)
 * @return 	string comment_content w\o cleantalk resume
 */
function ct_feedback($hash, $message, $allow){
    $CT = ct_get_instance();
    if(empty($hash)){
	$hash = $CT->getCleantalkCommentHash($message);		//try to get hash from comment text
    }
    $resultMessage = $CT->delCleantalkComment($message);

    if(!empty($hash)){
    	$ctFbParams = array(
    		'moderate'=>array(
			array('msg_hash'=>$hash, 'is_allow'=>$allow),
		)
	);
	ct_send_request(
	    'send_feedback',
	    $ctFbParams
	);
    }

    return $resultMessage;
}

/**
 * Public action 'init' - Inits locale
 */
function ct_init_locale() {
    load_plugin_textdomain('cleantalk', FALSE, basename( dirname( __FILE__ ) ) . '/i18n');
}

/**
 * Public action 'delete_comment' - Deletes comment's meta before deleting comment
 * @param 	int $post_id Post ID, not used
 */
function ct_delete_comment_meta($comment_id) {
    delete_comment_meta($comment_id, 'ct_hash');
}

/**
 * Public action 'comment_form' - Adds hidden filed to define avaialbility of client's JavaScript
 * @param 	int $post_id Post ID, not used
 */
function ct_add_hidden_fields($post_id = 0) {
?>
<a target="__blank" href="http://cleantalk.ru/wordpress" style="font-size: 8pt;">
<b style="color: #009900;">Clean</b><b style="color: #777;">talk</b>
</a>
<input type="hidden" id="ct_checkjs" name="ct_checkjs" value="0">
<script type="text/javascript">
// <![CDATA[
    document.getElementById("ct_checkjs").value = 1;
// ]]>
</script>
<?php
}

/**
 * Public action 'parse_request' - Inits session value
 */
function ct_set_session(){
    // this action is called any time WP process GET request (shows any page)
    // this action is called AFTER wp-comments-post.php executing and AFTER ct_check calling so we can create new session value here
    // it can be any action between init and send_headers, see http://codex.wordpress.org/Plugin_API/Action_Reference
    session_name('cleantalksession');
    session_start();
    $_SESSION['formtime'] = time();
}

/**
 * Public filter 'preprocess_comment' - Checks comment by cleantalk server
 * @param 	mixed[] $comment Comment data array
 * @return 	mixed[] New data array of comment
 */
function ct_check($comment) {
    // this action is called just when WP process POST request (adds new comment)
    // this action is called by wp-comments-post.php
    // after processing WP makes redirect to post page with comment's form by GET request (see above)
    global $wpdb;
    $comment_post_id = $comment['comment_post_ID'];
        
    $post = get_post($comment_post_id);
    if($post !== NULL){
        $baseText = $post->post_title . '<br>' . $post->post_content;
    }else{
        $baseText = '';
    }

    $prevComments = $wpdb->get_col("SELECT comment_content FROM $wpdb->comments WHERE comment_post_ID = '$comment_post_id' AND comment_approved = 1 ORDER BY comment_id DESC LIMIT 10", 0);
    $prevComments = implode("\n\n", $prevComments);
    
    $checkjs = (int) substr($_POST['ct_checkjs'], 0, 1);
    if($checkjs !== 0 && $checkjs !== 1) 
	$checkjs = 0;

    session_name('cleantalksession');
    session_start();
    if(array_key_exists('formtime', $_SESSION)){
        $submit_time = time() - (int)$_SESSION['formtime'];
    }else{
        $submit_time = NULL;
    }

    $user_info = '';
    if(function_exists('json_encode')){
	$blog_lang = substr(get_locale(),0,2);
	$arr = array(
	    'cms_lang' => $blog_lang,
	    'REFFERRER' => $_SERVER['HTTP_REFERER'],
	    'USER_AGENT' => $_SERVER['HTTP_USER_AGENT']
	);
	$user_info = json_encode($arr);
	if($user_info === FALSE) $user_info = '';
    }

    $ctResponse = ct_send_request(
				'check_message', 
				array(
				    'base_text'=>($baseText . "\n\n\n\n" . $prevComments),
				    'user_name'=>$comment['comment_author'],
				    'user_email'=>$comment['comment_author_email'],
				    'message'=>$comment['comment_content'],
				    'user_info'=>$user_info,
				    'session_ip'=>preg_replace('/[^0-9.]/', '', $_SERVER['REMOTE_ADDR']),
				    'checkjs'=>$checkjs,
				    'submit_time'=>$submit_time
				) 
    );
	    
    if(!empty($ctResponse) && is_array($ctResponse)){
	    if($ctResponse['blacklisted'] > 0){
			$err_text = '<center><b style="color: #009900;">Clean</b><b style="color: #777;">talk.</b> ' . __('Spam protection', 'cleantalk') . "</center><br><br>\n". $ctResponse['comment'];
			$err_text .= '<script>setTimeout("history.back()", 5000);</script>';
			wp_die($err_text, 'Blacklisted', array('back_link'=>TRUE));
	    }else{
		ct_hash($ctResponse['id']);
		if($ctResponse['allow'] == 0){
			$CT = ct_get_instance();
			$comment['comment_content'] = $CT->addCleantalkComment($comment['comment_content'], $ctResponse['comment']);
                        add_filter('pre_comment_approved', 'ct_set_not_approved');
		}
		add_action('comment_post', 'ct_set_meta', 10, 2);
	    }
    }
    return $comment;
}

/**
 * Public filter 'pre_comment_approved' - Mark comment unapproved always
 * @return 	int Zero
 */
function ct_set_not_approved(){
    return 0;
}

/**
 * Public action 'comment_post' - Store cleantalk hash in comment meta 'ct_hash'
 * @param	int $comment_id Comment ID
 * @param	mixed $comment_status Approval status ("spam", or 0/1), not used
 */
function ct_set_meta($comment_id, $comment_status){
    $hash1 = ct_hash();
    if(!empty($hash1)){
	update_comment_meta($comment_id, 'ct_hash', $hash1 );
    }
}

/**
 * Admin action 'comment_unapproved_to_approved' - Approve comment, sends good feedback to cleantalk, removes cleantalk resume
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_approved($comment_object){
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', TRUE);
    $comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);
    $comment['comment_approved'] = 1;
    wp_update_comment($comment);
    return TRUE;
}

/**
 * Admin actions 'comment_unapproved_to_spam', 'comment_approved_to_spam' - Mark comment as spam, sends bad feedback to cleantalk, removes cleantalk resume
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_spam($comment_object){
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', TRUE);
    $comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 0);
    $comment['comment_approved'] = 'spam';
    wp_update_comment($comment);
    return TRUE;
}

/**
 * Admin action 'trash_comment' - Sends bad feedback to cleantalk
 * @param 	int $comment_id Comment ID
 * @return	boolean TRUE
 */
function ct_comment_trash($comment_id){
    $comment = get_comment($comment_id, 'ARRAY_A');
    $hash = get_comment_meta($comment_id, 'ct_hash', TRUE);
    ct_feedback($hash, $comment['comment_content'], 0);
    return TRUE;
}

/**
 * Admin filter 'get_comment_text' - Adds some info to comment text to display
 * @param 	string $current_text Current comment text
 * @return	string New comment text
 */
function ct_get_comment_text($current_text){
    global $comment;
    $new_text = $current_text;
    if(isset($comment) && is_object($comment)){
        $hash = get_comment_meta($comment->comment_ID, 'ct_hash', TRUE);
        if(!empty($hash)){
            $new_text .= '<hr>Cleantalk ID = ' . $hash;
        }
    }
    return $new_text;
}


/**
 * Admin action 'admin_enqueue_scripts' - Enqueue admin script of reloading admin page after needed AJAX events
 * @param 	string $hook URL of hooked page
 */
function ct_enqueue_scripts($hook) {
    if($hook == 'edit-comments.php')
	 wp_enqueue_script( 'ct_reload_script', plugins_url('/cleantalk-rel.js', __FILE__) );
}

/**
 * Admin action 'admin_menu' - Add the admin options page
 */
function ct_admin_add_page() {
    add_options_page(__('Cleantalk settings', 'cleantalk'), '<b style="color: #009900;">Clean</b><b style="color: #777;">talk</b>', 'manage_options', 'cleantalk', 'ct_settings_page');
}

/**
 * Admin action 'admin_init' - Add the admin settings and such
 */
function ct_admin_init(){
    register_setting( 'cleantalk_settings', 'cleantalk_settings', 'ct_settings_validate' );

    add_settings_section('cleantalk_settings_main', __('Main settings', 'cleantalk'),   'ct_section_settings_main',  'cleantalk');
    add_settings_section('cleantalk_settings_server', __('Server settings', 'cleantalk'), 'ct_section_settings_server', 'cleantalk');

    add_settings_field('cleantalk_stopwords', __('Stop words checking', 'cleantalk'), 'ct_input_stopwords', 'cleantalk', 'cleantalk_settings_main');
    add_settings_field('cleantalk_allowlinks', __('Allow links', 'cleantalk'), 'ct_input_allowlinks', 'cleantalk', 'cleantalk_settings_main');
    add_settings_field('cleantalk_language', __('System messages language', 'cleantalk'), 'ct_input_language', 'cleantalk', 'cleantalk_settings_main');

    add_settings_field('cleantalk_server', __('<b style="color: #009900;">Clean</b><b style="color: #777;">talk</b> server URL', 'cleantalk'), 'ct_input_server', 'cleantalk', 'cleantalk_settings_server');
    add_settings_field('cleantalk_apikey', __('Autorization key', 'cleantalk'), 'ct_input_apikey', 'cleantalk', 'cleantalk_settings_server');
}

/**
 * Admin callback function - Displays description of 'main' plugin parameters section
 */
function ct_section_settings_main(){}

/**
 * Admin callback function - Displays description of 'server' plugin parameters section
 */
function ct_section_settings_server(){}

/**
 * Admin callback function - Displays inputs of 'stopwords' plugin parameter
 */
function ct_input_stopwords() {
    $options = ct_get_options();
    $value = $options['stopwords'];
    echo "<input type='radio' id='cleantalk_stopwords0' name='cleantalk_settings[stopwords]' value='0' " . ($value=='0'?'checked':'') . " /><label for='cleantalk_stopwords0'>".__('No')."</label>";
    echo "<input type='radio' id='cleantalk_stopwords1' name='cleantalk_settings[stopwords]' value='1' " . ($value=='1'?'checked':'') . " /><label for='cleantalk_stopwords1'>".__('Yes')."</label>";
}

/**
 * Admin callback function - Displays inputs of 'allowlinks' plugin parameter
 */
function ct_input_allowlinks() {
    $options = ct_get_options();
    $value = $options['allowlinks'];
    echo "<input type='radio' id='cleantalk_allowlinks0' name='cleantalk_settings[allowlinks]' value='0' " . ($value=='0'?'checked':'') . " /><label for='cleantalk_allowlinks0'>".__('No')."</label>";
    echo "<input type='radio' id='cleantalk_allowlinks1' name='cleantalk_settings[allowlinks]' value='1' " . ($value=='1'?'checked':'') . " /><label for='cleantalk_allowlinks1'>".__('Yes')."</label>";
}

/**
 * Admin callback function - Displays inputs of 'language' plugin parameter
 */
function ct_input_language() {
    $options = ct_get_options();
    $value = $options['language'];
    echo "<input type='radio' id='cleantalk_language0' name='cleantalk_settings[language]' value='en' " . ($value=='en'?'checked':'') . " /><label for='cleantalk_language0'>".__('English', 'cleantalk')."</label>";
    echo "<input type='radio' id='cleantalk_language1' name='cleantalk_settings[language]' value='ru' " . ($value=='ru'?'checked':'') . " /><label for='cleantalk_language1'>".__('Russian', 'cleantalk')."</label>";
}

/**
 * Admin callback function - Displays inputs of 'server' plugin parameter
 */
function ct_input_server() {
    $options = ct_get_options();
    $value = $options['server'];
    echo "<input id='cleantalk_server' name='cleantalk_settings[server]' size='50' type='text' value='$value' />";
}

/**
 * Admin callback function - Displays inputs of 'apikey' plugin parameter
 */
function ct_input_apikey() {
    $options = ct_get_options();
    $def_options = __('enter key', 'cleantalk');
    $value = $options['apikey'];
    $def_value = $def_options['apikey'];
    echo "<input id='cleantalk_apikey' name='cleantalk_settings[apikey]' size='10' type='text' value='$value' onfocus=\"if(this.value=='$def_value') this.value='';\"/>";
}

/**
 * Admin callback function - Plugin parameters validator
 */
function ct_settings_validate($input) {
    return $input;
}

/**
 * Admin callback function - Displays plugin options page
 */
function ct_settings_page() {
?>
<div>
<h2><b style="color: #009900;">Clean</b><b style="color: #777;">talk</b></h2>
<form action="options.php" method="post">
<?php settings_fields('cleantalk_settings'); ?>
<?php do_settings_sections('cleantalk'); ?>
<br>
<a target='__blank' href='https://cleantalk.ru/install/wordpress?step=2'><?php _e('Click here to get autorization key', 'cleantalk');?></a>
<br>
<br>
<input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
</form></div>
 
<?php
}

?>
