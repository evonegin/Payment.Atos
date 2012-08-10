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
class Response extends \Payment\Atos\Actions\AbstractAction {
	
	/**
	 * Returned message
	 * @var string
	 */
	protected $message;
	
	/**
	 * Path of the request binary
	 * @var string
	 */
	protected $path_bin;
	
	/**
	 * Path of Payment\Atos parameters
	 * @var string
	 */
	protected $path_file;
	
	/**
	 * Bank response as array
	 * @var array
	 */
	protected $bankResponse;
	
	/**
	 * Split payment informations
	 * @var array
	 */
	protected $splitPaymentInfos;
	
	/**
	 * Number of payment (usefull in the case of a split payment)
	 * @var int
	 */
	protected $nbPayment;
	
	
	public function __construct() {
		$this->nbPayment = 1;	
		$this->bankResponse = array();
		$this->splitPaymentInfos = array();
	}
    
	/**
	 * Call request API
	 *
	 * @param float $amount Amount of the transaction
	 * @param \TYPO3\Party\Domain\Model\Person $user User object
	 * @param string $orderId Order Id
	 * @return boolean
	 */
    public function call() {
    	// Init action
 		$this->init();
		
		// Get message data		
		$this->message = escapeshellcmd("message=".$_POST['DATA']);
		
		// Path
		$this->path_bin = FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/bin/static/response";
		$this->pathfile = "pathfile=".FLOW3_PATH_PACKAGES."Application/Payment.Atos/Resources/Private/PHP/param/pathfile";
		
		// Binary is executable?
		if(!is_executable($this->path_bin))
			chmod($this->path_bin, 0766);
		
		// Execute cmd and get result
		$cmd = exec($this->path_bin." ".$this->pathfile." ".$this->message);
		$result = explode ("!", $cmd);
		
		// Manage errors or return result message
		if(($result[1] == "") && ($result[2] == "")) {
			$this->logger->log("Request binary not found.");
			throw new \Payment\Atos\Security\Exception\RequestNotFoundException("Request binary not found.", 1);
			return FALSE;
		} else if ($result[1] != 0) {
			$this->logger->log("Request API error : ".$result[2]);
			throw new \Payment\Atos\Security\Exception\ApiException("Request API error : ".$result[2], 1);
			return FALSE;
		} else {
			// Save bank response as array
			$this->bankResponse = $result;
			
			// Log bank response
			$this->logger->log("Bank response :");
			$this->logger->log(str_replace("\n", "", print_r($this->bankResponse, TRUE)));
			
			// Get split payment informations if exists
			if(sizeof($this->bankResponse[32]) > 0) {
				$this->splitPaymentInfos = array();
				$data = explode(';', $this->bankResponse[32]);
				foreach ($data as $info) {
					$info_arr = explode('=', $info);
					
					// Get dates and amount
					if($info_arr[0] == 'PAYMENT_DUE_DATES') {
						$payments_arr = explode(',', $info_arr[1]);
						$this->nbPayment = count($payments_arr);
						$this->splitPaymentInfos = array();
						
						foreach ($payments_arr as $payments_info) {
							$payments_split_info = explode('/', $payments_info);
							$date = new \TYPO3\FLOW3\Utility\Now($payments_split_info[0]);
							$amount = $payments_split_info[1] / 100;
							$this->splitPaymentInfos[] = array('DATE' => $date->format('d/m/Y'), 'AMOUNT' => $amount);
						}
					}
				}
			}
				
			// Check bank response code
			if($result[18] == "00") {
				return TRUE;
			} else {
				// Throw payment refused exception
				$this->logger->log("Payment has been refused.");
				throw new \Payment\Atos\Security\Exception\PaymentRefusedException("Payment has been refused", 1);
				return FALSE;
			}
		}
    }
	
	/**
	 * Return bank response as array
	 *
	 * @return array The bank response
	 */
	public function getBankResponse() {
		return $this->bankResponse;
	}
	
	/**
	 * Return amount of the transaction
	 *
	 * @return float The amount
	 */
	public function getAmount() {
		if($this->bankResponse) {
			return $this->bankResponse[5];
		}
		
		return 0.0;
	}
	
	/**
	 * Return caddie
	 *
	 * @return array The caddie
	 */
	public function getCaddie() {
		if($this->bankResponse) {
			if(sizeof($this->bankResponse[22]) > 0) {
				return unserialize(base64_decode($this->bankResponse[22]));
			}
		}
		
		return NULL;
	}
	
	/**
	 * Return split payment infos like payment dates, amount, period...
	 *
	 * @return array The split payment informations
	 */
	public function getSplitPaymentInfos() {
		return $this->splitPaymentInfos;
	}
	
	/**
	 * Return number of payment
	 *
	 * @return int The number of payment
	 */
	public function getNbPayment() {
		return $this->nbPayment;
	}
	
}

?>
