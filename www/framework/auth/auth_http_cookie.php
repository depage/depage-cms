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


class auth_http_cookie extends auth_http_basic {
	public function enforce() {
		$user = $this->enforce_lazy();
		if (!$user)
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
		if ($this->has_session()) {
			$this->logout($_COOKIE[session_name()]);
			$this->destroy_session();
		}
	}
		
	public function login($username, $password) {
		$user = auth_user::get_by_username($this->pdo, $username);
		$hash = $this->hash_user_pass($username, $password);
		if ($user && $user->passwordhash == $hash) {
			// destroy session if logging in directly after registering user
			$this->destroy_session();
			$this->register_session($user->id);
			$this->start_session();
			return true;
		}

//		throw new Exception("Login failed! Wrong username password combination.");
		return false;
	}

	protected function auth_cookie() {
		if ($this->has_session()) {
			if ($this->is_valid_sid($_COOKIE[session_name()]) !== false) {
				$this->set_sid($_COOKIE[session_name()]);
				$this->start_session();

				$user = auth_user::get_by_sid($this->pdo, $this->get_sid());
				return $user;
			} else {
				$this->destroy_session();
			}
		}

//		throw new Exception("you are not allowed to do this!");
		return false;
	}
}

/* vim:set ft=php sw=4 sts=4 fdm=marker : */

?>
