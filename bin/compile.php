<?php 
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Finder\Finder;

// Loading package
$package = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);

$pharPath = sprintf('dist/%s.phar', 'bumper');

if (is_file($pharPath)) {
	unlink($pharPath);
}

$phar = new \Phar($pharPath, 0);
$phar->setSignatureAlgorithm(\Phar::SHA1);
//if ($phar->canCompress(\Phar::GZ)) $phar->compress(\Phar::GZ);

$phar->startBuffering();

// add project files
$finder = new Finder();
$finder
	->in('bin')
	->in('src')
	->in('patch')
	->in('vendor')
		->exclude('google')
		->exclude('symfony/finder')
	->ignoreVCS(true);

foreach ($finder->files() as $file) {
	$phar->addFile($file->getPathname());
}

// add google api library filtering require_once includes
$finder = new Finder();
$finder
	->in('vendor/google/apiclient')
	->exclude('src/Google/Service')
	->exclude('tests')
	->ignoreVCS(true);

foreach ($finder->files() as $file) {
	if (strtolower($file->getExtension()) == 'php') {
		// remove require_once if present

		$filecontent = file_get_contents($file->getRealpath());
		
		$filecontent = preg_replace('/^require_once (.*autoload\.php.*);\s*$/im', '//$0', file_get_contents($file->getRealpath()), 1);
		//$filecontent = str_ireplace('dirname(__FILE__)', '__DIR__', $filecontent);
		//$filecontent = str_ireplace('__DIR__', '\'' . dirname($file->getPathname()) . '\'', $filecontent);
		
		/*
		$filecontent = preg_replace('/^require_once (.*autoload\.php.*);\s*$/im', '//$0', file_get_contents($file->getRealpath()), 1);
		
		if ($file->getPathname() == 'vendor/google/apiclient/src/Google/IO/Curl.php') {
			$filecontent = str_replace('curl_setopt($curl, CURLOPT_CAINFO, dirname(__FILE__) . \'/cacerts.pem\');', 'curl_setopt($curl, CURLOPT_CAINFO, __DIR__ . \'/cacerts.pem\');', $filecontent);
		}*/		
		
		$phar->addFromString($file->getPathname(), $filecontent);
	} else {
		$phar->addFile($file->getPathname());
	}
	
	
}



$phar->addFile('composer.json');
$phar->delete('bin/compile.php');

$phar->stopBuffering();

// Generate stub author names
$stubNames = array();
foreach (isset($package['authors']) ? $package['authors'] : array() as $author) {
	if (isset($author['name'])) $name = $author['name'];
	elseif (isset($author['email'])) $name = $author['email'];
	else continue;
	$stubNames[] = sprintf(' * @author %s', $name);
}
$stubNames = implode("\n", $stubNames);

$stub=<<<EOF
#!/usr/bin/env php
<?php
/**
 * {$package['description']}
 *
{$stubNames}
 */
Phar::mapPhar('toolkit');
include 'phar://toolkit/bin/bumper.php';
__HALT_COMPILER();
EOF;

$phar->setStub($stub);

// fix phar executable permissions
chmod($pharPath, 0755);


