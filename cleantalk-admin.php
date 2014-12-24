<?php

$ct_plugin_basename = 'cleantalk-spam-protect/cleantalk.php';

// Timeout to get app server
$ct_server_timeout = 2;

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
    add_options_page(__('CleanTalk settings', 'cleantalk'), '<b style="color: #49C73B;">Clean</b><b style="color: #349ebf;">Talk</b>', 'manage_options', 'cleantalk', 'ct_settings_page');
}

/**
 * Admin action 'admin_init' - Add the admin settings and such
 */
function ct_admin_init() {
    global $show_ct_notice_trial, $ct_notice_trial_label, $show_ct_notice_online, $ct_notice_online_label, $trial_notice_showtime, $ct_plugin_name, $ct_options, $trial_notice_check_timeout, $account_notice_check_timeout, $ct_user_token_label, $ct_account_status_check;

    $ct_options = ct_get_options();

    $show_ct_notice_trial = false;
    if (isset($_COOKIE[$ct_notice_trial_label])) {
        if ($_COOKIE[$ct_notice_trial_label] == 1) {
            $show_ct_notice_trial = true;
        }
    }

    if (time() > $ct_options['next_account_status_check']) {
        $result = false;
	    if (function_exists('curl_init') && function_exists('json_decode') && ct_valid_key($ct_options['apikey'])) {
            $url = 'https://cleantalk.org/app_notice';
            $server_timeout = 2;
            $data['auth_key'] = $ct_options['apikey']; 
            $data['param'] = 'notice_paid_till'; 

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $server_timeout);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

            // receive server response ...
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // resolve 'Expect: 100-continue' issue
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $result = curl_exec($ch);
            curl_close($ch);
            
            $notice_check_timeout = $trial_notice_check_timeout; 
            if ($result) {
                $result = json_decode($result, true);
                if (isset($result['show_notice'])) {
                    if ($result['show_notice'] == 1 && isset($result['trial']) && $result['trial'] == 1) {
                        $show_ct_notice_trial = true;
                    }
                    
                    if ($result['show_notice'] == 0) {
                        $notice_check_timeout = $account_notice_check_timeout; 
                    }
                }
                
                if (isset($result['user_token'])) {
                    $ct_options['user_token'] = $result['user_token']; 
                }
            }
            
            // Save next status request time
            $ct_options['next_account_status_check'] = strtotime("+$notice_check_timeout hours", time());
            $ct_account_status_check = time(); 
            update_option('cleantalk_settings', $ct_options);
        }
        
        if ($result) {
            setcookie($ct_notice_trial_label, (int) $show_ct_notice_trial, strtotime("+$trial_notice_showtime minutes"), '/');
        }
    }

    $show_ct_notice_online = '';
    if (isset($_COOKIE[$ct_notice_online_label])) {
        if ($_COOKIE[$ct_notice_online_label] !== '0' && time() - $_COOKIE[$ct_notice_online_label] <= 5) {
            $show_ct_notice_online = 'Y';
	    } else {
            $show_ct_notice_online = '';
        }

        if ($_COOKIE[$ct_notice_online_label] === '0') {
            $show_ct_notice_online = 'N';
	    }
    }

    ct_init_session();
    
    register_setting('cleantalk_settings', 'cleantalk_settings', 'ct_settings_validate');
    add_settings_section('cleantalk_settings_main', __($ct_plugin_name, 'cleantalk'), 'ct_section_settings_main', 'cleantalk');
    add_settings_section('cleantalk_settings_anti_spam', __('Anti-spam settings', 'cleantalk'), 'ct_section_settings_anti_spam', 'cleantalk');
    add_settings_field('cleantalk_apikey', __('Access key', 'cleantalk'), 'ct_input_apikey', 'cleantalk', 'cleantalk_settings_main');
    add_settings_field('cleantalk_remove_old_spam', __('Automatically delete spam comments', 'cleantalk'), 'ct_input_remove_old_spam', 'cleantalk', 'cleantalk_settings_main');
    
    add_settings_field('cleantalk_registrations_test', __('Registration forms', 'cleantalk'), 'ct_input_registrations_test', 'cleantalk', 'cleantalk_settings_anti_spam');
    add_settings_field('cleantalk_comments_test', __('Comments form', 'cleantalk'), 'ct_input_comments_test', 'cleantalk', 'cleantalk_settings_anti_spam');
    add_settings_field('cleantalk_contact_forms_test', __('Contact forms', 'cleantalk'), 'ct_input_contact_forms_test', 'cleantalk', 'cleantalk_settings_anti_spam');
    add_settings_field('cleantalk_general_contact_forms_test', __('Custom contact forms', 'cleantalk'), 'ct_input_general_contact_forms_test', 'cleantalk', 'cleantalk_settings_anti_spam');
}

/**
 * Admin callback function - Displays description of 'main' plugin parameters section
 */
function ct_section_settings_main() {
    return true;
}

/**
 * Admin callback function - Displays description of 'anti-spam' plugin parameters section
 */
function ct_section_settings_anti_spam() {
    return true;
}

/**
 * Admin callback function - Displays inputs of 'apikey' plugin parameter
 */
function ct_input_apikey() {
    global $ct_options, $ct_notice_online_label;
    
    $value = $ct_options['apikey'];
    $def_value = ''; 
    echo "<input id='cleantalk_apikey' name='cleantalk_settings[apikey]' size='20' type='text' value='$value' style=\"font-size: 14pt;\"/>";
    if (ct_valid_key($value) === false) {
        echo "<a target='__blank' style='margin-left: 10px' href='http://cleantalk.org/install/wordpress?step=2'>".__('Click here to get access key', 'cleantalk')."</a>";
    } else{
        if (isset($_COOKIE[$ct_notice_online_label]) && $_COOKIE[$ct_notice_online_label] > 0) {
            echo '&nbsp;&nbsp;<span style="text-decoration: underline;">The key accepted!</span>&nbsp;<img src="' . plugin_dir_url(__FILE__) . 'inc/images/yes.png" alt=""  height="" />'; 
        }
    }
}

/**
 * Admin callback function - Displays inputs of 'comments_test' plugin parameter
 */
function ct_input_comments_test() {
    global $ct_options;
    
    $value = $ct_options['comments_test'];
    echo "<input type='radio' id='cleantalk_comments_test1' name='cleantalk_settings[comments_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_comments_test1'> " . __('Yes') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_comments_test0' name='cleantalk_settings[comments_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_comments_test0'> " . __('No') . "</label>";
    admin_addDescriptionsFields(__('WordPress, JetPack, WooCommerce', 'cleantalk'));
}

/**
 * Admin callback function - Displays inputs of 'comments_test' plugin parameter
 */
function ct_input_registrations_test() {
    global $ct_options;
    
    $value = $ct_options['registrations_test'];
    echo "<input type='radio' id='cleantalk_registrations_test1' name='cleantalk_settings[registrations_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_registrations_test1'> " . __('Yes') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_registrations_test0' name='cleantalk_settings[registrations_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_registrations_test0'> " . __('No') . "</label>";
    admin_addDescriptionsFields(__('WordPress, BuddyPress, bbPress, S2Member, WooCommerce', 'cleantalk'));
}

/**
 * Admin callback function - Displays inputs of 'contact_forms_test' plugin parameter
 */
function ct_input_contact_forms_test() {
    global $ct_options;
    
    $value = $ct_options['contact_forms_test'];
    echo "<input type='radio' id='cleantalk_contact_forms_test1' name='cleantalk_settings[contact_forms_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_contact_forms_test1'> " . __('Yes') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_contact_forms_test0' name='cleantalk_settings[contact_forms_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_contact_forms_test0'> " . __('No') . "</label>";
    admin_addDescriptionsFields(__('Contact Form 7, Formiadble forms, JetPack, Fast Secure Contact Form, WordPress Landing Pages', 'cleantalk'));
}

/**
 * Admin callback function - Displays inputs of 'general_contact_forms_test' plugin parameter
 */
function ct_input_general_contact_forms_test() {
    global $ct_options;
    
    $value = $ct_options['general_contact_forms_test'];
    echo "<input type='radio' id='cleantalk_general_contact_forms_test1' name='cleantalk_settings[general_contact_forms_test]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_general_contact_forms_test1'> " . __('Yes') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_general_contact_forms_test0' name='cleantalk_settings[general_contact_forms_test]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_general_contact_forms_test0'> " . __('No') . "</label>";
    admin_addDescriptionsFields(__('Anti spam test for any WordPress or themes contacts forms', 'cleantalk'));
}

/**
 * @author Artem Leontiev
 * Admin callback function - Displays inputs of 'Publicate relevant comments' plugin parameter
 *
 * @return null
 */
function ct_input_remove_old_spam() {
    $options = ct_get_options();
    $value = $options['remove_old_spam'];
    echo "<input type='radio' id='cleantalk_remove_old_spam1' name='cleantalk_settings[remove_old_spam]' value='1' " . ($value == '1' ? 'checked' : '') . " /><label for='cleantalk_remove_old_spam1'> " . __('Yes') . "</label>";
    echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
    echo "<input type='radio' id='cleantalk_remove_old_spam0' name='cleantalk_settings[remove_old_spam]' value='0' " . ($value == '0' ? 'checked' : '') . " /><label for='cleantalk_remove_old_spam0'> " . __('No') . "</label>";
    admin_addDescriptionsFields(sprintf(__('Delete spam comments older than %d days.', 'cleantalk'),  $options['spam_store_days']));
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
<style type="text/css">
input[type=submit] {padding: 10px; background: #3399FF; color: #fff; border:0 none;
    cursor:pointer;
    -webkit-border-radius: 5px;
    border-radius: 5px; 
    font-size: 12pt;
}
</style>

    <div>
        <form action="options.php" method="post">
            <?php settings_fields('cleantalk_settings'); ?>
            <?php do_settings_sections('cleantalk'); ?>
            <br>
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form>
    </div>
    <?php

    if (ct_valid_key() === false)
        return null;
    ?>
    <br />
    <br />
    <br />
    <div>
    <?php echo __('Plugin Homepage at', 'cleantalk'); ?> <a href="http://cleantalk.org" target="_blank">cleantalk.org</a>.
    </div>
    <?php
}

/**
 * Notice blog owner if plugin is used without Access key 
 * @return bool 
 */
function admin_notice_message(){
    global $show_ct_notice_trial, $show_ct_notice_online, $ct_plugin_name;

    $options = ct_get_options();
    $user_token = '';
    if (isset($options['user_token']) && $options['user_token'] != '') {
        $user_token = '&user_token=' . $options['user_token'];
    }

    $show_notice = true;
    if ($show_notice && ct_valid_key($options['apikey']) === false) {
        echo '<div class="updated"><h3>' . sprintf(__("Please enter Access Key in %s settings to enable anti spam protection!", 'cleantalk'), "<a href=\"options-general.php?page=cleantalk\">CleanTalk plugin</a>") . '</h3></div>';
        $show_notice = false;
    }

    if ($show_notice && $show_ct_notice_trial) {
        echo '<div class="updated"><h3>' . sprintf(__("%s trial period ends, please upgrade to %s!", 'cleantalk'), "<a href=\"options-general.php?page=cleantalk\">$ct_plugin_name</a>", "<a href=\"http://cleantalk.org/my/bill/recharge?utm_source=wp-backend&utm_medium=cpc&utm_campaign=WP%20backend%20trial$user_token\" target=\"_blank\"><b>premium version</b></a>") . '</h3></div>';
        $show_notice = false;
    }

    if ($show_notice && $show_ct_notice_online != '') {
        echo '<div class="updated"><h3><b>';
        if($show_ct_notice_online === 'Y'){
                echo __("Don’t forget to disable CAPTCHA if you have it!", 'cleantalk');
        }
        
        if($show_ct_notice_online === 'N'){
                echo __("Wrong <a href=\"options-general.php?page=cleantalk\"><b style=\"color: #49C73B;\">Clean</b><b style=\"color: #349ebf;\">Talk</b> access key</a>! Please check it or ask <a target=\"_blank\" href=\"https://cleantalk.org/forum/\">support</a>.", 'cleantalk');
        }
        echo '</b></h3></div>';
    }

    //ct_send_feedback(); -- removed to ct_do_this_hourly()

    return true;
}

/**
 * @author Artem Leontiev
 *
 * Add descriptions for field
 */
function admin_addDescriptionsFields($descr = '') {
    echo "<div style='font-size: 10pt; color: #666 !important'>$descr</div>";
}

/**
* Test API key 
*/
function ct_valid_key($apikey = null) {
    if ($apikey === null) {
        $options = ct_get_options();
        $apikey = $options['apikey'];
    }

    return ($apikey === 'enter key' || $apikey === '') ? false : true;
}

/**
 * Admin action 'comment_unapproved_to_approved' - Approve comment, sends good feedback to cleantalk, removes cleantalk resume
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_approved($comment_object) {
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', true);

    $comment['comment_content'] = ct_unmark_red($comment['comment_content']);
    $comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);
    $comment['comment_approved'] = 1;
    wp_update_comment($comment);

    return true;
}

/**
 * Admin action 'comment_approved_to_unapproved' - Unapprove comment, sends bad feedback to cleantalk
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_unapproved($comment_object) {
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', true);
    ct_feedback($hash, $comment['comment_content'], 0);
    $comment['comment_approved'] = 0;
    wp_update_comment($comment);

    return true;
}

/**
 * Admin actions 'comment_unapproved_to_spam', 'comment_approved_to_spam' - Mark comment as spam, sends bad feedback to cleantalk
 * @param 	object $comment_object Comment object
 * @return	boolean TRUE
 */
function ct_comment_spam($comment_object) {
    $comment = get_comment($comment_object->comment_ID, 'ARRAY_A');
    $hash = get_comment_meta($comment_object->comment_ID, 'ct_hash', true);
    ct_feedback($hash, $comment['comment_content'], 0);
    $comment['comment_approved'] = 'spam';
    wp_update_comment($comment);

    return true;
}


/**
 * Unspam comment
 * @param type $comment_id
 */
function ct_unspam_comment($comment_id) {
    update_comment_meta($comment_id, '_wp_trash_meta_status', 1);
    $comment = get_comment($comment_id, 'ARRAY_A');
    $hash = get_comment_meta($comment_id, 'ct_hash', true);
    $comment['comment_content'] = ct_unmark_red($comment['comment_content']);
    $comment['comment_content'] = ct_feedback($hash, $comment['comment_content'], 1);

    wp_update_comment($comment);
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
        $hash = get_comment_meta($comment->comment_ID, 'ct_hash', true);
        if (!empty($hash)) {
            $new_text .= '<hr>Cleantalk ID = ' . $hash;
        }
    }
    return $new_text;
}

/**
 * Send feedback for user deletion 
 * @return null 
 */
function ct_delete_user($user_id) {
    $hash = get_user_meta($user_id, 'ct_hash', true);
    if ($hash !== '') {
        ct_feedback($hash, null, 0);
    }
}

/**
 * Manage links and plugins page
 * @return array
*/
if (!function_exists ( 'ct_register_plugin_links')) {
    function ct_register_plugin_links($links, $file) {
        global $ct_plugin_basename;
	    
    	if ($file == $ct_plugin_basename) {
		    $links[] = '<a href="options-general.php?page=cleantalk">' . __( 'Settings' ) . '</a>';
		    $links[] = '<a href="http://wordpress.org/plugins/cleantalk-spam-protect/faq/" target="_blank">' . __( 'FAQ','cleantalk' ) . '</a>';
		    $links[] = '<a href="http://cleantalk.org/forum" target="_blank">' . __( 'Support','cleantalk' ) . '</a>';
	    }
	    return $links;
    }
}

/**
 * Manage links in plugins list
 * @return array
*/
if (!function_exists ( 'ct_plugin_action_links')) {
    function ct_plugin_action_links($links, $file) {
        global $ct_plugin_basename;

        if ($file == $ct_plugin_basename) {
            $settings_link = '<a href="options-general.php?page=cleantalk">' . __( 'Settings' ) . '</a>';
            array_unshift( $links, $settings_link ); // before other links
        }
        return $links;
    }
}

/**
 * After options update
 * @return array
*/
function ct_update_option($option_name) {
    global $show_ct_notice_online, $ct_notice_online_label, $ct_notice_trial_label, $trial_notice_showtime, $ct_account_status_check, $ct_options, $ct_server_timeout;

    if($option_name !== 'cleantalk_settings') {
        return;
    }


    // Skip test call if the function executet during account status check
    if ($ct_account_status_check > 0 && time() - $ct_account_status_check < 5) {
        return;
    }
    
    $key_valid = true;
    $app_server_error = false;
    if (function_exists('curl_init') && function_exists('json_decode')) {
        $api_key = $ct_options['apikey'];
        if (isset($_POST['cleantalk_settings']['apikey'])) {
            $api_key = $_POST['cleantalk_settings']['apikey']; 
        }
        
        if (!ct_valid_key($api_key)) {
            return null;
        }

        $url = 'https://cleantalk.org/app_notice';
        $server_timeout = $ct_server_timeout;

        $data['auth_key'] = $api_key; 
        $data['param'] = 'notice_validate_key'; 

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $server_timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // receive server response ...
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // resolve 'Expect: 100-continue' issue
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = curl_exec($ch);
        curl_close($ch);
        if ($result) {
            $result = json_decode($result, true);
            if (isset($result['valid']) && $result['valid'] == 0) {
                $key_valid = false;
            }
        }
        if (!$result || !isset($result['valid'])) {
            $app_server_error = true;
        }
    }
    
    if ($key_valid) {
        // Removes cookie for server errors
        if ($app_server_error) {
            setcookie($ct_notice_online_label, null, -1, '/');
            unset($_COOKIE[$ct_notice_online_label]);
        } else {
            setcookie($ct_notice_online_label, time(), strtotime("+14 days"), '/');
        }
        setcookie($ct_notice_trial_label, (int) 0, strtotime("+$trial_notice_showtime minutes"), '/');
    } else {
        setcookie($ct_notice_online_label, 0, null, '/');
    }
}

?>
