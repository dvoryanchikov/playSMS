<?php
defined('_SECURE_') or die('Forbidden');

use Gregwar\Captcha\CaptchaBuilder;

if (_OP_ == 'register') {
	
	$ok = FALSE;
	
	if (!auth_isvalid()) {
		if ($_REQUEST['captcha'] == $_SESSION['tmp']['captcha']) {
			$data = array();
			$data['name'] = $_REQUEST['name'];
			$data['username'] = $_REQUEST['username'];
			$data['mobile'] = $_REQUEST['mobile'];
			$data['email'] = $_REQUEST['email'];
			
			// force non-admin, status=3 is user and status=4 is subuser
			$data['status'] = ($core_config['main']['default_user_status'] == 3 ? $core_config['main']['default_user_status'] : 4);
			
			// if subuser and no site config then parent uid is 0
			$parent_uid = ((int)$site_config['uid'] ? (int)$site_config['uid'] : 0);
			$data['parent_uid'] = ($data['status'] == 4 ? $parent_uid : 0);
			
			// empty this and playSMS will generate random password
			$data['password'] = '';
			
			// set credit to 0 by default
			$data['credit'] = 0;

			$ret = user_add($data);
			$ok = ($ret['status'] ? TRUE : FALSE);
			$_SESSION['error_string'] = $ret['error_string'];
		} else {
			$_SESSION['error_string'] = _('Please type the displayed captcha phrase correctly');
		}
	}
	
	if ($ok) {
		header("Location: " . _u($core_config['http_path']['base']));
	} else {
		header("Location: " . _u('index.php?app=main&inc=core_auth&route=register'));
	}
	exit();
} else {
	
	// error string
	if ($_SESSION['error_string']) {
		$error_content = '<div class="error_string">' . $_SESSION['error_string'] . '</div>';
	}
	
	$enable_logo = FALSE;
	$show_web_title = TRUE;
	
	if ($core_config['main']['enable_logo'] && $core_config['main']['logo_url']) {
		$enable_logo = TRUE;
		if ($core_config['main']['logo_replace_title']) {
			$show_web_title = FALSE;
		}
	}
	
	// captcha
	$captcha = new CaptchaBuilder;
	$captcha->build();
	$_SESSION['tmp']['captcha'] = $captcha->getPhrase();
	
	$tpl = array(
		'name' => 'auth_register',
		'vars' => array(
			'HTTP_PATH_BASE' => $core_config['http_path']['base'],
			'WEB_TITLE' => $core_config['main']['web_title'],
			'ERROR' => $error_content,
			'URL_ACTION' => _u('index.php?app=main&inc=core_auth&route=register&op=register') ,
			'URL_FORGOT' => _u('index.php?app=main&inc=core_auth&route=forgot') ,
			'URL_LOGIN' => _u('index.php?app=main&inc=core_auth&route=login') ,
			'CAPTCHA_IMAGE' => $captcha->inline() ,
			'HINT_CAPTCHA' => _hint(_('Read and type the captcha phrase on verify captcha field. If you cannot read them please contact administrator.')) ,
			'Name' => _('Name') ,
			'Username' => _('Username') ,
			'Mobile' => _('Mobile') ,
			'Email' => _('Email') ,
			'Register an account' => _('Register an account') ,
			'Login' => _('Login') ,
			'Submit' => _('Submit') ,
			'Recover password' => _('Recover password') ,
			'Verify captcha' => _('Verify captcha') ,
			'logo_url' => $core_config['main']['logo_url'],
		) ,
		'ifs' => array(
			'enable_forgot' => $core_config['main']['enable_forgot'],
			'enable_logo' => $enable_logo,
			'show_web_title' => $show_web_title,
		) ,
	);
	
	_p(tpl_apply($tpl));
}
