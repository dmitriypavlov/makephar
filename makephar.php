#!/usr/bin/env php
<?php

// php.ini phar.readonly = 0

// exclude patterns
$exclude = [".DS_Store"];

// autoload
$index = "index.php";

define("DIR", __DIR__);
define("DS", DIRECTORY_SEPARATOR);

// archive name & path

$name = basename(DIR) . ".phar";
$path = DIR.DS . $name;

// exclude self
array_push($exclude, __FILE__);

// cleanup
@unlink($path);

$phar = new Phar($path);

// recursive scan

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(DIR, FileSystemIterator::SKIP_DOTS)
);

$include = iterator_to_array($iterator);

// filter files

$include = array_filter($include, function($file) use ($exclude) {
	$valid = true;
	foreach($exclude as $pattern) if (strpos($file, $pattern) !== false) { 
		$valid = false; break; // exclude
	}
	return $valid;
});

// filtered files
foreach($include as $file) echo $file . PHP_EOL;

// build archive

$phar->buildFromIterator(new ArrayIterator($include), DIR);
$phar->compressFiles(Phar::GZ);

// set stub

$stub = <<<"EOT"
<?php
Phar::mapPhar(__FILE__);
set_include_path("phar://" . __FILE__ . PATH_SEPARATOR . get_include_path());
require_once("$index");
__HALT_COMPILER();
EOT;

$phar->setStub($stub);