<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 *  Form Class
 *
 * @package		ExpressionEngine
 * @category	Module
 * @author		Lee Langley
 * @link		http://greenimp.co.uk/
 * @copyright 	Copyright (c) 2011 Lee Langley
 * @license   	http://creativecommons.org/licenses/by-nc-nd/3.0/  Attribution-NonCommercial-NoDerivs
 *
 */
class Greenform_mcp{
	private $tablePrefix = 'green_form_';

	// validation rules for the form
	private $validationRules = array(
		array(
			'field' => 'ruleName',
			'label' => 'Rule Set Name',
			'rules' => 'required|alpha_dash|callback_formIsRuleNameUnique'
		),

		array(
			'field' => 'fieldName[]',
			'label' => 'Field Name',
			'rules' => 'required|alpha_dash|callback_formIsFieldNameUnique[fieldName]'
		),
		array(
			'field' => 'fieldLabel[]',
			'label' => 'Field Label',
			'rules' => 'required'
		),
		array(
			'field' => 'fieldType[]',
			'label' => 'Field Type',
			'rules' => 'callback_formIsTypeValid'
		),
		array(
			'field' => 'fieldValidType[]',
			'label' => 'Validation Type',
			'rules' => 'callback_formIsValidationTypeValid'
		),
		array(
			'field' => 'fieldLengthMin[]',
			'label' => 'Minimum Length',
			//'rules' => 'integer'			// commented out until bug fixed in codeigniter with array fieldnames
			'rules' => ''
		),
		array(
			'field' => 'fieldLengthMax[]',
			'label' => 'Maximum Length',
			//'rules' => 'integer'			// commented out until bug fixed in codeigniter with array fieldnames
			'rules' => ''
		),
		array(
			'field' => 'fieldLengthExact[]',
			'label' => 'Exact Length',
			//'rules' => 'integer'			// commented out until bug fixed in codeigniter with array fieldnames
			'rules' => ''
		)
	);

	private $arrFieldType = array(
		'text' => 'text',
		'textarea' => 'textarea',
		'password' => 'password',
		'select' => 'select',
		'checkbox' => 'checkbox',
		'radio' => 'radio',
		'file' => 'file',
	);

	// list of valid rule validation types - used for the form drop-down
	private $arrValidationType = array(
		'' => 'None',
		'valid_email' => 'Email',
		'valid_emails' => 'Email List (comma separated)',
		'valid_ip' => 'IP address',
		'alpha' => 'Alpha (a-z)',
		'alpha_numeric' => 'Alphanumeric (a-z, 0-9)',
		'alpha_dash' => 'Alphanumeric+ (a-z, 0-9, -, _)',
		'numeric' => 'Numeric',
		'integer' => 'Integer'
	);

	/**
	 * Constructor
	 *
	 * @access	public
	 * @return void
	 */
	public function Greenform_mcp(){
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();

		$this->EE->load->helper('form');
		$this->EE->load->library('table');

		// load model
		$this->EE->load->model('form_model', 'form');
		$this->EE->load->model('form_file_validation_model', 'form_file_validation');

		$this->base_url = $this->EE->config->item('site_url') . SYSDIR . '/' . BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=greenform';

		// define the default page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('greenform_module_name'));

		// add the page navigation
		$this->EE->cp->set_right_nav(array(
			$this->EE->lang->line('button_nav_entries') => $this->base_url,
			$this->EE->lang->line('button_nav_rule_sets') => $this->base_url . AMP . 'method=validation_settings'
		));

		// include the CP css file
		$this->EE->cp->add_to_head('<link rel="stylesheet" href="' . $this->EE->config->item('theme_folder_url') . 'third_party/greenform/css/cp.css">');

		// add a script tag to the header containing the URL to post to to collect the rule row html and the dataTable language file
		$this->EE->cp->add_to_head('<script type="text/javascript">
			var ruleRowURL = \'' . str_replace('&amp;', '&', $this->base_url . AMP) . 'method=validation_settings_get_row\',
				tableLangURL = \'' . str_replace('&amp;', '&', $this->base_url . AMP) . 'method=datatable_get_language\',
				mimetypeURL = \'' . str_replace('&amp;', '&', $this->base_url . AMP) . 'method=mime_types\';
		</script>');

		// include the required javascript files
		//$this->EE->cp->add_js_script(array('plugin' => 'dataTables'));
		//$this->EE->javascript->compile();
		// include the dataTables functionality
		$this->EE->cp->add_to_head('<script type="text/javascript" src="http://ajax.aspnetcdn.com/ajax/jquery.dataTables/1.9.1/jquery.dataTables.min.js"></script>');
		// include JQuery UI sortable
		$this->EE->cp->add_js_script(array('ui' => array('core', 'sortable')));
		// include the required JS scripts
		$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/greenform/javascript/KeyCheck.class.js"></script>');
		$this->EE->cp->add_to_head('<script type="text/javascript" src="' . $this->EE->config->item('theme_folder_url') . 'third_party/greenform/javascript/cp.js"></script>');
	}

	// --------------------------------------------------------------------

	/**
	 * The main page.
	 * This displays a list of submitted form data
	 *
	 * @return string
	 */
	public function index(){
		// define the variables
		$view_data = array(
			'entries' => $this->EE->form->getAllEntries(),	// list of form entries
			'uploadURL' => $this->EE->config->item('theme_folder_url') . 'third_party/greenform/uploads/',
			'formActionURL' => str_replace('&amp;', '&', $this->base_url . AMP . 'method=submission_update')
		);
		
		return $this->EE->load->view('index', $view_data, true);
	}

	/**
	 * This page updates form submissions
	 *
	 * @return void
	 */
	public function submission_update(){
		$bolResult = false;

		if(false !== ($mixFlags = $this->EE->input->post('flagRead'))){
			// flags are being updated
			if($this->EE->input->post('single')){
				// we are only updating a single entry
				// ensure that we aren't dealing with an array
				if(is_array($mixFlags)){
					$mixFlags = reset($mixFlags);
				}

				// only continue if the flag ID is numeric
				if(is_numeric($mixFlags) && ($mixFlags > 0)){
					$intStatus = ($this->EE->input->post('status') == 'read') ? 1 : 0;

					$this->EE->db->where(array('entry_id' => $mixFlags));
					$bolResult = $this->EE->db->update($this->tablePrefix . 'submissions', array('entry_read' => $intStatus));
				}
			}else{
				// ensure that we are dealing with an array
				if(!is_array($mixFlags)){
					$mixFlags = array($mixFlags);
				}

				foreach($mixFlags as $flag){

				}
			}
		}

		if($bolResult){
			// updated successfully
			if(AJAX_REQUEST){
				die(json_encode(array(
					'status' => 'success',
					'data' => $this->EE->lang->line('submission_update_success')
				)));
			}else{
				$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('submission_update_success'));
			}
		}else{
			// error updating
			if(AJAX_REQUEST){
				die(json_encode(array(
					'status' => 'error',
					'data' => $this->EE->lang->line('submission_update_error')
				)));
			}else{
				$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('submission_update_error'));
			}
		}

		// re-direct to the previous page
		$this->EE->functions->redirect($this->base_url);
		exit;
	}

	/**
	 * This page displays a list of validation rules
	 *
	 * @return string
	 */
	public function validation_settings(){
		// define the page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('page_title_validation_rules'));
		// set up the breadcrumbs
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('greenform_module_name'));

		$editURLBase = $this->base_url . AMP . 'method=validation_settings_modify' . AMP . 'greenFormAction=';

		// define the variables
		$view_data = array(
			'rules' => $this->EE->form->getAllRules(),
			'addRuleURL' => $editURLBase . 'add',
			'editRuleURL' => $editURLBase . 'edit' . AMP . 'id=',
			'deleteRuleURL' => $editURLBase . 'delete' . AMP . 'id='
		);
		return $this->EE->load->view('validation_settings', $view_data, true);
	}

	/**
	 * Displays the validation rule form
	 * for adding/updating rules
	 *
	 * @return string
	 */
	public function validation_settings_modify(){
		// detect which action we are taking (adding|editing a rule set)
		$action = isset($_GET['greenFormAction']) ? $_GET['greenFormAction'] : 'add';

		// define the page title
		$this->EE->cp->set_variable('cp_page_title', $this->EE->lang->line('page_title_validation_rules_' . strtolower($action)));
		// set up the breadcrumbs
		$this->EE->cp->set_breadcrumb($this->base_url, $this->EE->lang->line('greenform_module_name'));

		// define the rule set information
		$arrRuleSet = array(
			'id' => '',
			'name' => '',
			'ruleList' => array()
		);

		if($action == 'edit'){
			// we are editing, so collect the rule set information from the database
			$strQuery = "SELECT
						*
					FROM
						" . $this->EE->db->protect_identifiers($this->EE->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
					WHERE
						`rule_id` = " . $this->EE->db->escape($_GET['id']) . "
					LIMIT 1";
			$results = $this->EE->db->query($strQuery);
			if($results->num_rows > 0){
				$objResult = $results->row();

				$arrRuleSet = array(
					'id' => $objResult->rule_id,
					'name' => $objResult->rule_name,
					'useCaptcha' => !!$objResult->rule_use_captcha,
					'ruleList' => json_decode($objResult->rule_fields, true)
				);
			}
		}elseif($action == 'delete'){
			// we are deleting

			if(isset($_GET['id']) && is_numeric($_GET['id'])){
				$strQuery = "DELETE FROM
							" . $this->EE->db->protect_identifiers($this->EE->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
						WHERE
							`rule_id` = " . $this->EE->db->escape($_GET['id']) . "
						LIMIT 1";
				$bolSuccess = $this->EE->db->query($strQuery);
			}else{
				// no ID defined
				$bolSuccess = false;
			}

			// notify the user of the result
			if(AJAX_REQUEST){
				// the request was via ajax so return the result as a JSON string
				if($bolSuccess){
					// success
					die(json_encode(array(
						'status' => 'success',
						'data' => $this->EE->lang->line('rule_set_deleted')
					)));
				}else{
					// error
					die(json_encode(array(
						'status' => 'error',
						'data' => $this->EE->lang->line('rule_set_delete_failed')
					)));
				}
			}else{
				// return the result
				if($bolSuccess){
					// success
					$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('rule_set_deleted'));
				}else{
					// error
					$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('rule_set_delete_failed'));
				}
				// reload the page
				$this->EE->functions->redirect($this->base_url . AMP . 'method=validation_settings');
			}

			exit;
		}

		// get a list of mime types for the typical extensions
		$arrExtensions = array(
			'image' => array('jpeg', 'gif', 'png'),
			'video' => array('avi', 'mov', 'mpg'),
			'audio' => array('mp3', 'wav')
		);
		foreach($arrExtensions as $type => $arrExtensions){
			$arrMimes = array();
			foreach($arrExtensions as $ext){
				$arrMimes = array_merge($arrMimes, $this->EE->form_file_validation->extensionToMime($ext));
			}
			$arrMimes = array_filter($arrMimes);

			if(!empty($arrMimes)){
				$arrTypicalFiletypes[$type] = array(
					'ext' => '.' . implode(', .', $arrExtensions),
					'mime' => implode(',', $arrMimes)
				);
			}
		}

		// define the variables
		$view_data = array(
			'action' => $action,
			'ruleListURL' => str_replace('&amp;', '&', $this->base_url . AMP . 'method=validation_settings'),
			'formActionURL' => str_replace('&amp;', '&', $this->base_url . AMP . 'method=validation_settings_modify' . AMP . 'greenFormAction=add'),
			'arrValidationType' => $this->arrValidationType,
			'arrFieldType' => $this->arrFieldType,
			'ruleSet' => $arrRuleSet,
			'acceptTypical' => $arrTypicalFiletypes
		);

		$this->validateForm($action);

		return $this->EE->load->view('validation_settings_modify', $view_data, true);
	}

	/**
	 * Handles AJAX mimetype/file extension requests
	 *
	 * @return void
	 */
	public function mime_types(){
		$requestType = $this->EE->input->get_post('type');
		$string = $this->EE->input->get_post('value');
		$bolIsPartial = !!$this->EE->input->get_post('partial');

		$intLimit = $this->EE->input->get_post('limit');
		$intLimit = (is_numeric($intLimit) && ($intLimit > 0)) ? $intLimit : false;

		$arrReturn = array();

		if((false !== $string) && ($string != '')){
			if($requestType == 'mime'){
				// the user is requesting a mime type, from an extension
				$string = ltrim($string, '.');
				if(count($arrMimes = $this->EE->form_file_validation->extensionToMime($string)) > 0){
					$arrReturn[] = array(
						'ext' => $string,
						'mime' => $arrMimes
					);
				}elseif($bolIsPartial && strlen($string) > 1){
					// no matches found, look for partial matches
					foreach($this->EE->form_file_validation->getMimeTypes() as $ext => $mime){
						if(strstr($ext, $string)){
							// the extension is a partial match - add it to the list
							$arrReturn[] = array(
								'ext' => $ext,
								'mime' => $mime
							);

							if((false !== $intLimit) && (count($arrReturn) == $intLimit)){
								// we have reached the limit - end the search
								break;
							}
						}
					}
				}
			}elseif($requestType == 'ext'){
				// the user is requesting an extension from a mime type
				if(count($arrExt = $this->EE->form_file_validation->mimeToExtension($string)) > 0){
					// extensions were found for the mime type
					$arrReturn[] = array(
						'ext' => reset($arrExt),
						'mime' => array($string)
					);
				}
			}
		}

		die(json_encode($bolIsPartial ? $arrReturn : $arrReturn[0]));
	}

	/**
	 * Used for AJAX requests, this outputs a form row
	 * for the validation rule form
	 *
	 * @return void
	 */
	public function validation_settings_get_row(){
		$arrFieldType = $this->arrFieldType;
		$arrValidationType = $this->arrValidationType;
		require_once(PATH_THIRD . 'greenform/form_row.php');

		$this->EE->table->add_row($arrRow);

		die(preg_replace('@.*?(<tr>((?!</tr>).*?)</tr>).*@s', '$1', $this->EE->table->generate()));
	}

	/**
	 * Used for dataTable settings.
	 * Outputs the language settings for
	 * dataTable as a JSON string
	 *
	 * @return void
	 */
	public function datatable_get_language(){
		// determine the Javascript language lines
		$arrLanguage = $this->EE->lang->language;	// list of all language lines
		$startKey = 'greenform_module_name';		// the key to start reading from
		$bolRun = false;							// flag whether we have started the retrieval or not
		$arrDTLanguage = array();					// list holding the Javascript language line
		// loop through and find each line
		foreach($arrLanguage as $key => $value){
			if($key == $startKey){
				$bolRun = true;
			}elseif($bolRun && ($key == '')){
				break;
			}elseif($bolRun && (0 === strpos($key, 'dt_'))){
				$arrDTLanguage[substr($key, 3)] = $value;
			}
		}

		die(json_encode($arrDTLanguage));
	}

	/**
	 * Checks if the rule form has been submitted and carries out
	 * any necessary validation.
	 *
	 * If the query was submitted via AJAX the script will die with
	 * a JSON response (to stop any page headers/html being included).
	 *
	 * Otherwise the function will add any necessary error/success messages
	 * to the notification handler.
	 *
	 * @param $action
	 * @return void
	 */
	private function validateForm($action){
		// check if the form has been submitted
		if(false !== $this->EE->input->post('ruleSubmit')){
			// the form has been submitted - let's validate it

			// load up the form validation library
			$this->EE->load->library('form_validation');

			// if we are editing or have an entry ID defined, we need to add validation for it
			if(($action == 'edit') || isset($_POST['id'])){
				$action = 'edit';	// force mode to edit

				$this->validationRules[] = array(
					'field' => 'id',
					'label' => 'Rule Set ID',
					'rules' => 'required|integer'
				);
			}

			$this->EE->form_validation->set_rules($this->validationRules);

			if($this->EE->form_validation->run()){
				// no errors were found, the form was successfully submitted

				// remove any post variables that have been marked for deletion
				if(isset($_POST['fieldDelete']) && (count($_POST['fieldDelete']) > 0)){
					foreach($_POST['fieldDelete'] as $number){
						unset($_POST['fieldName'][$number]);
						unset($_POST['fieldLabel'][$number]);
						unset($_POST['fieldValue'][$number]);
						unset($_POST['fieldType'][$number]);
						unset($_POST['fieldAccept'][$number]);
						unset($_POST['fieldRequired'][$number]);
						unset($_POST['fieldValidType'][$number]);
						unset($_POST['fieldLengthMin'][$number]);
						unset($_POST['fieldLengthMax'][$number]);
						unset($_POST['fieldLengthExact'][$number]);
						unset($_POST['fieldOther'][$number]);

						if(isset($_POST['fieldRecipient']) && ($_POST['fieldRecipient'] == $number)){
							unset($_POST['fieldRecipient']);
						}
					}
				}

				// loop through and build up the array of rules
				$arrRulesJSON = array();
				foreach($this->EE->input->post('fieldName') as $number => $name){
					$arrRuleList = array();

					if(isset($_POST['fieldRequired'][$number])){
						// add the 'required' rule
						$arrRuleList[] = 'required';
					}
					if(isset($_POST['fieldValidType'][$number]) && ($_POST['fieldValidType'][$number] != '')){
						// add the 'type' rule
						$arrRuleList[] = $_POST['fieldValidType'][$number];
					}


					if(isset($_POST['fieldType'][$number]) && ($_POST['fieldType'][$number] == 'file')){
						// an accept parameter was defined
						$_POST['fieldAccept'][$number] = explode(',', isset($_POST['fieldAccept'][$number]) ? $_POST['fieldAccept'][$number] : '');
						// loop through each type and check if it begins with 'images|audios|videos' and removes the 's' from the end as it's invalid
						foreach($_POST['fieldAccept'][$number] as $k => $strType){
							$strMimePattern = '/^(image|audio|video)s(\/.*)$/';
							if(preg_match($strMimePattern, $strType)){
								$_POST['fieldAccept'][$number][$k] = preg_replace($strMimePattern, '$1$2', $strType);
							}
						}
						$_POST['fieldAccept'][$number] = implode(',', $_POST['fieldAccept'][$number]);
					}else{
						// no accept parameter was defined
						$_POST['fieldAccept'][$number] == '';
					}


					$bolLengthSet = false;
					if(isset($_POST['fieldLengthMin'][$number]) && ($_POST['fieldLengthMin'][$number] != '')){
						// add the 'min_length' rule
						$arrRuleList[] = 'min_length[' . $_POST['fieldLengthMin'][$number] . ']';
						// mark the length as set to stop an exact length also being set
						$bolLengthSet = true;
					}
					if(isset($_POST['fieldLengthMax'][$number]) && ($_POST['fieldLengthMax'][$number] != '')){
						// add the 'max_length' rule
						$arrRuleList[] = 'max_length[' . $_POST['fieldLengthMax'][$number] . ']';
						// mark the length as set to stop an exact length also being set
						$bolLengthSet = true;
					}
					if(!$bolLengthSet && isset($_POST['fieldLengthExact'][$number]) && ($_POST['fieldLengthExact'][$number] != '')){
						$arrRuleList[] = 'exact_length[' . $_POST['fieldLengthExact'][$number] . ']';
					}

					if(isset($_POST['fieldOther'][$number]) && ($_POST['fieldOther'][$number] != '')){
						// add any other user defined validation methods
						$arrRuleList[] = trim($_POST['fieldOther'][$number], '|');
					}


					// loop through the rules and ensure that we have no duplicates
					$arrRuleList = array_unique($arrRuleList);
					$strRules = '';
					foreach($arrRuleList as $strRule){
						if((false !== strpos($strRule, '[')) && ($strRules != '')){
							// this rule has options - check that it has not been defined elsewhere with other options
							if(!preg_match('/' . $strRule . '\[[^\]]*\]/', $strRules)){
								// rule not already defined - add this one
								$strRules .= $strRule . '|';
							}
						}else{
							$strRules .= $strRule . '|';
						}
					}
					$strRules = rtrim($strRules, '|');

					// build up the rule object
					$arrRulesJSON[] = array(
						'field' => $name,
						'label' => $_POST['fieldLabel'][$number],
						'rules' => $strRules,
						'recipientEmail' => isset($_POST['fieldRecipient']) && ($_POST['fieldRecipient'] == $number),
						'type' => isset($_POST['fieldType'][$number]) ? $_POST['fieldType'][$number] : 'text',
						'value' => isset($_POST['fieldValue'][$number]) ? trim($_POST['fieldValue'][$number], ',') : ''
					);

					if($_POST['fieldAccept'][$number] != ''){
						$arrRulesJSON[count($arrRulesJSON)-1]['accept'] = $_POST['fieldAccept'][$number];
					}
				}
				$arrRulesJSON = json_encode($arrRulesJSON);

				// check if we can use the captcha on this form or not
				$intUseCaptcha = (isset($_POST['ruleCaptcha']) && (($_POST['ruleCaptcha'] == 'yes') || ($_POST['ruleCaptcha'] == true))) ? 1 : 0;

				// update the database
				if($action == 'edit'){
					// we are updating an existing row
					$strQuery = "UPDATE
								" . $this->EE->db->protect_identifiers($this->EE->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
							SET
								`rule_name` = " . $this->EE->db->escape($_POST['ruleName']) . ",
								`rule_use_captcha` = " . $intUseCaptcha . ",
								`rule_fields` = " . $this->EE->db->escape($arrRulesJSON) . ",
								`rule_date_modified` = '" . date('Y-m-d H:i:s') . "'
							WHERE
								`rule_id` = " . $this->EE->db->escape($_POST['id']) . "
							LIMIT 1";
					$bolSuccess = $this->EE->db->query($strQuery);
				}else{
					// we are adding a new row
					$strQuery = "INSERT INTO
								" . $this->EE->db->protect_identifiers($this->EE->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
							(
								`rule_name`,
								`rule_use_captcha`,
								`rule_fields`,
								`rule_date_created`,
								`rule_date_modified`
							) VALUES (
								" . $this->EE->db->escape($_POST['ruleName']) . ",
								" . $intUseCaptcha . ",
								" . $this->EE->db->escape($arrRulesJSON) . ",
								'" . date('Y-m-d H:i:s') . "',
								'" . date('Y-m-d H:i:s') . "'
							)";
					$bolSuccess = $this->EE->db->query($strQuery);
				}

				$setID = ($action == 'edit') ? $_POST['id'] : $this->EE->db->insert_id();


				// notify the user of the result
				if(AJAX_REQUEST){
					// the request was via ajax so return the result as a JSON string
					if($bolSuccess){
						// success
						die(json_encode(array(
							'status' => 'success',
							'data' => array(
								'message' => $this->EE->lang->line('rule_set_updated'),
								'id' => $setID
							)
						)));
					}else{
						// error
						die(json_encode(array(
							'status' => 'error',
							'data' => array(
								array('fieldName' => '', 'error' => $this->EE->lang->line('rule_set_update_failed'))
							)
						)));
					}
				}else{
					// return the result
					if($bolSuccess){
						// success
						$this->EE->session->set_flashdata('message_success', $this->EE->lang->line('rule_set_updated'));

						// reload the page
						$this->EE->functions->redirect($this->base_url . AMP . 'method=validation_settings_modify' . AMP . 'greenFormAction=edit' . AMP . 'id=' . $setID);
						exit;
					}else{
						// error
						$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('rule_set_update_failed'));

						// reload the page
						$this->EE->functions->redirect($this->base_url . AMP . 'method=validation_settings_modify' . AMP . 'greenFormAction=add');
						exit;
					}
				}
			}else{
				// errors were found in the form
				$arrErrorList = array();

				foreach($this->EE->form_validation->_error_array as $strKey => $strError){
					$arrErrorList[] = array(
						'fieldName' => $strKey,
						'error' => $strError
					);
				}

				if(AJAX_REQUEST){
					// the request was via ajax so return the success as a JSON object
					die(json_encode(array(
						'status' => 'error',
						'data' => $arrErrorList
					)));
				}else{
					// add the error messages
					$this->EE->session->set_flashdata('message_failure', $this->EE->lang->line('rule_set_update_check_failed'));

					// loop through the errors and output a message for each
					foreach($arrErrorList as $error){
						$this->EE->session->set_flashdata('message_failure', $error['error']);
					}
				}
			}
		}
	}

	/**
	 * Used for form validation.
	 * Checks whether the given rule set name is unique,
	 * within the database
	 *
	 * @param string $strVal
	 * @return bool
	 */
	public function formIsRuleNameUnique($strVal){
		$intID = $this->EE->input->post('id');

		$strQuery = "SELECT
					*
				FROM
					" . $this->EE->db->protect_identifiers($this->EE->db->dbprefix($this->tablePrefix . 'validation_rules')) . "
				WHERE
					`rule_name` = '" . $this->EE->db->escape_str($strVal) . "'
					" . ((false !== $intID) ? "AND `rule_id` != '" . $this->EE->db->escape_str($intID) . "'" : '') . "
				LIMIT 1";
		$results = $this->EE->db->query($strQuery);
		if($results->num_rows() > 0){
			$this->EE->form_validation->set_message('formIsRuleNameUnique', $this->EE->lang->line('rule_set_name_not_unique'));
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Used for form validation.
	 * Checks if the given form value is unique,
	 * within it's name
	 *
	 * @param string $strVal
	 * @param string $strField
	 * @return bool
	 */
	public function formIsFieldNameUnique($strVal, $strField){
		// because we have no way of knowing which field the given value relates to
		// we need to loop through every single field and find any matches.
		// if we have 1 match, we assume that it is the given value.
		// we only return false if more thanh one match is found.

		$intFoundCount = 0;

		// only run validation if the field is set and contains more than one entry.
		// If only one, we assume that it is the given value and ignore it
		if(isset($_POST[$strField]) && is_array($_POST[$strField]) && (count($_POST[$strField]) > 1)){
			foreach($_POST[$strField] as $mixVal){
				if($strVal === $mixVal){
					// the value is the same - increment the count
					$intFoundCount++;

					if($intFoundCount > 1){
						// count is greater than 1, so there is another field with the same name - break the loop
						break;
					}
				}
			}
		}

		if($intFoundCount > 1){
			// more than one field with this name was found, so it is not unique
			$this->EE->form_validation->set_message('formIsFieldNameUnique', $this->EE->lang->line('rule_set_field_not_unique'));

			return false;
		}else{
			return true;
		}
	}

	/**
	 * Used for form validation.
	 * Checks if the given field type
	 * (ie; text, checkbox, textarea etc)
	 * is valid
	 *
	 * @param string $strVal
	 * @return bool
	 */
	public function formIsTypeValid($strVal){
		if(in_array($strVal, array_keys($this->arrFieldType))){
			return true;
		}else{
			$this->EE->form_validation->set_message('formIsTypeValid', $this->EE->lang->line('rule_set_field_type_invalid'));

			return false;
		}
	}

	/**
	 * Used for form validation.
	 * Checks if the given rule type is valid
	 *
	 * @param string $strVal
	 * @return bool
	 */
	public function formIsValidationTypeValid($strVal){
		if(in_array($strVal, array_keys($this->arrValidationType))){
			return true;
		}else{
			$this->EE->form_validation->set_message('formIsTypeValid', $this->EE->lang->line('rule_set_field_type_invalid'));

			return false;
		}
	}
}
// END CLASS

/* End of file mcp.form.php */
/* Location: ./system/expressionengine/third_party/modules/greenform/mcp.greenform.php */