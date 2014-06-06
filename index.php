<?php

define('IN_COLLECTOR', true);
define('APP_PATH', dirname(__FILE__) . '/');

require(APP_PATH . 'lib/picker.php');
require(APP_PATH . 'lib/parser.php');
$picker = new Picker();
$parser = new Parser();


$url = 'http://www.baidu.com/';
$picker->fetch($url);


$links = $parser->getLinks($picker->results); //print_r($links);
$expLinks = $parser->expandLinks($links['href'], $url); //print_r($expLinks);
$reorganizationLinks = $parser->reorganizationLinks($links['name'],  $links['href']);//echo implode('<br/>',$reorganizationLinks);

$text = $parser->getText($picker->results);  //echo $text;
$preText = $parser->getPreText($picker->results);  //echo $preText;

$forms = $parser->getForms($picker->results);  //print_r($forms);

$imgs  = $parser->getImages($picker->results); //print_r($imgs);
$reorganizationImages = $parser->reorganizationImages($imgs['src'], $imgs['alt']); //echo implode('<br/>',$reorganizationImages);

$tables  = $parser->getTables($picker->results); //print_r($tables);

$stripTagsTxt = $parser->stripTags($picker->results); //echo $stripTagsTxt;

$styles  = $parser->getCssLinks($picker->results);    //print_r($styles);

$scripts = $parser->getScriptLinks($picker->results); //print_r($scripts);

$backgrouds = $parser->getBackgroundImages($picker->results); //print_r($backgrouds);
$reorganizationImages = $parser->reorganizationImages($backgrouds);  echo implode('<br/>',$reorganizationImages);

?>

