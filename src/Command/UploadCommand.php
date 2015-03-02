<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Uploads APK to android market using publishing API
 * 
 * @author Alexander Sergeychik
 */
class UploadCommand extends Command {

	protected function configure() {
		$this->setName('upload');
		$this->setDescription('Uploads APK to Google Play using Android Publishing API');
		
		$this->addArgument('package', InputArgument::REQUIRED, 'APK package name');
		$this->addArgument('apk', InputArgument::REQUIRED, 'APK package file path');
		
		$this->addArgument('clientEmail', InputArgument::REQUIRED, 'Google client email');
		$this->addArgument('clientKey', InputArgument::REQUIRED, 'Google client p12 key');
		
		
		$this->addOption('stage', null, InputOption::VALUE_REQUIRED, 'Stage (beta, alpha, production)', 'beta');
	}

	/**
	 * {@inheritDoc}
	 * 
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		// settings
		$clientEmail = $input->getArgument('clientEmail');
		$scopes = (array)'https://www.googleapis.com/auth/androidpublisher';
		$privateKey = $input->getArgument('clientKey');
		$packageName = $input->getArgument('package');
		$apk = $input->getArgument('apk');
		$track = strtolower($input->getOption('stage'));
		
		$output->writeln(sprintf('Configuring client'));
		$credentials = new \Google_Auth_AssertionCredentials($clientEmail, $scopes, file_get_contents($privateKey));
		$client = new \Google_Client();
		$client->setAssertionCredentials($credentials);
		if ($client->getAuth()->isAccessTokenExpired()) {
			$client->getAuth()->refreshTokenWithAssertion();
			$output->writeln('<comment>Assertion token created</comment>');
		}
		
		
		// Create new edit
		$output->writeln(sprintf('Creating new transaction'));
		$rest = new \Google_Http_REST();
		$request = new \Google_Http_Request(sprintf('https://www.googleapis.com/androidpublisher/v2/applications/%s/edits', $packageName), 'POST');
		$client->getAuth()->sign($request);
		$response = $rest->execute($client, $request);
		$editId = $response['id'];
		$output->writeln(sprintf('<comment>New edit <info>%s</info> created</comment>', $editId));
		
		
		// Upload package
		$output->writeln(sprintf('Uploading package file'));
		$request = new \Google_Http_Request(sprintf('https://www.googleapis.com/androidpublisher/v2/applications/%s/edits/%s/apks', $packageName, $editId), 'POST');
		$client->getAuth()->sign($request);
		
		$upload = new \Google_Http_MediaFileUpload($client, $request, 'application/vnd.android.package-archive', file_get_contents($apk), true);
		$progress = new ProgressBar($output, filesize($apk));
		$progress->start();
		do {
			$response = $upload->nextChunk();
			$progress->setProgress($upload->getProgress());
		} while ($response === false);
		$progress->finish();
		$output->writeln('');
		$versionCode = $response['versionCode'];
		$output->writeln(sprintf('<comment>Uploaded APK with version code <info>%s</info></comment>', $versionCode));
		
		
		// Put track information to edit
		$output->writeln(sprintf('Setting track <info>%s</info> to <info>%s</info>', $versionCode, $track));
		$request = new \Google_Http_Request(sprintf('https://www.googleapis.com/androidpublisher/v2/applications/%s/edits/%s/tracks/%s', $packageName, $editId, $track), 'PUT');
		$request->setRequestHeaders(array('Content-type' => 'application/json'));
		$request->setPostBody(json_encode(array(
			'track' => $track,
			'versionCodes' => array($versionCode)
		)));
		$client->getAuth()->sign($request);
		$response = $rest->execute($client, $request);
		$output->writeln(sprintf('<comment>APK release <info>%s</info> track set to <info>%s</info></comment>', implode(', ', $response['versionCodes']), $response['track']));
		
		// Commiting changes
		$output->writeln(sprintf('Commiting release <info>%s</info>', $versionCode));
		$request = new \Google_Http_Request(sprintf('https://www.googleapis.com/androidpublisher/v2/applications/%s/edits/%s:commit', $packageName, $editId), 'POST');
		$client->getAuth()->sign($request);
		$response = $rest->execute($client, $request);
		
		$output->writeln('<info>OK!</info>');
	}
	
	
}
