<?php
/**
 * Author: Lee Langley
 * Date Created: 13/03/2012 17:56
 */

$strFieldBaseInfo = '<label for="formName%1$d">' . lang('table_heading_rule_field_name') . '</label>' .
					form_input('fieldName[%1$d]', '%2$s', 'size="20" id="formName%1$d" class="fieldName"') .
					'<label for="formLabel%1$d">' . lang('table_heading_rule_field_label') . '</label>' .
					form_input('fieldLabel[%1$d]', '%3$s', 'size="20" id="formLabel%1$d"') .
					'<div class="valueBox extraInfo show-all hide-select hide-checkbox hide-radio hide-file">' .
						'<label for="formValue%1$d">' . lang('table_heading_rule_field_value') . '</label>' .
						form_input('fieldValue[%1$d]', '%4$s', 'size="20" id="formValue%1$d" class="value"') .
					'</div>';

// the drop-down field for choosing the field type
$strFieldTypeSelect = form_dropdown(
	'fieldType[%1$d]',
	$arrFieldType,
	isset($selectedFieldType) ? $selectedFieldType : '',
	'class="fieldType"'
);
// add the file input 'accept' option
$strFieldTypeSelect .= '<div class="typeBlock extraInfo show-file">' .
							'<label for="fieldAccept%1$d" class="editLink">' . lang('table_heading_rule_field_accept') . '</label>' .
							form_input(
								'fieldAccept[%1$d]',
								'%12$s',
								'id="fieldAccept%1$d" class="fieldAccept"'
							) .
					   '</div>';
// add the multiple option settings button (for select, checkbox and radio types)
$strFieldTypeSelect .= '<div class="typeBlock">' .
							'<a href="#" class="multiplePop editLink extraInfo show-select show-checkbox show-radio">' . lang('table_heading_rule_field_options') . '</a>' .
						'</div>';


// the drop-down field for choosing the validation type
$strValidationTypeSelect = form_dropdown(
	'fieldValidType[%1$d]',
	$arrValidationType,
	isset($selectedValidationType) ? $selectedValidationType : '',
	'class="fieldValType"'
);

$strValidationTypeSelect .= '<div class="otherRules"><strong>' . lang('table_heading_rule_other') . '</strong><br />' . form_input('fieldOther[%1$d]', '%11$s', 'size="20"') . '</div>';

// the input fields for setting min/max/exact field lengths
$strLengthFields = '<label for="lengthMin%1$d" class="lengthLabel">' . lang('table_heading_rule_field_length_min') . '</label>' .
				   form_input('fieldLengthMin[%1$d]', '%8$s', 'size="3" maxlength="4" title="minimum number of characters allowed" id="lengthMin%1$d" class="fieldLength min"');
$strLengthFields .= '<label for="lengthMax%1$d" class="lengthLabel">' . lang('table_heading_rule_field_length_max') . '</label>' .
					form_input('fieldLengthMax[%1$d]', '%9$s', 'size="3" maxlength="4" title="maximum number of characters allowed" id="lengthMax%1$d" class="fieldLength max"');
$strLengthFields .= '<label for="lengthExact%1$d" class="lengthLabel">' . lang('table_heading_rule_field_length_exact') . '</label>' .
					form_input('fieldLengthExact[%1$d]', '%10$s', 'size="3" maxlength="4" title="set an exact number of characters that the field must contain" id="lengthExact%1$d" class="fieldLength exact"');

$arrRow = array(
	array(
		'data' => $strFieldBaseInfo,
		'align' => 'left'
	),
	array(
		'data' => $strFieldTypeSelect,
		'align' => 'center'
	),
	array(
		'data' => form_checkbox('fieldRequired[%1$d]', '%1$d', isset($bolIsFieldRequired) ? $bolIsFieldRequired : false),
		'align' => 'center'
	),
	array(
		'data' => form_radio('fieldRecipient', '%1$d', isset($bolIsFieldEmail) ? $bolIsFieldEmail : false, 'title="select if this field should be used as the submitters email address" class="fieldIsRecipient"'),
		'align' => 'center'
	),
	array(
		'data' => $strValidationTypeSelect,
		'align' => 'center'
	),
	array(
		'data' => $strLengthFields,
		'align' => 'center'
	),
	array(
		'data' => form_checkbox('fieldDelete[%1$d]', 'delete', false, 'class="deleteCheck"'),
		'align' => 'center'
	)
);
?>