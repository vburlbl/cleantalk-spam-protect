<?php

/**
 * Generates random password 
 * @param  int $length
 * @param  string $list
 * @return string	
 */
function ct_random_password($length = 4, $list = '') {
    return substr(str_shuffle($list), 0, $length); 
}

/**
 * Check COOKIES
 * @return null 
 */
function ct_check_cookies() {
    global $ct_session_name, $ct_plugin_name;

    if (!isset($_COOKIE[$ct_session_name])) {
        wp_die('<p>Sorry, this is error. Please enable Cookies in your browser and try again! ' . $ct_plugin_name . '</p>', null, array('back_link' => true));
    }

    return null;
}

?>
