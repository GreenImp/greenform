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
class Greenform_upd{
	public $version = '1.2';
	private $moduleName = 'Greenform';
	private $tablePrefix = 'green_form_';
	private $tableSubmissions = 'submissions';
	private $tableRules = 'validation_rules';


	private $tableSubmissionsFields = array(
		'entry_id'		=> array(
			'type'				=> 'int',
			'constraint'		=> '10',
			'unsigned'			=> true,
			'auto_increment'	=> true
		),
		'entry_url'		=> array(
			'type'				=> 'varchar',
			'constraint'		=> '255'
		),
		'entry_field'	=> array('type' => 'longtext'),
		'entry_files'	=> array('type' => 'longtext'),
		'entry_ip'		=> array(
			'type'				=> 'varchar',
			'constraint'		=> '30'
		),
		'entry_read'	=> array(
			'type'				=> 'tinyint',
			'constraint'		=> '1',
			'unsigned'			=> true,
			'default'			=> 0
		),
		'entry_date'	=> array('type' => 'datetime')
	);

	private $tableRulesFields = array(
		'rule_id'				=> array(
			'type'					=> 'int',
			'constraint'			=> 10,
			'unsigned'				=> true,
			'auto_increment'		=> true
		),
		'rule_name'				=> array(
			'type'					=> 'varchar',
			'constraint'			=> 255
		),
		'rule_use_captcha'		=> array(
			'type'					=> 'boolean',
			'default'				=> 0
		),
		'rule_fields'			=> array('type' => 'longtext'),
		'rule_date_created'		=> array('type' => 'datetime'),
		'rule_date_modified'	=> array('type' => 'datetime')
	);


	function Greenform_upd(){
		// Make a local reference to the ExpressionEngine super object
		$this->EE =& get_instance();
		// load the dbforge library
		$this->EE->load->dbforge();

		$this->tableSubmissions = $this->tablePrefix . $this->tableSubmissions;
		$this->tableRules = $this->tablePrefix . $this->tableRules;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Module Installer
	 *
	 * @access	public
	 * @return	bool
	 */	
	function install(){
		// define the base module settings
		$data = array(
			'module_name' => $this->moduleName,
			'module_version' => $this->version,
			'has_cp_backend' => 'y',
			'has_publish_fields' => 'n'
		);
		// insert the module record
		$this->EE->db->insert('modules', $data);

		// define the module actions
		$data = array(
			'class'		=> $this->moduleName,
			'method'	=> 'index'
		);
		// insert into the actions table
		$this->EE->db->insert('actions', $data);


		// set up the DB table for storing form submissions
		$this->EE->dbforge->add_field($this->tableSubmissionsFields);	// add the fields
		$this->EE->dbforge->add_key('entry_id', true);					// define the primary key
		
		// add the form submissions table to database
		$this->EE->dbforge->create_table($this->tableSubmissions, true);



		// set up the table for storing form validation rules
		$this->EE->dbforge->add_field($this->tableRulesFields);	// add the fields
		$this->EE->dbforge->add_key('rule_id', true);			// define the primary key

		// add the form validation rules table to database
		$this->EE->dbforge->create_table($this->tableRules, true);

		return true;
	}
	
	
	// --------------------------------------------------------------------

	/**
	 * Module Uninstaller
	 *
	 * @access	public
	 * @return	bool
	 */
	function uninstall(){
		$this->EE->db->select('module_id');
		$query = $this->EE->db->get_where('modules', array('module_name' => $this->moduleName));

		$this->EE->db->where('module_id', $query->row('module_id'));
		$this->EE->db->delete('module_member_groups');

		$this->EE->db->where('module_name', $this->moduleName);
		$this->EE->db->delete('modules');

		$this->EE->db->where('class', $this->moduleName);
		$this->EE->db->delete('actions');
		
		// delete form submissions
		$this->EE->dbforge->drop_table($this->tableSubmissions);
		// delete all validation rules
		$this->EE->dbforge->drop_table($this->tableRules);

		return true;
	}



	// --------------------------------------------------------------------

	/**
	 * Module Updater
	 *
	 * @access	public
	 * @param string $current
	 * @return bool
	 */
	function update($current = ''){
		if(version_compare($current, $this->version, '=')){
			return false;
		}elseif(version_compare($current, $this->version, '<')){
			// add the 'read' flag to the db
			$arrFields = array(
				'entry_read'	=> array(
					'type'				=> 'tinyint',
					'constraint'		=> '1',
					'unsigned'			=> true,
					'default'			=> 0
				)
			);
			$this->EE->dbforge->add_column($this->tableSubmissions, $arrFields);
		}

		return true;
	}
}
/* END Class */

/* End of file upd.form.php */
/* Location: ./system/expressionengine/third_party/modules/greenform/upd.greenform.php */