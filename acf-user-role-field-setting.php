<?php 
	
	/* 
		Plugin Name: ACF User Role Field Setting
		Plugin URI: https://github.com/Hube2/acf-user-role-field-setting
		Description: Set user types that should see fields
		Version: 2.0.0
		Author: John A. Huebner II
		Author URI: https://github.com/Hube2/
		GitHub Plugin URI: https://github.com/Hube2/acf-user-role-field-setting
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
		
		public function __construct() {
			add_action('acf/init', array($this, 'init'));
			add_filter('acf/get_fields', array($this, 'get_fields'), 20, 2);
			add_filter('acf/load_field', array($this, 'load_field'));
			//add_action('acf/render_field_settings', array($this, 'render_field_settings'), 1);
			add_filter('jh_plugins_list', array($this, 'meta_box_data'));
			add_action('acf/save_post', array($this, 'save_post'), -1);
		} // end public function __construct
		
		public function save_post($post_id) {
			if (!isset($_POST['acf'])) {
				return;
			}
			if (is_array($_POST['acf'])) {
				$_POST['acf'] = $this->filter_post_values($_POST['acf']);
			}
		} // end public function save_post
		
		private function filter_post_values($input) {
			// this is a recursive function the examinse all posted fields
			// and removes any fields the a user is not supposed to have access to
			$output = array();
			foreach ($input as $index => $value) {
				$keep = true;
				if (substr($index, 0, 6) === 'field_') {
					// check to see if this field can be edited
					$field = get_field_object($index);
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
		
		public function load_field($field) {
			//echo '<pre>'; print_r($field); echo '</pre>';
			return $field;
		} // end public function load_field
		
		public function init() {
			$this->get_roles();
			$this->current_user_roles();
			$this->add_actions();
		} // end public function init
		
		private function add_actions() {
			$exclude = apply_filters('acf/user_role_setting/exclude_field_types', $this->exclude_field_types);
			$sections = acf_get_field_types();
			//echo '<pre>'; print_r($sections); die;
			foreach ($sections as $section) {
				foreach ($section as $type => $label) {
					if (!isset($exclude[$type])) {
						add_action('acf/render_field_settings/type='.$type, array($this, 'render_field_settings'), 1);
					}
				}
			}
		} // end private function add_actions
		
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
			$fields = $this->check_fields($fields);
			return $fields;
		} // end public function get_fields
		
		private function check_fields($fields) {
			// recursive function
			// see if field should be kept
			$keep_fields = array();
			if (count($fields)) {
				foreach ($fields as $field) {
					$keep = false;
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
			} // end if fields
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
			
		public function meta_box_data($plugins=array()) {
			$plugins[] = array(
				'title' => 'ACF User Role Field Setting',
				'screens' => array('acf-field-group', 'edit-acf-field-group'),
				'doc' => 'https://github.com/Hube2/acf-user-role-field-setting'
			);
			return $plugins;
		} // end function meta_box_data
		
	} // end class acf_user_type_field_settings
	
	if (!function_exists('jh_plugins_list_meta_box')) {
		function jh_plugins_list_meta_box() {
			if (apply_filters('remove_hube2_nag', false)) {
				return;
			}
			$plugins = apply_filters('jh_plugins_list', array());
				
			$id = 'plugins-by-john-huebner';
			$title = '<a style="text-decoration: none; font-size: 1em;" href="https://github.com/Hube2" target="_blank">Plugins by John Huebner</a>';
			$callback = 'show_blunt_plugins_list_meta_box';
			$screens = array();
			foreach ($plugins as $plugin) {
				$screens = array_merge($screens, $plugin['screens']);
			}
			$context = 'side';
			$priority = 'low';
			add_meta_box($id, $title, $callback, $screens, $context, $priority);
			
			
		} // end function jh_plugins_list_meta_box
		add_action('add_meta_boxes', 'jh_plugins_list_meta_box');
			
		function show_blunt_plugins_list_meta_box() {
			$plugins = apply_filters('jh_plugins_list', array());
			?>
				<p style="margin-bottom: 0;">Thank you for using my plugins</p>
				<ul style="margin-top: 0; margin-left: 1em;">
					<?php 
						foreach ($plugins as $plugin) {
							?>
								<li style="list-style-type: disc; list-style-position:">
									<?php 
										echo $plugin['title'];
										if ($plugin['doc']) {
											?> <a href="<?php echo $plugin['doc']; ?>" target="_blank">Documentation</a><?php 
										}
									?>
								</li>
							<?php 
						}
					?>
				</ul>
				<p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=hube02%40earthlink%2enet&lc=US&item_name=Donation%20for%20WP%20Plugins%20I%20Use&no_note=0&cn=Add%20special%20instructions%20to%20the%20seller%3a&no_shipping=1&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted" target="_blank">Please consider making a small donation.</a></p><?php 
		}
	} // end if !function_exists
	
?>