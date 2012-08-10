<?php
namespace Payment\Atos;

use \TYPO3\FLOW3\Package\Package as BasePackage;
use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Package base class of the Payment.Atos package.
 *
 * @FLOW3\Scope("singleton")
 */
class Package extends BasePackage {

	/**
	 * Boot the package. We wire some signals to slots here.
	 *
	 * @param \TYPO3\FLOW3\Core\Bootstrap $bootstrap The current bootstrap
	 * @return void
	 */
	public function boot(\TYPO3\FLOW3\Core\Bootstrap $bootstrap) {

	}
}
?>