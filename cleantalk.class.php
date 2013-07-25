<?php
/**
 * Cleantalk base class
 *
 * @version 0.20.2
 * @package Cleantalk
 * @subpackage Base
 * @author Сleantalk team (welcome@cleantalk.ru)
 * @copyright (C) 2013 СleanTalk team (http://cleantalk.org)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * @see http://cleantalk.ru/wiki/doku.php/api
 *
 */

/**
* @ignore
*/
require_once(dirname(__FILE__) . '/cleantalk.xmlrpc.php');

/**
 * Response class
 */
class CleantalkResponse {

    /**
     * Unique user ID
     * @var string
     */
    public $sender_id = null;

    /**
     *  Is stop words
     * @var int
     */
    public $stop_words = null;

    /**
     * Cleantalk comment
     * @var string
     */
    public $comment = null;

    /**
     * Is blacklisted
     * @var int
     */
    public $blacklisted = null;

    /**
     * Is allow, 1|0
     * @var int
     */
    public $allow = null;

    /**
     * Request ID
     * @var int
     */
    public $id = null;

    /**
     * Request errno
     * @var int
     */
    public $errno = null;

    /**
     * Error string
     * @var string
     */
    public $errstr = null;

    /**
     * Is fast submit, 1|0
     * @var string
     */
    public $fast_submit = null;

    /**
     * Is spam comment
     * @var string
     */
    public $spam = null;

    /**
     * Is JS
     * @var type 
     */
    public $js_disabled = null;

    /**
     * Sms check
     * @var type 
     */
    public $sms_allow = null;

    /**
     * Sms code result
     * @var type 
     */
    public $sms = null;
	
    /**
     * Sms error code
     * @var type 
     */
    public $sms_error_code = null;
	
    /**
     * Sms error code
     * @var type 
     */
    public $sms_error_text = null;
    
	/**
     * Stop queue message, 1|0
     * @var int  
     */
    public $stop_queue = null;
	
    /**
     * Account shuld by deactivated after registration, 1|0
     * @var int  
     */
    public $inactive = null;

    /**
     * Create server response
     *
     * @param type $response
     * @param xmlrpcresp $obj
     */
    function __construct($response = null, xmlrpcresp $obj = null) {
        if ($response && is_array($response) && count($response) > 0) {
            foreach ($response as $param => $value) {
                $this->{$param} = $value;
            }
        } else {
            $this->errno = $obj->errno;
            $this->errstr = $obj->errstr;

			$this->errstr = preg_replace("/.+(\*\*\*.+\*\*\*).+/", "$1", $this->errstr);

            // Разбираем xmlrpcresp ответ с клинтолка
            if ($obj->val !== 0) {
                $this->sender_id = (isset($obj->val['sender_id'])) ? $obj->val['sender_id'] : null;
                $this->stop_words = isset($obj->val['stop_words']) ? $obj->val['stop_words'] : null;
                $this->comment = $obj->val['comment'];
                $this->blacklisted = (isset($obj->val['blacklisted'])) ? $obj->val['blacklisted'] : null;
                $this->allow = (isset($obj->val['allow'])) ? $obj->val['allow'] : null;
                $this->id = (isset($obj->val['id'])) ? $obj->val['id'] : null;
                $this->fast_submit = (isset($obj->val['fast_submit'])) ? $obj->val['fast_submit'] : 0;
                $this->spam = (isset($obj->val['spam'])) ? $obj->val['spam'] : 0;
                $this->js_disabled = (isset($obj->val['js_disabled'])) ? $obj->val['js_disabled'] : 0;
                $this->sms_allow = (isset($obj->val['sms_allow'])) ? $obj->val['sms_allow'] : null;
                $this->sms = (isset($obj->val['sms'])) ? $obj->val['sms'] : null;
                $this->sms_error_code = (isset($obj->val['sms_error_code'])) ? $obj->val['sms_error_code'] : null;
                $this->sms_error_text = (isset($obj->val['sms_error_text'])) ? $obj->val['sms_error_text'] : null;
                $this->stop_queue = (isset($obj->val['stop_queue'])) ? $obj->val['stop_queue'] : 0;
                $this->inactive = (isset($obj->val['inactive'])) ? $obj->val['inactive'] : 0;
            } else {
                $this->comment = $this->errstr . '. Automoderator cleantalk.org';
            }
        }
    }

}

/**
 * Request class
 */
class CleantalkRequest {

    const VERSION = '0.7';

    /**
     * User message
     * @var string
     */
    public $message = null;

    /**
     * Post example with last comments
     * @var string
     */
    public $example = null;

    /**
     * Auth key
     * @var string
     */
    public $auth_key = null;

    /**
     * Engine
     * @var string
     */
    public $agent = null;

    /**
     * Is check for stoplist,
     * valid are 0|1
     * @var int
     */
    public $stoplist_check = null;

    /**
     * Language server response,
     * valid are 'en' or 'ru'
     * @var string
     */
    public $response_lang = null;

    /**
     * User IP
     * @var strings
     */
    public $sender_ip = null;

    /**
     * User email
     * @var strings
     */
    public $sender_email = null;

    /**
     * User nickname
     * @var string
     */
    public $sender_nickname = null;

    /**
     * Sender info JSON string
     * @var string
     */
    public $sender_info = null;

    /**
     * Post info JSON string
     * @var string
     */
    public $post_info = null;

    /**
     * Is allow links, email and icq,
     * valid are 1|0
     * @var int
     */
    public $allow_links = 0;

    /**
     * Time form filling
     * @var int
     */
    public $submit_time = null;

    /**
     * Is enable Java Script,
     * valid are 0|1|2
	 * Status:
	 *  null - JS html code not inserted into phpBB templates
	 *  0 - JS disabled at the client browser
	 *  1 - JS enabled at the client broswer
     * @var int
     */
    public $js_on = null;

    /**
     * user time zone
     * @var string
     */
    public $tz = null;

    /**
     * Feedback string,
     * valid are 'requset_id:(1|0)'
     * @var string
     */
    public $feedback = null;

    /**
     * Phone number
     * @var type 
     */
    public $phone = null;

    /**
     * Fill params with constructor
     * @param type $params
     */
    public function __construct($params = null) {
        if (is_array($params) && count($params) > 0) {
            foreach ($params as $param => $value) {
                $this->{$param} = $value;
            }
        }
    }

}

/**
 * Cleantalk class create request
 */
class Cleantalk {

    /**
     * Debug level
     * @var int
     */
    public $debug = 0;
	
    /**
	* Maximum data size in bytes
	* @var int
	*/
	private $dataMaxSise = 32768;
	
	/**
	* Data compression rate 
	* @var int
	*/
	private $compressRate = 6;
	
    /**
	* Server connection timeout in seconds 
	* @var int
	*/
	private $server_timeout = 2;

    /**
     * Cleantalk server url
     * @var string
     */
    public $server_url = null;

    /**
     * Last work url
     * @var string
     */
    public $work_url = null;

    /**
     * WOrk url ttl
     * @var int
     */
    public $server_ttl = null;

    /**
     * Time wotk_url changer
     * @var int
     */
    public $server_changed = null;

    /**
     * Flag is change server url
     * @var bool
     */
    public $server_change = false;

    /**
     * Use TRUE when need stay on server. Example: send feedback
     * @var bool
     */
    public $stay_on_server = false;

    /**
     * Function checks whether it is possible to publish the message
     * @param CleantalkRequest $request
     * @return type
     */
    public function isAllowMessage(CleantalkRequest $request) {
        $error_params = $this->filterRequest('check_message', $request);

        if (!empty($error_params)) {
            $response = new CleantalkResponse(
                            array(
                                'allow' => 0,
                                'comment' => 'CleanTalk. Request params error: ' . implode(', ', $error_params)
                            ), null);

            return $response;
        }

        $msg = $this->createMsg('check_message', $request);
        return $this->xmlRequest($msg);
    }

    /**
     * Function checks whether it is possible to publish the message
     * @param CleantalkRequest $request
     * @return type
     */
    public function isAllowUser(CleantalkRequest $request) {
        $error_params = $this->filterRequest('check_newuser', $request);

        if (!empty($error_params)) {
            $response = new CleantalkResponse(
                            array(
                                'allow' => 0,
                                'comment' => 'CleanTalk. Request params error: ' . implode(', ', $error_params)
                            ), null);

            return $response;
        }

        $msg = $this->createMsg('check_newuser', $request);
        return $this->xmlRequest($msg);
    }

    /**
     * Function sends the results of manual moderation
     *
     * @param CleantalkRequest $request
     * @return type
     */
    public function sendFeedback(CleantalkRequest $request) {
        $error_params = $this->filterRequest('send_feedback', $request);

        if (!empty($error_params)) {
            $response = new CleantalkResponse(
                            array(
                                'allow' => 0,
                                'comment' => 'Cleantalk. Spam protect. Request params error: ' . implode(', ', $error_params)
                            ), null);

            return $response;
        }

        $msg = $this->createMsg('send_feedback', $request);
        return $this->xmlRequest($msg);
    }

    /**
     *  Filter request params
     * @param CleantalkRequest $request
     * @return type
     */
    private function filterRequest($method, CleantalkRequest $request) {
        $error_params = array();

        // general and optional
        foreach ($request as $param => $value) {
            if (in_array($param, array('message', 'example', 'agent',
                        'sender_info', 'sender_nickname', 'post_info', 'phone')) && !empty($value)) {
                if (!is_string($value) && !is_integer($value)) {
                    $error_params[] = $param;
                }
            }

            if (in_array($param, array('stoplist_check', 'allow_links')) && !empty($value)) {
                if (!in_array($value, array(1, 2))) {
                    $error_params[] = $param;
                }
            }
            
            if (in_array($param, array('js_on')) && !empty($value)) {
                if (!is_integer($value)) {
                    $error_params[] = $param;
                }
            }

            if ($param == 'sender_ip' && !empty($value)) {
                if (!is_string($value)) {
                    $error_params[] = $param;
                }
            }

            if ($param == 'sender_email' && !empty($value)) {
                if (!is_string($value)) {
                    $error_params[] = $param;
                }
            }

            if ($param == 'submit_time' && !empty($value)) {
                if (!is_int($value)) {
                    $error_params[] = $param;
                }
            }
        }

        // special and must be
        switch ($method) {
            case 'check_message':
                
                // Convert strings to UTF8
                $this->message = $this->stringToUTF8($request->message);
                $this->example = $this->stringToUTF8($request->example);

                $request->message = $this->compressData($request->message);
				$request->example = $this->compressData($request->example);
                break;

            case 'check_newuser':
                if (empty($request->sender_nickname)) {
                    $error_params[] = 'sender_nickname';
                }
                if (empty($request->sender_email)) {
                    $error_params[] = 'sender_email';
                }
                break;

            case 'send_feedback':
                if (empty($request->feedback)) {
                    $error_params[] = 'feedback';
                }
                break;
        }
        
        if (isset($request->sender_info)) {
            if (function_exists('json_decode')){
                $sender_info = json_decode($request->sender_info, true);

                // Save request's creation timestamps 
                if (function_exists('microtime'))
                    $sender_info['request_submit_time'] = (float) (time() + (float) microtime()); 
                
                if (function_exists('json_encode')){
                    $request->sender_info = json_encode($sender_info);

                }
            }
        }
        
        return $error_params;
    }
    
	/**
     * Compress data and encode to base64 
     * @param type string
     * @return string 
     */
	private function compressData($data = null){
		
		if (strlen($data) > $this->dataMaxSise && function_exists('gzencode') && function_exists('base64_encode')){

			$localData = gzencode($data, $this->compressRate, FORCE_GZIP);

			if ($localData === false)
				return $data;
			
			$localData = base64_encode($localData);
			
			if ($localData === false)
				return $data;
			
			return $localData;
		}

		return $data;
	} 

    /**
     * Create msg for cleantalk server
     * @param type $method
     * @param CleantalkRequest $request
     * @return \xmlrpcmsg
     */
    private function createMsg($method, CleantalkRequest $request) {
        switch ($method) {
            case 'check_message':
                $params = array(
                    'message' => $request->message,
                    'base_text' => $request->example,
                    'auth_key' => $request->auth_key,
                    'agent' => $request->agent,
                    'sender_info' => $request->sender_info,
                    'ct_stop_words' => $request->stoplist_check,
                    'response_lang' => $request->response_lang,
                    'session_ip' => $request->sender_ip,
                    'user_email' => $request->sender_email,
                    'user_name' => $request->sender_nickname,
                    'post_info' => $request->post_info,
                    'ct_links' => $request->allow_links,
                    'submit_time' => $request->submit_time,
                    'js_on' => $request->js_on);
                break;

            case 'check_newuser':
                $params = array(
                    'auth_key' => $request->auth_key,
                    'agent' => $request->agent,
                    'response_lang' => $request->response_lang,
                    'session_ip' => $request->sender_ip,
                    'user_email' => $request->sender_email,
                    'user_name' => $request->sender_nickname,
                    'tz' => $request->tz,
                    'submit_time' => $request->submit_time,
                    'js_on' => $request->js_on,
                    'phone' => $request->phone,
                    'sender_info' => $request->sender_info);
                break;

            case 'send_feedback':
                if (is_array($request->feedback)) {
                    $feedback = implode(';', $request->feedback);
                } else {
                    $feedback = $request->feedback;
                }

                $params = array(
                    'auth_key' => $request->auth_key, // !
                    'feedback' => $feedback);
                break;
        }

        $xmlvars = array();
        foreach ($params as $param) {
            $xmlvars[] = new xmlrpcval($param);
        }

        $ct_params = new xmlrpcmsg(
                        $method,
                        array(new xmlrpcval($xmlvars, "array"))
        );

        return $ct_params;
    }

    /**
     * XM-Request
     * @param xmlrpcmsg $msg
     * @return boolean|\CleantalkResponse
     */
    private function xmlRequest(xmlrpcmsg $msg) {
        if (((isset($this->work_url) && $this->work_url !== '') && ($this->server_changed + $this->server_ttl > time()))
				|| $this->stay_on_server == true) {
	        
            $url = (!empty($this->work_url)) ? $this->work_url : $this->server_url;
            $ct_request = new xmlrpc_client($url);
            $ct_request->request_charset_encoding = 'utf-8';
            $ct_request->return_type = 'phpvals';
            $ct_request->setDebug($this->debug);
					
            $result = $ct_request->send($msg, $this->server_timeout);
        }

        if ((!isset($result) || $result->errno != 0) && $this->stay_on_server == false) {
            $matches = array();
            preg_match("#^(http://|https://)([a-z\.\-0-9]+):?(\d*)$#i", $this->server_url, $matches);
            $url_prefix = $matches[1];
            $pool = $matches[2];
            $port = $matches[3];
            if (empty($url_prefix))
                $url_prefix = 'http://';
            if (empty($pool)) {
                return false;
            } else {
                foreach ($this->get_servers_ip($pool) as $server) {
                    if ($server['host'] === 'localhost' || $server['ip'] === null) {
                        $work_url = $url_prefix . $server['host'];
                    } else {
                        $server_host = gethostbyaddr($server['ip']);
                        $work_url = $url_prefix . $server_host;
                    }
                    
                    $work_url = ($port !== '') ? $work_url . ':' . $port : $work_url;

                    $this->work_url = $work_url;
                    $this->server_ttl = $server['ttl'];
                    $ct_request = new xmlrpc_client($this->work_url);
                    $ct_request->request_charset_encoding = 'utf-8';
                    $ct_request->return_type = 'phpvals';
                    $ct_request->setDebug($this->debug);
                    $result = $ct_request->send($msg, $this->server_timeout);

                    if (!$result->faultCode()) {
                        $this->server_change = true;
                        break;
                    }
                }
            }
        }

        $response = new CleantalkResponse(null, $result);

        if (!empty($response->sender_id)) {
            $this->setSenderId($response->sender_id);
        }
        return $response;
    }

    /**
     * Function DNS request
     * @param $host
     * @return array
     */
    public function get_servers_ip($host) {
        $response = null;
        if (!isset($host))
            return $response;

        if (function_exists('dns_get_record')) {
            $records = dns_get_record($host, DNS_A);

            if ($records !== FALSE) {
                foreach ($records as $server) {
                    $response[] = $server;
                }
            }
        }

        if (count($response) == 0 && function_exists('gethostbynamel')) {
            $records = gethostbynamel($host);

            if ($records !== FALSE) {
                foreach ($records as $server) {
                    $response[] = array("ip" => $server,
                        "host" => $host,
                        "ttl" => $this->server_ttl
                    );
                }
            }
        }

        if (count($response) == 0) {
            $response[] = array("ip" => null,
                "host" => $host,
                "ttl" => $this->server_ttl
            );
        } else {

            // $i - to resolve collisions with localhost and 
            $i = 0;
            $r_temp = null;
            foreach ($response as $server) {
                $ping = $this->httpPing($server['ip']);
                
                // -1 server is down, skips not reachable server
                if ($ping != -1)
                    $r_temp[$ping * 10000 + $i] = $server;

                $i++;
            }
            if (count($r_temp)){
                ksort($r_temp);
                $response = $r_temp;
            }
        }

        return $response;
    }

    /**
     * Function to get the SenderID
     * @return string
     */
    public function getSenderId() {
        return ( isset($_COOKIE['ct_sender_id']) && !empty($_COOKIE['ct_sender_id']) ) ? $_COOKIE['ct_sender_id'] : '';
    }

    /**
     * Function to change the SenderID
     * @param $senderId
     * @return bool
     */
    private function setSenderId($senderId) {
        return @setcookie('ct_sender_id', $senderId);
    }

    /**
     * Function to get the message hash from Cleantalk.ru comment
     * @param $message
     * @return null
     */
    public function getCleantalkCommentHash($message) {
        $matches = array();
        if (preg_match('/\n\n\*\*\*.+([a-z0-9]{32}).+\*\*\*$/', $message, $matches))
            return $matches[1];
        else if (preg_match('/\<br.*\>[\n]{0,1}\<br.*\>[\n]{0,1}\*\*\*.+([a-z0-9]{32}).+\*\*\*$/', $message, $matches))
            return $matches[1];

        return NULL;
    }

    /**
     * Function adds to the post comment Cleantalk.ru
     * @param $message
     * @param $comment
     * @return string
     */
    public function addCleantalkComment($message, $comment) {
        $comment = preg_match('/\*\*\*(.+)\*\*\*/', $comment, $matches) ? $comment : '*** ' . $comment . ' ***';
        return $message . "\n\n" . $comment;
    }

    /**
     * Function deletes the comment Cleantalk.ru
     * @param $message
     * @return mixed
     */
    public function delCleantalkComment($message) {
        $message = preg_replace('/\n\n\*\*\*.+\*\*\*$/', '', $message);
        $message = preg_replace('/\<br.*\>[\n]{0,1}\<br.*\>[\n]{0,1}\*\*\*.+\*\*\*$/', '', $message);
        return $message;
    }

    /*
       Get user IP
    */
    public function ct_session_ip( $data_ip )
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        {
            $forwarded_for = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlentities($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
        }

        // 127.0.0.1 usually used at reverse proxy
        $session_ip = ($data_ip == '127.0.0.1' && !empty($forwarded_for)) ? $forwarded_for : $data_ip;

        return $session_ip;
    }
    
    /**
    * Function to check response time
    * param string
    * @return int
    */
    function httpPing($host){

        // Skip localhost ping cause it raise error at fsockopen.
        // And return minimun value 
        if ($host == 'localhost')
            return 0.001;

        $starttime = microtime(true);
        $file      = @fsockopen ($host, 80, $errno, $errstr, $this->server_timeout);
        $stoptime  = microtime(true);
        $status    = 0;

        if (!$file) {
            $status = -1;  // Site is down
        } else {
            fclose($file);
            $status = ($stoptime - $starttime);
            $status = round($status, 4);
        }
        
        return $status;
    }
    
    /**
    * Function convert string to UTF8 and removes non UTF8 characters 
    * param string
    * @return string
    */
    function stringToUTF8($str){
        
        if (!preg_match('//u', $str) && function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding')) {
            $encoding = mb_detect_encoding($str);
            $srt = mb_convert_encoding($str, 'UTF-8', $encoding);
        }
        
        return $str;
    }
}

?>
