<?php

/**
 * Android manifest version reader/modificator
 * 
 * @author Alexander Sergeychik
 */
class ManifestVersion {

	/**
	 * DOM
	 * 
	 * @var SimpleXMLElement
	 */
	protected $dom;
	
	protected $minorVersion = 0;
	protected $majorVersion = 0;
	protected $buildVersion = 0;
	
	/**
	 * Creates version modificator
	 * 
	 * @param string $manifestPath
	 * @throws \RuntimeException
	 */
	public function __construct($manifestPath) {
		$this->dom = simplexml_load_file($manifestPath);
		if (!$this->dom) {
			throw new \RuntimeException(sprintf('Unable to load Android manifest at %s', $manifestPath));
		}
		
		// Loading version
		$versionCode = (string)$this->dom->attributes('android', true)->versionCode;
		$matches = null;
		if (!preg_match('/^(\d{1})(\d{2})(\d{3})$/', $versionCode, $matches)) {
			throw new \RuntimeException(sprintf('Unable to parse version code "%s" against format XYYZZZ', $versionCode));
		}
		list(, $this->majorVersion, $this->minorVersion, $this->buildVersion) = $matches;
	}

	/**
	 * Returns minor version
	 *
	 * @return string
	 */
	public function getMinorVersion() {
		return $this->minorVersion;
	}

	/**
	 * Sets minor version
	 *
	 * @param string $minorVersion
	 * @return ManifestVersion        	
	 */
	public function setMinorVersion($minorVersion) {
		$this->minorVersion = $minorVersion;
		$this->_update();
		return $this;
	}

	/**
	 * Returns major version
	 * 
	 * @return string
	 */
	public function getMajorVersion() {
		return $this->majorVersion;
	}

	/**
	 * Sets major version
	 *
	 * @param string $majorVersion
	 * @return ManifestVersion        	
	 */
	public function setMajorVersion($majorVersion) {
		$this->majorVersion = $majorVersion;
		$this->_update();
		return $this;
	}

	/**
	 * Returns major version
	 * 
	 * @return string
	 */
	public function getBuildVersion() {
		return $this->buildVersion;
	}

	/**
	 * Sets build version
	 *
	 * @param string $majorVersion
	 * @return ManifestVersion        	
	 */
	public function setBuildVersion($buildVersion) {
		$this->buildVersion = $buildVersion;
		$this->_update();
		return $this;
	}

	/**
	 * Returns version code in format XYYZZZ
	 * 
	 * @return string
	 */	
	public function getVersionCode() {
		return sprintf('%01d%02d%03d', $this->getMajorVersion(), $this->getMinorVersion(), $this->getBuildVersion());
	}

	/**
	 * Returns version name in format X.YY.ZZZ
	 * 
	 * @return string
	 */
	public function getVersionName() {
		return sprintf('%d.%d.%d', $this->getMajorVersion(), $this->getMinorVersion(), $this->getBuildVersion());
	}
	
	/**
	 * Updates manifest attributes
	 * 
	 * @return ManifestVersion
	 */
	protected function _update() {
		$this->dom->attributes('android', true)->versionCode = $this->getVersionCode();
		$this->dom->attributes('android', true)->versionName = $this->getVersionName();
		return $this;
	}
	
	/**
	 * Returns manifest XML
	 * 
	 * @return string
	 */
	public function getXml() {
		return $this->dom->asXML();
	}
	
	
}