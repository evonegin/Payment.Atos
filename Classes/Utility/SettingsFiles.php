<?php
namespace Payment\Atos\Utility;

/*                                                                        *
 * This script belongs to the Payment.Atos Package.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * Copyright (c) 2012 Thomas Plessis - Totem Numerique Agency             *
 *                                                                        */

 use TYPO3\FLOW3\Annotations as FLOW3;
 
/**
 * SettingsFiles functions
 * @FLOW3\Scope("singleton")
 * 
 */
class SettingsFiles {
	
	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;
	
	/**
     * @FLOW3\Inject
   	 * @var \TYPO3\FLOW3\Resource\Publishing\ResourcePublisher
     */
    protected $resourcePublisher;
	
	/**
	 * Create pathfile if not exists
	 *
	 * @return void
	 */
	public function createPathFileIfNotExists() {
		$pathfile = FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/pathfile";
		if(!file_exists($pathfile)) {
			// Debug
			$content = "DEBUG!NO!\n";
			// Logo relative path
			$content .= "D_LOGO!/_Resources/Static/Packages/Payment.Atos/logo/!\n";
			$content .= "F_DEFAULT!".FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/parmcom.webaffaires!\n";
			$content .= "F_PARAM!".FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/parmcom!\n";
			$content .= "F_CERTIFICATE!".FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/certif!\n";
			$content .= "F_CTYPE!php!";
			
			// Save content in pathfile
			file_put_contents($pathfile, $content);
			
			// chmod
			chmod($pathfile, 0666);
		}
	}
	
	/**
	 * Create parmcom if not exists
	 *
	 * @param string $context Application context
	 * @return void
	 */
	public function createParmcomIfNotExists($context) {
		$merchantId = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Payment.Atos.Config.Merchant.Id.'.$context);
		$pathfile = FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/parmcom.".$merchantId;
		if(!file_exists($pathfile)) {
			// Debug
			$content = "#LOGO!logo.png!\n";
			$content .= "#LOGO2!commercant.gif!";
			
			// Save content in pathfile
			file_put_contents($pathfile, $content);
			
			// chmod
			chmod($pathfile, 0666);
		}
	}
}
?>
