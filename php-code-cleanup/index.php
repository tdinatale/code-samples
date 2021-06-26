<?php

require_once 'phpClean.class.php';

$phpClean = new phpClean();

$file = (!empty($argv[1]) && file_exists($argv[1])) ? $argv[1] : NULL;
$phpClean->open_file($file);

$phpClean->cleanup();

$database = (!empty($argv[2]) && $argv[2] == 'DB');
if ($database) {
	$phpClean->database();
}

$phpClean->write_clean_file();

$phpClean->get_diff();

$phpClean->php_error_check();

$phpClean->save();
