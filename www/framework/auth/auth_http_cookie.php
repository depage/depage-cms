<?php
/**
 * @file    auth_http_cookie.php
 *
 * User and Session Handling Library
 *
 * This file contains classes for session
 * handling. 
 *
 *
 * copyright (c) 2010 Lion Vollnhals
 *
 * @author    Lion Vollnhals
 */
	
class auth_http_cookie extends auth {
	public function __construct($pdo, $realm, $domain) {
		parent::__construct($pdo, $realm, $domain);

		$this->secret_word = "DO NOT TELL ANYONE!!!1eins"; 
	}

	public function enforce() {
		$user = $this->enforce_lazy();
		if (!user)
			throw new Exception("You are not allowed here!");
		
		return $user;
	}

	public function enforce_lazy() {
		if ($this->user === null) {
			$this->user = $this->auth_cookie();
		}

		return $this->user;
	}

	public function enforce_logout() {
		setcookie('login', '', time() - 3600);
	}
		
	public function login($username, $password) {
		$user = auth_user::get_by_username($this->pdo, $username);
		$hash = md5($username . ':' . $this->realm . ':' . $password);
		if ($user && $user->passwordhash == $hash) {
			setcookie('login', $username . ',' . md5($username . $this->secret_word));
			return true;
		}
//		throw new Exception("Login failed! Wrong username password combination.");
		return false;
	}

	protected function auth_cookie() {
		if ($_COOKIE['login']) {
			list($c_username, $c_hash) = split(',',$_COOKIE['login']);
			if (md5($c_username . $this->secret_word) == $c_hash) {
				$user = auth_user::get_by_username($this->pdo, $c_username);
				return $user;
			}
		}

//		throw new Exception("you are not allowed to do this!");
		return false;
	}
}

?>
