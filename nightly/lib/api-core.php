<?php
// Utility methods for the API
error_reporting(E_ALL);
require(__DIR__.'/../vendor/autoload.php');

use Analog\Analog;

function api_response($message) {
	header('Content-Type: text/plain');
	echo htmlspecialchars($message);
	Analog::info($message);
	die();
}
function api_error($code, $message) {
	$first_line = strtok($message, "\n");
	header('Status: ' . $code . ' ' . $first_line);
	header('Content-Type: text/plain');
	echo $message;
	Analog::warning($message);
	die();
}
// Convert all PHP errors to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function($exception) {
	Analog::warning($exception);
	api_error('500', $exception->getMessage());
});

// Set log file name based on name of script
Analog::handler(__DIR__.'/../logs/'.basename($_SERVER['SCRIPT_NAME'], '.php').'.log');
