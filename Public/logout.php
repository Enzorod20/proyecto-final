<?php
session_start();
$_SESSION = [];

if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params(); //tuve que hacer esto por un problema cuando registraba un docente
	setcookie(session_name(), '', time() - 42000,
		$params['path'], $params['domain'],
		$params['secure'], $params['httponly']
	);
}
session_unset();
session_destroy();
header('Location: login.php?msg=logout');
exit;