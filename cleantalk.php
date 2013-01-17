<?php
/*
  Plugin Name: CleanTalk. Spam protection
  Plugin URI: http://cleantalk.org/wordpress
  Description: Plugin stops 99% spam in WordPress comments without moving to blog's trash. It use several tests to stops spam. 1) Emails, IPs blacklists tests. 2) Compares comment with previous posts on blog. 3) Javascript availability. 4) Comment submit time. CleanTalk dramatically reduces spam activity at the blog. 
  Version: 1.4.4
  Author: СleanTalk team
  Author URI: http://cleantalk.org
 */

// Сleantalk team noc@cleantalk.ru

add_action('init', 'ct_init_locale');
add_action('delete_comment', 'ct_delete_comment_meta');    // param - comment ID
add_action('comment_form', 'ct_add_hidden_fields');
add_action('parse_request', 'ct_set_session');
add_action('admin_notices', 'admin_notice_message');
add_filter('preprocess_comment', 'ct_check');     // param - comment data array

if (is_admin()) {
    add_action('admin_init', 'ct_admin_init');
    add_action('admin_menu', 'ct_admin_add_page');
    add_action('admin_enqueue_scripts', 'ct_enqueue_scripts');
    add_action('comment_unapproved_to_approved', 'ct_comment_approved'); // param - comment object
    add_action('comment_approved_to_unapproved', 'ct_comment_unapproved'); // param - comment object
    add_action('comment_unapproved_to_spam', 'ct_comment_spam');  // param - comment object
    add_action('comment_approved_to_spam', 'ct_comment_spam');   // param - comment object
    add_action('trash_comment', 'ct_comment_trash');    // param - comment ID
    add_filter('get_comment_text', 'ct_get_comment_text');   // param - current comment text
    add_filter('unspam_comment', 'ct_unspam_comment');
}

/**
 * Inner function - Current Cleantalk options
 * @return 	mixed[] Array of options
 */
function ct_get_options() {
    $options = get_option('cleantalk_settings');
    if (!is_array($options))
        $options = array();
    return array_merge(ct_def_options(), $options);
}

/**
 * Inner function - Default Cleantalk options
 * @return 	mixed[] Array of default options
 */
function ct_def_options() {
    return array(
        'stopwords' => '1',
        'allowlinks' => '0',
        'language' => 'en',
        'server' => 'http://moderate.cleantalk.ru',
        'apikey' => __('enter key', 'cleantalk')
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
 * Inner function - Sends the results of moderation and delete cleantalk resume
 * @param 	string $hash Cleantalk comment hash
 * @param 	string $message comment_content
 * @param 	int $allow flag good comment (1) or bad (0)
 * @return 	string comment_content w\o cleantalk resume
 */
function ct_feedback($hash, $message, $allow) {
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
    $resultMessage = $ct->delCleantalkComment($message);

    $ct_request = new CleantalkRequest();
    $ct_request->auth_key = $options['apikey'];
    $ct_request->feedback = $hash . ':' . $allow;

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

    return $resultMessage;
}

/**
 * Public action 'init' - Inits locale
 */
function ct_init_locale() {
    load_plugin_textdomain('cleantalk', FALSE, basename(dirname(__FILE__)) . '/i18n');
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
    if (ct_is_user_enable() === false) {
            return false;
        }
    ?>
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
function ct_set_session() {
    if (ct_is_user_enable() === false) {
        return false;
    }
    // this action is called any time WP process GET request (shows any page)
    // this action is called AFTER wp-comments-post.php executing and AFTER ct_check calling so we can create new session value here
    // it can be any action between init and send_headers, see http://codex.wordpress.org/Plugin_API/Action_Reference
    session_name('cleantalksession');
    if (!isset($_SESSION)) {
        session_start();
        $_SESSION['formtime'] = time();
    }
}

/**
 * Get user role
 * @global type $current_user
 * @return type
 */
function ct_get_user_role() {
    global $current_user;

    $user_roles = $current_user->roles;
    $user_role = array_shift($user_roles);

    return $user_role;
}

/**
 * Is enable for user group
 * @return boolean
 */
function ct_is_user_enable() {
    $disable_roles = array('administrator', 'editor', 'author');
    $user_role = ct_get_user_role();
    if (in_array($user_role, $disable_roles)) {
        return false;
    }
    return true;
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
    global $wpdb, $current_user;

    if (ct_is_user_enable() === false) {
        return $comment;
    }

    $comment_post_id = $comment['comment_post_ID'];

    $post = get_post($comment_post_id);
    
	$checkjs = (int) substr($_POST['ct_checkjs'], 0, 1);
    if ($checkjs !== 0 && $checkjs !== 1)
        $checkjs = 0;

    session_name('cleantalksession');
    if (!isset($_SESSION)) {
        session_start();
    }
    if (array_key_exists('formtime', $_SESSION)) {
        $submit_time = time() - (int) $_SESSION['formtime'];
    } else {
        $submit_time = NULL;
    }

    $user_info = '';
	$post_info = '';
	$example = null;
    if (function_exists('json_encode')) {
        $blog_lang = substr(get_locale(), 0, 2);
        $arr = array(
            'cms_lang' => $blog_lang,
            'REFFERRER' => @$_SERVER['HTTP_REFFERRER'],
            'USER_AGENT' => @$_SERVER['HTTP_USER_AGENT'],
			'sender_url' => $comment['comment_author_url'],
        );
        $user_info = json_encode($arr);
        if ($user_info === FALSE)
            $user_info = '';
			
		$arr = array();
		$arr['comment_type'] = $comment['comment_type']; 
		
		$last_comment = get_comments('number=1');
		$new_comment_ID = isset($last_comment[0]->comment_ID) ? (int) $last_comment[0]->comment_ID + 1 : 1;
		
		$permalink = get_permalink($comment_post_id) !== NULL ? get_permalink($comment_post_id) : null;
		
		$arr['post_url'] = $permalink !== NULL ? $permalink . '#comment-' . $new_comment_ID : null; 

		$post_info = json_encode($arr);
        if ($post_info === FALSE)
			$post_info = '';
		
		if ($post !== NULL){
			$example['title'] = $post->post_title;
			$example['body'] = $post->post_content;
			$example['comments'] = null; 
			
			$last_comments = get_comments(array('status' => 'approve', 'number' >= 10, 'post_id' => $comment_post_id));
			foreach ($last_comments as $post_comment){
				$example['comments'] .= "\n\n" . $post_comment->comment_content;
			}

			$example = json_encode($example);
		}
    }
	
	// Use plain string format if've failed with JSON
	if ($example === FALSE || $example === NULL){
		$example = ($post !== NULL) ? $post->post_content : '';
		$example = ($post->post_title !== NULL) ? $post->post_title . ' ' . $baseText : $baseText;
	}

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
    $ct_request->message = $comment['comment_content'];
    $ct_request->sender_email = $comment['comment_author_email'];
    $ct_request->sender_nickname = $comment['comment_author'];
    $ct_request->example = $example; 
    $ct_request->agent = 'wordpress-144';
    $ct_request->sender_info = $user_info;
    $ct_request->sender_ip = preg_replace('/[^0-9.]/', '', $_SERVER['REMOTE_ADDR']);
    $ct_request->stoplist_check = $options['stopwords'];
    $ct_request->response_lang = $options['language'];
    $ct_request->allow_links = $options['allowlinks'];
    $ct_request->submit_time = $submit_time;
    $ct_request->js_on = $checkjs;
    $ct_request->post_info = $post_info;

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

    if ($ct_result->blacklisted > 0 || $ct_result->fast_submit == 1 ||
            $ct_result->js_disabled == 1) {
        $err_text = '<center><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk.</b> ' . __('Spam protection', 'cleantalk') . "</center><br><br>\n" . $ct_result->comment;
        $err_text .= '<script>setTimeout("history.back()", 5000);</script>';
        wp_die($err_text, 'Blacklisted', array('back_link' => TRUE));
    } else {
        ct_hash($ct_result->id);
        if ($ct_result->allow == 0) {
            if (!empty($ct_result->stop_words)) {
                global $ct_stop_words;
                $ct_stop_words = $ct_result->stop_words;
                add_action('comment_post', 'ct_mark_red', 11, 2);
            }
            $comment['comment_content'] = $ct->addCleantalkComment($comment['comment_content'], $ct_result->comment);
            if ($ct_result->spam == 1) {
                add_filter('pre_comment_approved', 'ct_set_comment_spam');
                global $ct_comment;
                $ct_comment = $ct_result->comment;
                add_action('comment_post', 'ct_die', 12, 2);
            } else {
                add_filter('pre_comment_approved', 'ct_set_not_approved');
            }
        }
        add_action('comment_post', 'ct_set_meta', 10, 2);
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
        wp_die($err_text, 'Blacklisted', array('back_link' => TRUE));
}
/**
 * Public filter 'pre_comment_approved' - Mark comment unapproved always
 * @return 	int Zero
 */
function ct_set_not_approved() {
    return 0;
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
    /* update_comment_meta($comment_id, 'ct_hash', 'huz');
      return; */
    $hash1 = ct_hash();
    if (!empty($hash1)) {
        update_comment_meta($comment_id, 'ct_hash', $hash1);
    }
}

/**
 * Admin action 'comment_unapproved_to_approved' - Approve comment, sends good feedback to cleantalk, removes cleantalk resume
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_approved($comment_object) {
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', TRUE);
    $comment['comment_content'] = ct_unmark_red($comment['comment_content']);
    $comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);
    $comment['comment_approved'] = 1;
    wp_update_comment($comment);
    return TRUE;
}

/**
 * Admin action 'comment_approved_to_unapproved' - Unapprove comment, sends bad feedback to cleantalk
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_unapproved($comment_object) {
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', TRUE);
    ct_feedback($hash, $comment['comment_content'], 0);
    $comment['comment_approved'] = 0;
    wp_update_comment($comment);
    return TRUE;
}

/**
 * Admin actions 'comment_unapproved_to_spam', 'comment_approved_to_spam' - Mark comment as spam, sends bad feedback to cleantalk
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_spam($comment_object) {
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', TRUE);
    ct_feedback($hash, $comment['comment_content'], 0);
    $comment['comment_approved'] = 'spam';
    wp_update_comment($comment);
    return TRUE;
}


/**
 *  Unspam comment
 * @param type $comment_id
 */
function ct_unspam_comment($comment_id) {
    update_comment_meta($comment_id, '_wp_trash_meta_status', 1);
    $comment = get_comment($comment_id, 'ARRAY_A');
    $hash = get_comment_meta($comment_id, 'ct_hash', TRUE);
    $comment['comment_content'] = ct_unmark_red($comment['comment_content']);
    $comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);
    wp_update_comment($comment);
}

/**
 * Admin action 'trash_comment' - Sends bad feedback to cleantalk
 * @param 	int $comment_id Comment ID
 * @return	boolean TRUE
 */
function ct_comment_trash($comment_id) {
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
function ct_get_comment_text($current_text) {
    global $comment;
    $new_text = $current_text;
    if (isset($comment) && is_object($comment)) {
        $hash = get_comment_meta($comment->comment_ID, 'ct_hash', TRUE);
        if (!empty($hash)) {
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
    if ($hook == 'edit-comments.php')
        wp_enqueue_script('ct_reload_script', plugins_url('/cleantalk-rel.js', __FILE__));
}

/**
 * Admin action 'admin_menu' - Add the admin options page
 */
function ct_admin_add_page() {
    add_options_page(__('CleanTalk settings', 'cleantalk'), '<b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk</b>. Spam protection', 'manage_options', 'cleantalk', 'ct_settings_page');
}

/**
 * Admin action 'admin_init' - Add the admin settings and such
 */
function ct_admin_init() {
    register_setting('cleantalk_settings', 'cleantalk_settings', 'ct_settings_validate');

    add_settings_section('cleantalk_settings_main', __('Main settings', 'cleantalk'), 'ct_section_settings_main', 'cleantalk');

    add_settings_field('cleantalk_stopwords', __('Stop words checking', 'cleantalk'), 'ct_input_stopwords', 'cleantalk', 'cleantalk_settings_main');
    add_settings_field('cleantalk_language', __('System messages language', 'cleantalk'), 'ct_input_language', 'cleantalk', 'cleantalk_settings_main');

    add_settings_field('cleantalk_apikey', __('Access key', 'cleantalk'), 'ct_input_apikey', 'cleantalk', 'cleantalk_settings_main');
}

/**
 * Admin callback function - Displays description of 'main' plugin parameters section
 */
function ct_section_settings_main() {

}

/**
 * Admin callback function - Displays inputs of 'stopwords' plugin parameter
 */
function ct_input_stopwords() {
    $options = ct_get_options();
    $value = $options['stopwords'];
    echo "<input type='radio' id='cleantalk_stopwords0' name='cleantalk_settings[stopwords]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_stopwords0'> " . __('No') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_stopwords1' name='cleantalk_settings[stopwords]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_stopwords1'> " . __('Yes') . "</label>";
}

/**
 * Admin callback function - Displays inputs of 'allowlinks' plugin parameter
 */
function ct_input_allowlinks() {
    $options = ct_get_options();
    $value = $options['allowlinks'];
    echo "<input type='radio' id='cleantalk_allowlinks0' name='cleantalk_settings[allowlinks]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_allowlinks0'> " . __('No') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_allowlinks1' name='cleantalk_settings[allowlinks]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_allowlinks1'> " . __('Yes') . "</label>";
}

/**
 * Admin callback function - Displays inputs of 'language' plugin parameter
 */
function ct_input_language() {
    $options = ct_get_options();
    $value = $options['language'];
    echo "<input type='radio' id='cleantalk_language0' name='cleantalk_settings[language]' value='en' " . ($value == 'en' ? 'checked' : '') . " /><label for='cleantalk_language0'> " . __('English', 'cleantalk') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_language1' name='cleantalk_settings[language]' value='ru' " . ($value == 'ru' ? 'checked' : '') . " /><label for='cleantalk_language1'> " . __('Russian', 'cleantalk') . "</label>";
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
        <h2><b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk</b>. Spam protection 

        </h2>
        <form action="options.php" method="post">
            <?php settings_fields('cleantalk_settings'); ?>
            <?php do_settings_sections('cleantalk'); ?>
            <br>
            <a target='__blank' href='http://cleantalk.org/install/wordpress?step=2'><?php _e('Click here to get access key', 'cleantalk'); ?></a>
            <br>
            <br>
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form></div>

    <?php
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
 * Notice blog owner if plugin using without Access key 
 * @return bool 
 */
function admin_notice_message(){    

	if (ct_active() === false)
		return false;

    $options = ct_get_options();
	if ($options['apikey'] === 'enter key' || $options['apikey'] === '')
		echo '<div class="updated"><p>' . __("Please enter the Access Key in <a href=\"options-general.php?page=cleantalk\">CleanTalk plugin</a> settings to enable protection from spam in comments!", 'cleantalk') . '</p></div>';
	
	return true;
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

?>
