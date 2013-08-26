<?php
namespace Cundd\LiveMaster\Controller;

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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 *
 *
 * @package live_master
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class ConfigurationController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {
	/**
	 * Prefix for variables
	 */
	const VARIABLE_PREFIX = '$';

	/**
	 * Specifies if the native type should be used for numbers
	 * @var bool
	 */
	protected $useNativeTypeForNumbers = FALSE;

	/**
	 * configurationRepository
	 *
	 * @var \Cundd\LiveMaster\Domain\Repository\ConfigurationRepository
	 * @inject
	 */
	protected $configurationRepository;

	/**
	 * Writes the Live Master scss file
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $configuration
	 * @return bool|int
	 */
	public function createScssFileFromConfiguration(\Cundd\LiveMaster\Domain\Model\Configuration $configuration) {
		// if (!isset($GLOBALS['BE_USER'])
		// 	|| !isset($GLOBALS['BE_USER']->user)
		// 	|| !intval($GLOBALS['BE_USER']->user['uid'])) {
		// 	return FALSE;
		// }

		/**
		 * @var \UnexpectedValueException $jsonError
		 */
		$jsonError = NULL;
		$contents = '';
		$jsonData = $configuration->getJsonData($jsonError);
		if ($jsonError) {
			$this->flashMessageContainer->add('JSON error: ' . $jsonError->getMessage());
		}
		foreach ($jsonData as $variable => $value) {
			$contents .= self::VARIABLE_PREFIX . $variable . ':' . $value . ';' . PHP_EOL;
		}

		if (isset($this->settings['stylesheet']) && $this->settings['stylesheet']) {
			$stylesheet = GeneralUtility::getFileAbsFileName($this->settings['stylesheet']);

			if (substr($stylesheet, -5) === '.scss' || substr($stylesheet, -4) === '.css') {
				$contents .= '@import "' . $stylesheet . '";';
			}
		}
		$path = PATH_site . 'typo3temp/livemaster/live.scss';
		return file_put_contents($path, $contents);
	}

	/**
	 * Parses the variables file and extracts the variables and values
	 * @param $filepath
	 * @return array
	 */
	public function parseScssVariablesFile($filepath) {
		$matches = array();
		$contents = array();
		$variables = array();
		$handle = @fopen($filepath, 'r');
		if ($handle) {
			while (($buffer = fgets($handle, 4096)) !== FALSE) {
				$buffer = trim($buffer);
				if (substr($buffer, 0, 2) !== '//') {
					$contents[] = $buffer;
				}
			}
			fclose($handle);
		}

		if (!preg_match_all('!\\' . self::VARIABLE_PREFIX .'[a-zA-Z0-9\$\-\_\@]+:.*;!', implode(PHP_EOL, $contents), $matches)) {
			return array();
		}

		$matches = reset($matches);
		while ($match = current($matches)) {
			$default = FALSE;
			$parts = explode(':', substr($match, 0, -1));
			$variableName = trim($parts[0]);
			$variableValue = trim($parts[1]);
			$variableValueUnparsed = $variableValue;
			$variableNameWithoutDollar = $variableName;
			if (substr($variableValue, -8) === '!default') {
				$default = TRUE;
				$variableValue = trim(substr($variableValue, 0, -8));
			}

			if (($default === FALSE)
				|| ($default && !isset($variables[$variableName]))
			) {
				if ($variableNameWithoutDollar[0] === self::VARIABLE_PREFIX) {
					$variableNameWithoutDollar = substr($variableNameWithoutDollar, 1);
				}
				if ($variableValue[0] === self::VARIABLE_PREFIX && isset($variables[$variableValue])) {
					$variableValue = $variables[$variableValue];
				}
				$inputType = $this->getInputTypeForVariable($variableNameWithoutDollar, $variableValue);
				$variables[$variableNameWithoutDollar] = array(
					'name' 				=> $variableName,
					'nameStripped' 		=> $variableNameWithoutDollar,
					'value' 			=> $variableValue,
					'valueUnparsed'		=> $variableValueUnparsed,
					'nameSentence'		=> $this->variableNameToSentence($variableNameWithoutDollar),
					'type'				=> $inputType,
					'isConfigured'		=> FALSE
				);
			} else {
				// Variable doesn't has to be reassigned
			}
			next($matches);
		}
		return $variables;
	}

	/**
	 * Tries to create a sentence for the given variable name
	 * @param  string $variableName
	 * @return string
	 */
	public function variableNameToSentence($variableName) {
		$sentenceFormat = '';
		$sentence = GeneralUtility::camelCaseToLowerCaseUnderscored($variableName);
		$sentence = str_replace('_i_e8_', '_IE8_', $sentence);
		$sentence = str_replace('_', ' ', $sentence);

		$words = explode(' ', $sentence);
		if (isset($words[0])) {
			if ($words[1] === 'padding') {
				$sentenceFormat = 'I want a %s of';
			// } elseif ($words[1] === 'background') {
			// 	$sentenceFormat = 'I want my %s in';
			// } elseif (strpos($sentence, 'font size')) {
			// 	$sentenceFormat = 'I want the %s to be';
			}

			if (!$sentenceFormat) {
				switch ($words[0]) {
					case 'enable':
						$sentenceFormat = 'I want to %s';
						break;

					case 'theme':
					default:
						$sentenceFormat = 'I want the %s to be';
						// $sentenceFormat = 'I want %s';
						break;
				}
			}
		}
		return sprintf($sentenceFormat, $sentence);
	}

	/**
	 * Tries to determine the input type for the given variable
	 * @param  string $variableName  Variable name
	 * @param  mixed $variableValue Reference to the variable value
	 * @return string
	 */
	public function getInputTypeForVariable($variableName, &$variableValue) {
		$type = 'text';

		$variableColorValue = $this->prepareColor($variableValue);
		if ($variableColorValue) {
			$variableValue = $variableColorValue;
			$type = 'color';
		} elseif ($this->useNativeTypeForNumbers && is_numeric($variableValue)) {
			$type = 'number';
		}
		return $type;
	}

	/**
	 * Prepares the given color to be used as a input color value
	 * @param string $color
	 * @return string|boolean Returns the prepared color or FALSE if the input couldn't be transformed to a color
	 */
	protected function prepareColor($color) {
		$newColor = FALSE;
		if (preg_match('!^#[a-z0-9]{3}([a-z0-9]{3})*$!i', $color)) {
			if (strlen($color) === 4) {
				$newColor = '#'
					. $color[1] . $color[1]
					. $color[2] . $color[2]
					. $color[3] . $color[3];
			} else {
				$newColor = $color;
			}
		}
		return $newColor;
	}

	/**
	 * Merges the variables from the configuration with the variables from the
	 * given file
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $configuration
	 * @param $filepath
	 * @return array
	 */
	public function mergeVariableConfigurationWithFile($configuration, $filepath) {
		$variables = $this->parseScssVariablesFile($filepath);
		$jsonData = $configuration->getJsonData();
		foreach ($jsonData as $variableName => $variableValue) {
			$inputType = $this->getInputTypeForVariable($variableName, $variableValue);
			$variables[$variableName] = array(
				'name' 				=> self::VARIABLE_PREFIX . $variableName,
				'nameStripped' 		=> $variableName,
				'value' 			=> $variableValue,
				'valueUnparsed'		=> $variableValue,
				'nameSentence'		=> $this->variableNameToSentence($variableName),
				'type'				=> $inputType,
				'isConfigured'		=> TRUE
			);
		}

		return $variables;
	}

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$variablesFile = '';
		$configurations = $this->configurationRepository->findAll();
		$this->view->assign('configurations', $configurations);

		// Get the current configuration
		$configuration = reset(iterator_to_array($configurations));

		// Get the variables file path and merge the variables with the
		// configuration
		if (isset($this->settings['variablesFile']) && $this->settings['variablesFile']) {
			$variablesFile = GeneralUtility::getFileAbsFileName($this->settings['variablesFile']);
		}
		$variables = $this->mergeVariableConfigurationWithFile($configuration, $variablesFile);
		$this->view->assign('variables', $variables);

		// Create the SCSS file
		$this->createScssFileFromConfiguration($configuration);
		$this->view->assign('configuration', $configuration);
	}

	/**
	 * action show
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $configuration
	 * @return void
	 */
	public function showAction(\Cundd\LiveMaster\Domain\Model\Configuration $configuration) {
		$this->view->assign('configuration', $configuration);
	}

	/**
	 * action new
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $newConfiguration
	 * @dontvalidate $newConfiguration
	 * @return void
	 */
	public function newAction(\Cundd\LiveMaster\Domain\Model\Configuration $newConfiguration = NULL) {
		$this->view->assign('newConfiguration', $newConfiguration);
	}

	/**
	 * action create
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $newConfiguration
	 * @return void
	 */
	public function createAction(\Cundd\LiveMaster\Domain\Model\Configuration $newConfiguration) {
		$this->configurationRepository->add($newConfiguration);
		$this->flashMessageContainer->add('Your new Configuration was created.');
		$this->redirect('list');
	}

	/**
	 * action edit
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $configuration
	 * @return void
	 */
	public function editAction(\Cundd\LiveMaster\Domain\Model\Configuration $configuration) {
		$this->view->assign('configuration', $configuration);
	}

	/**
	 * action update
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $configuration
	 * @return void
	 */
	public function updateAction(\Cundd\LiveMaster\Domain\Model\Configuration $configuration) {
		$this->configurationRepository->update($configuration);
		$this->flashMessageContainer->add('Your Configuration was updated.');
		$this->redirect('list');
	}

	/**
	 * action delete
	 *
	 * @param \Cundd\LiveMaster\Domain\Model\Configuration $configuration
	 * @return void
	 */
	public function deleteAction(\Cundd\LiveMaster\Domain\Model\Configuration $configuration) {
		$this->configurationRepository->remove($configuration);
		$this->flashMessageContainer->add('Your Configuration was removed.');
		$this->redirect('list');
	}

}
?>