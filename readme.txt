=== ACF User Role Field Setting ===
Contributors: Hube2
Tags: acf, advanced custom fields, user role, setting, security, multisite
Requires at least: 4.0
Tested up to: 4.7 
Stable tag: 2.1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

User Role Setting for ACF


== Description ==

This is an add on plugin for Advanced Custom Fields (ACF) Version 5.

***This plugin will not provide any functionality if ACF5 is not installed.***

This plugin adds a field setting to all field types so that user roles allowed to edit the field can
be selected. Only those roles selected for the field will be able to edit the field.

This adds additional security to fields. This plugin does not simply hide field, it removes them
completely from the field group. Using standard ACF filters is is possible to set many field types to
readonly or disabled. It is even possible by adding custom CSS to hide fields based on the current
user's role. However, this is not a secure way to prevent those that should not be allowed to edit
a field from editing them if they really want to. Anyone with limited HTML knowledge can easily instpect
the HTML of a page and alter the html and css to make the fields visible and editable. The only secure
way to prevent the fields from being edited is to not have them present in the form to begin with.

***$_POST Filtering:*** In addition to removing the fields from field groups so that they can not be
edited this plugin also checks submitted values to see if the current user is allowed to manage the
fields submitted before allowing ACF to save any values to the database.


== Installation ==

Install like any other plugin

== Screenshots ==

1. Field setting on example field


== Frequently Asked Questions ==

Nothing yet

== Other Notes ==

== Github Repository ==

This plugin is also on GitHub 
[https://github.com/Hube2/acf-user-role-field-setting](https://github.com/Hube2/acf-user-role-field-setting)

== Excluded Field Types ==

Most of the time it would not make sense for a tab field, unless all of the fields in the tab were set the same, in other words, removing a tab should remove all the fields in that tab. That's not something that I can do at this point.

I'm not sure about the clone field, I haven't worked with it much. You can test it out if you want.

I also added a filter so that you can adjust the types of fields that are excluded. Here is an example
`
<?php 
  
  add_filter('acf/user_role_setting/exclude_field_types', 'user_role_setting_excluded_field_types');
  function user_role_setting_excluded_field_types($exclude) {
    /* 
      $exclude holds an array of field types to exclude from adding user role settings
      default value:
      $exclude = array('tab' => 'tab', 'clone' => 'clone');
    */
  
    // remove tab field from exclude
    if (isset($exclude['tab'])) {
      unset($exclude['tab']);
    }
  
    // add message field to exclude
    $exclude['message'] = 'message';
  
    return $exclude;
  }
  
?>
`

== Remove Nag ==

If you would like to remove my little nag that appears on some admin pages add the following to your functions.php file
`
add_filter('remove_hube2_nag', '__return_true');
`

== Changelog ==

= 2.1.4 =
* removed github updater support

= 2.1.3 =
* initial release as a WordPress Plugin

