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
 * 
 */
class Request extends \Payment\Atos\Actions\AbstractAction {
	
	/**
	 * Amount of the transaction
	 * @var float
	 */
	protected $amount;
	
	/**
	 * User associated to the transaction
	 * @var \TYPO3\Party\Domain\Model\Person
	 */
	protected $user;
	
	/**
	 * Current request
	 * @var \TYPO3\FLOW3\Mvc\ActionRequest
	 */
	protected $request;
	
	/**
	 * Value of the caddie
	 * @var string
	 */
	protected $caddie;
	
	/**
	 * Order ID
	 * @var int
	 */
	protected $orderId;
	
	/**
	 * Split payment
	 * @var boolean
	 */
	protected $splitPayment;
	
	/**
	 * Request parameters as string
	 * @var string
	 */
	protected $parm;
	
	/**
	 * Path of the request binary
	 * @var string
	 */
	protected $path_bin;
	
	/**
	 * Bank HTML form as string
	 * @var string
	 */
	protected $bankForm;
	
	
	/**
	 * Constructor
	 *
	 * @param float $amount Amount of the transaction
	 * @param \TYPO3\Party\Domain\Model\Person $user User object
	 * @param \TYPO3\FLOW3\Mvc\ActionRequest $request Current request
	 * @return void
	 */
	public function __construct($amount, $user, $request) {
		$this->amount = $amount;
		$this->user = $user;
		$this->request = $request;
		$this->bankForm = NULL;
		$this->splitPayment = FALSE;
		$this->orderId = floor(mt_rand(1, 100000));
	}
    
	/**
	 * Call request API
	 *
	 * @return void
	 */
    public function call() {
 		// Init action
 		$this->init();
			
    	// Set request on uri builder
    	$this->uriBuilder->setRequest($this->request);
		$this->uriBuilder->setCreateAbsoluteUri(TRUE);
		
    	// Request parameters
		$this->parm = "merchant_id=".$this->settingsConfig['Merchant']['Id'][$this->context];
		$this->parm .= " merchant_country=".$this->settingsConfig['Merchant']['Country'];
		$this->parm .= " amount=".($this->amount* 100);
		$this->parm .= " language=fr";
		$this->parm .= " currency_code=".$this->settingsConfig['CurrencyCode'];
   		$this->parm .= " normal_return_url=".$this->uriBuilder->uriFor($this->settingsConfig['Uri']['Response']['ActionName'], NULL, $this->settingsConfig['Uri']['Response']['ControllerName'], $this->settingsConfig['Uri']['Response']['Package']);
		$this->parm .= " cancel_return_url=".$this->uriBuilder->uriFor($this->settingsConfig['Uri']['Cancel']['ActionName'], NULL, $this->settingsConfig['Uri']['Cancel']['ControllerName'], $this->settingsConfig['Uri']['Cancel']['Package']);
		$this->parm .= " automatic_response_url=".$this->uriBuilder->uriFor($this->settingsConfig['Uri']['AutoResponse']['ActionName'], NULL, $this->settingsConfig['Uri']['AutoResponse']['ControllerName'], $this->settingsConfig['Uri']['AutoResponse']['Package']);
		$this->parm .= " customer_id=".substr($this->user->getIdentifier(), 0, 19);
		$this->parm .= " customer_email=".$this->user->getPrimaryElectronicAddress()->getIdentifier();
		$this->parm .= " customer_ip_address=".$_SERVER["REMOTE_ADDR"];
		$this->parm .= " order_id=".$this->orderId;
		$this->parm .= " pathfile=".FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/pathfile";
		
		// Caddie?
		if($this->caddie)
			$this->parm .= " caddie=".$this->caddie;
		
		// Split payment?
		if($this->settingsConfig['SplitPayment']['Amount'] > 0 && $this->splitPayment == TRUE) {
			// Define nb payments
			$nbPayments = ceil($this->amount / $this->settingsConfig['SplitPayment']['Amount']);
			if($nbPayments > 1) {
				// Max days fixed to 90 by the law
				if($nbPayments > round(90 / $this->settingsConfig['SplitPayment']['NbDays']))
					$nbPayments = round(90 / $this->settingsConfig['SplitPayment']['NbDays']);
				$this->parm .= " capture_day=0";
				$this->parm .= " capture_mode=PAYMENT_N";
				$this->parm .= " data=NB_PAYMENT=".$nbPayments.";PERIOD=".$this->settingsConfig['SplitPayment']['NbDays'].";INITIAL_AMOUNT=".($this->settingsConfig['SplitPayment']['Amount']*100);
			}
		}  	
		
		// Log request
		$this->logger->log("Bank request from ".$_SERVER["REMOTE_ADDR"]);
		$this->logger->log($this->parm);
		
		// Escape shell
		$this->parm = escapeshellcmd($this->parm);
		
		// Path of the request binary
		$this->path_bin = FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/bin/static/request";
		
		// Binary is executable?
		if(!is_executable($this->path_bin))
			chmod($this->path_bin, 0766);
		
		// Execute cmd and get result
		$cmd = exec($this->path_bin." ".$this->parm);
		$result = explode ("!", $cmd);
		
		// Manage errors or return result message
		if(($result[1] == "") && ($result[2] == "")) {
			$this->logger->log("Request binary not found.");
			throw new \Payment\Atos\Security\Exception\RequestNotFoundException("Request binary not found.", 1);
			return;
		} else if ($result[1] != 0) {
			$this->logger->log("Request API error : ".$result[2]);
			throw new \Payment\Atos\Security\Exception\ApiException("Request API error : ".$result[2], 1);
			return;
		} else {
			$this->bankForm = $result[3];
		}
    }

	/**
	 * Return bank HTML form
	 *
	 * @return string The bank HTML form
	 */
	public function getBankForm() {
		return $this->bankForm;
	}
	
	/**
	 * Set caddie
	 *
	 * @return void
	 */
	public function setCaddie($caddie) {
		return $this->caddie = $caddie;
	}
	
	/**
	 * Set order id
	 *
	 * @return void
	 */
	public function setOrderId($orderId) {
		return $this->orderId = $orderId;
	}
	
	/**
	 * Set split payment
	 *
	 * @return void
	 */
	public function setSplitPayment($splitPayment) {
		return $this->splitPayment = $splitPayment;
	}
	
}

?>
