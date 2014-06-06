<?php
if(!defined('IN_COLLECTOR')) {
	exit('Access Denied');
}
/*************************************************

Picker - the PHP net client
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2013 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Picker by e-mail at: jacky325@qq.com

The latest version of Picker can be obtained from:
https://github.com/flyfishsoft/collector

*************************************************/

class Picker
{
	public $results     = '';   // Where the content is put
	public $timeout     = 20;   // The maximum number of seconds to allow CURL functions to execute. 
	public $cookie_path = '';   // Temporary directory that the webserver has permission to write to.
	public $cookie_file = '';   // Cookie file name
	public $logs_path   = '';   // Temporary directory that the webserver has permission to write to.
	public $log_file    = '';   // log file name

	public function __construct($config = array())
	{
		$tmp_path = dirname(dirname(realpath(__FILE__))) . '/tmp/';
		$config   = array_merge(array(
			'cookie' => $tmp_path . 'cookie',
			'cache'  => $tmp_path . 'cache',
			'logs'   => $tmp_path . 'logs'
		), (array) $config);

		$this->cookie_path = $config['cookie'];
		$this->logs_path   = $config['logs'];
		$this->log_file    = $this->logs_path . '/picker.log';
		!is_dir($this->cookie_path) && @mkdir($this->cookie_path, 0777, true);
		!is_dir($this->logs_path) && @mkdir($this->logs_path, 0777, true);
		if(!is_dir($this->cookie_path) || !is_dir($this->logs_path)){
			echo 'Init temporary directory failure';
			exit;
		}
	}

	public function fetch($URI)
	{
		$URI_PARTS = parse_url($URI);
		$scheme    = isset($URI_PARTS["scheme"])? strtolower($URI_PARTS["scheme"]): 'http';
		switch($scheme){
			case "http":
			case "https":
				$this->_grab_web_content($scheme, $URI, 'get');
				break;
			default:
				error_log(date('Y-m-d H:i:s') . " - Unknow scheme.\n\n", 3, $this->log_file);
				return false;
		}		
		return true;
	}

	public function submit($URI, $formvars = array(), $formfiles = array())
	{
		$URI_PARTS = parse_url($URI);
		$scheme = isset($URI_PARTS["scheme"])? strtolower($URI_PARTS["scheme"]): 'http';
		switch($scheme){
			case "http":
			case "https":
				$this->_grab_web_content($scheme, $URI, 'post', $formvars, $formfiles);
				break;
			default:
				error_log(date('Y-m-d H:i:s') . " - Unknow scheme.\n\n", 3, $this->log_file);
				return false;
		}		
		return true;
	}

	private function _grab_web_content($scheme, $URI, $method, $formvars = array(), $formfiles = array())
	{	
		$this->results = '';
		empty($this->cookie_file) && $this->cookie_file = tempnam($this->cookie_path, "cookie");
		$ch = curl_init();
		if($scheme == 'https'){
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}

		if($method == "get"){
			curl_setopt($ch, CURLOPT_POST, false);
		}
		else{
			if(!empty($formfiles) && is_array($formfiles)){
				foreach($formfiles as $key => $val){
					$formvars[$key] = "@" . $val;
				}
			}
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $formvars);
		}

		if($this->timeout > 0){
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_URL, $URI);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1; rv:29.0) Gecko/20100101 Firefox/29.0');
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_file);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_file);
		$results = curl_exec($ch);
		curl_close($ch);
		$this->results = $results;
	}

	public function __destruct()
	{
		is_file($this->cookie_file) && unlink($this->cookie_file);
	}
}
?>
