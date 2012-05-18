<?php
/**
 * Cleantalk base class
 *
 * @version 0.3.1
 * @package Cleantalk
 * @subpackage Base
 * @author Сleantalk team (shagimuratov@cleantalk.ru)
 * @copyright (C) 2011 - 2012 Сleantalk team (http://cleantalk.ru)
 * @license GNU/GPL: http://www.gnu.org/copyleft/gpl.html
 * 
 **/


require(dirname(__FILE__).'/cleantalk.xmlrpc.php');

class Cleantalk
{
	/**
	 * Main config
	 * @var array
	 */
	public $config = array(
		'auth_key'=>null,
		'server_url'=>null,
		'response_lang'=>null,
	);

	/**
	 * Constructor
	 * @param $config
	 */
	public function Cleantalk($config)
	{
		$this->config = array_merge($this->config, $config);
	}


	/**
	 * Function checks if a user is blacklisted
	 * @param $params
	 * @param $response
	 * @return bool|int
	 */
	public function isAllowUser($params, &$response)
	{
		$params = array_merge(array(
				$params['engine'] => '',
				$params['ip'] => '',
				$params['email'] => '',
				$params['username'] => '',
			), $params);

		return $this->xmlRequest(
			'check_newuser',
			array(
				$this->config['auth_key'],
				$params['engine'],
				$this->config['response_lang'],
				$params['ip'],
				$params['email'],
				$params['username'],
			),
			&$response
		);
	}

	/**
	 * Function checks whether it is possible to publish the message
	 * @param $params
	 * @param $response
	 * @return bool|int
	 */
	public function isAllowMessage($params, &$response)
	{
		$params = array_merge(array(
				$params['message'] => '',
				$params['base_text'] => '',
				$params['engine'] => '',
				$params['url'] => '',
				$params['stoplist_check'] => '',
				$params['session_ip'] => '',
				$params['user_email'] => '',
				$params['user_name'] => '',
                                $params['allow_links'] => ''
			), $params);

		$sender_id = $this->getSenderId();

		$result = $this->xmlRequest(
			'check_message',
			array(
				$params['message'],
				$params['base_text'],
				$this->config['auth_key'],
				$params['engine'],
				$params['url'],
				$params['stoplist_check'],
				$this->config['response_lang'],
				$params['session_ip'],
				$params['user_email'],
				$params['user_name'],
                                $sender_id,
                                $params['allow_links']
			),
			&$response
		);

		if($sender_id == '' && is_array($response) && isset($response['sender_id']))
			$this->setSenderId($response['sender_id']);

		return $result;
	}

	/**
	 * Function sends the results of manual moderation
	 * @param $params
	 * @param $response
	 * @return bool|int
	 */
	public function sendFeedback($params, &$response)
	{
		$feedback = array();
		foreach($params['moderate'] as $msgFeedback)
			$feedback[] = $msgFeedback['msg_hash'] . ':' . intval($msgFeedback['is_allow']);
		$feedback = implode(';', $feedback);

		return $this->xmlRequest(
			'send_feedback',
			array(
				$this->config['auth_key'],
				$feedback,
			),
			&$response
		);
	}

	/**
	 * Function adds to the post comment Cleantalk.ru
	 * @param $message
	 * @param $comment
	 * @return string
	 */
	public function addCleantalkComment($message, $comment)
	{
		$comment = preg_match('/\*\*\*(.+)\*\*\*/', $comment, $matches) ? $comment : '*** '.$comment.' ***';
		return $message . "\n\n" . $comment;
	}

	/**
	 * Function deletes the comment Cleantalk.ru
	 * @param $message
	 * @return mixed
	 */
	public function delCleantalkComment($message)
	{
		$message = preg_replace('/\n\n\*\*\*.+\*\*\*$/', '', $message);
		$message = preg_replace('/\<br.*\>[\n]{0,1}\<br.*\>[\n]{0,1}\*\*\*.+\*\*\*$/', '', $message);
		return $message;
	}

	/**
	 * Function to get the message hash from Cleantalk.ru comment
	 * @param $message
	 * @return null
	 */
	public function getCleantalkCommentHash($message)
	{
		$matches = array();
		if(preg_match('/\n\n\*\*\*.+([a-z0-9]{32}).+\*\*\*$/', $message, $matches))
			return $matches[1];
		else if(preg_match('/\<br.*\>[\n]{0,1}\<br.*\>[\n]{0,1}\*\*\*.+([a-z0-9]{32}).+\*\*\*$/', $message, $matches))
			return $matches[1];

		return NULL;
	}

	/**
	 * Function to get the SenderID
	 * @return string
	 */
	public function getSenderId()
	{
		return ( isset($_COOKIE['ct_sender_id']) && !empty($_COOKIE['ct_sender_id']) ) ? $_COOKIE['ct_sender_id'] : '';
	}

	/**
	 * Function to change the SenderID
	 * @param $senderId
	 * @return bool
	 */
	public function setSenderId($senderId)
	{
		return @setcookie('ct_sender_id', $senderId);
	}

	
	/**
	 * Function XML-request
	 * @param $method
	 * @param $params
	 * @param $response
	 * @return bool|int
	 */
	private function xmlRequest($method, $params, &$response)
	{
		$xmlvars = array();
		foreach ($params as $param)
			$xmlvars[] = new xmlrpcval($param);

		$ct_params = new xmlrpcmsg(
			$method,
			array(new xmlrpcval($xmlvars,"array"))
		);

		$ct_request = new xmlrpc_client($this->config['server_url']);
		$ct_request->request_charset_encoding = 'utf-8';
		$ct_request->return_type = 'phpvals';
		$ct_request->setDebug(0);
		$response = $ct_request->send($ct_params);

		if ($response->faultCode())
		{
			return false;
		}

		$response = $response->value();
		return true;
	}
	
	public function xmlRequest2($method, $params, $server_url = '')
	{
		$xmlvars = array();
		$response = null;
		foreach ($params as $param)
			$xmlvars[] = new xmlrpcval($param);

		$ct_params = new xmlrpcmsg(
			$method,
			array(new xmlrpcval($xmlvars,"array"))
		);

		$server_url = isset($server_url) ? $server_url : $this->config['server_url'];
		
		$ct_request = new xmlrpc_client($server_url);
		$ct_request->request_charset_encoding = 'utf-8';
		$ct_request->return_type = 'phpvals';
		$ct_request->setDebug(0);
		$response = $ct_request->send($ct_params);
			
		return $response;
	}
}
