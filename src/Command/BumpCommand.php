<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Bumps version to specific build
 * 
 * @author Alexander Sergeychik
 */
class BumpCommand extends Command {

	protected function configure() {
		$this->setName('bump');
		$this->setDescription('Bump manifest version');
		
		$this->addArgument('build', InputArgument::REQUIRED, 'Build version');
		
		$this->addOption('manifest', null, InputOption::VALUE_REQUIRED, 'Manifest path', 'AndroidManifest.xml');
		$this->addOption('dump', null, InputOption::VALUE_NONE, 'Dump bumped manifest XML');
		$this->addOption('save', null, InputOption::VALUE_NONE, 'Saves bumped manifest XML');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Console\Command\Command::execute()
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		
		$manifest = new \ManifestVersion($input->getOption('manifest'));
		
		$manifest->setBuildVersion($input->getArgument('build'));

		if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
			$output->writeln(sprintf('Setting build version to <info>%s</info> (<comment>%s</comment>)', $manifest->getVersionCode(), $manifest->getVersionName()));
		}
		
		if ($input->getOption('dump')) {
			$output->writeln($manifest->getXml());
		}
		
		if ($input->getOption('save')) {
			file_put_contents($input->getOption('manifest'), $manifest->getXml());
		}
	}
	
	
}
