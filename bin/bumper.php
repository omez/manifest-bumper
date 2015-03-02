<?php 
/**
 * Manifest version bumper bootstrap
 * 
 */
require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Command\BumpCommand;
use Command\UploadCommand;

$application = new Application();

// Loading package
if (is_file(__DIR__ . '/../composer.json')) {
	$package = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
	$application->setName(isset($package['description']) ? $package['description'] : $package['name']);
	if (isset($package['version'])) $application->setVersion($package['version']);
}

$application->add(new BumpCommand());
$application->add(new UploadCommand());

$application->run();