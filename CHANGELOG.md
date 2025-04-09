# Changelog

All notable changes to this project will be documented in this file.

## [0.0.2] - 2025-04-09
### Added
- Initial functionality for the plugin. You can now set a custom name for the group tab. 
- Choose what happens when someone clicks the tab. 
    * Shortcode - Display a custom shortcode
    * Redirect - Provide a URL to redirect the user to. 
    * Elementor Pro Saved Section - Choose a saved section to display in the tab. 
- Change Visibility. 
    Which users can see the extension’s navigation tab. Possible values: 'anyone', 'loggedin', 'member', 'mod', 'admin' or 'noone'. ('member', 'mod', 'admin' refer to user’s role in the current group.)
### FIXED
- Removed the requirement for elementor pro to be instlled. If elementor pro is not installed the dropdown to choose that is removed only leaving Shortcode, and Redirect.

## [0.0.1] - YYYY-MM-DD
### Added
- Initial scaffold for the plugin.
