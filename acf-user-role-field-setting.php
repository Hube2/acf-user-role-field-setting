<?php 
	
	/* 
		Plugin Name: ACF User Role Field Setting
		Plugin URI: https://wordpress.org/plugins/user-role-field-setting-for-acf/
		Description: Set user types that should be allowed to edit fields
		Version: 2.1.15
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
			'tab' => 'tab',
			'clone' => 'clone'
		);
		private $removed = array();
		
		public function __construct() {
			add_action('init', array($this, 'init'), 1);
			add_action('acf/init', array($this, 'add_actions'));
			add_action('acf/save_post', array($this, 'save_post'), -1);
			add_action('after_setup_theme', array($this, 'after_setup_theme'));
			//add_filter('acf/get_field_types', array($this, 'add_actions'), 20, 1);
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
			} else {
				// if < 5.5.0 user the acf/get_fields hook to remove fields
				add_filter('acf/get_fields', array($this, 'get_fields'), 20, 2);
			}
		} // end public function after_setup_theme
		
		public function prepare_field($field) {
			$return_field = false;
			$exclude = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			if (in_array($field['type'], $exclude)) {
				$return_field = true;
			}
			if (isset($field['user_roles'])) {
				if (!empty($field['user_roles']) && is_array($field['user_roles'])) {
					foreach ($field['user_roles'] as $role) {
						if ($role == 'all' || in_array($role, $this->current_user)) {
							$return_field = true;
						}
					}
				} else {
					// no user roles have been selected for this field
					// it will never be displayed, this is probably an error
				}
			} else {
				// user roles not set for this field
				// this field was created before this plugin was in use
				// or user roles is otherwise disabled for this field
				$return_field = true;
			}
			//echo '<pre>'; print_r($field); echo '</pre>';
			if ($return_field) {
				return $field;
			}
			// [
			preg_match('/(\[[^\]]+\])$/', $field['name'], $matches);
			$name = $matches[1];
			if (!in_array($name, $this->removed)) {
				$this->removed[] = $name;
				?><input type="hidden" name="acf_removed<?php echo $name; ?>" value="<?php 
						echo $field['name']; ?>" /><?php 
			}
			return false;
		} // end public function prepare_field
		
		public function save_post($post_id=false, $values=array()) {
			if (!isset($_POST['acf'])) {
				return;
			}
			$this->exclude_field_types = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			if (is_array($_POST['acf'])) {
				$_POST['acf'] = $this->filter_post_values($_POST['acf']);
			}
			if (isset($_POST['acf_removed'])) {
				$this->get_removed($post_id);
				$_POST['acf'] = $this->array_merge_recursive_distinct($_POST['acf'], $_POST['acf_removed']);
			}
		} // end public function save_post
		
		private function get_removed($post_id) {
			foreach ($_POST['acf_removed'] as $field_key => $value) {
				$_POST['acf_removed'][$field_key] = get_field($field_key, $post_id, false);
			}
		} // end private function get_removed
		
		private function array_merge_recursive_distinct(array &$array1, array &$array2) {
			$merged = $array1;
			foreach ($array2 as $key => &$value) {
				if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
					$merged[$key] = $this->array_merge_recursive_distinct($merged[$key], $value);
				} else {
					// do not overwrite value in first array
					if (!isset($merged[$key])) {
						$merged[$key] = $value;
					}
				}
			}
			return $merged;
		} // end private function array_merge_recursive_distinct
		
		private function filter_post_values($input) {
			// this is a recursive function the examinse all posted fields
			// and removes any fields the a user is not supposed to have access to
			$output = array();
			foreach ($input as $index => $value) {
				$keep = true;
				if (substr($index, 0, 6) === 'field_') {
					// check to see if this field can be edited
					$field = get_field_object($index);
					if (in_array($field['type'], $this->exclude_field_types)) {
						$keep = true;
					} else {
						if (isset($field['user_roles'])) {
							$keep = false;
							if (!empty($field['user_roles']) && is_array($field['user_roles'])) {
								foreach ($field['user_roles'] as $role) {
									if ($role == 'all' || in_array($role, $this->current_user)) {
										$keep = true;
										// keepiing, no point in continuing to other rolese
										break;
									}
								} // end foreach
							} // end if settings is array
						} // end if setting exists
					} // end if excluded field type else
				} // end if field_
				if ($keep) {
					if (is_array($value)) {
						// recurse nested array
						$output[$index] = $this->filter_post_values($value);
					} else {
						$output[$index] = $value;
					}
				} // end if keep
			} // end foreach input
			return $output;
		} // end private function filter_post_values
		
		public function init() {
			$this->get_roles();
			$this->current_user_roles();
		} // end public function init
		
		public function add_actions() {
			$exclude = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			if (!function_exists('acf_get_setting')) {
				return;
			}
			$acf_version = acf_get_setting('version');
			$sections = acf_get_field_types();
			if ((version_compare($acf_version, '5.5.0', '<') || version_compare($acf_version, '5.6.0', '>=')) && version_compare($acf_version, '5.7.0', '<')) {
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
			if (is_multisite() && current_user_can('update_core')) {
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
		
		public function get_fields($fields, $parent) {
			global $post;
			if (is_object($post) && isset($post->ID) &&
					(get_post_type($post->ID) == 'acf-field-group') ||
					(get_post_type($post->ID) == 'acf-field')) {
				// do not alter when editing field or field group
				return $fields;
			}
			$this->exclude_field_types = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			$fields = $this->check_fields($fields);
			return $fields;
		} // end public function get_fields
		
		private function check_fields($fields) {
			// recursive function
			// see if field should be kept
			$keep_fields = array();
			if (is_array($fields) && count($fields)) {
				foreach ($fields as $field) {
					$keep = false;
					if (in_array($field['type'], $this->exclude_field_types)) {
						$keep = true;
					} else {
						if (isset($field['user_roles'])) {
							if (!empty($field['user_roles']) && is_array($field['user_roles'])) {
								foreach ($field['user_roles'] as $role) {
									if ($role == 'all' || in_array($role, $this->current_user)) {
										$keep = true;
										// already keeping, no point in continuing to check
										break;
									}
								}
							}
						} else {
							// field setting is not set
							// this field was created before this plugin was in use
							// or this field is not effected, it could be a "layout"
							// there is currently no way to add field settings to
							// layouts in ACF
							// assume 'all'
							$keep = true;
						}
					} // end if excluded type else
					if ($keep) {
						$sub_fields = false;
						if (isset($field['layouts'])) {
							$sub_fields = 'layouts';
						}
						if (isset($field['sub_fields'])) {
							$sub_fields = 'sub_fields';
						}
						if ($sub_fields) {
							// rucurse sub fields
							$field[$sub_fields] = $this->check_fields($field[$sub_fields]);
						}
						$keep_fields[] = $field;
					}
				} // end foreach field
			} else {
				return $fields;
			}
			return $keep_fields;
		} // end private function check_fields
		
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