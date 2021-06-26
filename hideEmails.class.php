<?php

require_once 'functions.php';

/**
* Use this class to find and replace instances of email addresses with a link to the contact form with a token of who to email.
* This will help protect against email harvesting that will lead to spam.
*/

define('URL_BASE', (SANDBOX) ? 'https://dev.example.com' : 'https://example.com');

class hideEmails {

	public function replaceEmails($text) {
		return preg_replace_callback_array(
			[
				'/\<a ([^\>]*)href=[\'"]mailto:([A-Za-z]+\@example\.com)[\'"]([^\>]*)\>([^\<]+)\<\/a\>/i' => function ($match) {
					$link_text = (!empty($match[4]) && $match[4] != $match[2]) ? $match[4] : 'send a message';
					return '<a ' . $match[1] . 'href="' . URL_BASE . '/contact-us/?token=' . $this->getEmailToken($match[2]) . '"' . $match[3] . ' target="_blank">' . $link_text . '</a>';
				},
				'/([A-Za-z]+\@example\.com)/i' => function ($match) {
					return '<a href="' . URL_BASE . '/contact-us/?token=' . $this->getEmailToken($match[1]) . '" target="_blank">send a message</a>';
				},
			],
			$text
		);
	}

	public function getEmailToken($email = 'office@example.com') {

		static $token;

		if (!empty($token[$email])) {
			return $token[$email];
		}

		global $databaseWrapper;

		$res = $databaseWrapper->query('SELECT token FROM ' . $databaseWrapper::DB_MAIN_SITE . '.email_tokens WHERE email = ?', [$email]);
		$row = $databaseWrapper->fetch($res);

		if (!empty($row['token'])) {
			return $token[$email] = $row['token'];
		} elseif (empty($row)) {
			$token[$email] = md5($email . microtime());
			$res = $databaseWrapper->query('INSERT INTO ' . $databaseWrapper::DB_MAIN_SITE . '.email_tokens (`email`, `token`) VALUES (?, ?)', [$email, $token[$email]]);
			return $token[$email];
		}
		return NULL;
	}


	public function getTokenEmail($token = NULL) {

		if (empty($token) || strlen($token) != 32) {
			return NULL;
		}

		static $email;

		if (!empty($email[$token])) {
			return $email[$token];
		}

		global $databaseWrapper;

		$res = $databaseWrapper->query('SELECT email FROM ' . $databaseWrapper::DB_MAIN_SITE . '.email_tokens WHERE token = ?', [$token]);
		$row = $databaseWrapper->fetch($res);

		if (!empty($row['email'])) {
			return $email[$token] = $row['email'];
		}
		return NULL;
	}

	public function getEmailName($email = NULL) {
		if (empty($email)) {
			$email = 'office@example.com';
		}

		static $name;

		if (!empty($name[$email])) {
			return $name[$email];
		}

		switch ($email) {
			case 'office@example.com':
				$name[$email] = 'The Company Office';
				break;
			case 'admin@example.com':
				$name[$email] = 'The Admin Team';
				break;
		}

		if (!empty($name[$email])) {
			return $name[$email];
		}

		global $databaseWrapper;

		$res = $databaseWrapper->query('SELECT user_FullName FROM ' . $databaseWrapper::DB_INTRA . '.users WHERE user_Email = ?', [$email]);
		$row = $databaseWrapper->fetch($res);

		if (!empty($row['user_FullName'])) {
			return $name[$email] = $row['user_FullName'];
		}

		# Attempt to get a name from AD.
		$ad = ldap_connect('192.168.0.123');
		ldap_set_option($ad, LDAP_OPT_REFERRALS, 0);
		ldap_set_option($ad, LDAP_OPT_PROTOCOL_VERSION, 3);
		
		$bind = @ldap_bind($ad, 'DOMAIN\\test', 'testtt');
		$search = ldap_search($ad, 'dc=example,dc=local', 'samaccountname=' . str_ireplace('@example.com', '', $email));
		$count = ldap_count_entries($ad, $search);
		$entries = ldap_get_entries($ad, $search); 
		ldap_unbind($ad);
		if (!empty($entries[0]['displayname'][0])) {
			return $name[$email] = $entries[0]['displayname'][0];
		}
		return 'The Office';
	}
}