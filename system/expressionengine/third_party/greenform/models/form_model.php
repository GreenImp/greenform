<?php
if(!defined('BASEPATH')) exit('No direct script access allowed');
/**
 *  Form Model
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

class Form_model extends CI_Model{
	private $tablePrefix = 'green_form_';
	public $EE;

	/**
	* Constructor
	*
	*/
	public function Form_model(){
		parent::__construct();

		$this->EE =& get_instance();
	}

	/**
	* Returns a list of all the form entries
	*
	* @return array
	*/
	public function getAllEntries(){
		$this->db->order_by('`entry_date` DESC, `entry_id` ASC');
		$query = $this->db->get($this->tablePrefix . 'submissions');
		return $query->result_array();
	}

	/**
	 * Returns a list of all the validation rules
	 *
	 * @return array
	 */
	public function getAllRules(){
		$query = $this->db->get($this->tablePrefix . 'validation_rules');
		return $query->result_array();
	}

	/**
	 * Returns the validation rules for the specific type
	 *
	 * @param $strType
	 * @return array
	 */
	public function getValidationRules($strType){
		$arrRules = array();

		if(is_string($strType) && ($strType != '')){
			$strQuery = "SELECT
						`rule_fields`
					FROM
						" . $this->db->protect_identifiers($this->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
					WHERE
						`rule_name` = " . $this->db->escape($strType) . "
					LIMIT 1";
			$result = $this->db->query($strQuery);
			if($result->num_rows > 0){
				$arrRules = json_decode($result->row('rule_fields'), true);
			}
		}

		return $arrRules;
	}

	/**
	 * Checks whether the current rule type allows the use
	 * of a captcha
	 * 
	 * @param $strType
	 * @param bool $buildForm
	 * @return bool
	 */
	public function useCaptcha($strType, $buildForm = true){
		if(!$buildForm){
			// user is building the form - allow captcha because we don't know if they want it or not
			return true;
		}elseif(is_string($strType) && ($strType != '')){
			// form is being built automatically - check if captcha is turned on in the rule set
			$strQuery = "SELECT
						`rule_use_captcha`
					FROM
						" . $this->db->protect_identifiers($this->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
					WHERE
						`rule_name` = " . $this->db->escape($strType) . " AND
						`rule_use_captcha` = 1
					LIMIT 1";
			$result = $this->db->query($strQuery);
			return $result->num_rows > 0;
		}

		return false;
	}
     
	/**
	* Inserts the data from the form
	*
	* @param bool $data
	* @return bool
	*/
	function insert($data = false){
		if(is_array($data)){
			$this->db->insert($this->tablePrefix . 'submissions', $data);
			return true;
		}
		return false;
	}

	/**
	 * Builds up the form HTML tags and returns it,
	 * with a sprintf style '%s' for easy inserting
	 * of the form contents
	 *
	 * @param array $settings
	 * @param array $arrRuleList
	 * @return string
	 */
	public function buildFormHTML(array $settings, array $arrRuleList){
		// define the form ID
		if($settings['formID'] != ''){
			// an ID has been specified - use it
			$strFormID = str_replace('"', '', $settings['formID']);
		}elseif($settings['jqueryValidation']){
			// no ID has been specified, but we are doing jQuery validation, so add one
			$strFormID = 'greenForm' . time();
		}else{
			// no form ID
			$strFormID = '';
		}

		// add the form opening tag
		$strHTMLOpen = '<form ';
		$strHTMLOpen .= 'action="' . $settings['post_url'] . '" ';
		$strHTMLOpen .= 'method="post"' . ($settings['allowFiles'] ? ' enctype="multipart/form-data"' : '');
		if($strFormID != ''){
			// a form ID was specified
			$strHTMLOpen .= ' id="' . $strFormID . '"';
		}
		if($settings['formClass'] != ''){
			// a form class (or list of) was specified
			$strHTMLOpen .= ' class="' . str_replace(array('"', ',', '|'), array('', ' ', ' '), $settings['formClass']) . '"';
		}
		$strHTMLOpen .= '>';

		if($settings['allowFiles']){
			// we have files to upload - specify a max size input (value is size in kb)
			// load the file validation model
			$this->EE->load->model('form_file_validation_model', 'form_file_validation');
			// insert the max size input (this is purely a first-level check and is NOT reliable!)
			$strHTMLOpen .= PHP_EOL . '<input type="hidden" name="MAX_FILE_SIZE" value="' . $this->EE->form_file_validation->getMaxUploadSize() . '">';

			$uploadID = uniqid();
			switch($this->EE->form_file_validation->getFileUploadMethod()){
				case Form_File_Validation_model::UPLOAD_TYPE_UPLOAD_PROGRESS:
					// uploadprogress is installed - add the field input
					$strHTMLOpen .= PHP_EOL . '<input type="hidden" name="UPLOAD_IDENTIFIER" value="' . $uploadID . '" id="uploadID" />';
				break;
				case Form_File_Validation_model::UPLOAD_TYPE_ACP:
					// APC is installed - add the field input
					$strHTMLOpen .= PHP_EOL . '<input type="hidden" name="APC_UPLOAD_PROGRESS" value="' . $uploadID . '" id="uploadID" />';
				break;
				case Form_File_Validation_model::UPLOAD_TYPE_SESSION_UPLOAD_PROGRESS:
					// insert an input to allow the tracking of file uploads, using session.upload_progress
					$strHTMLOpen .= PHP_EOL . '<input type="hidden" name="' . ini_get("session.upload_progress.name") . '" value="' . $uploadID . '" id="uploadID" />';
				default:
				break;
			}
		}
		if($settings['jquerySubmit']){
			// we are submitting the form in jQuery, so add a hidden input telling the script to only return errors or success
			$strHTMLOpen .= PHP_EOL . '<input type="hidden" name="greenFormRequestType" value="ajax">';
		}

		$strHTMLClose = '';
		if($settings['jqueryValidation'] || $settings['jquerySubmit']){
			// determine the Javascript language lines
			$arrLanguage = $this->EE->lang->language;	// list of all language lines
			$startKey = 'greenform_module_name';		// the key to start reading from
			$bolRun = false;							// flag whether we have started the retrieval or not
			$arrJSLanguage = array();					// list holding the Javascript language line
			// loop through and find each line
			foreach($arrLanguage as $key => $value){
				if($key == $startKey){
					$bolRun = true;
				}elseif($bolRun && ($key == '')){
					break;
				}elseif($bolRun && (0 === strpos($key, 'js_'))){
					$arrJSLanguage[substr($key, 3)] = $value;
				}
			}


			// we need to include the javascript file for handling form validation and submission
			$strJS = '';
			$strJS .= '<script type="text/javascript">' . PHP_EOL;
			if($settings['jqueryValidation'] && (count($arrRuleList) > 0)){
				// we need to add form validation
				$strJS .= "\tvar formValidate = true," . PHP_EOL;								// flag to validate the form
				$strJS .= "\truleList = " . json_encode($arrRuleList) . ";" . PHP_EOL;			// the validation rules
			}
			if($settings['jquerySubmit']){
				// we need to catch form submission
				$strJS .= "\tvar formCatch = true;" . PHP_EOL;									// flag to catch the form submission
			}
			$strJS .= "\tvar strFormID = '" . $strFormID . "'," . PHP_EOL;						// set the form element's ID
			$strJS .= "\terrorBoxClass = '" . $settings['errorMsgClass'] . "'," . PHP_EOL;		// the error message box's class name
			$strJS .= "\tsuccessBoxClass = '" . $settings['successMsgClass'] . "'," . PHP_EOL;	// the success message box's class name
			$strJS .= "\terrorClass = '" . $settings['errorClass'] . "'," . PHP_EOL;			// the class assigned to field elements with an error
			$strJS .= "\tvalidateLanguage = " . json_encode($arrJSLanguage) . ";" . PHP_EOL;	// the class assigned to field elements with an error
			$strJS .= '</script>' . PHP_EOL;
			$strHTMLClose .= $strJS;

			if($settings['jquerySubmit']){
				// we are submitting using jquery so, we need to use an ajax file uploader for any file inputs
				$strHTMLClose .= '<script type="text/javascript" src="' . URL_THIRD_THEMES . 'greenform/libraries/uploadprogress/jquery.uploadprogress.js"></script>' . PHP_EOL;
			}

			$strHTMLClose .= '<script type="text/javascript" src="' . URL_THIRD_THEMES . 'greenform/libraries/webtoolkit/webtoolkit.sprintf.js"></script>' . PHP_EOL;
			$strHTMLClose .= '<script type="text/javascript" src="' . URL_THIRD_THEMES . 'greenform/javascript/form-handler.js"></script>' . PHP_EOL;
		}

		// add the form closing tag
		$strHTMLClose .= '</form>';

		return array(
			'openTag' => $strHTMLOpen,
			'closeTag' => $strHTMLClose
		);
	}

	/**
	 * Takes a list of validation rules and returns a basic html layout
	 * for the form elements
	 *
	 * @param array $validationRules
	 * @param string $buildType
	 * @param string $strHTMLVersion
	 * @return string
	 */
	public function buildInnerFormHTML(array $validationRules, $buildType = 'ul', $strHTMLVersion = 'html5'){
		$bolHTML5 = strtolower($strHTMLVersion) == 'html5';	// flag whether we are using HTML5 or not
		$strIDAppend = 'cForm';								// the string to append to form element IDs, to ensure that they're unique
		$strFieldBlockClass = 'fieldBlock';					// the class name to apply to the holder element for all of the form elements
		$strSurroundFieldClass = $strIDAppend . 'Row';		// the class name to apply to the direct row/holder of a form field

		$arrTypeMap = array(					// list of mappings between validation rules and HTML5 input types
			'valid_email' => 'email',

			'greater_than' => 'number',
			'less_than' => 'number',
			'numeric' => 'number',
			'integer' => 'number',
			'is_natural' => 'number',
			'is_natural_no_zero' => 'number'
		);
		$arrClassMap = array(					// list of mappings between validation rules and class names
			'required' => 'required',
			'valid_email' => 'email',

			'numeric' => 'number',
			'integer' => 'integer',
			'is_natural' => 'number',
			'is_natural_no_zero' => 'number',

			'valid_ip' => 'ip'
		);

		$arrFieldsNoValue = array(				// list of field types that can't use a default value as a title/placeholder text
			'select',
			'radio',
			'checkbox',
			'file'
		);



		// determine which markup to use to surround the form elements in
		switch($buildType){
			case 'table':
				// we are using a table
				$formMarkup = array(
					'openTag' => '<table cellpadding="0" cellspacing="0" border="0" class="%s">',
					'closeTag' => '</table>',
					'surroundTag' => "\t".'<tr class="%s">' . PHP_EOL . "\t\t".'<td valign="top">%s</td>' . PHP_EOL . "\t\t".'<td>%s</td>' . PHP_EOL . "\t".'</tr>' . PHP_EOL
				);
			break;
			case 'dl':
			case 'definition-list':
				// we are using a definition list
				$formMarkup = array(
					'openTag' => '<dl class="%s">',
					'closeTag' => '</dl>',
					'surroundTag' => "\t".'<dt class="%1$s">%2$s</dt>' . PHP_EOL . "\t".'<dd class="%1$s">%3$s</dd>' . PHP_EOL,
				);
			break;
			default:
				// none specified or default, so use an un-ordered list
				$formMarkup = array(
					'openTag' => '<ul class="%s">',
					'closeTag' => '</ul>',
					'surroundTag' => "\t".'<li class="%s">%s%s</li>' . PHP_EOL
				);
			break;
		}


		// start building the form HTML
		// set up the opening tag (ie; <ul>) and insert the field block class
		$strHTML =  PHP_EOL . sprintf($formMarkup['openTag'], $strFieldBlockClass) . PHP_EOL;

		// loop through each rule and create the html for the input field
		$intRuleCount = count($validationRules);
		foreach($validationRules as $intCount => $arrRule){
			// define any class names to attach to the element around the form field and it's label
			$surroundClass = $strSurroundFieldClass . ' ';
			$surroundClass .= ($intCount % 2 == 0) ? 'even' : 'odd';
			if($intCount == 0){
				// this is the first element
				$surroundClass .= ' first';
			}elseif($intCount == $intRuleCount-1){
				// this is the last element
				$surroundClass .= ' last';
			}

			// get a list of the rules for this input
			$rules = explode('|', $arrRule['rules']);

			// determine the input type to associate with the field
			$strType = $arrRule['type'];
			if($bolHTML5 && ($strType == 'text')){
				// the type is text and we are using HTML5 -
				// loop through and check if we can match it to a more specific type, such as 'email' or 'number'
				foreach($arrTypeMap as $ruleName => $type){
					if(in_array($ruleName, $rules)){
						// a matching type was found
						$strType = $type;
						break;
					}
				}
			}

			// determine any attributes/class names for the field
			$arrAttributes = array();	// list of attributes to add to the element
			$arrClassNames = array();	// list of class names to add to the element
			foreach($rules as $rule){
				switch($rule){
					case 'required':
						// this field is required
						if($bolHTML5){
							// HTML5 so use the required attribute
							$arrAttributes[] = 'required';
						}else{
							// not HTML5 so add a 'required' class
							$arrClassNames[] = 'required';
						}
					break;
					default:
						if(preg_match('/^((max|exact)_length)\[([\d]+)\]$/', $rule, $matches)){
							// we have specified a max or exact length, so add a maxlength attribute
							$arrAttributes[] = 'maxlength="' . $matches[3] . '"';
						}elseif($bolHTML5 && preg_match('/^((greater|less)_than)\[([\d]+)\]$/', $rule, $matches)){
							// we have specified that the field be greater|less than a value, so add a min|max value
							$arrAttributes[] = (($matches[2] == 'greater') ? 'min' : 'max') . '="' . $matches[3] . '"';
						}
					break;
				}

				if(isset($arrClassMap[$rule])){
					// the rule type has a class name associated with it
					$arrClassNames[] = $arrClassMap[$rule];
				}
			}

			$mimePattern = '[a-z0-9\*\-\_]+\/[a-z0-9\*\-\_]+';
			if(($arrRule['type'] == 'file') && isset($arrRule['accept']) && preg_match('/^' . $mimePattern . '(,' . $mimePattern . ')*$/', $arrRule['accept'])){
				// the field is a file input and has an accept parameter
				$arrAttributes[] = 'accept="' . $arrRule['accept'] . '"';
			}
			if($bolHTML5 && ($intCount == 0) && ($arrRule['type'] != 'file')){
				// this is the first form element - add autofocus
				$arrAttributes[] = 'autofocus';
			}
			if($bolHTML5 && ($arrRule['type'] == 'password')){
				$arrAttributes[] = 'autocomplete="off"';
			}
			if($bolHTML5 && ($arrRule['value'] != '') && !in_array($arrRule['type'], $arrFieldsNoValue)){
				// this element has a default value and we are using HTML5 - set it as a placeholder, instead of value
				$arrAttributes[] = 'placeholder="' . $arrRule['value'] . '"';
			}

			// clean the attribute and classname arrays, to remove any duplicated values
			$arrAttributes = array_unique($arrAttributes);
			$arrClassNames = array_unique($arrClassNames);


			// build the field HTML
			$arrFieldHTML = array();	// stores a list of inputs for this field (in case of radio/check boxes)
			switch($strType){
				case 'textarea':
					// field is a textarea
					$arrFieldHTML[] = '<textarea name="%2$s" cols="40" rows="8"%5$s id="%4$s"%6$s>%3$s</textarea>';
				break;
				case 'select':
					// field is a select box
					$strFieldHTML = '<select name="%2$s"%5$s id="%4$s"%6$s>';
					if(isset($arrRule['value']) && ($arrRule['value'] != '')){
						// loop through the values and build up the options
						foreach(explode(',', $arrRule['value']) as $intCount => $option){
							@list($label, $value) = explode(':', $option);
							$value = isset($value) ? $value : $label;
							$strFieldHTML .= '<option value="' . $value . '"' . (($intCount == 0) ? ' selected="selected"' : '') . '>' . $label . '</option>';
						}
					}
					$strFieldHTML .= '</select>';
					// add the field to the list
					$arrFieldHTML[] = $strFieldHTML;
				break;
				case 'checkbox':
				case 'radio':
					// the field is a radio button or checkbox

					// ensure that the 'label => value' variable is an array
					if(isset($arrRule['value']) && ($arrRule['value'] != '')){
						$arrOptions = explode(',', $arrRule['value']);
					}else{
						$arrOptions = array('');
					}

					// loop through the label=>value pairs and add a separate input field for each element
					$intOptionCount = count($arrOptions);
					foreach($arrOptions as $intCount => $option){
						@list($label, $value) = explode(':', $option);
						$value = isset($value) ? $value : $label;
						$arrFieldHTML[] = array(
							'data' => '<input type="%1$s" name="%2$s[]" value="' . $value . '"%5$s id="%4$s' . ((($intCount > 0) && ($intOptionCount > 1)) ? $intCount : '') . '"%6$s />',
							'label' => $label
						);
					}
				break;
				default:
					// default - probably text, email, number etc
					$arrFieldHTML[] = '<input type="%1$s" name="%2$s" value="%3$s"%5$s id="%4$s"%6$s />';
				break;
			}

			// loop through each input and add the HTML to the output string
			$intFieldCount = count($arrFieldHTML);
			$strFieldBlock = '';
			foreach($arrFieldHTML as $intCount => $mixInput){
				if(is_array($mixInput)){
					$strInput = $mixInput['data'];
					$strLabel = $mixInput['label'];
				}else{
					$strInput = $mixInput;
					$strLabel = $arrRule['label'];
				}

				// prep the field
				$arrRule['field'] = form_prep(str_replace(' ', '', $arrRule['field']));

				// set up the input field, inserting the actual values into the template field input
				$strInput = sprintf(
					$strInput,																				// the field string
					$strType,																				// (%1$s) the input type (ie; text, password etc)
					$arrRule['field'],																		// (%2$s) the field name
					'{' . $arrRule['field'] . '}',															// (%3$s) the field value (set to {FIELDNAME})
					$strIDAppend . $arrRule['field'],														// (%4$s) the field ID
					((count($arrAttributes) > 0) ? ' ' . implode(' ', $arrAttributes) : ''),				// (%5$s) the field attributes
					((count($arrClassNames) > 0) ? ' class="' . implode(' ', $arrClassNames) . '"' : '')	// (%6$s) the field class(es)
				);

				// add the field to the field block
				if($intFieldCount > 1){
					// the field contains more than one element (ie; checkbox/radio) - add an opening label tag
					$strFieldBlock .= '<label for="' . $strIDAppend . $arrRule['field'] . (($intCount > 0) ? $intCount : '') . '" class="' . $strIDAppend . 'surround">' . $strLabel;
				}
				$strFieldBlock .= $strInput;
				if($intFieldCount > 1){
					// the field contains more than one element (ie; checkbox/radio) - close the label tag
					$strFieldBlock .= '</label>';
				}
			}

			/*if(($arrRule['type'] == 'file') && isset($arrRule['accept']) && preg_match('/^' . $mimePattern . '(,' . $mimePattern . ')*$/', $arrRule['accept'])){
				// the field is a file input and has an accept parameter
				$arrAttributes[] = 'accept="' . $arrRule['accept'] . '"';
			}*/
			if($strType == 'file'){
				// the input type is file - add any information for it (ie; max size, file types etc)
				if($this->EE->form_file_validation->getMaxUploadSize() > 0){
					$arrFileData[] = '<strong>Max size:</strong> ' . implode('', $this->EE->form_file_validation->getHighestDenominator($this->EE->form_file_validation->getMaxUploadSize()));
				}
				if(isset($arrRule['accept'])){
					// an accept parameter has been defined
					if(preg_match('/^' . $mimePattern . '(,' . $mimePattern . ')*$/', $arrRule['accept'])){
						// the accept parameter is a mime type (or list of)
						$arrExtensions = array();
						foreach(explode(',', $arrRule['accept']) as $strMimeType){
							$strMimeType = trim($strMimeType);
							if(false !== strpos($strMimeType, '/*')){
								// the second part of the mime type is a wildcard - use the first part as the extension
								$arrExtensions[] = reset(explode('/', $strMimeType));
							}else{
								// fetch a list of extensions for the mime type
								foreach($this->EE->form_file_validation->mimeToExtension($strMimeType) as $strMime){
									$arrExtensions[] = '.' . $strMime;
								}
							}
						}
					}else{
						// the accept parameter is an extension (or list of)
						$arrExtensions = explode(',', $arrRule['accept']);
					}
					if(!empty($arrExtensions)){
						$arrExtensions = array_unique(array_filter($arrExtensions));
						$arrFileData[] = '<strong>File types:</strong> ' . implode(', ', $arrExtensions);
					}
				}
				$strFieldBlock .= '<ul class="fileData"><li>' . implode('</li><li>', $arrFileData) . '</li></ul>';
			}

			// add the field(s) to the output string
			$strHTML .= sprintf(
				$formMarkup['surroundTag'],																	// the surrounding HTML to hold the input and it's label
				$surroundClass,																				// (%1$s) the class to give the surrounding HTML
				'<label for="' . $strIDAppend . $arrRule['field'] . '">' . $arrRule['label'] . '</label>',	// (%2$s) the field's label (if more than 1 field, this is for the first one)
				$strFieldBlock																				// (%3$s) the field element(s)
			);
		}

		
		// determine what captcha markup we need (this could change if the user has a Captcha plugin installed, rather than the default)
		// this currently only checks for JOA Captivate (http://bytedesign.nl/blog/captivate), but may work with others
		if($this->EE->extensions->active_hook('freeform_module_validate_end')){
			// a captcha extension is installed, requiring only a {captcha} tag, no input field
			$strCaptchaClass = 'recaptcha_response_field';
			$strCaptcha = '{captcha}';
		}else{
			// just use the normal captcha
			$strCaptchaClass = $strIDAppend . 'captcha';
			$strCaptcha = '<div class="' . $strIDAppend . 'captchaImage">{captcha}</div><input type="text" name="captcha" value="" maxlength="20" id="' . $strCaptchaClass . '" />';
		}

		// add the captcha markup
		$strHTML .= '{if captcha}';	// open the captcha tag
		$strHTML .= sprintf(
			$formMarkup['surroundTag'],																				// the surrounding HTML to hold the input and it's label
			$strSurroundFieldClass . ' ' . ((($intRuleCount+1) % 2 == 0) ? 'even' : 'odd'),							// (%1$s) the class to give the surrounding HTML
			'<label for="' . $strCaptchaClass . '">' . $this->EE->lang->line('captcha_input_label') . '</label>',	// (%2$s) the field's label
			$strCaptcha																								// (%3$s) the captcha element
		);
		$strHTML .= '{/if}';		// close the captcha tag


		// add the form closing tag, submit button and the error/success boxes
		$strHTML .= $formMarkup['closeTag'] . PHP_EOL . PHP_EOL .
		'<input type="submit" name="fSubmit" value="' . $this->EE->lang->line('form_submit_button_label') . '" class="' . $strIDAppend . 'Submit">' . PHP_EOL . PHP_EOL .
		'<div class="errorBox">{errors}</div>' . PHP_EOL .
		'<div class="successBox">{success}</div>' . PHP_EOL;

		return $strHTML;
	}

	/**
	 * Validates whether the image captcha, on a form,
	 * has been filled out correctly
	 *
	 * @return bool
	 */
	public function validateCaptcha(){
		if(!isset($_POST) || !is_array($_POST) || empty($_POST)){
			// no post set - ignore
			return true;
		}else{
			// connect with joa_captivate extension captcha validation
			if($this->EE->extensions->active_hook('freeform_module_validate_end')){
				// this validates the captcha field -
				// if it is correct, it stores it in the $_POST['captcha'] variable to be used
				// if it is incorrect then it sets $_POST['captcha'] to an empty value
				$this->EE->extensions->call('freeform_module_validate_end');
			}


			// now validate the captcha
			if(!$this->EE->input->post('captcha') || ($this->EE->input->post('captcha') == '')){
				// the captcha field wasn't set
				$this->EE->form_validation->_error_array['required_captcha'] = $this->EE->lang->line('captcha_required');

				return false;
			}else{
				// captcha entered - ensure that it is correct
				$strQuery = "SELECT
						COUNT(*) AS `count`
					FROM
						`exp_captcha`
					WHERE
						`word` = '" . $this->EE->db->escape_str($this->EE->input->post('captcha')) . "' AND
						`ip_address` = '" . $this->EE->db->escape_str($this->EE->input->ip_address()) . "' AND
						`date` > UNIX_TIMESTAMP()-7200";
				$result = $this->EE->db->query($strQuery);

				if($result->row('count') == 0){
					// the captcha text is incorrect
					$this->EE->form_validation->_error_array['required_captcha'] = $this->EE->lang->line('captcha_incorrect');

					return false;
				}else{
					return true;
				}
			}
		}
	}
}
// ------------------------------------------------------------------------
?>