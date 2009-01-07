<?php

$path_extra = '/var/simplesamlphp-openwiki/lib';
$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);



include('/var/simplesamlphp-openwiki/www/_include.php');



/**
 * Loading simpleSAMLphp libraries
 */

/*
 * Loading Foodle libraries
 */
require_once('../lib/Foodle.class.php');
require_once('../lib/FoodleListings.php');


/**
 * Initializating configuration
 */
SimpleSAML_Configuration::init(dirname(dirname(__FILE__)) . '/config', 'foodle');
SimpleSAML_Configuration::init('/var/simplesamlphp-openwiki/config');

$config = SimpleSAML_Configuration::getInstance('foodle');

// Starting sessions.
session_start();



try {

	
	/* Load simpleSAMLphp, configuration and metadata */
	$sspconfig = SimpleSAML_Configuration::getInstance();
	$session = SimpleSAML_Session::getInstance();
	
	/* Check if valid local session exists.. */
	if (!isset($session) || !$session->isValid('saml2') ) {
		SimpleSAML_Utilities::redirect(
			'/' . $sspconfig->getValue('baseurlpath') .
			'saml2/sp/initSSO.php',
			array('RelayState' => SimpleSAML_Utilities::selfURL())
			);
	}
	$attributes = $session->getAttributes();
	
	$userid = 'na';
	if (isset($attributes['mail'])) {
		$userid = $attributes['mail'][0];
	}
	if (isset($attributes['eduPersonPrincipalName'])) {
		$userid = $attributes['eduPersonPrincipalName'][0];
	}
	
	
	
	$displayname = 'NA';
	if (isset($attributes['cn'])) 
		$displayname = $attributes['cn'][0];
	
	if (isset($attributes['displayName'])) 
		$displayname = $attributes['displayName'][0];
	
	
	
	if (!isset($_SESSION['foodle_cache'])) {
		$_SESSION['foodle_cache'] = array();
	}
	
	
	$link = mysql_connect(
		$config->getValue('db.host', 'localhost'), 
		$config->getValue('db.user'),
		$config->getValue('db.pass'));
	if(!$link){
		throw new Exception('Could not connect to database: '.mysql_error());
	}
	mysql_select_db($config->getValue('db.name','feidefoodle'));
	
	
	
	

	
	$fl = new FoodleListings($userid, $link);
	$entries = $fl->getYourEntries();
	
	$allentries = null;
	if (in_array($userid, array('andreas@rnd.feide.no', 'andreas@uninett.no')))
		$allentries = $fl->getAllEntries(25);
		
	$ownerentries = $fl->getOwnerEntries($userid, 10);	

	/*
	echo 'entries:<pre>';
	print_r($entries);
	exit;
	*/
	
	/*
	Array
(
    [0] => Array
        (
            [id] => tkgnpz3m
            [foodleid] => tkgnpz3m
            [userid] => andreas@rnd.feide.no
            [username] => Andreas Solberg
            [response] => 1,1,0
            [name] => test 2
            [descr] => sdfsdf
            [columns] => Thu 26. Jun|Fri 27. Jun|Sat 28. Jun
        )

    [1] => Array
        (
            [id] => hvcm1j8s
            [foodleid] => hvcm1j8s
            [userid] => andreas@rnd.feide.no
            [username] => Andreas Solberg
            [response] => 0,0
            [name] => Publishers meeting
            [descr] => Meeting with Dutch Publishers in Utrecht
            [columns] => Tue 24. Jun(I will attend lunch,I will attend drink)
        )

)
*/
	
	


	$et = new SimpleSAML_XHTML_Template($config, 'foodlefront.php', 'foodle_foodle');
	$et->data['yourentries'] = $entries;
	$et->data['allentries'] = $allentries;
	$et->data['ownerentries'] = $ownerentries;
	$et->data['userid'] = $userid;
	$et->data['displayname'] = $displayname;
	$et->data['bread'] = array(array('title' => 'bc_frontpage'));
	$et->show();

} catch(Exception $e) {

	$et = new SimpleSAML_XHTML_Template($config, 'foodleerror.php', 'foodle_foodle');
	$et->data['bread'] = array(array('href' => '/', 'title' => 'bc_frontpage'), array('title' => 'bc_errorpage'));
	$et->data['message'] = $e->getMessage();
	$et->show();

}


?>