<div class="btnOptions">
	<span class="button rightNav">
		<a href="<?php echo $ruleListURL; ?>" title="<?php echo lang('button_back_label'); ?>" class="submit"><?php echo lang('button_back'); ?></a>
	</span>
</div>

<div class="dataTables_wrapper">
	<?php
	echo form_open($formActionURL, array('id' => 'validationForm'));
	?>
	<div class="btnOptions">
		<?php echo form_submit('ruleSubmit', lang('button_update'), 'class="submit"'); ?>
		<span class="button rightNav">
			<a href="#" title="<?php echo lang('button_add_rule_label'); ?>" class="addRuleBtn submit"><?php echo lang('button_add_rule'); ?></a>
		</span>
	</div>

	<?php
	if($ruleSet['id'] != ''){
		echo form_hidden('id', $ruleSet['id']);
	}

	// set up the header table for the rule information
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		array(
			'data' => lang('table_heading_rule_settings'),
			'colspan' => 2
		)
	);

	// add the Type name row
	$this->table->add_row(
		'<label for="ruleName">' . lang('table_heading_rule_set_name') . '</label>',
		form_input('ruleName', form_prep($ruleSet['name']), 'size="20" id="ruleName"') .
			'<span class="instruction_text">' . lang('form_caption_rule_name') . '</span>'
	);

	// add the 'include captcha' row
	$this->table->add_row(
		'<label for="ruleCaptcha">' . lang('table_heading_rule_set_use_captcha') . '</label>',
		form_checkbox('ruleCaptcha', 'yes', isset($ruleSet['useCaptcha']) ? !!$ruleSet['useCaptcha'] : false, 'id="ruleCaptcha"') .
			'<span class="instruction_text">' . lang('form_caption_rule_use_captcha') . '</span>'
	);

	// output the table
	echo $this->table->generate();


	// set up the table for defining rules
	$template = $cp_table_template;
	$template['table_open'] = rtrim($template['table_open'], '>') . ' id="ruleTable">';

	$this->table->set_template($template);
	$this->table->set_heading(
		lang('table_heading_rule_field_basic'),
		lang('table_heading_rule_field_type'),
		array(
			'data' => lang('table_heading_rule_field_required'),
			'align' => 'center'
		),
		array(
			'data' => lang('table_heading_rule_field_recipient_email'),
			'align' => 'center'
		),
		array(
			'data' => lang('table_heading_rule_field_validation_type'),
			'align' => 'center'
		),
		array(
			'data' => lang('table_heading_rule_field_length'),
			'width' => 50
		),
		array(
			'data' => lang('table_heading_delete'),
			'align' => 'center'
		)
	);

	if(count($ruleSet['ruleList']) > 0){
		foreach($ruleSet['ruleList'] as $num => $rule){
			// default rule values
			$arrRules = array(
				'required' => false,
				'validationType' => '',
				'min_length' => '',
				'max_length' => '',
				'exact_length' => '',
				'other' => ''
			);
			// loop through the rules and determine where they need to be placed
			foreach(explode('|', $rule['rules']) as $vRule){
				switch($vRule){
					case 'required':
						$arrRules['required'] = true;
					break;
					case 'valid_email':
					case 'valid_emails':
					case 'alpha':
					case 'alpha_numeric':
					case 'alpha_dash':
					case 'numeric':
					case 'integer':
					case 'valid_ip':
						if($arrRules['validationType'] == ''){
							$arrRules['validationType'] = $vRule;
						}else{
							$arrRules['other'] .= $vRule . '|';
						}
					break;
					default:
						if(preg_match('/^((min|max|exact)_length)\[([\d]+)\]$/', $vRule, $matches)){
							// the rule is a length value
							$arrRules[$matches[1]] = $matches[3];
						}else{
							$arrRules['other'] .= $vRule;
						}
					break;
				}
			}
			$arrRules['other'] = trim($arrRules['other'], '|');

			$selectedValidationType = $arrRules['validationType'];
			$selectedFieldType = $rule['type'];
			$bolIsFieldRequired = $arrRules['required'];
			$bolIsFieldEmail = isset($rule['recipientEmail']) && ($rule['recipientEmail'] == true);

			// loop through each form input and replace the relevant data
			require(PATH_THIRD . 'greenform/form_row.php');
			foreach($arrRow as &$row){
				if(is_array($row)){
					$data = &$row['data'];
				}else{
					$data = &$row;
				}

				$data = sprintf(
					$data,
					$num,
					form_prep($rule['field']),
					form_prep($rule['label']),
					isset($rule['value']) ? form_prep($rule['value']) : '',
					$arrRules['required'],
					$bolIsFieldEmail,
					form_prep($arrRules['validationType']),
					form_prep($arrRules['min_length']),
					form_prep($arrRules['max_length']),
					form_prep($arrRules['exact_length']),
					form_prep($arrRules['other']),
					isset($rule['accept']) ? form_prep($rule['accept']) : ''
				);
			}

			$this->table->add_row($arrRow);
		}
	}else{
		$this->table->add_row(
			array(
				'data' => '<a href="#" title="' . lang('button_add_rule_label') . '" class="addRuleBtn">' . lang('button_add_rule') . '</a>',
				'align' => 'center',
				'colspan' => 8,
				'class' => 'blank'
			)
		);
	}

	echo '<h3>' . lang('table_heading_rule_field_rules') . '</h3>';
	echo $this->table->generate();
	?>

	<div class="btnOptions">
		<?php echo form_submit('ruleSubmit', lang('button_update'), 'class="submit"'); ?>
		<span class="button rightNav">
			<a href="#" title="<?php echo lang('button_add_rule_label'); ?>" class="addRuleBtn submit"><?php echo lang('button_add_rule'); ?></a>
		</span>
	</div>

	<?php
	echo form_close();
	?>
</div>

<div id="multiPop" class="dialog">
	<h1><?php echo lang('dialog_heading_options'); ?></h1>

	<div class="btnOptions">
		<button class="submit add" title="<?php echo lang('dialog_add_option'); ?>"><?php echo lang('dialog_add_option'); ?></button>
	</div>

	<input type="hidden" value="" class="inputOptions">

	<h2><?php echo lang('table_heading_options'); ?></h2>
	<ul class="optionsBox"></ul>

	<div class="btnOptions">
		<button class="submit save" title="<?php echo lang('button_options_save'); ?>"><?php echo lang('button_options_save'); ?></button>
		<button class="submit close" title="<?php echo lang('button_options_cancel'); ?>"><?php echo lang('button_options_cancel'); ?></button>
	</div>
</div>

<div id="acceptPop" class="dialog">
	<h1><?php echo lang('dialog_heading_file_types'); ?></h1>

	<p><?php echo lang('dialog_files_desc'); ?></p>

	<div class="listBlock custom">
		<h2><?php echo lang('dialog_files_list_custom'); ?></h2>

		<p><?php echo lang('dialog_files_custom_desc'); ?></p>

		<input type="text" value="" class="customExt">
		<span class="resultCloseBtn">close</span>
		<ul class="resultBox"></ul>

		<ul class="list">
		</ul>
	</div>

	<div class="listBlock all">
		<h2><?php echo lang('dialog_files_list_general'); ?></h2>

		<ul class="list">
			<li>
				<label for="acceptAllImages" title="Accept all images (image/*)">
					<input type="checkbox" name="acceptsAll[]" value="image/*" id="acceptAllImages">
					All Images
				</label>
			</li>

			<li>
				<label for="acceptAllVideo" title="Accept all videos (videos/*)">
					<input type="checkbox" name="acceptsAll[]" value="video/*" id="acceptAllVideo">
					All Video
				</label>
			</li>

			<li>
				<label for="acceptAllAudio" title="Accept all audio (audio/*)">
					<input type="checkbox" name="acceptsAll[]" value="audio/*" id="acceptAllAudio">
					All Audio
				</label>
			</li>
		</ul>
	</div>

	<div class="listBlock">
		<h2><?php echo lang('dialog_files_list_typical'); ?></h2>

		<ul class="list">
			<li>
				<label for="acceptTypicalImages" title="Typical image selection (<?php echo $acceptTypical['image']['ext']; ?>)">
					<input type="checkbox" name="accepts[]" value="<?php echo $acceptTypical['image']['mime']; ?>" id="acceptTypicalImages">
					<?php echo $acceptTypical['image']['ext']; ?>
				</label>
			</li>

			<li>
				<label for="acceptTypicalVideo" title="Typical video selection (<?php echo $acceptTypical['video']['ext']; ?>)">
					<input type="checkbox" name="accepts[]" value="<?php echo $acceptTypical['video']['mime']; ?>" id="acceptTypicalVideo">
					<?php echo $acceptTypical['video']['ext']; ?>
				</label>
			</li>

			<li>
				<label for="acceptTypicalAudio" title="Typical audio selection (<?php echo $acceptTypical['audio']['ext']; ?>)">
					<input type="checkbox" name="accepts[]" value="<?php echo $acceptTypical['audio']['mime']; ?>" id="acceptTypicalAudio">
					<?php echo $acceptTypical['audio']['ext']; ?>
				</label>
			</li>
		</ul>
	</div>

	<div class="btnOptions">
		<button class="submit save" title="<?php echo lang('button_options_save'); ?>"><?php echo lang('button_options_save'); ?></button>
		<button class="submit close" title="<?php echo lang('button_options_cancel'); ?>"><?php echo lang('button_options_cancel'); ?></button>
	</div>
</div>