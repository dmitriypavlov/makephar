#!/usr/bin/env php
<?php

// php.ini phar.readonly = 0

// autoload
$index = "index.php";

// exclude patterns
$exclude = [".DS_Store", ".git"];

// exclude self
array_push($exclude, __FILE__);

// archive name & path

$name = basename(__DIR__) . ".phar";
$path = __DIR__ . DIRECTORY_SEPARATOR . $name;

// cleanup
@unlink($path);

// recursive scan

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(__DIR__, FileSystemIterator::SKIP_DOTS)
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

$phar = new Phar($path);
$phar->buildFromIterator(new ArrayIterator($include), __DIR__);
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