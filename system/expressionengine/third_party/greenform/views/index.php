<div class="sortable">
	<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		lang('table_heading_id'),
		lang('table_heading_timestamp'),
		lang('table_heading_ip'),
		lang('table_heading_page'),
		lang('table_heading_field'),
		lang('table_heading_files'),
		lang('table_heading_read')
	);

	if(isset($entries) && (count($entries) > 0)){
		foreach($entries as $entry){
			$bolIsRead = !!$entry['entry_read'];

			$jsonFields = json_decode($entry['entry_field']);
			$jsonFiles = json_decode($entry['entry_files'], true);
			if(!is_array($jsonFiles)){
				$jsonFiles = array();
			}

			// loop through the files and turn them into active links
			$files = array();
			if(!empty($jsonFiles)){
				foreach($jsonFiles as $name => $file){
					$strName = $file;
					foreach($jsonFields as $field){
						if(($field->type == 'file') && ($field->field == $name)){
							$strName = $field->value;
							break;
						}
					}
					
					$files[$name] = '<a href="' . $uploadURL . $file . '" target="_blank" title="' . lang('button_view_file_label') . '">' . $strName . '</a>';
				}
			}

			// loop through the fields and build up the layout
			$arrFields = array();
			foreach($jsonFields as $field){
				$value = form_prep($field->value);

				$string = $field->label . ': ';
				// set the field value
				switch($field->type){
					case 'file':
						// the value is a file - turn it into an active link
						if($value != ''){
							$strURL = $value;
							foreach($jsonFiles as $name => $file){
								if($field->field == $name){
									$strURL = $file;
								}
							}
							$string .= '<a href="' . $uploadURL . $strURL . '" target="_blank" title="' . lang('button_view_file_label') . '">' . $value . '</a>';
						}
					break;
					case 'password':
						// the field is a password - add some security
						$string .= '<span class="passwordBox">' . $value . '</span>';
					break;
					case 'email':
						// the field is an email - turn it into an email link
						if($value != ''){
							$string .= '<a href="mailto:' . $value . '" title="' . lang('button_send_email') . '">' . $value . '</a>';
						}
					break;
					default:
						// default - just output the value
						$string .= nl2br($value);
					break;
				}

				// set the string to the field
				$arrFields[] = $string;
			}

			$this->table->add_row(
				array(
					'data' => $entry['entry_id'],
					'class' => $bolIsRead ? 'read' : ''
				),
				array(
					'data' => $entry['entry_date'],
					'class' => $bolIsRead ? 'read' : ''
				),
				array(
					'data' => $entry['entry_ip'],
					'class' => $bolIsRead ? 'read' : ''
				),
				array(
					'data' => $entry['entry_url'],
					'class' => $bolIsRead ? 'read' : ''
				),
				array(
					'data' => implode('<br />', $arrFields),
					'class' => $bolIsRead ? 'read' : ''
				),
				array(
					'data' => implode('<br />', $files),
					'class' => $bolIsRead ? 'read' : ''
				),
				array(
					'data' => '<label for="flagRead' . $entry['entry_id']  .'">' . ($bolIsRead ? 'read' : 'unread') . '</label>' . form_checkbox('flagRead[]', $entry['entry_id'], $bolIsRead, 'title="' . ($bolIsRead ? 'read' : 'unread') . '" id="flagRead' . $entry['entry_id']  .'"'),
					'align' => 'center',
					'class' => 'flagRead' . ($bolIsRead ? ' read' : '')
				)
			);
		}
	}else{
		$this->table->add_row(array(
				'data' => lang('table_no_form_submissions'),
				'colspan' => 7,
				'align' => 'center'
		));
	}

	echo form_open($formActionURL);
	echo $this->table->generate();
	echo form_close();
	?>
</div>
