<?php
/*
  Plugin Name: Anti-spam by CleanTalk
  Plugin URI: http://cleantalk.org
  Description:  Cloud antispam for comments, registrations and contacts. The plugin doesn't use CAPTCHA, Q&A, math, counting animals or quiz to stop spam bots. 
  Version: 2.49
  Author: СleanTalk <welcome@cleantalk.ru>
  Author URI: http://cleantalk.org
 */

define('CLEANTALK_PLUGIN_DIR', plugin_dir_path(__FILE__));

$ct_agent_version = 'wordpress-249';
$ct_plugin_name = 'Anti-spam by CleanTalk';
$ct_checkjs_frm = 'ct_checkjs_frm';
$ct_checkjs_register_form = 'ct_checkjs_register_form';
$ct_session_request_id_label = 'request_id';
$ct_session_register_ok_label = 'register_ok';

$ct_checkjs_cf7 = 'ct_checkjs_cf7';
$ct_cf7_comment = 'This is a spam!';

$ct_checkjs_jpcf = 'ct_checkjs_jpcf';
$ct_jpcf_patched = false; 
$ct_jpcf_fields = array('name', 'email');

// Comment already proccessed
$ct_comment_done = false;

// Default value for JS test
$ct_checkjs_def = 0;

// COOKIE label to store request id for last approved  
$ct_approved_request_id_label = 'ct_approved_request_id';

// Last request id approved for publication 
$ct_approved_request_id = null;

// COOKIE label for trial notice flag
$ct_notice_trial_label = 'ct_notice_trial';

// Flag to show trial notice
$show_ct_notice_trial = false;

// COOKIE label for online notice flag
$ct_notice_online_label = 'ct_notice_online';

// Flag to show online notice - 'Y' or 'N'
$show_ct_notice_online = '';

// Timeout before new check for trial notice in minutes
$trial_notice_check_timeout = 10;

// COOKIE label for WP Landing Page proccessing result
$ct_wplp_result_label = 'ct_wplp_result';

// Flag indicates active JetPack comments 
$ct_jp_comments = false;

// S2member PayPal post data label
$ct_post_data_label = 's2member_pro_paypal_registration'; 

// S2member Auth.Net post data label
$ct_post_data_authnet_label = 's2member_pro_authnet_registration'; 

// Form time load label  
$ct_formtime_label = 'formtime'; 

// Init action.
add_action('init', 'ct_init', 1);

// After plugin loaded - to load locale as described in manual
add_action( 'plugins_loaded', 'ct_plugin_loaded' );

// Comments 
add_filter('preprocess_comment', 'ct_preprocess_comment');     // param - comment data array
add_filter('comment_text', 'ct_comment_text' );

// Formidable
add_action('frm_validate_entry', 'ct_frm_validate_entry', 20, 2);
add_action('frm_entries_footer_scripts', 'ct_frm_entries_footer_scripts', 20, 2);

// Registrations
add_action('register_form','ct_register_form');
add_filter('registration_errors', 'ct_registration_errors', 10, 3);
add_action('user_register', 'ct_user_register');

// Multisite registrations
add_action('signup_extra_fields','ct_register_form');
add_filter('wpmu_validate_user_signup', 'ct_registration_errors_wpmu', 10, 3);

// BuddyPress
add_action('bp_before_registration_submit_buttons','ct_register_form');
add_filter('bp_signup_validate', 'ct_registration_errors');

// Contact Form7 
add_filter('wpcf7_form_elements', 'ct_wpcf7_form_elements');
add_filter('wpcf7_spam', 'ct_wpcf7_spam');

// JetPack Contact form
add_filter('grunion_contact_form_field_html', 'ct_grunion_contact_form_field_html', 10, 2);
add_filter('contact_form_is_spam', 'ct_contact_form_is_spam');

// Fast Secure contact form
add_filter('si_contact_display_after_fields', 'ct_si_contact_display_after_fields');
add_filter('si_contact_form_validate', 'ct_si_contact_form_validate');

// Login form - for notifications only
add_filter('login_message', 'ct_login_message');

if (is_admin()) {
	require_once(CLEANTALK_PLUGIN_DIR . 'cleantalk-admin.php');

    add_action('admin_init', 'ct_admin_init', 1);
    add_action('admin_menu', 'ct_admin_add_page');
    add_action('admin_enqueue_scripts', 'ct_enqueue_scripts');
    add_action('admin_notices', 'admin_notice_message');
    add_action('comment_unapproved_to_approved', 'ct_comment_approved'); // param - comment object
    add_action('comment_approved_to_unapproved', 'ct_comment_unapproved'); // param - comment object
    add_action('comment_unapproved_to_spam', 'ct_comment_spam');  // param - comment object
    add_action('comment_approved_to_spam', 'ct_comment_spam');   // param - comment object
    add_filter('get_comment_text', 'ct_get_comment_text');   // param - current comment text
    add_filter('unspam_comment', 'ct_unspam_comment');
    add_action('delete_user', 'ct_delete_user');
    add_filter('plugin_row_meta', 'ct_register_plugin_links', 10, 2);
    add_filter('plugin_action_links', 'ct_plugin_action_links', 10, 2);
    add_action('updated_option', 'ct_update_option'); // param - option name, i.e. 'cleantalk_settings'
}

/**
 * Init functions 
 * @return 	mixed[] Array of options
 */
function ct_init() {
    global $ct_wplp_result_label, $ct_jp_comments, $ct_post_data_label, $ct_post_data_authnet_label;

    ct_init_session();

    add_action('comment_form', 'ct_comment_form');

    $jetpack_active_modules = get_option('jetpack_active_modules');
    if (
	(class_exists( 'Jetpack', false) && $jetpack_active_modules && in_array('comments', $jetpack_active_modules)) ||
	(defined('LANDINGPAGES_CURRENT_VERSION'))
	|| (defined('WS_PLUGIN__S2MEMBER_PRO_VERSION'))
    ) {
	    add_action('wp_footer', 'ct_footer_add_cookie');
    }
    if (
	(class_exists( 'Jetpack', false) && $jetpack_active_modules && in_array('comments', $jetpack_active_modules))
    ) {
        $ct_jp_comments = true;
    }

    //intercept WordPress Landing Pages POST
    if (defined('LANDINGPAGES_CURRENT_VERSION') && !empty($_POST)){
        if(array_key_exists('action', $_POST) && $_POST['action'] === 'inbound_store_lead'){ // AJAX action(s)
            ct_check_wplp();
        }else if(array_key_exists('inbound_submitted', $_POST) && $_POST['inbound_submitted'] == '1'){ // Final submit
            ct_check_wplp();
        }
    }
    
    // intercept S2member POST
    if (defined('WS_PLUGIN__S2MEMBER_PRO_VERSION') && (isset($_POST[$ct_post_data_label]['email']) || isset($_POST[$ct_post_data_authnet_label]['email']))){
        ct_s2member_registration_test(); 
    }
}

/**
 * Public action 'plugins_loaded' - Loads locale, see http://codex.wordpress.org/Function_Reference/load_plugin_textdomain
 */
function ct_plugin_loaded() {
    load_plugin_textdomain('cleantalk', false, basename(dirname(__FILE__)) . '/i18n');
}

/**
 * Inner function - Current Cleantalk options
 * @return 	mixed[] Array of options
 */
function ct_get_options() {
    $options = get_option('cleantalk_settings');
    if (!is_array($options))
        $options = array();
    return array_merge(ct_def_options(), (array) $options);
}

/**
 * Inner function - Default Cleantalk options
 * @return 	mixed[] Array of default options
 */
function ct_def_options() {
    $lang = get_bloginfo('language');
    return array(
        'server' => 'http://moderate.cleantalk.org',
        'apikey' => __('enter key', 'cleantalk'),
        'autoPubRevelantMess' => '1', 
        'registrations_test' => '1', 
        'comments_test' => '1', 
        'contact_forms_test' => '1', 
        'remove_old_spam' => '0',
        'spam_store_days' => '31' // Days before delete comments from folder Spam 
    );
}

/**
 * Inner function - Stores ang returns cleantalk hash of current comment
 * @param	string New hash or NULL
 * @return 	string New hash or current hash depending on parameter
 */
function ct_hash($new_hash = '') {
    /**
     * Current hash
     */
    static $hash;

    if (!empty($new_hash)) {
        $hash = $new_hash;
    }
    return $hash;
}

/**
 * Inner function - Write manual moderation results to PHP sessions 
 * @param 	string $hash Cleantalk comment hash
 * @param 	string $message comment_content
 * @param 	int $allow flag good comment (1) or bad (0)
 * @return 	string comment_content w\o cleantalk resume
 */
function ct_feedback($hash, $message = null, $allow) {

    require_once('cleantalk.class.php');
    $options = ct_get_options();

    $config = get_option('cleantalk_server');

    $ct = new Cleantalk();
    $ct->work_url = $config['ct_work_url'];
    $ct->server_url = $options['server'];
    $ct->server_ttl = $config['ct_server_ttl'];
    $ct->server_changed = $config['ct_server_changed'];

    if (empty($hash)) {
	$hash = $ct->getCleantalkCommentHash($message);
    }
    if ($message !== null) {
        $resultMessage = $ct->delCleantalkComment($message);
    }

    $ct_feedback = $hash . ':' . $allow . ';';
    if (empty($_SESSION['feedback_request'])) {
	$_SESSION['feedback_request'] = $ct_feedback; 
    } else {
	$_SESSION['feedback_request'] .= $ct_feedback; 
    }

    return $resultMessage;
}

/**
 * Inner function - Sends the results of moderation
 * @param string $feedback_request
 * @return bool
 */
function ct_send_feedback($feedback_request = null) {

    if (empty($feedback_request) && isset($_SESSION['feedback_request']) && preg_match("/^[a-z0-9\;\:]+$/", $_SESSION['feedback_request'])) {
	$feedback_request = $_SESSION['feedback_request'];
	unset($_SESSION['feedback_request']);
    }

    if ($feedback_request !== null) {
	require_once('cleantalk.class.php');
	$options = ct_get_options();

	$config = get_option('cleantalk_server');

	$ct = new Cleantalk();
	$ct->work_url = $config['ct_work_url'];
	$ct->server_url = $options['server'];
	$ct->server_ttl = $config['ct_server_ttl'];
	$ct->server_changed = $config['ct_server_changed'];

	$ct_request = new CleantalkRequest();
	$ct_request->auth_key = $options['apikey'];
	$ct_request->feedback = $feedback_request;

	$ct->sendFeedback($ct_request);

	if ($ct->server_change) {
		update_option(
			'cleantalk_server', array(
			'ct_work_url' => $ct->work_url,
			'ct_server_ttl' => $ct->server_ttl,
			'ct_server_changed' => time()
			)
		);
	}
	return true;
    }

    return false;
}

/**
 * Session init
 * @return null;
 */
function ct_init_session() {
    if(session_id() === '') {
        session_name('cleantalksession');
        @session_start();
    }

    return null;
}

/**
 * Inner function - Common part of request sending
 * @param array Array of parameters:
 *  'message' - string
 *  'example' - string
 *  'checkjs' - int
 *  'sender_email' - string
 *  'sender_nickname' - string
 *  'sender_info' - array
 *  'post_info' - string
 * @return array array('ct'=> Cleantalk, 'ct_result' => CleantalkResponse)
 */
function ct_base_call($params = array()) {
    global $wpdb, $ct_agent_version;

    require_once('cleantalk.class.php');
        
    $submit_time = submit_time_test();

    $sender_info = array(
        'cms_lang' => substr(get_locale(), 0, 2),
        'REFFERRER' => @$_SERVER['HTTP_REFERER'],
        'USER_AGENT' => @$_SERVER['HTTP_USER_AGENT'],
    );
    if(array_key_exists('sender_info', $params)){
	    $sender_info = array_merge($sender_info, (array) $params['sender_info']);
    }
    $sender_info = json_encode($sender_info);
    if ($sender_info === false)
        $sender_info = '';

    $config = get_option('cleantalk_server');
    $options = ct_get_options();

    $ct = new Cleantalk();
    $ct->work_url = $config['ct_work_url'];
    $ct->server_url = $options['server'];
    $ct->server_ttl = $config['ct_server_ttl'];
    $ct->server_changed = $config['ct_server_changed'];

    $ct_request = new CleantalkRequest();

    $ct_request->auth_key = $options['apikey'];
    $ct_request->message = $params['message'];
    $ct_request->example = $params['example'];
    $ct_request->sender_email = $params['sender_email'];
    $ct_request->sender_nickname = $params['sender_nickname'];
    $ct_request->sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
    $ct_request->agent = $ct_agent_version;
    $ct_request->sender_info = $sender_info;
    $ct_request->js_on = $params['checkjs'];
    $ct_request->submit_time = $submit_time;
    $ct_request->post_info = $params['post_info'];

    $ct_result = $ct->isAllowMessage($ct_request);
    if ($ct->server_change) {
        update_option(
                'cleantalk_server', array(
                'ct_work_url' => $ct->work_url,
                'ct_server_ttl' => $ct->server_ttl,
                'ct_server_changed' => time()
                )
        );
    }

    return array('ct' => $ct, 'ct_result' => $ct_result);
}

/**
 * Adds hidden filed to comment form 
 */
function ct_comment_form($post_id) {
    global $ct_jp_comments;
    
    if (ct_is_user_enable() === false) {
        return false;
    }

    $options = ct_get_options();
    if ($options['comments_test'] == 0) {
        return false;
    }
    
    ct_add_hidden_fields(null, 'ct_checkjs', false, false);
    
    return null;
}

/**
 * Adds cookie script filed to footer
 */
function ct_footer_add_cookie() {
    if (ct_is_user_enable() === false) {
        return false;
    }

    $options = ct_get_options();
    if ($options['comments_test'] == 0) {
        return false;
    }
    ct_add_hidden_fields(null, 'ct_checkjs', false, true);

    return null;
}

/**
 * Adds hidden filed to define avaialbility of client's JavaScript
 * @param 	int $post_id Post ID, not used
 */
function ct_add_hidden_fields($post_id = null, $field_name = 'ct_checkjs', $return_string = false, $cookie_check = false) {
    global $ct_checkjs_def, $ct_formtime_label, $ct_plugin_name;

    $ct_checkjs_key = ct_get_checkjs_value(); 
    
    $_SESSION[$ct_formtime_label] = time();

    if ($cookie_check) { 
			$html = '
<script type="text/javascript">
// <![CDATA[
function ctSetCookie(c_name, value) {
    document.cookie = c_name + "=" + escape(value) + "; path=/";
}
ctSetCookie("%s", "%s");
// ]]>
</script>
';
		$html = sprintf($html, $field_name, $ct_checkjs_key);
    } else {
    		$field_id = $field_name . '_' . md5(rand(0, 1000));
			$html = '
<input type="hidden" id="%s" name="%s" value="%s" />
<script type="text/javascript">
// <![CDATA[
var ct_input_name = \'%s\';
var ct_input_value = document.getElementById(ct_input_name).value;
var ct_input_challenge = \'%s\'; 

document.getElementById(ct_input_name).value = document.getElementById(ct_input_name).value.replace(ct_input_value, ct_input_challenge);

if (document.getElementById(ct_input_name).value == ct_input_value) {
    document.getElementById(ct_input_name).value = ct_set_challenge(ct_input_challenge); 
}

function ct_set_challenge(val) {
    return val; 
}; 

// ]]>
</script>
';
		$html = sprintf($html, $field_id, $field_name, $ct_checkjs_def, $field_id, $ct_checkjs_key);
    };
    
    $html .= '<noscript><p><b>Please enable JavaScript to pass anti-spam protection!</b><br />Here are the instructions how to enable JavaScript in your web browser <a href="http://www.enable-javascript.com" rel="nofollow" target="_blank">http://www.enable-javascript.com</a>.<br />' . $ct_plugin_name . '.</p></noscript>';

    if ($return_string === true) {
        return $html;
    } else {
        echo $html;
    } 
}

/**
 * Is enable for user group
 * @return boolean
 */
function ct_is_user_enable() {
    global $current_user;

    if (!isset($current_user->roles)) {
        return true; 
    }

    $disable_roles = array('administrator', 'editor', 'author');
    foreach ($current_user->roles as $k => $v) {
        if (in_array($v, $disable_roles))
            return false;
    }

    return true;
}

/**
* Public function - Insert JS code for spam tests
* return null;
*/
function ct_frm_entries_footer_scripts($fields, $form) {
    global $current_user, $ct_checkjs_frm, $ct_formtime_label;

    $options = ct_get_options();
    if ($options['contact_forms_test'] == 0) {
        return false;
    }
    
    $_SESSION[$ct_formtime_label] = time();

    $ct_checkjs_key = ct_get_checkjs_value();
    $ct_frm_name = 'form_' . $form->form_key;

    ?>

    var input = document.createElement("input");
    input.setAttribute("type", "hidden");
    input.setAttribute("name", "<?php echo $ct_checkjs_frm; ?>");
    input.setAttribute("value", "<?php echo $ct_checkjs_key; ?>");
    document.getElementById("<?php echo $ct_frm_name; ?>").appendChild(input);

    <?php
}

/**
* Public function - Test Formidable data for spam activity
* return @array with errors if spam has found
*/
function ct_frm_validate_entry ($errors, $values) {
    global $wpdb, $current_user, $ct_agent_version, $ct_checkjs_frm;

    $options = ct_get_options();
    if ($options['contact_forms_test'] == 0) {
        return false;
    }

    $checkjs = js_test($ct_checkjs_frm, $_POST);

    $post_info['comment_type'] = 'feedback';
    $post_info = json_encode($post_info);
    if ($post_info === false)
        $post_info = '';

    $sender_email = null;
    $message = '';
    foreach ($values['item_meta'] as $v) {
        if (preg_match("/^\S+@\S+\.\S+$/", $v)) {
            $sender_email = $v;
            continue;
        }
        $message .= ' ' . $v;
    }

    $ct_base_call_result = ct_base_call(array(
        'message' => $message,
        'example' => null,
        'sender_email' => $sender_email,
        'sender_nickname' => null,
        'post_info' => $post_info,
        'checkjs' => $checkjs
    ));
    $ct = $ct_base_call_result['ct'];
    $ct_result = $ct_base_call_result['ct_result'];

    if ($ct_result->spam == 1) {
        $errors['ct_error'] = '<br /><b>' . $ct_result->comment . '</b><br /><br />';
    }

    return $errors;
}

/**
 * Public filter 'preprocess_comment' - Checks comment by cleantalk server
 * @param 	mixed[] $comment Comment data array
 * @return 	mixed[] New data array of comment
 */
function ct_preprocess_comment($comment) {
    // this action is called just when WP process POST request (adds new comment)
    // this action is called by wp-comments-post.php
    // after processing WP makes redirect to post page with comment's form by GET request (see above)
    global $wpdb, $current_user, $comment_post_id, $ct_agent_version, $ct_comment_done, $ct_approved_request_id_label, $ct_jp_comments;

    $options = ct_get_options();
    if (ct_is_user_enable() === false || $options['comments_test'] == 0 || $ct_comment_done) {
        return $comment;
    }

    $local_blacklists = wp_blacklist_check(
        $comment['comment_author'],
        $comment['comment_author_email'], 
        $comment['comment_author_url'], 
        $comment['comment_content'], 
        @$_SERVER['REMOTE_ADDR'], 
        @$_SERVER['HTTP_USER_AGENT']
    );

    // Go out if author in local blacklists
    if ($local_blacklists === true) {
        return $comment;
    }

    // Skip pingback anti-spam test
    if ($comment['comment_type'] == 'pingback') {
        return $comment;
    }

    $ct_comment_done = true;

    $comment_post_id = $comment['comment_post_ID'];

    $post = get_post($comment_post_id);

    $checkjs = js_test('ct_checkjs', $_POST);

    $example = null;

    $post_info['comment_type'] = $comment['comment_type'];
    $post_info['post_url'] = ct_post_url(null, $comment_post_id); 

    $post_info = json_encode($post_info);
    if ($post_info === false)
	$post_info = '';

    if ($post !== null){
	$example['title'] = $post->post_title;
	$example['body'] = $post->post_content;
	$example['comments'] = null;

	$last_comments = get_comments(array('status' => 'approve', 'number' => 10, 'post_id' => $comment_post_id));
	foreach ($last_comments as $post_comment){
		$example['comments'] .= "\n\n" . $post_comment->comment_content;
	}

	$example = json_encode($example);
    }

    // Use plain string format if've failed with JSON
    if ($example === false || $example === null){
	$example = ($post->post_title !== null) ? $post->post_title : '';
	$example .= ($post->post_content !== null) ? "\n\n" . $post->post_content : '';
    }

    $ct_base_call_result = ct_base_call(array(
        'message' => $comment['comment_content'],
        'example' => $example,
        'sender_email' => $comment['comment_author_email'],
        'sender_nickname' => $comment['comment_author'],
        'post_info' => $post_info,
        'checkjs' => $checkjs
    ));
    $ct = $ct_base_call_result['ct'];
    $ct_result = $ct_base_call_result['ct_result'];

    if ($ct_result->stop_queue == 1) {
        $err_text = '<center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> ' . __('Spam protection', 'cleantalk') . "</center><br><br>\n" . $ct_result->comment;
        $err_text .= '<script>setTimeout("history.back()", 5000);</script>';
        wp_die($err_text, 'Blacklisted', array('back_link' => true));

        return $comment;
    }

    ct_hash($ct_result->id);

    if ($ct_result->spam == 1) {
        $comment['comment_content'] = $ct->addCleantalkComment($comment['comment_content'], $ct_result->comment);
        add_filter('pre_comment_approved', 'ct_set_comment_spam');

        global $ct_comment;
        $ct_comment = $ct_result->comment;
        add_action('comment_post', 'ct_die', 12, 2);
		add_action('comment_post', 'ct_set_meta', 10, 2);

        return $comment;
    }

    if (isset($comment['comment_author_email'])) {
        $approved_comments = get_comments(array('status' => 'approve', 'count' => true, 'author_email' => $comment['comment_author_email']));

        // Change comment flow only for new authors
        if ((int) $approved_comments == 0 || $ct_result->stop_words !== null) { 

            if ($ct_result->allow == 1 && $options['autoPubRevelantMess'] == 1) {
                add_filter('pre_comment_approved', 'ct_set_approved');
                setcookie($ct_approved_request_id_label, $ct_result->id, 0, '/');
            }
            if ($ct_result->allow == 0) {
                if (isset($ct_result->stop_words)) {
                    global $ct_stop_words;
                    $ct_stop_words = $ct_result->stop_words;
                    add_action('comment_post', 'ct_mark_red', 11, 2);
                }

                $comment['comment_content'] = $ct->addCleantalkComment($comment['comment_content'], $ct_result->comment);
                add_filter('pre_comment_approved', 'ct_set_not_approved');
            }

            add_action('comment_post', 'ct_set_meta', 10, 2);
        }
    }

    return $comment;
}

/**
 * Set die page with Cleantalk comment.
 * @global type $ct_comment
    $err_text = '<center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> ' . __('Spam protection', 'cleantalk') . "</center><br><br>\n" . $ct_comment;
 * @param type $comment_status
 */
function ct_die($comment_id, $comment_status) {
    global $ct_comment;
    $err_text = '<center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> ' . __('Spam protection', 'cleantalk') . "</center><br><br>\n" . $ct_comment;
        $err_text .= '<script>setTimeout("history.back()", 5000);</script>';
        wp_die($err_text, 'Blacklisted', array('back_link' => true));
}

/**
 * Set die page with Cleantalk comment from parameter.
 * @param type $comment_body
 */
function ct_die_extended($comment_body) {
    $err_text = '<center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> ' . __('Spam protection', 'cleantalk') . "</center><br><br>\n" . $comment_body;
        $err_text .= '<script>setTimeout("history.back()", 5000);</script>';
        wp_die($err_text, 'Blacklisted', array('back_link' => true));
}

/**
 * Validates JavaScript anti-spam test
 *
 */
function js_test($field_name = 'ct_checkjs', $data = null) {
    $checkjs = null;
    $js_post_value = null;
    
    if (!$data)
        return $checkjs;

    if (isset($data[$field_name])) {
	    $js_post_value = $data[$field_name];
        $ct_challenge = ct_get_checkjs_value();
        if(preg_match("/$ct_challenge/", $js_post_value)) {
            $checkjs = 1;
        } else {
            $checkjs = 0; 
        }
    }

    return $checkjs;
}

/**
 * Validate form submit time 
 *
 */
function submit_time_test() {
    global $ct_formtime_label;

    $submit_time = null;
    if (isset($_SESSION[$ct_formtime_label])) {
        $submit_time = time() - (int) $_SESSION[$ct_formtime_label];
    }

    return $submit_time;
}

/**
 * Get post url 
 * @param int $comment_id 
 * @param int $comment_post_id
 * @return string|bool
 */
function ct_post_url($comment_id = null, $comment_post_id) {

    if (empty($comment_post_id))
	return null;

    if ($comment_id === null) {
	    $last_comment = get_comments('number=1');
	    $comment_id = isset($last_comment[0]->comment_ID) ? (int) $last_comment[0]->comment_ID + 1 : 1;
    }
    $permalink = get_permalink($comment_post_id);

    $post_url = null;
    if ($permalink !== null)
	$post_url = $permalink . '#comment-' . $comment_id;

    return $post_url;
}

/**
 * Public filter 'pre_comment_approved' - Mark comment unapproved always
 * @return 	int Zero
 */
function ct_set_not_approved() {
    return 0;
}

/**
 * @author Artem Leontiev
 * Public filter 'pre_comment_approved' - Mark comment approved always
 * @return 	int 1
 */
function ct_set_approved() {
    return 1;
}

/**
 * Public filter 'pre_comment_approved' - Mark comment unapproved always
 * @return 	int Zero
 */
function ct_set_comment_spam() {
    return 'spam';
}

/**
 * Public action 'comment_post' - Store cleantalk hash in comment meta 'ct_hash'
 * @param	int $comment_id Comment ID
 * @param	mixed $comment_status Approval status ("spam", or 0/1), not used
 */
function ct_set_meta($comment_id, $comment_status) {
    global $comment_post_id;
    $hash1 = ct_hash();
    if (!empty($hash1)) {
        update_comment_meta($comment_id, 'ct_hash', $hash1);
        if (function_exists('base64_encode') && isset($comment_status) && $comment_status != 'spam') {
	    $post_url = ct_post_url($comment_id, $comment_post_id);
	    $post_url = base64_encode($post_url);
	    if ($post_url === false)
		return false;
	    // 01 - URL to approved comment
	    $feedback_request = $hash1 . ':' . '01' . ':' . $post_url . ';';
	    ct_send_feedback($feedback_request);
	}
    }
    return true;
}

/**
 * Mark bad words
 * @global string $ct_stop_words
 * @param int $comment_id
 * @param int $comment_status Not use
 */
function ct_mark_red($comment_id, $comment_status) {
    global $ct_stop_words;

    $comment = get_comment($comment_id, 'ARRAY_A');
    $message = $comment['comment_content'];
    foreach (explode(':', $ct_stop_words) as $word) {
        $message = preg_replace("/($word)/ui", '<font rel="cleantalk" color="#FF1000">' . "$1" . '</font>', $message);

    }
    $comment['comment_content'] = $message;
    kses_remove_filters();
    wp_update_comment($comment);
}

/**
 * Unmark bad words
 * @param string $message
 * @return string Cleat comment
 */
function ct_unmark_red($message) {
    $message = preg_replace("/\<font rel\=\"cleantalk\" color\=\"\#FF1000\"\>(\S+)\<\/font>/iu", '$1', $message);

    return $message;
}

/**
	* Tests plugin activation status
	* @return bool 
*/
function ct_active(){
    $ct_active = false;
    foreach (get_option('active_plugins') as $k => $v) {
	if (preg_match("/cleantalk.php$/", $v))
	    $ct_active = true;
    }

    return $ct_active;
}
/**
	* Tests plugin activation status
	* @return bool 
*/
function ct_plugin_active($plugin_name){
	$active = false;
	foreach (get_option('active_plugins') as $k => $v) {
	    if ($plugin_name == $v)
		    $active = true;
	}
    return $active; 
}

/**
 * Get ct_get_checkjs_value 
 * @return string
 */
function ct_get_checkjs_value() {
    $options = ct_get_options();

    $salt = $options['apikey'] . '+' . get_option('admin_email');

    return md5($salt); 
}

/**
 * Delete old spam comments 
 * @return null 
 */
function delete_spam_comments() {
    $options = ct_get_options();

    if ($options['remove_old_spam'] == 1) {
        $last_comments = get_comments(array('status' => 'spam', 'number' => 1000, 'order' => 'ASC'));
        foreach ($last_comments as $c) {
            if (time() - strtotime($c->comment_date_gmt) > 86400 * $options['spam_store_days']) {
                // Force deletion old spam comments
                wp_delete_comment($c->comment_ID, true);
            } 
        }
    }

    return null; 
}


/**
 * Insert a hidden field to registration form
 * @return null
 */
function ct_register_form() {
    global $ct_checkjs_register_form;

    $options = ct_get_options();
    if ($options['registrations_test'] == 0) {
        return false;
    }

    ct_add_hidden_fields(null, $ct_checkjs_register_form, false);

    return null;
}

/**
 * Adds notification text to login form - to inform about approced registration
 * @return null
 */
function ct_login_message($message) {
    global $errors, $ct_session_register_ok_label;

    $options = ct_get_options();
    if ($options['registrations_test'] != 0) {
        if( isset($_GET['checkemail']) && 'registered' == $_GET['checkemail'] ) {
	    if (isset($_SESSION[$ct_session_register_ok_label])) {
		unset($_SESSION[$ct_session_register_ok_label]);
		if(is_wp_error($errors))
		    $errors->add('ct_message','<br />' . sprintf(__('Registration is approved by %s.', 'cleantalk'), '<b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk</b>'), 'message');
	    }
        }
    }
    return $message;
}

/**
 * Test users registration for multisite enviroment
 * @return array with errors 
 */
function ct_registration_errors_wpmu($errors) {
    //
    // Multisite actions
    //
    $sanitized_user_login = null;
    if (isset($errors['user_name'])) {
        $sanitized_user_login = $errors['user_name']; 
        $wpmu = true;
    }
    $user_email = null;
    if (isset($errors['user_email'])) {
        $user_email = $errors['user_email'];
        $wpmu = true;
    }
    
    if ($wpmu && isset($errors['errors']->errors) && count($errors['errors']->errors) > 0) {
        return $errors;
    }
    $errors['errors'] = ct_registration_errors($errors['errors'], $sanitized_user_login, $user_email);
    
    // Show CleanTalk errors in user_name field
    if (isset($errors['errors']->errors['ct_error'])) {
       $errors['errors']->errors['user_name'] = $errors['errors']->errors['ct_error']; 
       unset($errors['errors']->errors['ct_error']); 
    }
    
    return $errors;
}
/**
 * Test users registration
 * @return array with errors 
 */
function ct_registration_errors($errors, $sanitized_user_login = null, $user_email = null) {
    global $ct_agent_version, $ct_checkjs_register_form, $ct_session_request_id_label, $ct_session_register_ok_label, $bp;

    //
    // BuddyPress actions
    //
    $buddypress = false;
    if ($sanitized_user_login === null && isset($_POST['signup_username'])) {
        $sanitized_user_login = $_POST['signup_username'];
        $buddypress = true;
    }
    if ($user_email === null && isset($_POST['signup_email'])) {
        $user_email = $_POST['signup_email'];
        $buddypress = true;
    }
    
    $submit_time = submit_time_test();

    $options = ct_get_options();
    if ($options['registrations_test'] == 0) {
        return $errors;
    }

    $checkjs = js_test($ct_checkjs_register_form, $_POST);

    require_once('cleantalk.class.php');

    $blog_lang = substr(get_locale(), 0, 2);
    $user_info = array(
        'cms_lang' => $blog_lang,
        'REFFERRER' => @$_SERVER['HTTP_REFERER'],
        'USER_AGENT' => @$_SERVER['HTTP_USER_AGENT'],
    );
    $user_info = json_encode($user_info);
    if ($user_info === false)
        $user_info = '';

    $sender_email = $user_email;

    $config = get_option('cleantalk_server');

    $ct = new Cleantalk();
    $ct->work_url = $config['ct_work_url'];
    $ct->server_url = $options['server'];
    $ct->server_ttl = $config['ct_server_ttl'];
    $ct->server_changed = $config['ct_server_changed'];

    $ct_request = new CleantalkRequest();

    $ct_request->auth_key = $options['apikey'];
    $ct_request->sender_email = $sender_email; 
    $ct_request->sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
    $ct_request->sender_nickname = $sanitized_user_login; 
    $ct_request->agent = $ct_agent_version; 
    $ct_request->sender_info = $user_info;
    $ct_request->js_on = $checkjs;
    $ct_request->submit_time = $submit_time; 

    $ct_result = $ct->isAllowUser($ct_request);
    if ($ct->server_change) {
        update_option(
                'cleantalk_server', array(
                'ct_work_url' => $ct->work_url,
                'ct_server_ttl' => $ct->server_ttl,
                'ct_server_changed' => time()
                )
        );
    }

    if ($ct_result->errno != 0) {
        return $errors;
    }
    
    if ($ct_result->inactive != 0) {
	$timelabel_reg = intval( get_option('cleantalk_timelabel_reg') );
	if(time() - 900 > $timelabel_reg){
	    update_option('cleantalk_timelabel_reg', time());

	    $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
	    $message  = __('Attention, please!', 'cleantalk') . "\r\n\r\n";
	    $message .= sprintf(__('"Anti-spam by CleanTalk" plugin error on your site %s:', 'cleantalk'), $blogname) . "\r\n\r\n";
	    $message .= $ct_result->comment . "\r\n\r\n";
	    @wp_mail(get_option('admin_email'), sprintf(__('[%s] Anti-spam by CleanTalk error!', 'cleantalk'), $blogname), $message);
	}
        return $errors;
    }

    if ($ct_result->allow == 0) {
        if ($buddypress === true) {
            $bp->signup->errors['signup_username'] =  $ct_result->comment;
        } else {
            $errors->add('ct_error', $ct_result->comment);
        }
    } else {
        if ($ct_result->id !== null) {
            $_SESSION[$ct_session_request_id_label] = $ct_result->id;
            $_SESSION[$ct_session_register_ok_label] = $ct_result->id;
        }
    }

    return $errors;
}

/**
 * Set user meta 
 * @return null 
 */
function ct_user_register($user_id) {
    global $ct_session_request_id_label;

    if (isset($_SESSION[$ct_session_request_id_label])) {
        update_user_meta($user_id, 'ct_hash', $_SESSION[$ct_session_request_id_label]);
	unset($_SESSION[$ct_session_request_id_label]);
    }
}


/**
 * Test for JetPack contact form 
 */
function ct_grunion_contact_form_field_html($r, $field_label) {
    global $ct_checkjs_jpcf, $ct_jpcf_patched, $ct_jpcf_fields;

    $options = ct_get_options();
    if ($options['contact_forms_test'] == 1 && $ct_jpcf_patched === false && preg_match("/[text|email]/i", $r)) {

        // Looking for element name prefix
        $name_patched = false;
        foreach ($ct_jpcf_fields as $v) {
            if ($name_patched === false && preg_match("/(g\d-)$v/", $r, $matches)) {
                $ct_checkjs_jpcf = $matches[1] . $ct_checkjs_jpcf;
                $name_patched = true;
            }
        }

        $r .= ct_add_hidden_fields(null, $ct_checkjs_jpcf, true);
        $ct_jpcf_patched = true;
    }

    return $r;
}
/**
 * Test for JetPack contact form 
 */
function ct_contact_form_is_spam($form) {
    global $ct_checkjs_jpcf;

    $options = ct_get_options();

    if ($options['contact_forms_test'] == 0) {
        return null;
    }

    $js_field_name = $ct_checkjs_jpcf;
    foreach ($_POST as $k => $v) {
        if (preg_match("/^.+$ct_checkjs_jpcf$/", $k))
           $js_field_name = $k; 
    }
    $checkjs = js_test($js_field_name, $_COOKIE);

    $sender_info = array(
	'sender_url' => @$form['comment_author_url']
    );

    $post_info['comment_type'] = 'feedback';
    $post_info = json_encode($post_info);
    if ($post_info === false)
        $post_info = '';

    $sender_email = null;
    $sender_nickname = null;
    $message = '';
    if (isset($form['comment_author_email']))
        $sender_email = $form['comment_author_email']; 

    if (isset($form['comment_author']))
        $sender_nickname = $form['comment_author']; 

    if (isset($form['comment_content']))
        $message = $form['comment_content']; 

    $ct_base_call_result = ct_base_call(array(
        'message' => $message,
        'example' => null,
        'sender_email' => $sender_email,
        'sender_nickname' => $sender_nickname,
        'post_info' => $post_info,
	'sender_info' => $sender_info,
        'checkjs' => $checkjs
    ));
    $ct = $ct_base_call_result['ct'];
    $ct_result = $ct_base_call_result['ct_result'];

    if ($ct_result->spam == 1) {
        global $ct_comment;
        $ct_comment = $ct_result->comment;
        ct_die(null, null);
        exit;
    }

    return (bool) $ct_result->spam;
}


/**
 * Inserts anti-spam hidden to CF7
 */
function ct_wpcf7_form_elements($html) {
    global $ct_checkjs_cf7;
    global $wpdb, $current_user, $ct_checkjs_cf7;

    $options = ct_get_options();
    if ($options['contact_forms_test'] == 0) {
        return $html;
    }

    $html .= ct_add_hidden_fields(null, $ct_checkjs_cf7, true);

    return $html;
}

/**
 * Test CF7 message for spam
 */
function ct_wpcf7_spam($spam) {
    global $wpdb, $current_user, $ct_agent_version, $ct_checkjs_cf7, $ct_cf7_comment;

    $options = ct_get_options();
    if ($spam === true)
        return $spam;

    if ($options['contact_forms_test'] == 0) {
        return $spam;
    }

    $checkjs = js_test($ct_checkjs_cf7, $_POST);

    $post_info['comment_type'] = 'feedback';
    $post_info = json_encode($post_info);
    if ($post_info === false)
        $post_info = '';

    $sender_email = null;
    $sender_nickname = null;
    $message = '';
    foreach ($_POST as $k => $v) {
        if ($sender_email === null && preg_match("/^\S+@\S+\.\S+$/", $v)) {
            $sender_email = $v;
        }
        if ($message === '' && preg_match("/-message$/", $k)) {
            $message = $v;
        }
        if ($sender_nickname === null && preg_match("/-name$/", $k)) {
            $sender_nickname = $v;
        }
    }

    $ct_base_call_result = ct_base_call(array(
        'message' => $message,
        'example' => null,
        'sender_email' => $sender_email,
        'sender_nickname' => $sender_nickname,
        'post_info' => $post_info,
        'checkjs' => $checkjs
    ));
    $ct = $ct_base_call_result['ct'];
    $ct_result = $ct_base_call_result['ct_result'];

    if ($ct_result->spam == 1) {
        $spam = true;
        $ct_cf7_comment = $ct_result->comment;
	    add_filter('wpcf7_display_message', 'ct_wpcf7_display_message', 10, 2);
    }

    return $spam;
}
/**
 * Changes CF7 status message 
 * @param 	string $hook URL of hooked page
 */
function ct_wpcf7_display_message($message, $status) {
    global $ct_cf7_comment;

    if ($status == 'spam') {
        $message = $ct_cf7_comment; 
    }

    return $message;
}

/**
 * Inserts anti-spam hidden to Fast Secure contact form
 */
function ct_si_contact_display_after_fields($string = '', $style = '', $form_errors = array(), $form_id_num = 0) {
    $string .= ct_add_hidden_fields(null, 'ct_checkjs', true);
    return $string;
}

/**
 * Test for Fast Secure contact form
 */
function ct_si_contact_form_validate($form_errors = array(), $form_id_num = 0) {
    if (!empty($form_errors))
	return $form_errors;

    $options = ct_get_options();
    if ($options['contact_forms_test'] == 0)
	return $form_errors;

    $checkjs = js_test('ct_checkjs', $_POST);

    $post_info['comment_type'] = 'feedback';
    $post_info = json_encode($post_info);
    if ($post_info === false)
        $post_info = '';

    $sender_email = null;
    $sender_nickname = null;
    $subject = '';
    $message = '';
    if (isset($_POST['email']))
        $sender_email = $_POST['email']; 

    if (isset($_POST['full_name']))
        $sender_nickname = $_POST['full_name']; 

    if (isset($_POST['subject']))
        $subject = $_POST['subject'];

    if (isset($_POST['message']))
        $message = $_POST['message'];

    $ct_base_call_result = ct_base_call(array(
        'message' => $subject . "\n\n" . $message,
        'example' => null,
        'sender_email' => $sender_email,
        'sender_nickname' => $sender_nickname,
        'post_info' => $post_info,
	'sender_info' => $sender_info,
        'checkjs' => $checkjs
    ));
    $ct = $ct_base_call_result['ct'];
    $ct_result = $ct_base_call_result['ct_result'];

    if ($ct_result->spam == 1) {
        global $ct_comment;
        $ct_comment = $ct_result->comment;
        ct_die(null, null);
        exit;
    }

    return $form_errors;
}

/**
 * Notice for commentators which comment has automatically approved by plugin 
 * @param 	string $hook URL of hooked page
 */
function ct_comment_text($comment_text) {
    global $comment, $ct_approved_request_id_label;

    if (isset($_COOKIE[$ct_approved_request_id_label])) {
        $ct_hash = get_comment_meta($comment->comment_ID, 'ct_hash', true);

        if ($ct_hash !== '' && $_COOKIE[$ct_approved_request_id_label] == $ct_hash) {
            $comment_text .= '<br /><br /> <em class="comment-awaiting-moderation">' . __('Comment is approved. Anti-spam by CleanTalk.', 'cleantalk') . '</em>'; 
        }
    }

    return $comment_text;
}


/**
 * Checks WordPress Landing Pages raw $_POST values
*/
function ct_check_wplp(){
    global $ct_wplp_result_label;
    if (!isset($_COOKIE[$ct_wplp_result_label])) {
        // First AJAX submit of WPLP form
	$options = ct_get_options();
	if ($options['contact_forms_test'] == 0)
    	    return;

    $checkjs = js_test('ct_checkjs', $_COOKIE);

    if (null === $checkjs)
        $checkjs = 0;

	$post_info['comment_type'] = 'feedback';
	$post_info = json_encode($post_info);
	if ($post_info === false)
    	    $post_info = '';

        $sender_email = '';
        foreach ($_POST as $v) {
            if (preg_match("/^\S+@\S+\.\S+$/", $v)) {
                $sender_email = $v;
                break;
            }
        }

        $message = '';
        if(array_key_exists('form_input_values', $_POST)){
            $form_input_values = json_decode(stripslashes($_POST['form_input_values']), true);
            if (is_array($form_input_values) && array_key_exists('null', $form_input_values))
                $message = $form_input_values['null'];
        }else if(array_key_exists('null', $_POST)){
            $message = $_POST['null'];
        }

	$ct_base_call_result = ct_base_call(array(
    	    'message' => $message,
    	    'example' => null,
    	    'sender_email' => $sender_email,
    	    'sender_nickname' => null,
    	    'post_info' => $post_info,
    	    'checkjs' => $checkjs
	));
	$ct = $ct_base_call_result['ct'];
	$ct_result = $ct_base_call_result['ct_result'];

	if ($ct_result->spam == 1) {
            $cleantalk_comment = $ct_result->comment;
	} else {
            $cleantalk_comment = 'OK';
        }

	setcookie($ct_wplp_result_label, $cleantalk_comment, strtotime("+5 seconds"), '/');
    } else {
        // Next POST/AJAX submit(s) of same WPLP form
        $cleantalk_comment = $_COOKIE[$ct_wplp_result_label];
    }
    if ($cleantalk_comment !== 'OK')
        ct_die_extended($cleantalk_comment);
}

/**
 * Test S2member registration
 * @return array with errors 
 */
function ct_s2member_registration_test() {
    global $ct_agent_version, $ct_post_data_label, $ct_post_data_authnet_label;
    
    $options = ct_get_options();
    if ($options['registrations_test'] == 0) {
        return null;
    }
    
    $submit_time = submit_time_test();

    $checkjs = js_test('ct_checkjs', $_COOKIE);

    require_once('cleantalk.class.php');

    $blog_lang = substr(get_locale(), 0, 2);
    $user_info = array(
        'cms_lang' => $blog_lang,
        'REFFERRER' => @$_SERVER['HTTP_REFERER'],
        'USER_AGENT' => @$_SERVER['HTTP_USER_AGENT'],
    );
    $user_info = json_encode($user_info);
    if ($user_info === false)
        $user_info = '';
    
    $sender_email = null;
    if (isset($_POST[$ct_post_data_label]['email']))
        $sender_email = $_POST[$ct_post_data_label]['email'];
    
    if (isset($_POST[$ct_post_data_authnet_label]['email']))
        $sender_email = $_POST[$ct_post_data_authnet_label]['email'];

    $sender_nickname = null;
    if (isset($_POST[$ct_post_data_label]['username']))
        $sender_nickname = $_POST[$ct_post_data_label]['username'];
    
    if (isset($_POST[$ct_post_data_authnet_label]['username']))
        $sender_nickname = $_POST[$ct_post_data_authnet_label]['username'];

    $config = get_option('cleantalk_server');

    $ct = new Cleantalk();
    $ct->work_url = $config['ct_work_url'];
    $ct->server_url = $options['server'];
    $ct->server_ttl = $config['ct_server_ttl'];
    $ct->server_changed = $config['ct_server_changed'];

    $ct_request = new CleantalkRequest();

    $ct_request->auth_key = $options['apikey'];
    $ct_request->sender_email = $sender_email; 
    $ct_request->sender_ip = $ct->ct_session_ip($_SERVER['REMOTE_ADDR']);
    $ct_request->sender_nickname = $sender_nickname; 
    $ct_request->agent = $ct_agent_version; 
    $ct_request->sender_info = $user_info;
    $ct_request->js_on = $checkjs;
    $ct_request->submit_time = $submit_time; 

    $ct_result = $ct->isAllowUser($ct_request);
    if ($ct->server_change) {
        update_option(
                'cleantalk_server', array(
                'ct_work_url' => $ct->work_url,
                'ct_server_ttl' => $ct->server_ttl,
                'ct_server_changed' => time()
                )
        );
    }

    if ($ct_result->errno != 0) {
        return false;
    }
    if ($ct_result->allow == 0) {
        ct_die_extended($ct_result->comment);
    }

    return true;
}

?>
