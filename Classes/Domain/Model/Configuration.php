<?php
namespace Cundd\LiveMaster\Domain\Model;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Daniel Corn <info@cundd.net>, cundd
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 *
 * @package live_master
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Configuration extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * Name
	 *
	 * @var \string
	 * @validate NotEmpty
	 */
	protected $name;

	/**
	 * Configuration data
	 *
	 * @var \string
	 * @validate NotEmpty
	 */
	protected $data;

	/**
	 * Decoded data
	 * @var Mixed
	 */
	protected $_dataDecoded;

	/**
	 * Returns the name
	 * @return \string $name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the name
	 * @param \string $name
	 * @return void
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the data
	 * @return string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * Returns the decoded data
	 *
	 * @param \UnexpectedValueException $error Reference to be filled with the occurred exception
	 * @return Mixed $data
	 */
	public function getJsonData(&$error = NULL) {
		if (!$this->_dataDecoded) {
			$this->_dataDecoded = json_decode($this->data);
			if (!$this->_dataDecoded) {
				$error = new \UnexpectedValueException($this->getJsonError() . ': "' . $this->data . '"', 1375625105);
			}
		}
		return $this->_dataDecoded;
	}

	/**
	 * Returns a error message for the last JSON error
	 * @return string
	 */
	protected function getJsonError() {
		$error = '';
		switch (json_last_error()) {
			case JSON_ERROR_NONE:
				$error = 'No errors';
				break;
			case JSON_ERROR_DEPTH:
				$error = 'Maximum stack depth exceeded';
				break;
			case JSON_ERROR_STATE_MISMATCH:
				$error = 'Underflow or the modes mismatch';
				break;
			case JSON_ERROR_CTRL_CHAR:
				$error = 'Unexpected control character found';
				break;
			case JSON_ERROR_SYNTAX:
				$error = 'Syntax error, malformed JSON';
				break;
			case JSON_ERROR_UTF8:
				$error = 'Malformed UTF-8 characters, possibly incorrectly encoded';
				break;
			default:
				$error = 'Unknown error';
				break;
		}
		return $error;
	}

	/**
	 * Sets the data
	 *
	 * @param \string $data
	 * @return void
	 */
	public function setData($data) {
		$this->data = $data;
	}

}
?>