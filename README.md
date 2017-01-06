# ACF User Role Field Setting

***This plugin requires Advanced Custom Fields (ACF) Version >= 5.***  
This plugin will not provide any functionality if ACF 5 is not installed and active. This plugin does
not support ACF4 or before as the filters used were not available before ACF5.

This plugin adds a field setting to all field types that allows for the selection of WP User Roles
that should be allowed to manage the value of the field. Fields that the current user does not have
permission to edit are removed from the field group when it's fields are loaded.

## Additional Security for All ACF Fields

***Please note that this plugin does not hide the fields, it completely removes the fields!*** However,
this removal does not effect any values that are already saved in the field. This plugin does not effect the operation of any of the other ACF function like get_field(), the_field(), get_fields(), get_field_ojects(), etc. If you find any of the template functions are being effected by this plugin please 
[open a new issue](https://github.com/Hube2/acf-user-role-field-setting/issues).

***Why remove fields?*** Using an ACF load_field filter it is possible disable a field or to make a field
read only. It is also possible to add CSS in the admin head to hide a field base on the user. However,
this is not a secure way to keep people that should not be allowed to edit fields from editing them.
Anyone with limited HTML knowledge can easily inspect and alter the HTML and CSS for a page and make
the fields visible and editable. If someone is not supposed to be able to modify a value then that value
should not be present on the page in the first place. This is the only secure way to ensure that it
cannot be edited.

***$_POST['acf'] Input Checking*** All fields submitted are checked agains the user role field
setting. Any submitted value that a user should not be able to edit is removed from the submitted
values. This is done before acf saves any values. Why? This prevents the possibility of someone
modifying a forms HTML to add input fields that they are not supposed to be able to edit. A field
that a user should not be able to edit could only be submitted by aomeone attempting to hack the
form.

***Caution:*** It is possible to set a field so that it can never be edited by anyone. This can be done
in several ways. The first example is easy, simply do not select any user role that can edit the field.
The second example is less obvious. If you have a field that appears on "Posts" and you set this field
so that only "Subscribers" can edit the field, since subscribers cannot by default edit a post in the
admin, then you have effectively made this field uneditable. However, allowing only subscribers to edit
a field could be useful on a front end form where you want extra field for subscribers that are not
available to visitors that are not logged in.

### Exclude Field Types
Added in version 1.1.0
The Tab and Clone fields have been excluded from having the user role setting added to them. 

Most of the time it would not make sense for a tab field, unless all of the fields in the tab were set 
the same, in other words, removing a tab should remove all the fields in that tab. That's not something 
that I can do at this point.

I'm not sure about the clone field, I haven't worked with it much. You can test it out if you want.

I also added a filter so that you can adjust the types of fields that are excluded
```
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
```

#### Automatic Updates
Github updater support has been removed. This plugin has been published to WordPress.Org here
https://wordpress.org/plugins/user-role-field-setting-for-acf/. If you are having problems updating please
try installing from there. 

#### Remove Nag
You may notice that I've started adding a little nag to my plugins. It's just a box on some pages that lists my
plugins that you're using with a request do consider making a donation for using them. If you want to disable them
add the following filter to your functions.php file.
```
add_filter('remove_hube2_nag', '__return_true');
```