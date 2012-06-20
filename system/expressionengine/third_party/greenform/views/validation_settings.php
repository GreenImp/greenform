<div class="btnOptions">
	<span class="button rightNav">
		<a href="<?php echo $addRuleURL; ?>" title="<?php echo lang('button_add_rule_set_label'); ?>" class="submit"><?php echo lang('button_add_rule_set'); ?></a>
	</span>
</div>

<div class="sortable">
	<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		lang('table_heading_id'),
		lang('table_heading_name'),
		lang('table_heading_timestamp'),
		lang('table_heading_options')
	);

	if(count($rules) > 0){
		foreach($rules as $rule){
			$this->table->add_row(
				$rule['rule_id'],
				$rule['rule_name'],
				$rule['rule_date_modified'],
				'<a href="' . $editRuleURL . $rule['rule_id'] . '" title="' . lang('button_edit_rule_set_label') . '" class="submit">' . lang('button_edit') . '</a> ' .
				'<a href="' . $deleteRuleURL . $rule['rule_id'] . '" title="' . lang('button_delete_rule_set_label') . '" class="formDeleteBtn submit">' . lang('button_delete') . '</a>'
			);
		}
	}else{
		$this->table->add_row(array(
				'data' => sprintf(lang('table_no_rules_defined'), $addRuleURL),
				'colspan' => 4,
				'align' => 'center'
		));
	}

	echo $this->table->generate();
	?>
</div>

<div class="btnOptions">
	<span class="button rightNav">
		<a href="<?php echo $addRuleURL; ?>" title="<?php echo lang('button_add_rule_set_label'); ?>" class="submit"><?php echo lang('button_add_rule_set'); ?></a>
	</span>
</div>