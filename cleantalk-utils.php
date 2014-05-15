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

?>
