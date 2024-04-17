<?php

class Widget_UserManager extends SiteEngine_Widget {

	public function process($widgetParams, $requestMethod, $requestParams) {
	}

	private static $currentUser = false;
	private static $currentUserId = null;
	private static $loginFormIncluded = false;

	public static function setCurrentUserId($id) {
		self::$currentUserId = $id;
	}

	public static function resetCurrentUser() {
		self::$currentUser = false;
		self::$currentUserId = null;
	}

	public static function getCurrentUser() {
		if (self::$currentUser === false && self::$currentUserId !== null) {
			self::$currentUser = R::findOne('user', 'id = ?', array(self::$currentUserId));
		}
		return self::$currentUser !== false ? self::$currentUser : null;
	}

	public static function requireUserRights($rights, $showLoginForm = true) {
		if (!is_array($rights)) $rights = array($rights);
		$user = self::getCurrentUser();
		if ($user !== null) {
			$userRights = explode(',', $user->rights);
			$ok = true;
			foreach($rights as $right) {
				if (!in_array($right, $userRights)) {
					$ok = false;
					break;
				}
			}
			if ($ok) return $user;
		}
		
		if (!self::$loginFormIncluded && $showLoginForm && getSite()->isUIQuery()) {

			// No user logged in or the current user is not the correct type, show login form.
			$widget = new Widget_UserManager(getSite(), 'userManager');
			$labels = array();
			if (array_key_exists('um-login', $_POST) && array_key_exists('um-password', $_POST)) {
				$labels['loginResult'] = 'Wrong login/password. Please try again.';
			}
			$widget->outputOnLoadScriptCode('document.getElementById(\'um-login\').focus()');
			$widget->outputTemplate('UserManager/login.tpl', $labels);
			getSite()->getCurrentTemplate()->addMovePending($widget->getMovePending());
			$output = $widget->getContent();
			getSite()->addToSection(':loginForm', $output);

			self::$loginFormIncluded = true;
		}

		return false;
	}

	public function draw($widgetParams, $requestMethod, $requestParams) {
		if (!isset($widgetParams['loginScreen']) || !$widgetParams['loginScreen']) {
			if (($user = self::getCurrentUser()) !== null) {
				$this->outputTemplate('UserManager/logged.tpl', array('userName' => $user->name, 'imgBaseUrl' => ($this->getBaseUrl() . 'img/')));
			}
		} else {
			if (array_key_exists('um-login', $_POST) && array_key_exists('um-password', $_POST)) {
				$user = self::getCurrentUser();
				if ($user === null) {
					$this->outputOnLoadScriptCode('document.getElementById(\'um-login\').focus()');
					$labels = array();
					$labels['loginResult'] = 'Wrong login/password. Please try again.';
					$labels['loginOkUrl'] = (!isset($widgetParams['loginOkUrl']) ? '' : (Helpers_Url::isAbsolute($widgetParams['loginOkUrl']) ? $widgetParams['loginOkUrl'] : (getSite()->getBaseUrl() . $widgetParams['loginOkUrl'])));
					$this->outputTemplate('UserManager/login.tpl', $labels);
				} else {
					$labels = array('userName' => $user->name);
					$this->outputTemplate('UserManager/loginOk.tpl', $labels);
				}
			} else {
				$this->outputOnLoadScriptCode('document.getElementById(\'um-login\').focus()');
				$labels = array();
				$labels['loginOkUrl'] = (!isset($widgetParams['loginOkUrl']) ? '' : (Helpers_Url::isAbsolute($widgetParams['loginOkUrl']) ? $widgetParams['loginOkUrl'] : (getSite()->getBaseUrl() . $widgetParams['loginOkUrl'])));
				$this->outputTemplate('UserManager/login.tpl', $labels);
			}
		}
	}
}

if (session_start()) {
	if (array_key_exists('um-logout', $_POST) && $_POST['um-logout']) {
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
			unset($params);
		}
		session_destroy();
		Widget_UserManager::resetCurrentUser();
	} else {
		if (array_key_exists('um-login', $_POST) && array_key_exists('um-password', $_POST)) {
			if (session_regenerate_id(true)) {
				$_SESSION = array();
				$user = R::findOne('user', "name = ? and password=CONCAT('*', UPPER(SHA1(UNHEX(SHA1(?)))))", array($_POST['um-login'], $_POST['um-password']));
				if ($user !== null) {
					$_SESSION['userid'] = $user->id;
					if (isset($_POST['goto']) && $_POST['goto'] != '') {
						header('Location: ' . $_POST['goto']);
					}
				}
				unset($user);
			}
		}
		if (isset($_SESSION['userid'])) Widget_UserManager::setCurrentUserId($_SESSION['userid']);
		else Widget_UserManager::resetCurrentUser();
	}
}

?>
