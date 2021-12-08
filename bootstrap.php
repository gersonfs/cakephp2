<?php

define('DS', '/');

require_once 'app/Vendor/phpunit.phar';

require_once __DIR__ . '/lib/Cake/Core/App.php';
require_once __DIR__ . '/lib/Cake/TestSuite/CakeTestCase.php';

function my_autoload($pClassName)
{
	$pastas = ['Core', 'Event'];


	foreach ($pastas as $pasta) {

		$pastaCompleta = __DIR__ . '/lib/Cake/' . $pasta . '/' . $pClassName . '.php';
		if (file_exists($pastaCompleta)) {
			require_once $pastaCompleta;
		}
	}

}

spl_autoload_register("my_autoload");

