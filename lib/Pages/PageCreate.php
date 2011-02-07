<?php

class Pages_PageCreate extends Pages_Page {
	
	private $user;
	private $auth;
	
	private $timezone;
	
	function __construct($config, $parameters) {
		parent::__construct($config, $parameters);
		$this->auth();
		
		$this->timezone = new TimeZone(NULL, $this->user);
	}
	
	// Authenticate the user
	private function auth() {
		$this->auth = new FoodleAuth($this->fdb);
		$this->auth->requireAuth(FALSE);

		$this->user = $this->auth->getUser();
	}
	
	
	function addEntry() {
	
		$foodle = new Data_Foodle($this->fdb);
		$foodle->updateFromPost($this->user);
		#echo '<pre>'; print_r($foodle); exit;
		$foodle->save();
		
		if (isset($this->user->email)) {
			$this->sendMail($foodle);
		}
		
		$newurl = FoodleUtils::getUrl() . 'foodle/' . $foodle->identifier . '#distribute';
		SimpleSAML_Utilities::redirect($newurl);
		exit;

	}
	
	protected function sendMail($foodle) {
	
		if (!$this->user->notification('newfoodle', FALSE)) {
			error_log('Foodle was updated, but mail notification was not sent because of users preferences');
			return;
		}
		error_log('Foodle was updated, sending notification!');
		
		
		$profileurl = FoodleUtils::getUrl() . 'profile/';
		$url = FoodleUtils::getUrl() . 'foodle/' . $foodle->identifier;
		$name = $foodle->name;
		$to = $this->user->email;
		$mail = '
		
		<p>Hi, your new Foodle named <i>' . htmlspecialchars($name) . '</i> was successfully created.</p>
		
		<p>You may visit the Foodle link below to respond to the foodle or to view other responses:
		<ul>
			<li><a href="' . $url . '">Response to this Foodle</a></li>
			<li><a href="' . $url . '#responses">View responses of other participants</a></li>
		</ul></p>
		
		<p>If you want so invite others to respond to this Foodle, you should share the link below:</p>
		
		<pre><code>' . htmlspecialchars($url) . '</code></pre>
		
		<p>You can turn of this e-mail notification, and configure other notification messages <a href="' . 
			htmlspecialchars($profileurl) . '">from your Foodle preference page</a>:</p>
		
		<pre><code>' . htmlspecialchars($profileurl) . '</code></pre>

		
		';
		$mailer = new Foodle_EMail($to, 'New foodle: ' . htmlspecialchars($name), 'Foodl.org <no-reply@foodl.org>');
		$mailer->setBody($mail);
		$mailer->send();
		
		#echo '<pre>'; print_r($mail); exit;

	}
	
	
	// Process the page.
	function show() {

		if (isset($_REQUEST['save'])) $this->addEntry();

		$t = new SimpleSAML_XHTML_Template($this->config, 'foodlecreate.php', 'foodle_foodle');

		$t->data['authenticated'] = $this->auth->isAuth();
		$t->data['user'] = $this->user;	
		$t->data['loginurl'] = $this->auth->getLoginURL();
		$t->data['logouturl'] = $this->auth->getLogoutURL('/');
		$t->data['today'] = date('Y-m-d');
		$t->data['tomorrow'] = date('Y-m-d', time() + 60*60*24 );
		
		$t->data['allowChangeColumn'] = TRUE;
		
		$t->data['timezone'] = $this->timezone;

		$t->data['bread'] = array(
			array('href' => '/', 'title' => 'bc_frontpage'), 
			array('title' => 'bc_createnew')
		);
		$t->show();

	}
	
}

