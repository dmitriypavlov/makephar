#!/usr/bin/env php
<?php

// php.ini phar.readonly = 0

// autoload
$index = "index.php";

// exclude patterns
$exclude = [".DS_Store", ".git"];

system("clear");

// exclude self
array_push($exclude, __FILE__);

// archive name & path

$name = basename(__DIR__) . ".phar";
$path = __DIR__ . DIRECTORY_SEPARATOR . $name;

// cleanup
@unlink($path);

// recursive scan

$include = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(__DIR__, FileSystemIterator::SKIP_DOTS)
);

$include = iterator_to_array($include);

// filter files

$include = array_filter($include, function($file) use ($exclude) {
	$valid = true;
	foreach($exclude as $pattern) if (strpos($file, $pattern) !== false) { 
		$valid = false; break; // exclude
	}
	return $valid;
});

// list files
foreach($include as $file) echo "[$name] + " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, null, $file) . PHP_EOL;

// build archive

$phar = new Phar($path);
$phar->buildFromIterator(new ArrayIterator($include), __DIR__);
$phar->compressFiles(Phar::GZ);

// sign archive
$phar->setSignatureAlgorithm(Phar::SHA256);
$hash = $phar->getSignature()["hash"];

// set stub

$stub = <<<END
#!/usr/bin/env php

<?php # $hash #

Phar::mapPhar(__FILE__);
set_include_path("phar://" . __FILE__ . PATH_SEPARATOR . get_include_path());
require_once("$index");
__HALT_COMPILER(); ?>
END;

$phar->setStub($stub);

// execute
system("chmod +x '$path' && '$path'");