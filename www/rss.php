<?php

$path_extra = '/var/simplesamlphp-openwiki/lib';
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);


include('/var/simplesamlphp-openwiki/www/_include.php');


/**
 * Loading simpleSAMLphp libraries
 */
require_once('SimpleSAML/Configuration.php');
require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

/*
 * Loading Foodle libraries
 */
require_once('../lib/Foodle.class.php');
require_once('../lib/RSS.class.php');
#require_once('../lib/OpenWikiDictionary.class.php');

/**
 * Initializating configuration
 */
SimpleSAML_Configuration::init(dirname(dirname(__FILE__)) . '/config', 'foodle');
SimpleSAML_Configuration::init('/var/simplesamlphp-openwiki/config');

$config = SimpleSAML_Configuration::getInstance('foodle');

// Starting sessions.
session_start();


#include('../config/groups.php');

try {

	/* Load simpleSAMLphp, configuration and metadata */
	$sspconfig = SimpleSAML_Configuration::getInstance();

	
	if (!isset($_SESSION['foodle_cache'])) {
		$_SESSION['foodle_cache'] = array();
	}
	
	
	/*
	 * What wiki are we talking about?
	 */
	$thisfoodle = null;
	if (isset($_REQUEST['id'])) {
		$_SESSION['id'] = $_REQUEST['id'];
		$thisfoodle = $_REQUEST['id'];
	} elseif(isset($_SESSION['id'])) {
		$thisfoodle = $_SESSION['id'];
	}
	if (empty($thisfoodle)) throw new Exception('No foodle selected');
	
	
	
	$link = mysql_connect(
		$config->getValue('db.host', 'localhost'), 
		$config->getValue('db.user'),
		$config->getValue('db.pass'));
	if(!$link){
		throw new Exception('Could not connect to database: '.mysql_error());
	}
	mysql_select_db($config->getValue('db.name','feidefoodle'));
	
	
	
	
	// TODO: REMOVE true to enable caching..
	if (! array_key_exists($thiswiki,$_SESSION['foodle_cache'] ) || true) {
	
		$foodle = new Foodle($thisfoodle, 'rss@example.org', $link);
		$_SESSION['foodle_cache'][$thisfoodle] =& $foodle;
		
	} else {
	
		$foodle =& $_SESSION['foodle_cache'][$thiswiki];
	
	}
	

	$name = $foodle->getName();
	$descr = $foodle->getDescr();
	$entries = $foodle->getOtherEntries();
	
	$identifier = $foodle->getIdentifier();
	
	$url = 'https://foodle.feide.no/foodle.php?id=' . $identifier;
	
	$et->data['expire'] = $foodle->getExpire();
	$et->data['expired'] = $foodle->expired();
	$et->data['expiretext'] = $foodle->getExpireText();
	$et->data['columns'] = $foodle->getColumns();
	
	
	function encodeSingleResponse($r) {
		if ($r == 1) {
			return '☒';
		}
		return '☐';
	}

	function encodeResponse($r) {
		$k = array();
		foreach ($r AS $nr) {
			$k[] = encodeSingleResponse($nr);
		}
		return join(' ', $k);
	}

// 	echo '<pre>';
// 	print_r($entries);
	
	$rssentries = array();
	foreach ($entries AS $entry) {
		$newrssentry = array(
			'title' => $entry['username'] . ' (' . $entry['userid'] . ')',
			'description' => 'Response: ' . encodeResponse($entry['response']),
			'pubDate' => $entry['created'],
#			'link' => $url, 
		);
		if (isset($entry['notes'])) {
			$newrssentry['description'] .= '<br /><strong>Comment from user: </strong><i>' . $entry['notes'] . '</i>';
		}
		$newrssentry['description'] .= '<br />[ <a href="' . $url . '">go to foodle</a> ]';
		
		$rssentries[] = $newrssentry;
	}
	
	$rss = new RSS($name);
	$rsstext = $rss->get($rssentries);
	
	
	header('Content-Type: text/xml');
	echo $rsstext;
	
	
	
} catch(Exception $e) {

	

}

?>