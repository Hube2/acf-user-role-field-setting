=== ACF User Role Field Setting ===
Contributors: Hube2
Tags: acf, advanced custom fields, user role, setting, security, multisite
Requires at least: 4.0
Tested up to: 5.5
Stable tag: 3.0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

User Role Setting for ACF


== Description ==

This is an add on plugin for Advanced Custom Fields (ACF) Version 5.

***This plugin will not provide any functionality if ACF5 is not installed.***

This plugin adds a field setting to all field types so that user roles allowed to edit the field can
be selected. Only those roles selected for the field will be able to edit the field.


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

I have also removed support for repeater, group and flexible content fields because I cannot preserver the values of these fields if they are not editable in the ACF interface. Please note that I do not know what the effect of allowing user role settings on these fields will be.

There is a filter so that you can adjust the types of fields that are excluded. Here is an example
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

== Changelog ==

= 3.0.2 =
* Changed super admin test permission from "update_core" to "manage_network"

= 3.0.1 =
* Corrected issue with getting property of non object
* Corrected issues with fields that have array values
* Re-enabled repeaters

= 3.0.0 =
* removed support for ACF < Version 5.5.0
* removed user role setting from repeater, group and flexibe content fields (these can be re-enabled using available filter, however doing so may not be safe)
* corrected issue with repeater sub field reordering
* no longer filtering $_POST to corect repeater reordering issue

= 2.1.15 =
* added composer support
* removed donation nag

= 2.1.14 =
* resolving version # issue with WP SVN

= 2.1.13 =
* corrected issue w/deleting repeater rows when sub fields is removed
* Correct issue w/ACF 5.7.0

= 2.1.12 =
* minor code reorganiztion in prepare_field filter
* other code cleanup
* reverted to acf/save_post for $_POST filtering (corrected in ACF)
* corrected an issue with repeater sub fields when reordered
* PLEASE NOTE THAT VERSION 3 WILL REMOVE SUPPORT FOR ACF < 5.5.0

= 2.1.11 =
* corrected issue - field values not saved when fields set for specifice user roles

= 2.1.10 =
* corrected warning call_user_func_array() expects parameter 1 to be a valid callback

= 2.1.9 =
* changed plugins_loaded funtion to run on after_setup_theme to ensure that if ACF is loaded as part  
or the theme that it is loaded before running
* changed when $_POST filtering runs to deal with changes in ACF >= 5.6;

= 2.1.8 =
* corrected bug w/ACF version >= 5.6.0

= 2.1.7 =
* correct call to undefined function acf_get_setting()

= 2.1.6 =
* corrects issue with setting not appearing during js field initialization cause by 2.1.5 update

= 2.1.5 =
* altered field setting initialization to deal with non-standard ACF add on field types that do not initialize their fields when they are supposed on the correct ACF hooks

= 2.1.4 =
* removed github updater support

= 2.1.3 =
* initial release as a WordPress Plugin

