<?php
namespace Payment\Atos\Actions;

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
 * An abstract action
 *
 * @api
 */
abstract class AbstractAction {
	
	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Configuration\ConfigurationManager
	 */
	protected $configurationManager;
	
	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;
	
	/**
	 * @FLOW3\Inject
	 * @var \Payment\Atos\Utility\SettingsFiles
	 */
	protected $settingsFiles;
	
	/**
	 * Config settings
	 * @var array
	 */
	protected $settingsConfig;
	
	/**
	 * Config settings
	 * @var array
	 */
	protected $settingsLog;
	
	/**
	 * FLOW3 logger
	 * @var \TYPO3\FLOW3\Log\LoggerInterface
	 */
	protected $logger;
	
	/**
	 * Application context
	 * @var string
	 */
	protected $context;
	
	/**
	 * Constructs this action
	 *
	 * @api
	 */
	public function __construct() {
		
	}

	/**
	 * Initialise action
	 *
	 * @return void
	 */
    public function init() {
    	// Get settings
    	$this->settingsConfig = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Payment.Atos.Config');
		$this->settingsLog = $this->configurationManager->getConfiguration(\TYPO3\FLOW3\Configuration\ConfigurationManager::CONFIGURATION_TYPE_SETTINGS, 'Payment.Atos.Log');
		
		// Context
		$this->context = getenv('FLOW3_CONTEXT') ?: (getenv('REDIRECT_FLOW3_CONTEXT') ?: 'Development');
		
    	// Create FLOW3 logger
    	$this->logger = \TYPO3\FLOW3\Log\LoggerFactory::create('LogPayment', 'TYPO3\FLOW3\Log\Logger', $this->settingsLog['PaymentLogger']['backend'], $this->settingsLog['PaymentLogger']['backendOptions']);
		
    	// Create pathfile and comemrcant parmaters file if not exists
    	$this->settingsFiles->createPathFileIfNotExists();
		$this->settingsFiles->createParmcomIfNotExists($this->context);
	}

}
?>