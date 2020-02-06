<?php 
	
	/* 
		Plugin Name: ACF User Role Field Setting
		Plugin URI: https://wordpress.org/plugins/user-role-field-setting-for-acf/
		Description: Set user types that should be allowed to edit fields
		Version: 3.0.2
		Author: John A. Huebner II
		Author URI: https://github.com/Hube2/
		License: GPL
	*/
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	new acf_user_role_field_setting();
	
	class acf_user_role_field_setting {
		
		private $choices = array();
		private $current_user = array();
		private $exclude_field_types = array(
			'tab' => 'tab'
		);
		
		public function __construct() {
			add_action('init', array($this, 'init'), 1);
			add_action('acf/init', array($this, 'add_actions'));
			add_action('after_setup_theme', array($this, 'after_setup_theme'));
		} // end public function __construct
		
		public function after_setup_theme() {
			// check the ACF version
			// if >= 5.5.0 use the acf/prepare_field hook to remove fields
			if (!function_exists('acf_get_setting')) {
				// acf is not installed/active
				return;
			}
			$acf_version = acf_get_setting('version');
			if (version_compare($acf_version, '5.5.0', '>=')) {
				add_filter('acf/prepare_field', array($this, 'prepare_field'), 99);
			}
		} // end public function after_setup_theme
		
		private function user_can_edit($field) {
			$exclude = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			if (in_array($field['type'], $exclude)) {
				return true;
			}
			if (isset($field['user_roles'])) {
				if (!empty($field['user_roles']) && is_array($field['user_roles'])) {
					foreach ($field['user_roles'] as $role) {
						if ($role == 'all' || in_array($role, $this->current_user)) {
							return true;
						}
					}
				} else {
					// no user roles have been selected for this field
					// it will never be displayed, this is probably an error
					return false;
				}
			} else {
				// user roles not set for this field
				// this field was created before this plugin was in use
				// or user roles is otherwise disabled for this field
				return true;
			}
			return false;
		} // end private function user_can_edit
		
		public function prepare_field($field) {
			global $post;
			if ($post) {
				$post_type = get_post_type($post->ID);
				if ($post_type == 'acf-field' || $post_type == 'acf-field-group') {
					return $field;
				}
			}
			//echo '<pre>'; print_r($field); echo '</pre>';
			if ($this->user_can_edit($field)) {
				return $field;
			}
			$this->output_hidden_fields($field['name'], $field['value']);
			return false;
		} // end public function prepare_field
		
		private function output_hidden_fields($field_name, $value) {
			if (is_array($value)) {
				foreach ($value as $i => $v) {
					$this->output_hidden_fields($field_name.'['.$i.']', $v);
				}
			} else {
				?><input type="hidden" name="<?php echo $field_name; ?>" value="<?php echo $value; ?>" /><?php 
			}
		} // end private function output_hidden_fields
		
		public function init() {
			$this->get_roles();
			$this->current_user_roles();
		} // end public function init
		
		public function add_actions() {
			if (!function_exists('acf_get_setting')) {
				return;
			}
			$acf_version = acf_get_setting('version');
			if (version_compare($acf_version, '5.5.0', '<')) {
				return;
			}
			$exclude = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			$sections = acf_get_field_types();
			if (version_compare($acf_version, '5.6.0', '>=') && version_compare($acf_version, '5.7.0', '<')) {
				foreach ($sections as $section) {
					foreach ($section as $type => $label) {
						if (!isset($exclude[$type])) {
							add_action('acf/render_field_settings/type='.$type, array($this, 'render_field_settings'), 1);
						}
					}
				}
			} else {
				// >= 5.5.0 || < 5.6.0
				foreach ($sections as $type => $settings) {
					if (!isset($exclude[$type])) {
						add_action('acf/render_field_settings/type='.$type, array($this, 'render_field_settings'), 1);
					}
				}
			}
		} // end public function add_actions
		
		private function current_user_roles() {
			global $current_user;
			if (is_object($current_user) && isset($current_user->roles)) {
				$this->current_user = $current_user->roles;
			}
			if (is_multisite() && current_user_can('manage_network')) {
				$this->current_user[] = 'super_admin';
			}
		} // end private function current_user_roles
		
		private function get_roles() {
			if (count($this->choices)) {
				return;
			}
			global $wp_roles;
			$choices = array('all' => 'All');
			if (is_multisite()) {
				$choices['super_admin'] = 'Super Admin';
			}
			foreach ($wp_roles->roles as $role => $settings) {
				$choices[$role] = $settings['name'];
			}
			$this->choices = $choices;
		} // end private function get_roles
		
		public function render_field_settings($field) {
			$args = array(
				'type' => 'checkbox',
				'label' => 'User Roles',
				'name' => 'user_roles',
				'instructions'	=> 'Select the User Roles that are allowed to view and edit this field.'.
				                   ' This field will be removed for any user type not selected.'.
													 ' <strong><em>If nothing is selected then this field will never be'.
													 ' included in the field group.</em></strong>',
				'required' => 0,
				'default_value' => array('all'),
				'choices' => $this->choices,
				'layout' => 'horizontal'
			);
			acf_render_field_setting($field, $args, false);
		} // end public function render_field_settings
		
	} // end class acf_user_type_field_settings
	
?>