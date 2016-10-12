<?php
/*************************************************

Parser - the PHP net client
Author: Jacky Yu <jacky325@qq.com>
Copyright (c): 2012-2015 Jacky Yu, All rights reserved
Version: 1.0.0

* This library is free software; you can redistribute it and/or modify it.
* You may contact the author of Parser by e-mail at: jacky325@qq.com

The latest version of Parser can be obtained from:
https://github.com/uniqid/collector

*************************************************/

if(!defined('IN_COLLECTOR')) {
	exit('Access Denied');
}

class Parser
{
	public function __construct()
	{

	}

	public function getLinks($data, $options = array('link', 'href', 'name'))
	{
		!is_array($options) && $options = (array)$options;
		foreach($options as $val){
			$results[$val] = array();
		}
		preg_match_all("'<\s*a\s[^>]*href\s*=\s*([\"\'])?(?(1)([^>\"\']*)\\1|([^\s\>\'\"]*))[^>]*>(.*?)</a>'is", $data, $match);
		foreach($match[0] as $key => $link){
			$href = !empty($match[2][$key])? $match[2][$key]: $match[3][$key];
			if(empty($href) || $href == '#' || $href == '/'){
				continue;
			}
			in_array('link', $options) && $results['link'][] = trim($link);
			in_array('href', $options) && $results['href'][] = trim($href);
			in_array('name', $options) && $results['name'][] = trim($match[4][$key]);
		}
		return $results;
	}

	public function expandLinks($links, $URI)
	{
		preg_match("/^[^\?]+/", $URI, $match); //strip paramter
		$match = preg_replace("|/[^\/\.]+\.[^\/\.]+$|", "", $match[0]); //strip script name
		$match = preg_replace("|/$|","",$match); //strip last '/'
		$match_part = parse_url($match);
		if(isset($match_part["scheme"])){
			$match_root = $match_part["scheme"] . "://" . $match_part["host"];
		}
		else{
			$match_root = "http://" . $match_part["path"];
		}

		$patterns = array(
			"|^".preg_quote($match_root)."|i",
			"|^(\/)|i",
			"|^(?!http://)(?!https://)(?!mailto:)|i",
			"|/\./|",
			"|/[^\/]+/\.\./|"
		);
		$replaces = array(
			"",
			$match_root."/",
			$match."/",
			"/",
			"/"
		);
		$expandedLinks = preg_replace($patterns, $replaces, $links);
		return $expandedLinks;
	}

	public function reorganizationLinks($names, $hrefs, $extend = ''){
		$links = array();
		foreach($names as $key => $name){
			$links[] = '<a href="' .$hrefs[$key]. '"'.($extend? ' '.$extend: '').'>' .($name? $name: '#'). '</a>';
		}
		return $links;
	}

	public function getText($data)
	{
		$patterns = array(
			'/<script[^>]*>.*?<\/script>/is',
			'/<style[^>]*>.*?<\/style>/is',
			'/<[\/\!]*?[^<>]*?>/is',
			'/[\r\n\t\s]*/is',
			'/\&nbsp;/',
		);

		$replaces = array(
			'',
			'',
			'',
			'',
			'',
		);

		$data = preg_replace($patterns, $replaces, $data);
		return $data;
	}

	public function getPreText($data)
	{
		$patterns = array('/<script[^>]*>.*?<\/script>/is', '/<style[^>]*>.*?<\/style>/is');
		$replaces = array('', '');

		$data = preg_replace($patterns, $replaces, $data);
		$data = strip_tags($data);
		$data = preg_replace('/\s+/', "\n", $data);
		return '<pre>' . $data . '</pre>';
	}

	public function getForms($data)
	{
		preg_match_all("'<\/?(form|input|select|textarea|(option))[^<>]*?>(?(2)(.*(?=<\/?(option|select)[^<>]*?>[\r\n]*?)|(?=[\r\n]*))|(?=[\r\n]*))'Uis", $data, $elems);
		$forms = implode("\r\n", $elems[0]);
		return $forms;
	}

	public function getImages($data, $options = array('img', 'src', 'alt'))
	{
		!is_array($options) && $options = (array)$options;
		foreach($options as $val){
			$results[$val] = array();
		}
		preg_match_all("'<\s*img\s[^>]*?src\s*=\s*([\"\'])?(?(1)([^>\"\']*)\\1|([^\s\>\'\"]*))[^>]*>'is", $data, $match);
		foreach($match[0] as $key => $img){
			$src = !empty($match[2][$key])? $match[2][$key]: $match[3][$key];
			if(empty($src) || $src == '\\'){
				continue;
			}
			in_array('img', $options) && $results['img'][] = trim($img);
			in_array('src', $options) && $results['src'][] = trim($src);
			if(in_array('alt', $options)){
				preg_match_all("'\s+alt\s*=\s*([\"\'])?(?(1)([^>\"\']*)\\1|([^\s\>\'\"]*))'is", $img, $alts);
				$alt = !empty($alts[2][0])? $alts[2][0]: (!empty($alts[3][0])? $alts[3][0]: '');
				$results['alt'][] = trim($alt);
			}
		}
		return $results;
	}

	public function reorganizationImages($srcs, $alts = array(), $extend = ''){
		$imgs = array();
		foreach($srcs as $key => $src){
			$imgs[] = '<img src="' .$src. '"'.(isset($alts[$key])? ' alt="'.$alts[$key].'"': '').($extend? ' '.$extend: '').' />';
		}
		return $imgs;
	}

	public function getTables($data)
	{
		preg_match_all("'<table([^>]*)>.*?</table>'is", $data, $elems);
		$tables = array();
		foreach($elems[0] as $elem){
			$tables[] = preg_replace('/<(table|tbody|tr|th|td)[^>]*>/is', '<\\1>', $elem);
		}
		$tables = implode("\r\n", $tables);
		return $tables;
	}

	public function stripTags($data, $options = array('a', 'script', 'iframe')){
		$patterns = $replaces = array();
		if(in_array('a', $options)){
			$patterns[] = "'<\s*a\s[^>]*>|</a>'is";
			$replaces[] = '';
 		}

		if(in_array('img', $options)){
			$patterns[] = "'<\s*img\s[^>]*>'is";
			$replaces[] = '';
		}

		if(in_array('iframe', $options)){
			$patterns[] = "'<\s*iframe\s[^>]*>|</iframe>'is";
			$replaces[] = '';
		}

		if(in_array('script', $options)){
			$patterns[] = '/<\s*script[^>]*>.*?<\/script>/is';
			$replaces[] = '';
		}

		if(in_array('style', $options)){
			$patterns[] = '/<\s*style[^>]*>.*?<\/style>/is';
			$replaces[] = '';
		}

		$data = preg_replace($patterns, $replaces, $data);
		return $data;
	}

	public function getCssLinks($data, $options = array('link', 'href')){
		!is_array($options) && $options = (array)$options;
		foreach($options as $val){
			$results[$val] = array();
		}
		preg_match_all("'<\s*link\s[^>]*?href\s*=\s*([\"\'])?(?(1)([^>\"\']*)\\1|([^\s\>\'\"]*))[^>]*>'is", $data, $match);
		foreach($match[0] as $key => $img){
			if(empty($match[2][$key]) || $match[2][$key] == '#' || $match[2][$key] == '/'){
				continue;
			}
			in_array('link', $options) && $results['link'][] = trim($img);
			in_array('href', $options) && $results['href'][] = trim($match[2][$key]);
		}
		return $results;
	}

	public function getScriptLinks($data, $options = array('script', 'src')){
		!is_array($options) && $options = (array)$options;
		foreach($options as $val){
			$results[$val] = array();
		}
		preg_match_all("'<\s*script\s[^>]*?src\s*=\s*([\"\'])?(?(1)([^>\'\"]*?)\\1|([^\s\>\'\"]*))[^>]*>'is", $data, $match);
		foreach($match[0] as $key => $img){
			if(empty($match[2][$key]) || $match[2][$key] == '#' || $match[2][$key] == '/'){
				continue;
			}
			in_array('script', $options) && $results['script'][] = trim($img);
			in_array('src', $options) && $results['src'][] = trim($match[2][$key]);
		}
		return $results;
	}

	public function getBackgroundImages($data){
		preg_match_all("'url\s*\([\'\"]?([^\)\'\"\+\#]*)[\'\"]?\)'is", $data, $elems);
		$results = array();
		foreach($elems[1] as $elem){
			$results[] = trim($elem);
		}
		return $results;
	}

	public function __destruct()
	{

	}
}
?>
