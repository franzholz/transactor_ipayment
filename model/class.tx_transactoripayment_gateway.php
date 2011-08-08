<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * This script handles payment via the iPayment gateway.
 *
 *
 * iPayment:	http://www.ipayment.de
 *
 * $Id$
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage transactor_ipayment
 *
 *
 */



require_once (t3lib_extMgM::extPath('transactor') . 'model/class.tx_transactor_gateway.php');



class tx_transactoripayment_gateway extends tx_transactor_gateway {
	protected $gatewayKey = 'transactor_ipayment';
	protected $extKey = 'transactor_ipayment';
	protected $supportedGatewayArray = array(TX_TRANSACTOR_GATEWAYMODE_FORM);
	protected $bMergeConf = FALSE;


		// Setup array for modifying the inputs
	public function __construct () {

		$result = parent::__construct();
		return $result;
	}


	/**
	 * Returns the form action URI to be used in mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	string		Form action URI
	 * @access	public
	 */
	public function transaction_formGetActionURI () {
		if ($this->getGatewayMode() == TX_TRANSACTOR_GATEWAYMODE_FORM)	{
			$result = str_replace('<AccountID>', $this->conf['accountId'], $this->conf['provideruri']);
		} else {
			$result = FALSE;
		}

		return $result;
	}


	/* md5 algorithm */
	public function createHash ($paramArray) {

		$text = '';
		foreach ($paramArray as $param) {
			$text .= $param;
		}
		$result = md5($text); // Einmalige Zeichenkette zur Prüfung der Auftragsdaten.

		return $result;
	}


	/**
	 * Returns an array of field names and values which must be included as hidden
	 * fields in the form you render use mode TX_TRANSACTOR_GATEWAYMODE_FORM.
	 *
	 * @return	array		Field names and values to be rendered as hidden fields
	 * @access	public
	 */
	public function transaction_formGetHiddenFields () {
		global $TSFE;

		$detailsArray = $this->getDetails();
		$address = $detailsArray['address'];
		$total = $detailsArray['total'];

		$fieldsArray = array();
		$fieldsArray = $this->config;

		$nFieldsArray = array();
		$nFieldsArray['accountId'] = $this->conf['accountId'];
		$nFieldsArray['trxuser_id'] = $this->conf['trxuser_id'];
		$nFieldsArray['trxpassword'] = $this->conf['trxpassword'];
		$nFieldsArray['security_key'] = $this->conf['security_key'];
		$nFieldsArray['adminactionpassword'] = $this->conf['adminactionpassword'];

		$nFieldsArray['trx_currency'] = $detailsArray['transaction']['currency'];
		$nFieldsArray['trx_amount'] = intval ($total['amounttax'] * 100);
		$nFieldsArray['trx_paymenttyp'] = $this->conf['trx_paymenttyp'];
		$nFieldsArray['addr_name'] = $address['person']['first_name'] . ' ' . $address['person']['last_name'];
		$nFieldsArray['addr_email'] = $address['person']['email'];
		$nFieldsArray['addr_street'] = $address['person']['address1'];
		$nFieldsArray['addr_city'] = $address['person']['city'];
		$nFieldsArray['addr_zip'] = $address['person']['zip'];
		$nFieldsArray['addr_country'] = $address['person']['country'];
		$nFieldsArray['addr_telefon'] = $address['person']['phone'];

		$nFieldsArray['error_lang'] = $detailsArray['language'];
		if ($nFieldsArray['error_lang'] != 'de') {
			$nFieldsArray['error_lang'] = 'en'; // only 'de' or 'en' are allowed
		}

		$nFieldsArray['shopper_id'] = $detailsArray['transaction']['orderuid'];
		$nFieldsArray['advanced_strict_id_check'] = '1';
		$nFieldsArray['check_double_trx'] = '1';
		$nFieldsArray['check_fraudattack'] = '1';
		$nFieldsArray['return_paymentdata_details'] = '1';

		$nFieldsArray['invoice_text'] = $nFieldsArray['shopper_id'] . ': ' . $nFieldsArray['addr_name'];
		$nFieldsArray['trx_user_comment'] = $detailsArray['tracking'];

		$nFieldsArray['from_ip'] = t3lib_div::getIndpEnv('REMOTE_ADDR');

		$nFieldsArray['redirect_url'] = $detailsArray['transaction']['successlink'];
		$nFieldsArray['redirect_action'] = 'POST';
		$nFieldsArray['backlink'] = $detailsArray['transaction']['returi'];

// client_name, client_version

		foreach ($nFieldsArray as $k => $v) {
			if ($v != '') {
				$fieldsArray[$k] = $v;
			}
		}

		// *******************************************************
		// Set article vars if selected
		// *******************************************************

		if (is_array($detailsArray) && isset($detailsArray['options']) && is_array($detailsArray['options'])) {
			foreach ($detailsArray['options'] as $k => $v) {
				if ($v != '') {
					$fieldsArray[$k] = $v;
				}
			}
		}

		$hashParamArray = array();
		$fieldArray = array('trxuser_id', 'trx_amount', 'trx_currency', 'trxpassword', 'security_key');

		foreach ($fieldArray as $field) {
			$hashParamArray[$field] = $paramArray[$field];
		}
		$securityHash = $this->createHash($hashParamArray);
		$fieldsArray['trx_securityhash'] = $securityHash;

		return $fieldsArray;
	}


	/**
	 * Returns the results of a processed transaction
	 *
	 * @param	string		$reference
	 * @return	array		Results of a processed transaction
	 * @access	public
	 */
	public function transaction_getResults ($reference) {
		global $TYPO3_DB;

		$result = array();
		if (($row = $this->getTransaction($reference)) === FALSE) {
			$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_IDLE, 'keine Transaktion gestartet');
		} else {
			$paramArray = $this->readParams();

			if (is_array($paramArray)) {
				$callExt = $this->getCallingExtension();
				$theReference = $this->generateReferenceUid($paramArray['shopper_id'], $callExt);

				if ($reference == $theReference && $paramArray['ret_errorcode'] == 0) {
					$result['state'] = TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_OK;
					$result['amount'] = doubleval($paramArray['trx_amount'] / 100);
					$result['message'] = $TYPO3_DB->fullQuoteStr(
						$paramArray['invoice_text'] . ';' . $paramArray['paydata_cc_typ'] . ';' . $paramArray['paydata_cc_number'] . ';' . $paramArray['paydata_cc_expdate'] . ';' . $paramArray['ret_transdate'] . ';' . $paramArray['ret_transtime'],
						'tx_transactor_transactions');
				} else {
					$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK, 'Payment has failed. (' . $paramArray['ret_errorcode'] . ')');
				}
			} else {
				$result = $this->transaction_getResultsMessage(TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK, 'Bei der Bezahlung ist ein Fehler aufgetreten.');
			}
			$res = $TYPO3_DB->exec_UPDATEquery(
				'tx_transactor_transactions',
				'reference = ' . $TYPO3_DB->fullQuoteStr($reference, 'tx_transactor_transactions'),
				$result
			);
		} // error_transaction_no

		return $result;
	}


	public function transaction_failed ($resultsArray) {

		$result = FALSE;

		if ($resultsArray['status'] == TX_TRANSACTOR_TRANSACTION_STATE_APPROVE_NOK) {
			$result = TRUE;
		}

		return $result;
	}


	// *****************************************************************************
	// Helpers Return of payment parameters
	// *****************************************************************************
	public function readParams () {
		$result = '';

		$orderID = t3lib_div::_GP('shopper_id');

		if ($orderID) {
			$paramArray = array();
			$paramTypeArray = array(
				'accountId', 'trxuser_id', 'trx_currency', 'trx_amount', 'trx_paymenttyp',
				'addr_name', 'addr_email', 'addr_street', 'addr_city', 'addr_zip', 'addr_country', 'addr_telefon',
				'shopper_id', 'trx_user_comment', 'trx_typ',
				'ret_transdate', 'ret_transtime', 'ret_errorcode', 'ret_fatalerror', 'ret_errormsg', 'ret_authcode', 'ret_ip', 'ret_additionalmsg',
				'ret_booknr', 'ret_trx_number', 'ret_status',
				'redirect_needed', 'trx_paymentmethod', 'trx_paymentdata_country', 'trx_remoteip_country',
				'trx_issuer_avs_response', 'trx_payauth_status', 'ret_param_checksum', 'ret_url_checksum'
			);

			foreach ($paramTypeArray as $type) {
				$value = t3lib_div::_GP($type);

				if ($value != '') {
					$paramArray[$type] = $value;
				}
			}

			$hashParamArray = array();
			$fieldArray = array('trxuser_id', 'trx_amount', 'trx_currency', 'ret_authcode', 'ret_trx_number', 'security_key');

			foreach ($fieldArray as $field) {
				$hashParamArray[$field] = $paramArray[$field];
			}
			$securityHash = $this->createHash($hashParamArray);

			if (($securityHash == $paramArray['ret_param_checksum'] || !isset($paramArray['ret_param_checksum'])) && $paramArray['ret_ip'] == t3lib_div::getIndpEnv('REMOTE_ADDR')) {
				$result = $paramArray;
			}
		}

		return $result;
	} // readParams
}

?>