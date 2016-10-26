<?php
// Utility methods for the API

error_reporting(E_ALL);
require(__DIR__.'/../vendor/autoload.php');

function api_response($message) {
	header('Content-Type: text/plain');
	echo htmlspecialchars($message);
	die();
}
function api_error($code, $message) {
	header('Status: ' . $code . ' ' . $message);
	api_response($message);
}
// Convert all PHP errors to exceptions
set_error_handler(function($errno, $errstr, $errfile, $errline) {
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});
set_exception_handler(function($exception) {
	api_error('500', $exception->getMessage());
});