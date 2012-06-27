<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *  Form File Validation Model
 *
 * @package		ExpressionEngine
 * @category	Module
 * @author		Lee Langley
 * @link		http://greenimp.co.uk/
 * @copyright 	Copyright (c) 2011 Lee Langley
 * @license   	http://creativecommons.org/licenses/by-nc-nd/3.0/  Attribution-NonCommercial-NoDerivs
 *
 */

// ------------------------------------------------------------------------

class Form_File_Validation_model extends CI_Model{
	public $EE;

	// constants for values of file upload methods
	// values CANNOT be zero as checks for NULL give false positives
	const UPLOAD_TYPE_UPLOAD_PROGRESS = 1;			// PECL uploadprogress
	const UPLOAD_TYPE_ACP = 2;						// PECL ACP
	const UPLOAD_TYPE_SESSION_UPLOAD_PROGRESS = 3;	// PHP 5.4+ session.uploadprogress

	private $fileInputs = array();
	private $formFiles = array();
	private $filePath = '';
	private $fileURL = '';

	private $fileUploadMethod = null;		// the method used for tracking upload progress

	private $validationErrors = array();

	private $invalidMimeTypes = array(
		// exe
		'application/octet-stream',
		'application/x-msdownload',
		'application/exe',
		'application/x-exe',
		'application/dos-exe',
		'vms/exe',
		'application/x-winexe',
		'application/msdos-windows',
		'application/x-msdos-program',

		// msi
		'application/x-ole-storage',
		'text/mspg-legacyinfo',

		// sh/bash/perl etc
		'application/x-sh',
		'text/x-python',
		'text/x-perl',
		'text/x-bash',
		'text/x-csh',
		'text/x-c++',
		'text/x-c',
		'application/x-tcl',

		// windows metafile
		'application/x-msmetafile',

		// php
		'text/php',
		'text/x-php',
		'application/php',
		'application/x-php',
		'application/x-httpd-php',
		'application/x-httpd-php-source',

		// html
		'text/html',

		// js
		'application/ecmascript',
		'application/javascript',

		// ruby
		'application/x-ruby',
		
	);

	private $invalidFileExtensions = array(
		'exe',
		'php',
		'php3',
		'php4',
		'phtml',
		'pl',
		'py',
		'jsp',
		'asp',
		'htm',
		'shtml',
		'sh',
		'cgi',
		'msi'
	);
	
	/**
	 * Constructor
	 */
	public function Form_File_Validation_model(){
		parent::__construct();
		
		$this->EE =& get_instance();

		// load the mime types helper
		$this->EE->load->library('mime_types');

		// define the file upload path
		$this->filePath = PATH_THIRD_THEMES . 'greenform/files/';
		$this->fileURL = $this->EE->config->item('theme_folder_url') . 'third_party/greenform/uploads/';

		// ensure that the upload directory exists and is writable
		if(!file_exists($this->getFilePath())){
			@mkdir($this->getFilePath(), 0777);
		}
		@chmod($this->getFilePath(), 0777);
    }

	/**
	 * Checks whether the referring URL is local to the domain name
	 * specified in the EE config.
	 * It also returns true if running on localhost, regardless of the
	 * domain specified in the EE config.
	 * Returns true or false
	 *
	 * @return bool
	 */
	private function isReferrerLocal(){
		if(is_string($this->EE->input->server('HTTP_REFERER'))){
			$strURLHost = parse_url($this->EE->input->server('HTTP_REFERER'), PHP_URL_HOST);

			if(($strURLHost == 'localhost') || ($strURLHost == parse_url($this->EE->functions->fetch_site_index(1, 0), PHP_URL_HOST))){
				return true;
			}
		}

		return false;
	}

	/**
	 * Checks what progress tracking functionality is available
	 * for file uploads (ie; uploadprogress or ACP)
	 * Returns the value of the pre-defined class constants for the found
	 * method or null if no methods are found.
	 *
	 * The class constants are:
	 * UPLOAD_TYPE_UPLOAD_PROGRESS
	 * UPLOAD_TYPE_ACP
	 * UPLOAD_TYPE_SESSION_UPLOAD_PROGRESS
	 *
	 * @return null|int
	 */
	public function getFileUploadMethod(){
		if(is_null($this->fileUploadMethod)){
			if(function_exists('uploadprogress_get_info')){
				$this->fileUploadMethod = self::UPLOAD_TYPE_UPLOAD_PROGRESS;
			}elseif(function_exists('apc_fetch')){
				$this->fileUploadMethod = self::UPLOAD_TYPE_ACP;
			}/*elseif(ini_get('session.upload_progress.enabled')){
				$this->fileUploadMethod = self::UPLOAD_TYPE_SESSION_UPLOAD_PROGRESS;
			}*/else{
				$this->fileUploadMethod = null;
			}
		}

		return $this->fileUploadMethod;
	}

	/**
	 * Returns the maximum size, in bytes, for file uploads
	 *
	 * @return int
	 */
	public function getMaxUploadSize(){
		$limits = array(
			ini_get('upload_max_filesize'),	// the maximum per-file limit
			ini_get('post_max_size'),		// the maximum overall post size
			ini_get('memory_limit')			// the memory limit
		);

		// loop through the limits and convert them into bytes
		foreach($limits as &$limit){
			if(preg_match('/(b|k|m|g|t)(b|(ilob|egab|igab|erab)(y|i)tes?)?$/', strtolower($limit), $matches)){
				// the value contains a size type (KB, MB, GB etc)
				// ensure that the value is an integer
				$limit = (int) $limit;

				// calculate the amount of bytes
				// - each catch also runs through those beneath it, so 't' will also be caught by 'g', 'm', and 'k'
				// this is for code re-use, as each type is 1024 more than the previous
				switch($matches[1]){
					case 't':
						// size is terabytes
						$limit *= 1024;
					case 'g':
						// size is gigabytes
						$limit *= 1024;
					case 'm':
						// size is megabytes
						$limit *= 1024;
					case 'k':
						// size is kilobytes
						$limit *= 1024;
					default:
						// size is bytes - do nothing
					break;
				}
			}else{
				// no size type is defined - assume it's bytes
				$limit = (int) $limit;
			}
		}

		return min($limits);
	}

	/**
	 * Takes a numeric value in bytes and calculates the
	 * highest denominator that it fits within (ie; kb, mb, gb)
	 * and returns an array of the value in that denomination
	 * along with the denomination identifier.
	 * ie;
	 * array(
	 * 	'value' => 346,
	 * 	'denomination' => 'kb'
	 * )
	 *
	 * @param $fltSize
	 * @return array
	 */
	public function getHighestDenominator($fltSize){
		$fltSize = (float) $fltSize;
		$strDenom = 'b';

		$arrDenominators = array('kb', 'mb', 'gb', 'tb');
		foreach($arrDenominators as $denom){
			if(($fltSize / 1024) >= 1){
				$fltSize /= 1024;
				$strDenom = $denom;
			}else{
				break;
			}
		}

		return array(
			'value' => $fltSize,
			'denomination' => $strDenom
		);
	}

	/**
	 * Returns the file path to the upload directory
	 *
	 * @return string
	 */
	public function getFilePath(){
		return $this->filePath;
	}

	public function getFileURL(){
		return $this->fileURL;
	}

	/**
	 * Returns the file path to the temporary upload directory
	 *
	 * @return string
	 */
	public function getTempFilePath(){
		return $this->getFilePath() . 'tmp/';
	}

	/**
	 * Returns a list of uploaded files, if any, otherwise an empty array
	 *
	 * @return array
	 */
	public function getFormFiles(){
		return $this->formFiles;
	}

	/**
	 * Removes any current validation errors
	 *
	 * @return void
	 */
	private function clearErrors(){
		$this->validationErrors = array();
	}

	/**
	 * Adds an error to the list
	 *
	 * @param $strError
	 * @return void
	 */
	private function addError($strError){
		$this->validationErrors[] = $strError;
	}

	/**
	 * Returns a list of any validation errors
	 *
	 * @return array
	 */
	public function getErrors(){
		return $this->validationErrors;
	}

	/**
	 * Returns a list of file inputs, found in the given html
	 *
	 * @param array|null $arrRules
	 * @return array
	 */
	public function getFileInputs($arrRules = null){
		if(is_array($arrRules)){
			$this->fileInputs = $this->parseFileInputs($arrRules);
		}

		return $this->fileInputs;
	}

	/**
	 * parses the validation rules for any file inputs and adds them to the validation list
	 * It returns a list of any found file inputs
	 *
	 * @param array $arrRules
	 * @return array
	 */
	private function parseFileInputs(array $arrRules){
		// reset the input list
		$this->fileInputs = array();

		foreach($arrRules as $arrRule){
			if($arrRule['type'] == 'file'){
				// this field type is file - add it

				// check if the field is required
				$bolIsRequired = in_array('required', explode('|', $arrRule['rules']));
				// define the list of allowed mime types
				$arrAllowedTypes = isset($arrRule['accept']) ? $this->parseInputAcceptTypes($arrRule['accept']) : array();

				$this->fileInputs[] = array(
					'name' => $arrRule['field'],
					'label' => $arrRule['label'],
					'types' => $arrAllowedTypes,
					'required' => $bolIsRequired
				);
			}
		}

		return $this->fileInputs;
	}

	/**
	 * Returns a list of Mime types that are disallowed
	 *
	 * @return array
	 */
	public function getInvalidMimeTypes(){
		return $this->invalidMimeTypes;
	}

	/**
	 * Returns a list opf file extensions that are disallowed
	 *
	 * @return array
	 */
	public function getInvalidFileExtensions(){
		return $this->invalidFileExtensions;
	}

	/**
	 * Takes a Mime type and checks whether it is valid or not.
	 * If $arrMimeList is set, then the Mime type must exist
	 * in the list, otherwise it just ensures that it is not
	 * blacklisted
	 *
	 * @param string $strMime
	 * @param array $arrMimeList
	 * @return bool
	 */
	private function isMimeValid($strMime, $arrMimeList = array()){
		$bolMatch = false;
		
		if(is_array($arrMimeList) && (count($arrMimeList) > 0)){
			foreach($arrMimeList as $mimeType){
				$arrMimeParts = explode('/', $mimeType);
				if($arrMimeParts[1] == '*'){
					// the Mime allows anything that matches the first part
					if(reset(explode('/', $strMime)) == $arrMimeParts[0]){
						// the Mime type matches the first part
						$bolMatch = true;
						break;
					}
				}elseif($strMime == $mimeType){
					// the Mime type must be an exact match - and it is
					$bolMatch = true;
					break;
				}
			}
		}else{
			$bolMatch = !in_array($strMime, $this->getInvalidMimeTypes());
		}

		return $bolMatch;
	}

	/**
	 * Returns a list of all mime types
	 *
	 * @return array
	 */
	public function getMimeTypes(){
		return $this->EE->mime_types->getMimeTypes();
	}

	/**
	 * Takes a Mime type and returns a list of matching file extension.
	 * If no extension is found and empty array is returned
	 *
	 * @param string $strMime
	 * @return array
	 */
	public function mimeToExtension($strMime){
		return $this->EE->mime_types->mimeToExtension($strMime);
	}

	/**
	 * Takes a file extension and returns a list of Mime types
	 * associated with it
	 *
	 * @param string $strExtension
	 * @return array
	 */
	public function extensionToMime($strExtension){
		return $this->EE->mime_types->extensionToMime($strExtension);
	}

	/**
	 * Takes a file input's 'accept' attribute and parses the values.
	 * It then returns an array of valid mime types for this input
	 *
	 * @param $strAccept
	 * @return array
	 */
	private function parseInputAcceptTypes($strAccept){
		$arrFileTypes = array();

		if(is_array($strAccept)){
			// string is an array - loop through and get allowed types for each
			foreach($strAccept as $str){
				$arrFileTypes[] = $this->parseInputAcceptTypes($str);
			}
		}else{
			// loop through each value and validate them
			foreach(explode(',', $strAccept) as $strType){
				$strType = strtolower($strType);
				switch($strType){
					case 'audio/*':
					case 'video/*':
					case 'image/*':
						// type is a global 'accept all of type'
						$arrFileTypes[] = $strType;
					break;
					default:
						// check whether the type is actually a Mime type or not
						if(false !== strpos($strType, '/')){
							// the type contains a slash, so we assume it is a mime type

							// ensure that the mime type isn't in the black list
							if(!in_array($strType, $this->getInvalidMimeTypes())){
								// mime type isn't black-listed
								$arrFileTypes[] = $strType;
							}
						}else{
							// the string isn't a mime type - let's verify it
							// we need to convert extensions to mime types
							$arrFileTypes = array_merge($arrFileTypes, $this->extensionToMime($strType));
						}
					break;
				}
			}
		}
		
		return $arrFileTypes;
	}

	/**
	 * Takes a file name and remove any funny characters
	 *
	 * @param string $strName
	 * @return string
	 */
	private function parseFileName($strName){
		return preg_replace('/[^a-z0-9\.\_\-]/i', '', str_replace(' ', '_', strtolower($strName)));
	}
    
    /**
	 * Checks whether any $_FILE inputs have been saved and validates them
	 *
	 * @param array $arrRules
	 * @return bool
	 */
	public function validate(array $arrRules){
		$this->clearErrors();

		// get the cutsom upload files, if set
		$customUploadFiles = $this->EE->input->get_post('CUSTOM_UPLOAD_FILES');
		if((!isset($_POST) || !is_array($_POST) || empty($_POST)) && !$customUploadFiles){
			// no post variables were sent and the custom files attributes wasn't sent - return false
			return false;
		}

		// verify that the referrer is set and comes from the same URL
		if($this->isReferrerLocal()){
			// parse the validation rules to find file inputs and store them
			$arrFileInputs = $this->getFileInputs($arrRules);

			// only continue if file inputs have been defined
			if(count($arrFileInputs) > 0){
				// initiate the file info object set the file info fetch type
				$finfo = new finfo(FILEINFO_MIME_TYPE);

				// loop through each input field and check if an associated file has been uploaded
				foreach($arrFileInputs as $fileInput){
					$files = (isset($_FILES) && (count($_FILES) > 0)) ? $_FILES : (!!$customUploadFiles ? $customUploadFiles : array());

					$fieldLabel = ($fileInput['label'] != '') ? $fileInput['label'] : $fileInput['name'];

					if(isset($files[$fileInput['name']]) && isset($files[$fileInput['name']]['name']) && ($files[$fileInput['name']]['name'] != '')){
						// a file has been uploaded - validate it

						$arrFile = $files[$fileInput['name']];		// store a reference to the file
						$fileTempLocation = $arrFile['tmp_name'];	// the temp name of the file

						// check if the upload has an error
						if(!isset($arrFile['error']) || ($arrFile['error'] == 0)){
							// no error - ensure that the file has an extension
							if(false !== ($fileExt = strrchr($arrFile['name'], '.'))){
								// ensure that it is not in the blacklist
								if(!in_array($fileExt, $this->getInvalidFileExtensions())){
									// file extension checks out - ensure that it is not over the max upload size
									if(filesize($fileTempLocation) <= $this->getMaxUploadSize()){
										$fileMime = $finfo->file($fileTempLocation);	// the Mime type of the file

										if($this->isMimeValid($fileMime, $fileInput['types'])){
											// the Mime type is valid - we need to move it and add it to the list

											// define a new name for the file (just append the Unix timestamp after the name)
											$fileName = explode('.', $arrFile['name']);
											
											$curFileExt = array_pop($fileName);						// the current file extension
											$newFileExt = reset($this->mimeToExtension($fileMime));	// try and get a new file extension, from the mime type
											// check if the current extension is valid for the mime type
											foreach($this->extensionToMime($curFileExt) as $strExt){
												if($strExt == $fileMime){
													// the mime type matches - keep the current extension
													$newFileExt = '';
												}
											}

											$fileName = implode('.', $fileName) . '_' . time() . '_' . mt_rand(1, 1000) . '.' . (($newFileExt == '') ? $curFileExt : $newFileExt);
											$fileName = $this->parseFileName($fileName);		// ensure that the name doesn't contain any odd characters

											// move the file to the storage location, under it's new name
											if(@move_uploaded_file($fileTempLocation, $this->getFilePath() . $fileName) || rename($fileTempLocation, $this->getFilePath() . $fileName)){
												// add the file to the list
												$this->formFiles[$fileInput['name']] = $fileName;
											}else{
												// the file couldn't be moved or isn't actually an uplpoaded file
												$this->addError(sprintf($this->EE->lang->line('validation_file_move_error'), $fieldLabel));
											}
										}else{
											// the actual mime type of the file is incorrect
											$this->addError(sprintf($this->EE->lang->line('validation_file_invalid_file_type'), $fieldLabel));
										}
									}else{
										// the file is larger than the maximum upload limit
										$this->addError(sprintf($this->EE->lang->line('validation_file_too_large'), $fieldLabel));
									}
								}else{
									// the file type is in the blacklist
									$this->addError(sprintf($this->EE->lang->line('validation_file_invalid_file_type'), $fieldLabel));
								}
							}else{
								// no file type exists for the file, which is suspicious
								$this->addError(sprintf($this->EE->lang->line('validation_file_no_file_type'), $fieldLabel));
							}
						}else{
							// an error has occurred with the file upload
							switch($arrFile['error']){
								case UPLOAD_ERR_INI_SIZE:
									$this->addError(sprintf($this->EE->lang->line('validation_file_ini_size'), $fieldLabel));
								break;
								case UPLOAD_ERR_FORM_SIZE:
									$this->addError(sprintf($this->EE->lang->line('validation_file_form_size'), $fieldLabel));
								break;
								case UPLOAD_ERR_PARTIAL:
									$this->addError(sprintf($this->EE->lang->line('validation_file_partial'), $fieldLabel));
								break;
								case UPLOAD_ERR_NO_FILE:
									if($fileInput['required']){
										$this->addError(sprintf($this->EE->lang->line('validation_file_required'), $fieldLabel));
									}
								break;
								case UPLOAD_ERR_NO_TMP_DIR:
									$this->addError(sprintf($this->EE->lang->line('validation_file_no_tmp'), $fieldLabel));
								break;
								case UPLOAD_ERR_CANT_WRITE:
									$this->addError(sprintf($this->EE->lang->line('validation_file_cant_write'), $fieldLabel));
								break;
								case UPLOAD_ERR_EXTENSION:
									$this->addError(sprintf($this->EE->lang->line('validation_file_extension'), $fieldLabel));
								break;
								default:
									$this->addError(sprintf($this->EE->lang->line('validation_file_error'), $fieldLabel));
								break;
							}
						}

						// ensure that the temp file has been removed
						@unlink($fileTempLocation);
					}elseif($fileInput['required']){
						// no file has been uploaded, but it is required!
						$this->addError(sprintf($this->EE->lang->line('validation_file_required'), $fieldLabel));
					}
				}
			}
		}else{
			// the referrer set is invalid
			$this->addError($this->EE->lang->line('validation_invalid_referrer'));
		}

		if(count($this->getErrors()) > 0){
			$this->EE->form_validation->_error_array = array_merge($this->EE->form_validation->_error_array, $this->getErrors());
			return false;
		}else{
			return true;
		}
	}

	/**
	 * This deals with displaying file uploads when
	 * uploading through an Ajax submitted form
	 *
	 * @param array $arrRules
	 * @return void
	 */
	public function handleAjaxUpload($arrRules){
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

		if($this->isReferrerLocal()){
			$fileInputs = $this->getFileInputs($arrRules);

			if(empty($fileInputs)){
				// there aren't actually any file inputs specified in the rules
				$data['error'] = $this->EE->lang->line('validation_no_file_inputs');
				die(json_encode($data));
			}else{
				extract($_REQUEST);

				// servlet that handles uploadprogress requests:
				if(isset($upload_id)){
					$data = array();

					switch($this->getFileUploadMethod()){
						case self::UPLOAD_TYPE_UPLOAD_PROGRESS:
							if(function_exists('uploadprogress_get_info')){
								// the uploadprogress module is installed
								if(!is_array($data = uploadprogress_get_info($upload_id))){
									// the file wasn't found
									$data['error'] = $this->EE->lang->line('validation_upload_no_id');
									// no need to define the variables on success as this module sets them all as we want
								}
							}else{
								// no upload progress functionality was found
								$data['error'] = $this->EE->lang->line('validation_upload_not_available');
							}
						break;
						case self::UPLOAD_TYPE_ACP:
							if(function_exists('apc_fetch')){
								// the APC module is installed
								if(false === ($tmpData = apc_fetch('upload_' . $upload_id))){
									// the file wasn't found
									$data['error'] = $this->EE->lang->line('validation_upload_no_id');
								}else{
									// define the $data array
									$fltTime = time();
									$fltTimeTaken = ($fltTime > $tmpData['start_time']) ? $fltTime - $tmpData['start_time'] : 0;
									$data['time_taken'] = $fltTimeTaken;
									// define the $data array
									$data['bytes_uploaded'] = $tmpData['current'];																		// the bytes uploaded
									$data['bytes_total'] = $tmpData['total'];																			// the total file size
									$data['speed_average'] = ($fltTimeTaken == 0) ? $data['bytes_uploaded'] : $data['bytes_uploaded'] / $fltTimeTaken;	// the average upload speed in bytes
									$data['est_sec'] = ($data['bytes_total'] - $data['bytes_uploaded']) / $data['speed_average'];						// estimated seconds until complete
								}
							}else{
								// no APC functionality was found
								$data['error'] = $this->EE->lang->line('validation_upload_not_available');
							}
						break;
						case self::UPLOAD_TYPE_SESSION_UPLOAD_PROGRESS:
							if(ini_get('session.upload_progress.enabled')){
								// session.upload_progress is enabled, we must be running PHP 5.4+
								$strKey = ini_get('session.upload_progress.prefix') . ini_get('session.upload_progress.name') . $upload_id;

								if(isset($_SESSION[$strKey]) && is_array($_SESSION[$strKey]) && !empty($_SESSION[$strKey])){
									$fltTime = time();
									$fltTimeTaken = ($fltTime > $_SESSION[$strKey]['start_time']) ? $fltTime - $_SESSION[$strKey]['start_time'] : 0;
									$data['time_taken'] = $fltTimeTaken;
									// define the $data array
									$data['bytes_uploaded'] = $_SESSION[$strKey]['bytes_processed'];													// the bytes uploaded
									$data['bytes_total'] = $_SESSION[$strKey]['content_length'];														// the total file size
									$data['speed_average'] = ($fltTimeTaken == 0) ? $data['bytes_uploaded'] : $data['bytes_uploaded'] / $fltTimeTaken;	// the average upload speed in bytes
									$data['est_sec'] = ($data['bytes_total'] - $data['bytes_uploaded']) / $data['speed_average'];						// estimated seconds until complete
								}
							}else{
								// no session.uploadprogress functionality was found
								$data['error'] = $this->EE->lang->line('validation_upload_not_available');
							}
						break;
						default:
							// no upload progress functionality was found
							$data['error'] = $this->EE->lang->line('validation_upload_not_available');
						break;
					}

					if(!empty($data) && (!isset($data['error']) || empty($data['error']))){
						// data has been set

						if($this->getMaxUploadSize() < $data['bytes_total']){
							// the file size is too large - stop reading the progress
							$data['error'] = $this->EE->lang->line('validation_file_too_large');
						}else{
							// we need to get the average, total and uploaded in different data types

							$data['average'] = $data['speed_average'];
							$data['total'] = $data['bytes_total'];
							$data['uploaded'] = $data['bytes_uploaded'];

							// loop through the data types and fetch their upload values
							$arrDataTypes = array('kb', 'mb', 'gb', 'tb');
							foreach($arrDataTypes as $k => $type){
								// get the previous data type
								$prevK = isset($arrDataTypes[$k-1]) ? $arrDataTypes[$k-1] : 'bytes';

								// calculate the average speed per second
								$speedAverage = (isset($data[$prevK . '_average']) ? $data[$prevK . '_average'] : $data['speed_average']) / 1024;
								if($speedAverage < 100){
									$speedAverage = round($speedAverage, 1);
								}elseif($speedAverage < 10){
									$speedAverage = round($speedAverage, 2);
								}else{
									$speedAverage = round($speedAverage);
								}

								// define the data type average speed, total upload size and uploaded data size
								$data[$type . '_average'] = $speedAverage;
								$data[$type . '_total'] = round($data[$prevK . '_total'] / 1024, 2);
								$data[$type . '_uploaded'] = round($data[$prevK . '_uploaded'] / 1024, 2);

								// if the average is not less than one, we set it to the largest type to use
								if($data[$type . '_average'] >= 1){
									$data['average'] = $data[$type . '_average'] . $type;
								}
								// if the total is not less than one, we set it to the largest type to use
								if($data[$type . '_total'] >= 1){
									$data['total'] = $data[$type . '_total'] . $type;
								}
								// if the uploaded amount is not less than one, we set it to the largest type to use
								if($data[$type . '_uploaded'] >= 1){
									$data['uploaded'] = $data[$type . '_uploaded'] . $type;
								}
							}

							// round up the estimated time to a full second
							$data['est_sec'] = ceil($data['est_sec']);
						}
					}

					// output the result
					die(json_encode($data));
				}


				// check if we need to display the completion message
				// determine which post variable to use, depending on the method used
				switch($this->getFileUploadMethod()){
					case self::UPLOAD_TYPE_UPLOAD_PROGRESS:
						$idFieldName = 'UPLOAD_IDENTIFIER';
					break;
					case self::UPLOAD_TYPE_ACP:
						$idFieldName = 'APC_UPLOAD_PROGRESS';
					break;
					case self::UPLOAD_TYPE_SESSION_UPLOAD_PROGRESS:
					default:
						$idFieldName = null;
					break;
				}

				// loop through and create a list of file input names to compare against
				foreach($fileInputs as $k => $data){
					$fileInputs[$k] = $data['name'];
				}

				if(is_null($idFieldName) || isset($$idFieldName)){
					// either a specific PHP upload method was used or
					// no PHP upload method was used, but HTML5 might have
					// - check the $_FILES variable as the form could have been submitted with HTML5

					// loop through each file and move it to the temp directory
					$files = array();
					foreach($_FILES as $k => $file){
						// only move the file if it's field exists
						if(in_array($k, $fileInputs)){
							move_uploaded_file($file['tmp_name'], $this->getTempFilePath() . $file['name']);

							$file['tmp_name'] = $this->getTempFilePath() . $file['name'];
							$files[$k] = $file;
						}
					}

					die(json_encode($files));
				}
			}
		}else{
			$data['error'] = $this->EE->lang->line('validation_invalid_referrer');
			die(json_encode($data));
		}

		die(json_encode(array('error' => 'unknown')));
	}
// ------------------------------------------------------------------------    
}
?>