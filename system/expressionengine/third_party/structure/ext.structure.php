<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Extension for Structure
 *
 * This file must be in your /system/third_party/structure directory of your ExpressionEngine installation
 *
 * @package             Structure for EE2
 * @author              Jack McDade (jack@jackmcdade.com)
 * @author              Travis Schmeisser (travis@rockthenroll.com)
 * @copyright			Copyright (c) 2010 Travis Schmeisser
 * @link                http://buildwithstructure.com
 */

/**
 * Include Structure SQL Model
 */
require_once PATH_THIRD.'structure/sql.structure.php';

/**
 * Include Structure Core Mod
 */
require_once PATH_THIRD.'structure/mod.structure.php';

class Structure_ext {
	
	var $name			= 'Structure';
	var $version 		= '3.0.4';
	var $description	= 'Enable some nice Structure-friendly control panel features';
	var $settings_exist	= 'n';
	var $docs_url		= 'http://buildwithstructure.com/documentation';
	var $settings 		= array();
	

	function structure_ext($settings = '')
	{
		$this->EE =& get_instance();
		$this->sql = new Sql_structure();
		$this->site_pages = $this->sql->get_site_pages();

		if ($this->sql->module_is_installed() !== TRUE || ! is_array($this->site_pages))
			return FALSE;
		
		$this->settings = $settings;
		$this->structure_settings = $this->sql->get_settings();
	}
	
	
	function sessions_start($ee)
	{
		if (REQ == 'PAGE' && array_key_exists('uris', $this->site_pages) && is_array($this->site_pages['uris']) && count($this->site_pages['uris']) > 0)
		{
			// -------------------------------------------
			//  Sanitize the URL for pagination and other bypasses
			// -------------------------------------------
			
			$this->_create_clean_structure_segments();
			
			// -------------------------------------------
			//  Set all other class variables
			// -------------------------------------------
			
			// Set this current URI (homepage = '/')
			$this->uri = $this->EE->functions->remove_double_slashes('/'.$this->EE->uri->uri_string().'/');
			
			// Make sure there is Structure data
			if (array_key_exists('uris', $this->site_pages) && is_array($this->site_pages['uris']) && count($this->site_pages['uris']) > 0)
			{
				$this->entry_id = array_search($this->uri, $this->site_pages['uris']);
				$this->parent_id = $this->sql->get_parent_id($this->entry_id);
				$this->segment_1 = $this->EE->uri->segment(1) ? '/'.$this->EE->uri->segment(1).'/' : FALSE;
				$this->top_id = array_search($this->segment_1, $this->site_pages['uris']);
			}
			
			// -------------------------------------------
			//  Create all Structure global variabes
			// -------------------------------------------
			
			$this->_create_global_vars();	
			
		}
	}
	
	
	function entry_submission_redirect($entry_id, $meta, $data, $cp_call, $orig_loc)
	{
		if ($cp_call === TRUE && isset($this->structure_settings['redirect_on_publish']) && $this->structure_settings['redirect_on_publish'] == 'y')
		{
			return BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure';
		}
		else
		{
			return $orig_loc;
		} 
	}
	
	
	function cp_member_login()
	{
		if (isset($this->structure_settings['redirect_on_login']) && $this->structure_settings['redirect_on_login'] == 'y')
		{
			$this->EE->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=structure');
		}
	}
	
	function _is_search()
	{
		$qstring = $this->EE->uri->query_string;
		$string_array = explode("/",$qstring);
		
		$search_id_key = count($string_array)-2;
		$search_id = array_key_exists($search_id_key, $string_array) ? $string_array[$search_id_key] : FALSE;
		
		if ($search_id !== FALSE)
		{
			// Fetch the cached search query
			$query = $this->EE->db->get_where('search', array('search_id' => $search_id));

			// if ($query->num_rows() > 0 || $query->row('total_results') > 0)
			if (count($query->result_array()) > 0 && ($query->num_rows() > 0 || $query->row('total_results') > 0))
				return TRUE;
		}
		
		return FALSE;
	}
	
	
	private function _create_clean_structure_segments()
	{
		// structure_segment global vars
		$segments = array_pad($this->EE->uri->segments, 10, '');
		for ($i = 1; $i <= count($segments); $i++)
		{
			$this->EE->config->_global_vars['structure_'.$i] = $segments[$i - 1]; // {structure_X}
		}
		
		// Create pagination_segment and last_segment
		$segment_count = $this->EE->uri->total_segments();
		$last_segment = $this->EE->uri->segment($segment_count);
		
		// Check for pagination
		$pagination_segment = FALSE;
		if(substr($last_segment,0,1) == 'P' && $this->_is_search() === FALSE)
		{
			$pagination_segment = $segment_count;
			$pagination_page = substr($last_segment,1);
			
			// echo $pagination_page;
	
			$this->EE->config->_global_vars['structure_pagination_segment'] = $pagination_segment; // {structure_pagination_segment}
			$this->EE->config->_global_vars['structure_pagination_page'] = $pagination_page; // {structure_pagination_page}
			$this->EE->config->_global_vars['structure_last_segment'] = $last_segment; // {structure_last_segment}
	
			// Clean and dirty laundry, thanks to Freebie's cleverness
			$clean_array	= array();
			$dirty_array	= explode('/', $this->EE->uri->uri_string);
	
			// move any segments that don't match patterns to clean array
			foreach ($dirty_array as $segment)
			{
				if ($pagination_segment !== FALSE && $segment != 'P'.$pagination_page)
				{
					array_push($clean_array, $segment);	
				}
			}
	
			// -------------------------------------------
			//  Clean up and overwrite the URI vars
			// -------------------------------------------
	
			// Rewrite the uri_string
			if (count($clean_array) != 0)
			{
				$clean_string = '/'.implode('/', $clean_array).'/';
				
				if (array_search($clean_string, $this->site_pages['uris']))
				{
					$this->EE->uri->uri_string = $clean_string;
					
					$this->EE->config->_global_vars['structure_debug_uri_cleaned'] = $this->EE->uri->uri_string; 

					$this->EE->uri->segments = array();
					$this->EE->uri->rsegments = array();
					$this->EE->uri->_explode_segments();

					// Load the router class
					$RTR =& load_class('Router', 'core');
					$RTR->_parse_routes();

					// re-index the segments
					$this->EE->uri->_reindex_segments();
				}	
			}
		}
	}
	
	
	private function _create_global_vars()
	{
		// utility global vars
		$this->EE->config->_global_vars['structure:is:page'] 			= $this->entry_id !== FALSE && $this->sql->is_listing_entry($this->entry_id) !== TRUE ? TRUE : FALSE;
		$this->EE->config->_global_vars['structure:is:listing'] 		= $this->sql->is_listing_entry($this->entry_id);
		$this->EE->config->_global_vars['structure:is:listing:parent'] 	= $this->sql->get_listing_channel($this->entry_id) !== FALSE && is_numeric($this->entry_id) && $this->sql->is_listing_entry($this->entry_id) === FALSE ? TRUE : FALSE;

		// current page global vars
		$this->EE->config->_global_vars['structure:page:entry_id'] 		= $this->entry_id !== FALSE ? $this->entry_id : FALSE; // {page:entry_id}
		$this->EE->config->_global_vars['structure:page:template_id'] 	= $this->entry_id !== FALSE ? $this->site_pages['templates'][$this->entry_id] : FALSE; // {page:template_id}
		$this->EE->config->_global_vars['structure:page:title'] 		= $this->entry_id !== FALSE ? $this->sql->get_page_title($this->entry_id) : FALSE; // {page:title}
		$this->EE->config->_global_vars['structure:page:slug'] 			= $this->entry_id !== FALSE ? $this->EE->uri->segment($this->EE->uri->total_segments()) : FALSE;
		$this->EE->config->_global_vars['structure:page:uri'] 			= $this->entry_id !== FALSE ? $this->uri : FALSE;
		$this->EE->config->_global_vars['structure:page:url'] 			= $this->entry_id !== FALSE ? $this->EE->functions->remove_double_slashes($this->site_pages['url'] . $this->EE->config->_global_vars['structure:page:uri']) : FALSE; // {page:url}
		
		// parent page global vars
		$this->EE->config->_global_vars['structure:parent:entry_id'] 	= $this->parent_id !== FALSE ? $this->parent_id : FALSE; // {page:entry_id}
		$this->EE->config->_global_vars['structure:parent:title'] 		= $this->parent_id !== FALSE ? $this->sql->get_page_title($this->parent_id) : FALSE; // {page:title}
		$this->EE->config->_global_vars['structure:parent:slug'] 		= $this->parent_id !== FALSE ? $this->EE->uri->segment($this->EE->uri->total_segments() - 1)  : FALSE; // {parent:slug}
		$this->EE->config->_global_vars['structure:parent:uri'] 		= $this->parent_id !== FALSE ? $this->site_pages['uris'][$this->parent_id]  : FALSE; // {parent:relative_url}
		$this->EE->config->_global_vars['structure:parent:url'] 		= $this->parent_id !== FALSE ? $this->EE->functions->remove_double_slashes($this->site_pages['url'] . $this->EE->config->_global_vars['structure:parent:uri'])  : FALSE; // {parent:url}

		// top page global vars
		$this->EE->config->_global_vars['structure:top:entry_id'] 	= $this->segment_1 !== FALSE ? $this->top_id : FALSE; // {top:entry_id}
		$this->EE->config->_global_vars['structure:top:title'] 		= $this->segment_1 !== FALSE ? $this->sql->get_page_title($this->top_id) : FALSE; // {top:title}
		$this->EE->config->_global_vars['structure:top:slug'] 		= $this->segment_1 !== FALSE ? $this->EE->uri->segment(1) : FALSE; // {top:slug}
		$this->EE->config->_global_vars['structure:top:uri'] 		= $this->segment_1 !== FALSE ? '/'.$this->EE->uri->segment(1).'/' : FALSE; // {top:relative_url}
		$this->EE->config->_global_vars['structure:top:url'] 		= $this->segment_1 !== FALSE ? $this->EE->functions->remove_double_slashes($this->site_pages['url'] . $this->EE->uri->segment(1) . '/')  : FALSE; // {top:url}		

		// listing global vars
		$this->EE->config->_global_vars['structure:child_listing:channel_id'] = $this->sql->get_listing_channel($this->entry_id) !== FALSE && is_numeric($this->entry_id)? $this->sql->get_listing_channel($this->entry_id) : FALSE;
		$this->EE->config->_global_vars['structure:child_listing:short_name'] = $this->sql->get_listing_channel($this->entry_id) !== FALSE && is_numeric($this->entry_id)? $this->sql->get_listing_channel_short_name($this->EE->config->_global_vars['structure:child_listing:channel_id']) : FALSE;
		
		// child global vars
		$child_ids = $this->sql->get_child_entries($this->entry_id);
		$this->EE->config->_global_vars['structure:child_ids'] = FALSE;
		
		// freebie
		$this->EE->config->_global_vars['structure:freebie:entry_id'] = isset($this->EE->config->_global_vars['freebie_debug_uri']) ? array_search('/'.$this->EE->config->_global_vars['freebie_debug_uri'].'/', $this->site_pages['uris']) : FALSE;
		
		if ($child_ids !== FALSE && count($child_ids > 0))
		{
			$this->EE->config->_global_vars['structure:child_ids'] = count($child_ids > 1) ? implode('|', $child_ids) : $child_ids;	
		}
		
	}
	
	
	function channel_module_create_pagination($channel_object)
	{
		if ($this->_is_search() === FALSE && isset($this->EE->config->_global_vars['structure_pagination_segment']))
		{
			$this->EE->uri->uri_string = $this->EE->uri->uri_string . "/P" . $this->EE->config->_global_vars['structure_pagination_page'];
			$channel_object->p_page = $this->EE->config->_global_vars['structure_pagination_page'];
		}
	}
	
	
	/**
	* wygwam_config hook
	*/
	function wygwam_config($config, $settings)
	{
		// If another extension shares the same hook,
		// we need to get the latest and greatest config
		if ($this->EE->extensions->last_call !== FALSE)
			$config = $this->EE->extensions->last_call;
		
		// get EE's record of site pages
		$site_pages = $this->EE->config->item('site_pages');
		$site_id = $this->EE->config->item('site_id');
			
		$pages = $this->sql->get_data();
		foreach ($pages as $entry_id => $page_data)
		{
			// ignore if EE doesn't have a record of this page
			if ( ! isset($site_pages[$site_id]['uris'][$entry_id])) continue;
			
			// add this page to the config
			$config['link_types']['Structure Pages'][] = array(
				'label' => $page_data['title'],
				'label_depth' => $page_data['depth'],
				'url' => $this->EE->functions->create_page_url($site_pages[$site_id]['url'], $site_pages[$site_id]['uris'][$entry_id])
			);
		}
		
		$listing_channels = $this->sql->get_structure_channels('listing');
		
		if ($listing_channels !== FALSE)
		{		
			foreach ($listing_channels as $channel => $row)
			{
				$entries = $this->sql->get_entry_titles_by_channel($row['channel_id']);
				foreach ($entries as $page_data)
				{
					// ignore if EE doesn't have a record of this page
					if ( ! isset($site_pages[$site_id]['uris'][$page_data['entry_id']])) continue;
				
					$config['link_types']['Structure Listing: ' . $row['channel_title']][] = array(
						'label' => $page_data['title'],
						'label_depth' => 0,
						'url' => $this->EE->functions->create_page_url($site_pages[$site_id]['url'], $site_pages[$site_id]['uris'][$page_data['entry_id']])
					);
				}
			}
		}
		
		return $config;
	}
	
	
	/**
	 * Activate Extension
	 * @return void
	 */
	function activate_extension()
	{		
		$hooks = array(
			'entry_submission_redirect' 		=> 'entry_submission_redirect',
			'cp_member_login'					=> 'cp_member_login',
			'sessions_start'					=> 'sessions_start',
			'channel_module_create_pagination' 	=> 'channel_module_create_pagination',
			'wygwam_config'						=> 'wygwam_config'
			);
			
		foreach ($hooks as $hook => $method)
		{
			$priority = $hook == 'channel_module_create_pagination' ? 9 : 10;
			
			$data = array(
				'class'		=> __CLASS__,
				'method'	=> $method,
				'hook'		=> $hook,
				'settings'	=> '',
				'priority'	=> $priority,
				'version'	=> $this->version,
				'enabled'	=> 'y'
				);
			$this->EE->db->insert('extensions', $data);
		}
		
	}
		
		
	/**
	 * Disable Extension
	 * @return void
	 */
	function disable_extension()
	{
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->delete('extensions');
	}
	
	
	/**
	 * Update Extension
	 * @return 	mixed	void on update / false if none
	 */

	function update_extension($current = FALSE)
	{
		if (! $current || $current == $this->version)
		{
			return FALSE;
		}

		// add pagination and wygwam hooks
		if (version_compare($current, '3.0', '<'))
		{
			$hooks = array('channel_module_create_pagination' => 'channel_module_create_pagination', 'wygwam_config' => 'wygwam_config');

			foreach ($hooks as $hook => $method)
			{
				$data = array('class' => __CLASS__, 'method' => $method, 'hook' => $hook, 'settings' => '', 'priority' => 10, 'version' => $this->version, 'enabled' => 'y');
				$this->EE->db->insert('extensions', $data);
			}
		}
		
		$this->EE->db->where('class', __CLASS__);
		$this->EE->db->update('extensions', array('version' => $this->version));
	}
	
}