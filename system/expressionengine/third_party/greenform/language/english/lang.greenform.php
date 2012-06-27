<?php
$lang = array(
	// module definitions
	'greenform_module_name' 					=> 'Green Form',
	'greenform_module_description'	 			=> 'Fully customisable, advanced form module, with built in validation',

	'module_no_rules_defined'					=> 'The %s rule set has no rules defined',

	/** Admin **/
	// page titles
	'page_title_validation_rules'				=> 'Validation Rule Sets',
	'page_title_validation_rules_add'			=> 'Validation Rules - Add',
	'page_title_validation_rules_edit'			=> 'Validation Rules - Edit',
	'page_title_validation_rules_delete'		=> 'Validation Rules - Delete',

	// buttons
	'button_nav_entries'						=> 'Form Entries',
	'button_nav_rule_sets'						=> 'Rule Sets',
	'button_back'								=> 'Back',
	'button_back_label'							=> 'go back',
	'button_edit'								=> 'Edit',
	'button_delete'								=> 'Delete',
	'button_update'								=> 'Update',
	'button_view_file_label'					=> 'view file',
	'button_send_email'							=> 'send email',
	'button_add_rule_set'						=> 'Add Rule Set',
	'button_add_rule_set_label'					=> 'add validation rule set',
	'button_edit_rule_set_label'				=> 'edit the rule set',
	'button_delete_rule_set_label'				=> 'remove the rule set',
	'button_add_rule'							=> 'Add Rule',
	'button_add_rule_label'						=> 'Add a new rule',

	'button_options_save'						=> 'Save',
	'button_options_cancel'						=> 'Cancel',

	// table headers - default
	'table_heading_id'							=> 'ID',
	'table_heading_timestamp'					=> 'Timestamp',
	'table_heading_ip'							=> 'IP',
	'table_heading_page'						=> 'Page',
	'table_heading_field'						=> 'Fields',
	'table_heading_files'						=> 'Files',
	'table_heading_read'						=> 'Read',
	'table_heading_name'						=> 'Name',
	'table_heading_options'						=> 'Options',

	// table headers & labels - rule set form
	'table_heading_rule_settings'				=> 'General Settings',
	'table_heading_rule_set_name'				=> 'Rule Set Name',
	'table_heading_rule_set_use_captcha'		=> 'Use Captcha?',
	'table_heading_rule_field_rules'			=> 'Rule Definitions',
	'table_heading_rule_field_basic'			=> 'Base Information',
	'table_heading_rule_field_name'				=> 'Field Name',
	'table_heading_rule_field_label'			=> 'Label',
	'table_heading_rule_field_value'			=> 'Default Value / Placeholder',
	'table_heading_rule_field_options'			=> 'Set Options',
	'table_heading_rule_field_type'				=> 'Field Type',
	'table_heading_rule_field_accept'			=> 'File Types',
	'table_heading_rule_field_required'			=> 'Required?',
	'table_heading_rule_field_recipient_email'	=> 'User\'s Email?' ,
	'table_heading_rule_field_validation_type'	=> 'Validation Type',
	'table_heading_rule_field_length'			=> 'Length',
	'table_heading_rule_field_length_min'		=> 'Min',
	'table_heading_rule_field_length_max'		=> 'Max',
	'table_heading_rule_field_length_exact'		=> 'Exact',
	'table_heading_rule_other'					=> 'Other Rules',
	'table_heading_delete'						=> 'Delete',

	// rule set dialog boxes
	'dialog_heading_options'					=> 'Set Options',
	'dialog_add_option'							=> 'Add Option',
	'dialog_heading_file_types'					=> 'Allowed Filetypes',
	'dialog_files_desc'							=> 'Choose the file types that users can upload via this input',
	'dialog_files_custom_desc'					=> 'Start typing a file extension to find a match',
	'dialog_files_list_general'					=> 'General',
	'dialog_files_list_typical'					=> 'Typical',
	'dialog_files_list_custom'					=> 'Custom',

	// dataTable settings - used for table pagination/search etc in CP
	'dt_oAria'			=> array(
		'sSortAscending'	=> ': activate to sort column ascending',
		'sSortDescending'	=> ': activate to sort column descending'
	),
	'dt_oPaginate'		=> array(
		'sFirst'			=> 'First',
		'sLast'				=> 'Last',
		'sNext'				=> 'Next',
		'sPrevious'			=> 'Previous'
	),
	'dt_sEmptyTable'		=> 'No data available in table',
	'dt_sInfo'				=> 'Showing _START_ to _END_ of _TOTAL_ entries',
	'dt_sInfoEmpty'			=> 'Showing 0 to 0 of 0 entries',
	'dt_sInfoFiltered'		=> '(filtered from _MAX_ total entries)',
	'dt_sLengthMenu'		=> 'Show _MENU_ entries',
	'dt_sLoadingRecords'	=> 'Loading...',
	'dt_sProcessing'		=> 'Processing...',
	'dt_sSearch'			=> 'Search',
	'dt_sZeroRecords'		=> 'No matching records found',

	// form captions
	'form_caption_rule_name'					=> 'Single word, no spaces. Underscores and dashes allowed',
	'form_caption_rule_use_captcha'				=> 'Only used if auto-generating the form',

	// table notes/misc text
	'table_no_form_submissions' 				=>	'No form submissions have been registered.',
	'table_no_rules_defined' 					=>	'No rules defined, why don\'t you <a href="%s" title="add validation rule">add one</a>?',

	
	// notification emails
	'email_posts_heading'						=> 'Details',
	'email_files_heading'						=> 'Files',
	'email_view_file'							=> 'View File',

	// submissions update
	'submission_update_success'					=> 'The submission(s) has been updated',
	'submission_update_error'					=> 'There was a problem updating the submission(s)',

	// rule set messages - update
	'rule_set_updated'							=> 'Rule set updated successfully',
	'rule_set_update_failed'					=> 'Error updating the database, please try again',
	'rule_set_update_check_failed'				=> 'Errors were found in the form',
	'rule_set_name_not_unique'					=> '%s already exists, please choose a unique name.',
	'rule_set_field_not_unique'					=> 'The %s field must be unique.',
	'rule_set_field_type_invalid'				=> 'The %s field contains an invalid value.',

	// rule set messages - delete
	'rule_set_deleted'							=> 'Rule set removed',
	'rule_set_delete_failed'					=> 'Error removing rule set, please try again',


	/** Front-end **/
	'form_submit_button_label'					=> 'Submit',
	// form validation
	'validation_invalid_referrer'				=> 'The form post is invalid',
	'validation_no_file_inputs'					=> 'This form doesn\'t accept file inputs',
	'validation_file_required'					=> 'The %s file upload is missing',
	'validation_file_no_file_type'				=> 'The %s file upload type could not be found',
	'validation_file_invalid_file_type'			=> 'The %s file upload type is invalid',
	'validation_file_error'						=> 'An error has occurred uploading the %s file, please try again',
	'validation_file_move_error'				=> 'The %s file is invalid or couldn\'t be moved',
	'validation_file_too_large'					=> 'The %s file is too large',
	'validation_file_ini_size'					=> 'The %s file is too large',
	'validation_file_form_size'					=> 'The %s file is too large',
	'validation_file_partial'					=> 'The %s file was only partially uploaded',
	'validation_file_no_tmp'					=> 'Missing temporary folder to store files',
	'validation_file_cant_write'				=> 'The %s file couldn\t be written to disk',
	'validation_file_extension'					=> 'Server extension stopped The %s file upload',
	// file uploadprogress
	'validation_upload_no_id'					=> 'Upload not found - ensure the files aren\'t too large',
	'validation_upload_not_available'			=> 'No upload functionality found',

	// form submit messages
	'form_submit_success_header'				=> 'Success',
	'form_submit_success'						=> 'The form has been successfully submitted.',

	'form_submit_error_header'					=> 'Error',

	'captcha_input_label'						=> 'Please enter the characters that you see in the image',
	'captcha_required'							=> 'In order to submit this form, you must complete the captcha field.',
	'captcha_incorrect'							=> 'The captcha word you submitted is incorrect. Please go back and try again.',

	'captivate_error'							=> 'In order to submit this form, you must complete the captcha field.',


	// javascript messages
	'js_heading_error'							=> 'Error',
	'js_heading_success'						=> 'Success',
	'js_error_general'							=> 'An error has occurred, please try again',
	'js_error_unknown'							=> 'An un-known error has occurred, please try again',
	'js_error_parse'							=> 'Problem parsing result, please try again',
	'js_error_file_upload'						=> 'Problem uploading file, please try again',
	'js_error_uploadprogress_not_found'			=> 'No method was found for checking the progress of the upload',

	'js_validation_file_too_large'				=> "The file is too large",

	'js_validation_required'					=> 'The %s field is required',
	'js_validation_email'						=> 'The %s field must be a valid email address',
	'js_validation_emails'						=> 'The %s field must be a valid email address (multiple email addresses can be separated by a comma \',\')',
	'js_validation_alpha'						=> 'The %s field must contain only letters (a-z)',
	'js_validation_alpha_numeric'				=> 'The %s field must contain only letters and numbers (a-z, 0-9)',
	'js_validation_alpha_dash'					=> 'The %s field must contain only letters, numbers, underscores and dashes (a-z, 0-9, -, _)',
	'js_validation_numeric'						=> 'The %s field must be numeric (ie; 0.9, 56, -10)',
	'js_validation_integer'						=> 'The %s field must contain only numbers (0-9)',
	'js_validation_ip'							=> 'The %s field must be a valid IP address (ie; 123.123.1.2)',
	'js_validation_length'						=> 'The %s field must be %s %d characters long',
	'js_validation_numeric_value'				=> 'The %s field must be %s %d',
	'js_validation_match'						=> 'The %s field does not match the %s field',

	// DO NO REMOVE THE BELOW LINE
	'' => ''
);