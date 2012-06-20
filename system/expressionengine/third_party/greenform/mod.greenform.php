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
class Greenform{
	private $debugIPs = array();					// list of IP addresses which count as debugging
	private $isDebug = false;						// flag whether we are debugging or not

	private $requestType = 'http';					// the request type (http||ajax)

	private $settings = array(
		'site_url' => '',							// the root url of the site
		'post_url' => '',							// the url to submit the form to
		'return_url' => '',							// the url to re-direct to after successful submission

		'allowFiles' => false,						// whether to allow files/attachments
		'successMessage' => '',						// message to displayed when form is submitted (if no re-direct)
		'mailType' => 'text',						// email type (text|html)

		'formID' => '',								// the CSS ID to assign the form
		'classID' => '',							// the CSS class (or list of) to assign the form
		'errorClass' => 'error',					// the CSS class to assign to inputs containing errors
		'errorMsgClass' => 'greenFormErrors',		// the CSS class to assign to the error message container
		'errorListClass' => 'greenFormErrorList',	// the CSS class to assign to the actual ul list of error
		'successMsgClass' => 'greenFormSuccess',	// the CSS class to assign to the success message box, if using jquery form submission

		'jqueryValidation' => false,				// flag whether we use jquery to validate the form
		'jquerySubmit' => false,					// flag whether we use jquery to submit the form

		'validationType' => '',						// the form validation set
		'validationRules',							// list of the form validation rules

		'buildForm' => false,						// flag whether to build the entire form or not
		'buildType' => 'ul',						// define how to lay out the form (if buildForm == true)
		'HTMLVersion' => 'html5'					// the version of HTMl to build forms with (currently supports HTMl5 or 'other'
	);

	private $validation_rules = array();

	/**
	 * Constructor
	 *
	 */
	function Greenform(){
		if(in_array($_SERVER['REMOTE_ADDR'], $this->debugIPs)){
			$this->isDebug = true;
		}

		$this->requestType = AJAX_REQUEST ? 'ajax' : 'http';

		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		
		// load CI libraries / helpers etc
		$this->EE->load->library('form_validation');
		$this->EE->load->helper('url');
		$this->EE->load->model('form_model');
		$this->EE->load->model('form_file_validation_model', 'form_file_validation');

		// load the language file
		$this->EE->lang->loadfile('greenform');
		
		// assign the default setting values
		$this->settings['site_url'] = $this->EE->functions->fetch_site_index(1, 0);
		$this->settings['post_url'] = $_SERVER['REQUEST_URI'];
	}
	
	/**
	 * Create form - main function
	 *
	 * @return string|void
	 */
	function create_form(){
		// replacement variables for the template
		$variable_row = array(
			'site_url' => $this->settings['site_url'],		// the website root URL
			'errors' => '',									// error message output
			'success' => ''									// message to display on successful submit (if no re-direct is defined)
		);

		// fetch the user defined parameters
		$strPostURL = $this->EE->TMPL->fetch_param('post');	  		  											// the url to submit the form to
		if($strPostURL != ''){
			$this->settings['post_url'] = $this->EE->TMPL->parse_variables_row($strPostURL, $variable_row);
		}
		$return_url = $this->EE->TMPL->fetch_param('return');	    											// the url to return to after form submission
		if($return_url != ''){
			$this->settings['return_url'] = $this->EE->TMPL->parse_variables_row($return_url, $variable_row);
		}
		$recipients = $this->EE->TMPL->fetch_param('recipients');											    // a pipe separated list of recipients, if the form needs to be emailed
		$userRecipients = $this->EE->TMPL->fetch_param('user_recipients', 'no') == 'yes';					    // boolean - whether the form needs to be emailed to the customer
		$this->settings['email_from'] = $this->EE->TMPL->fetch_param('email_from', 'no-reply@' . $_SERVER['SERVER_NAME']);	// the from address for emails
		$subject = $this->EE->TMPL->fetch_param('subject', 'Form Message');										// the subject of the email, if needed
		$this->settings['mailType'] = $this->EE->TMPL->fetch_param('mail_type', 'text');						// the email type (html|text)

		// form CSS attributes
		$this->settings['formID'] = trim($this->EE->TMPL->fetch_param('form_id'));								// the ID to give the form element
		$this->settings['formClass'] = trim($this->EE->TMPL->fetch_param('form_class'));						// pipe separated list of class names to apply to the form element
		$errorClass = $this->EE->TMPL->fetch_param('error_class');												// the class name to give any input fields that contain errors
		if($errorClass != ''){
			$this->settings['errorClass'] = $errorClass;
		}

		$this->settings['validationType'] = $this->EE->TMPL->fetch_param('validation_type');					// (optional) the type of the form validation (ie; contact, request)

		// check whether the form needs to process files/attachments or not
		foreach($this->getFormValidation() as $rule){
			if($rule['type'] == 'file'){
				$this->settings['allowFiles'] = true;
				break;
			}
		}

		// check if we need to build the form
		$this->settings['buildForm'] = $this->EE->TMPL->fetch_param('build_form', 'no');						// boolean - whether we need to build up the form HTML or not
		$this->settings['buildForm'] = ($this->settings['buildForm'] == 'yes') || ($this->settings['buildForm'] == 'true');
		$this->settings['buildType'] = $this->EE->TMPL->fetch_param('build_type', 'ul');						// defines the markup used for laying out the form (if building it)
		$this->settings['HTMLVersion'] = strtolower($this->EE->TMPL->fetch_param('html_version', 'html5'));		// define which type of HTMl to build the form with

		// check if we need to use jquery for the form
		$useJQuery = $this->EE->TMPL->fetch_param('use_jquery', 'no');
		if(($useJQuery == 'yes') || ($useJQuery == 'true')){
			// we have a global 'use jquery' flag of 'true' which overwrites individual settings for validation or submit
			$this->settings['jqueryValidation'] = true;
			$this->settings['jquerySubmit'] = true;
		}else{
			$this->settings['jqueryValidation'] = $this->EE->TMPL->fetch_param('jquery_validate', 'no');			// flag to specify whether to add JQuery form validation or not
			$this->settings['jqueryValidation'] = ($this->settings['jqueryValidation'] == 'yes') || ($this->settings['jqueryValidation'] == 'true');
			$this->settings['jquerySubmit'] = $this->EE->TMPL->fetch_param('jquery_submit', 'no');					// flag to specify whether to use JQuery for the form submission or not
			$this->settings['jquerySubmit'] = ($this->settings['jquerySubmit'] == 'yes') || ($this->settings['jquerySubmit'] == 'true');
		}

		$this->settings['emailTemplate'] = trim($this->EE->TMPL->fetch_param('email_template'));				// the template to use for the email

		// check if we need to include a captcha
		$bolUsersCaptcha = ($this->EE->config->item('captcha_require_members') == 'y') || (($this->EE->config->item('captcha_require_members') == 'n') && ($this->EE->session->userdata('member_id') == 0));
		$bolFormCaptcha = $this->EE->form_model->useCaptcha($this->settings['validationType'], $this->settings['buildForm']);
		if($bolUsersCaptcha && $bolFormCaptcha){
			// captcha is applicable - add the captcha to the list of tags to insert
			$variable_row['captcha'] = $this->EE->functions->create_captcha();
			$this->settings['needCaptcha'] = true;
		}else{
			// no captcha
			$variable_row['captcha'] = '';
			$this->settings['needCaptcha'] = false;
		}

		// check the recipients
		if($recipients != ''){
			// a form recipient was set, let's check that it is valid
			// split the recipient by pipes in case more than one is specified
			$recipients = explode('|', str_replace(',', '|', $recipients));
			// loop through each recipient and ensure that it is valid
			foreach($recipients as $k => $recipient){
				if(!preg_match('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}$/i', trim($recipient))){
					// the recipient is invalid - unset it
					unset($recipients[$k]);
				}
			}
		}


		// set up the form validation depending on the type
		$arrValidationRules = $this->getFormValidation(null, false);	// rules without file inputs (these are validated separately)
		$this->EE->form_validation->set_rules($arrValidationRules);


		// let's check if we have any file inputs and, if so, has a request been made to upload them via Ajax
		if(($this->settings['jquerySubmit'] && ($this->EE->input->post('fileUploadAjax') == true)) || ((false !== $this->EE->input->get('upload_id')) && (false !== $this->EE->input->get('stamp')))){
			// request made - upload any files to be verified later
			$this->EE->form_file_validation->handleAjaxUpload($this->getFormValidation());
		}

		

		if($this->settings['buildForm']){
			// we need to build up the form html
			$this->EE->TMPL->tagdata = $this->EE->form_model->buildInnerFormHTML($this->getFormValidation(), $this->settings['buildType'], $this->settings['HTMLVersion']);
		}


		// prep any conditional template statements
		$arrConditionals = array(
			'captcha' => $this->settings['needCaptcha']
		);
		$this->EE->TMPL->tagdata = $this->EE->functions->prep_conditionals($this->EE->TMPL->tagdata, $arrConditionals);


		if(count($this->getFormValidation()) == 0){
			// no rules were found for the rule set specified
			return sprintf($this->EE->lang->line('module_no_rules_defined'), $this->settings['validationType']);
		}

		
		// validation/output the form
		if(
			((count($arrValidationRules) > 0) && !$this->EE->form_validation->run()) ||			// general form validation
			($this->settings['needCaptcha'] && !$this->EE->form_model->validateCaptcha()) ||	// captcha validation
			!$this->EE->form_file_validation->validate($this->getFormValidation())				// file validation (this needs to be run last!)
		){
			// either the form wasn't posted or it contains errors

			// loop through the validation rules for the input names and
			// add them to the variable list, so we can re-populate the form
			foreach($this->validation_rules as $val){
				$variable_row[$val['field']] = (false != $this->EE->input->get_post($val['field'])) ? $this->EE->input->get_post($val['field']) : (($this->settings['HTMLVersion'] != 'html5') && isset($val['value']) ? $this->EE->form_model->form_prep($val['value']) : '');
				$variable_row[$val['field'] . 'Error'] = '';
			}

			if(($this->settings['jqueryValidation'] || $this->settings['errorMsgClass']) && preg_match_all('/< *([^\/][^<]+[^\/])> *{errors}/', $this->EE->TMPL->tagdata, $matches, PREG_OFFSET_CAPTURE)){
				// we are doing JQuery validation/submit so loop through all of the found 'error'
				// locations and try adding a class to the box, so we can hook on to it later
				foreach($matches[0] as $match){
					$intLength = strlen($match[0]);	// length of original string

					if(false !== ($intPos = strpos($match[0], 'class='))){
						// the input field already has a class attribute - add to it
						$match[0] = substr_replace($match[0], $this->settings['errorMsgClass'] . ' ', $intPos+7, 0);
					}else{
						// the input field has no class attribute - add one
						$intPos = $intLength - 1;
						$match[0] = substr_replace($match[0], ' class="' . $this->settings['errorMsgClass'] . '"', $intPos, 0);
					}
					// insert the updated input field into the page HTML
					$this->EE->TMPL->tagdata = substr_replace($this->EE->TMPL->tagdata, $match[0], $match[1], $intLength);
				}
			}

			if($this->settings['jquerySubmit'] && preg_match_all('/< *([^\/][^<]+[^\/])> *{success}/', $this->EE->TMPL->tagdata, $matches, PREG_OFFSET_CAPTURE)){
				// we are doing JQuery form submission so loop through all of the found 'success'
				// locations and try adding a class to the box, so we can hook on to it later
				foreach($matches[0] as $match){
					$intLength = strlen($match[0]);	// length of original string

					if(false !== ($intPos = strpos($match[0], 'class='))){
						// the input field already has a class attribute - add to it
						$match[0] = substr_replace($match[0], $this->settings['successMsgClass'] . ' ', $intPos+7, 0);
					}else{
						// the input field has no class attribute - add one
						$intPos = $intLength - 1;
						$match[0] = substr_replace($match[0], ' class="' . $this->settings['successMsgClass'] . '"', $intPos, 0);
					}
					// insert the updated input field into the page HTML
					$this->EE->TMPL->tagdata = substr_replace($this->EE->TMPL->tagdata, $match[0], $match[1], $intLength);
				}
			}

			// check for any errors
			if(isset($this->EE->form_validation->_error_array) && (count($this->EE->form_validation->_error_array) > 0)){
				// add all of the errors to the 'error' variable
				$variable_row['errors'] = '<ul>' . validation_errors('<li>', '</li>') . '</ul>';

				// loop through the errors and add a separate error message for each,
				// so we can display an error next to each input if we wish
				foreach($this->EE->form_validation->_error_array as $strKey => $strError){
					// add the error message
					$variable_row[$strKey . 'Error'] = $strError;

					// add an error class to the input
					if(preg_match('/< ?(input|textarea|select) [^>]*name=("|\')' . $strKey . '\2[^>]*>/i', $this->EE->TMPL->tagdata, $matches, PREG_OFFSET_CAPTURE)){
						// the input field was found

						$intLength = strlen($matches[0][0]);	// length of original string

						if(false !== ($intPos = strpos($matches[0][0], 'class='))){
							// the input field already has a class attribute - add to it
							$matches[0][0] = substr_replace($matches[0][0], $this->settings['errorClass'] . ' ', $intPos+7, 0);
						}else{
							// the input field has no class attribute - add one
							$intPos = strlen($matches[1][0]) + 1;
							$matches[0][0] = substr_replace($matches[0][0], ' class="' . $this->settings['errorClass'] . '"', $intPos, 0);
						}
						// insert the updated input field into the page HTML
						$this->EE->TMPL->tagdata = substr_replace($this->EE->TMPL->tagdata, $matches[0][0], $matches[0][1], $intLength);
					}
				}

				if($this->requestType == 'ajax'){
					// the request was made via ajax so the only output we need is the error list
					die(json_encode(array(
						'status' => 'error',
						'returnHTML' => $variable_row['errors']
					)));
				}else{
					$variable_row['errors'] = '<h2>' . $this->EE-> 	lang->line('form_submit_error_header') . '</h2>'.$variable_row['errors'];
				}
			}else{
				// no errors, set the message to blank
				$variable_row['errors'] = '';
			}

			
			// return the form HTML
			return $this->getFormHTML($this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $variable_row));
		}else{
			// the form has been successfully submitted

			// the email address of the form submitter
			$strRecipientEmailName = 'from';
			
			// build data array of posted values
			$files = (isset($_FILES) && (count($_FILES) > 0)) ? $_FILES : ((false !== $this->EE->input->get_post('CUSTOM_UPLOAD_FILES')) ? $this->EE->input->get_post('CUSTOM_UPLOAD_FILES') : array());
			$data = array();
			foreach($this->getFormValidation() as $field){
				if($field['type'] == 'file'){
					$value = (isset($files[$field['field']]['name']) ? $files[$field['field']]['name'] : '');
				}else{
					$value = $this->EE->input->post($field['field']);
				}

				$data[$field['field']] = array(
                    'field' => $field['field'],
                    'label' => $field['label'],
                    'value' => is_array($value) ? implode(', ', $value) : $value,
					'type' => $field['type']
                );

				if($field['recipientEmail']){
					$strRecipientEmailName = $field['field'];
				}
			}
			die(print_r($data));
			
			if(!$this->isDebug){
				// if recipients are set, we send out the form information as an email

				// send the email to the recipient (usually site admin)
				if(is_array($recipients) && (count($recipients) > 0)){
					$from = isset($data[$strRecipientEmailName]['value']) ? $data[$strRecipientEmailName]['value'] : $this->settings['email_from'];
					$this->sendForm($subject, $from, $recipients, $data, $this->EE->form_file_validation->getFormFiles());
				}

				// send a copy to the user who submitted the form
				if(isset($data[$strRecipientEmailName]['value'])){
					// if $userRecipients is true we need to send a copy of the email to the customer
					if($userRecipients == 'true'){
						$this->sendForm($subject, $this->settings['email_from'], $data[$strRecipientEmailName]['value'], $data, $this->EE->form_file_validation->getFormFiles());
					}
				}
				
				// build up the data correctly for sql insertion
				$data = array(
					'entry_url' => $this->EE->uri->uri_string(),
					'entry_field' => json_encode($data),
					'entry_files' => json_encode($this->EE->form_file_validation->getFormFiles()),
					'entry_ip' => $_SERVER['REMOTE_ADDR'],
					'entry_date' => date('Y-m-d H:i:s')
				);
				// insert data into the database
				$this->EE->form_model->insert($data);
			}

			if($this->requestType == 'ajax'){
				// the request was made via ajax so the only output we need is the success message
				die(json_encode(array(
						'status' => 'success',
						'returnHTML' => '<h1>' . $this->EE->lang->line('form_submit_success_header') . '</h1><p>' . $this->EE->lang->line('form_submit_success') . '</p>'
					)));
			}elseif($this->settings['return_url'] != ''){
				// redirect to thank you page
				redirect($this->settings['return_url']);
				return '';
			}else{
				// ensure that all module tags are reset to their default values,
				// so that the page doesn't display the tags
				$variable_row['errors'] = '';
				$variable_row['success'] = '<h1>' . $this->EE->lang->line('form_submit_success_header') . '</h1><p>' . $this->EE->lang->line('form_submit_success') . '</p>';

				foreach($this->validation_rules as $val){
					$variable_row[$val['field']] = '';
					$variable_row[$val['field'] . 'Error'] = '';
				}

				return $this->getFormHTML($this->EE->TMPL->parse_variables_row($this->EE->TMPL->tagdata, $variable_row));
			}
		}
	}


	/**
	 * Sends all of the form details in an email
	 *
	 * @param $subject
	 * @param $from
	 * @param $recipients
	 * @param array $data
	 * @param array $files
	 * @return bool
	 */
	function sendForm($subject, $from, $recipients, array $data, array $files = array()){
		// build the email parameters
		$bolIsHTML = (strtolower($this->settings['mailType']) == 'html');

		if(is_array($recipients)){
			// if the $recipients is an array of recipients we need to fetch the first one for the 'to' header
			$to = array_shift($recipients);
		}else{
			// $recipients is not an array so just assign it to the $to variable
			$to = $recipients;
		}

		// set up the email
		// load the required functionality
		$this->EE->load->library('email');
		$this->EE->load->helper('text');
		// initialise the email class
		$this->EE->email->initialize();

		$this->EE->email->debug = $this->isDebug;						// set debugging on or off
		$this->EE->email->validate = true;								// set to validate email addresses
		$this->EE->email->mailtype = $bolIsHTML ? 'html' : 'text';		// email type (text|html)

		$this->EE->email->from($this->settings['email_from']);			// from address
		$this->EE->email->to($to);										// to address
		$this->EE->email->reply_to($from);								// reply to address
		if(is_array($recipients) && (count($recipients) > 0)){
			$this->EE->email->cc($recipients);							// CC recipients
		}
		$this->EE->email->subject($subject);							// email subject


		// build the email contents
		$strBody = $this->getEmailHTML($to, $from, $data, $files);

		// set the message body
		$this->EE->email->message($strBody);

		// send the email and return the result
		return $this->EE->email->Send();
	}

	/**
	 * Builds up the form HTML tags and returns it,
	 * with a sprintf style '%s' for easy inserting
	 * of the form contents
	 *
	 * @param string $data
	 * @return string
	 */
	private function getFormHTML($data){
		$mixHTML = $this->EE->form_model->buildFormHTML($this->settings, $this->getFormValidation());
		if(is_array($mixHTML)){
			$strReturn = $mixHTML['openTag'] . $data . $mixHTML['closeTag'];
		}else{
			$strReturn = sprintf($mixHTML, $data);
		}

		return $strReturn;
	}

	/**
	 * Builds up the email template and returns it
	 *
	 * @param $strTo
	 * @param $strFrom
	 * @param array $arrData
	 * @param array $arrFiles
	 * @return string
	 */
	private function getEmailHTML($strTo, $strFrom, array $arrData, array $arrFiles){
		$bolIsHTML = (strtolower($this->settings['mailType']) == 'html');

		$strMessage = '';

		// loop through all of the post values and build the html for them
		$strMessage .= $bolIsHTML ? '<ul class="emailPostList">' : '';
		$strEncapsulate = $bolIsHTML ? '<li><strong>%s:</strong> %s</li>' : '%s: %s' . PHP_EOL;
		foreach($arrData as $field){
			$strPostName = ucwords(strtolower(str_replace('_', ' ', htmlentities(strip_tags(stripslashes($field['label']))))));
			$strPostVal = htmlentities(strip_tags(stripslashes($field['value'])));

			$strMessage .= sprintf($strEncapsulate, $strPostName, $bolIsHTML ? nl2br($strPostVal) : $strPostVal);
		}
		$strMessage .= $bolIsHTML ? '</ul>' : '';

		// check if any files exist
		if($this->settings['allowFiles'] && (count($arrFiles) > 0)){
			// files exist
			$strMessage .= ($bolIsHTML ? '<br />' : PHP_EOL) . $this->EE->lang->line('email_files_attached');

			// define the path to the file location
			$filePath = $this->EE->form_file_validation->getFilePath();
			// loop through and attach the files to the email
			foreach($arrFiles as $file){
				$this->EE->email->attach($filePath . $file);
			}
		}


		// check if an EE template has been specified for the email content
		if($this->settings['emailTemplate'] != ''){
			// define the template group and name
			list($strTGroup, $strTName) = explode('/', $this->settings['emailTemplate']);

			// load up the template a parse it
			$TMPL = new EE_Template();
			$TMPL->fetch_and_parse($strTGroup, $strTName);
			$strEmailTemplate = $TMPL->parse_globals($TMPL->final_template);

			// replace the email contents
			$arrReplacements = array(
				'email_body' => $strMessage,	// the body of the email

				'recipient_email' => $strTo,	// the recipient's email address
				'to_email' => $strTo,			// the recipient's email address

				'sender_email' => $strFrom,		// the sender's email address
				'from_email' => $strFrom		// the sender's email address
			);
			// if template has content, parse it and return, otherwise return the default information
			$strBody = ($strEmailTemplate != '') ? $TMPL->parse_variables_row($strEmailTemplate, $arrReplacements) : $strMessage;
		}else{
			// no email template defined - return the default information
			$strBody = $strMessage;
		}

		return $strBody;
	}

	/**
	 * Sets the list of validation rules for the current form
	 * and returns it.
	 * 
	 * @param string $validationType
	 * @param bool $includeFiles
	 * @return array
	 */
	private function getFormValidation($validationType = '', $includeFiles = true){
		if(!isset($this->validation_rules) || !is_array($this->validation_rules) || (count($this->validation_rules) == 0)){
			$this->validation_rules = $this->EE->form_model->getValidationRules(($validationType != '') ? $validationType : $this->settings['validationType']);
		}

		if(!$includeFiles){
			$arrRules = $this->validation_rules;
			foreach($arrRules as $k => $rule){
				if(($rule['type'] == 'file')){
					unset($arrRules[$k]);
				}
			}

			return $arrRules;
		}else{
			return $this->validation_rules;
		}
	}
}

/* End of file mod.form.php */
/* Location: ./system/expressionengine/third_party/greenform/mod.greenform.php */