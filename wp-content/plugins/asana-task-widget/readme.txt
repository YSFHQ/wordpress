=== Plugin Name ===
Contributors: mackerelsky
Tags: asana, widget
Requires at least: 2.8
Tested up to: 3.3.2
Stable tag: 1.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds dashboard widgets for creating tasks in Asana, and for displaying tasks that are due.


== Description ==

This plugin adds a dashboard widget that displays a list of your Asana tasks.  Choose to display one or more of: tasks due today, due this week, assigned for today, or overdue.  You can mark tasks as complete from the plugin, and they will be updated in Asana.  

You can also create Asana tasks from a second dashboard widget.

Please note that you will need an Asana account to use this plugin.

== Installation ==


1. Upload `asana-task-widget.php` to your /wp-content/plugins/ directory
1. Activate the plugin through the Plugins menu
1. Go to your profile page (Users - Your Profile) to configure the plugin

== Changelog ==

= 1.2 =
*Appends project name to task name in the widget (useful if you have multiple tasks with the same name in different projects, and thanks to Omar Gourari for the request)

= 1.1.1 =
*Bug fixes

= 1.1 =
*Added due date field to task creation widget

= 1.0 =
*Moved all options to user profile so plugin can be used on multi-user sites.

= 0.5 =
*Added task creation widget

= 0.4 =
*Added options to choose which kinds of tasks (overdue, due today, due this week and assigned for today) should be displayed in the widget
*Added notification in widget if there were no tasks due in a selected category

= 0.3 =
*Initial version
