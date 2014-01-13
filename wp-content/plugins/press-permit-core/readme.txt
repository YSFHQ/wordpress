=== Press Permit Core ===
Contributors: kevinB
Donate Link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=JWZVFUDLLYQBA
Tags: restrict, access, permissions, cms, user, private, category, pages, privacy, capabilities, role, scoper
License: GPLv3
Requires at least: 3.4
Tested up to: 3.8
Stable tag: 2.1.39

Advanced yet accessible content permissions. Give users or groups type-specific roles. Enable or block access for specific posts or terms.

== Description ==

[Press Permit](http://presspermit.com) is an advanced content permissions system. It is derived from Role Scoper, but with extensive improvements in versatility, performance and user-friendliness. 

Core Features include:

  * New "Roles and Exceptions" model is closely integrated with the WP capability system
  * Assign supplemental roles and exceptions for custom post types [youtube http://www.youtube.com/watch?v=v7jTkgmjHrw&rel=0&hd=1]
  
  * For any user, group or WP role, customize reading access by specifying "also these", "not these" or "only these" posts or terms.
  * Control reading access to specified categories [youtube http://www.youtube.com/watch?v=SMnybRf5neY&rel=0&hd=1] 

  * Post and term edit screens get a straightforward and uncluttered UI to "enable" or "block" users, roles or groups
  * Permission Groups integrate with [Eyes Only User Access Shortcodes](http://wordpress.org/plugins/eyes-only-user-access-shortcode/) for conditional display of content blocks within a post

Pro [extensions](http://presspermit.com/extensions) are [available](http://presspermit.com/purchase) for [additional access control and features](http://www.youtube.com/playlist?list=PLyelWaWwt1HxuwrZDRBO_c70Tm8A7lfb3):
	
  * Customize editing access for specific posts or terms - [video](http://presspermit.com/tutorial/page-editing-exceptions)
  * Limit category/term assignment and page parent selection - [video](http://presspermit.com/tutorial/limit-posting-category-parent)
  * Define your own custom post statuses - [video](http://presspermit.com/tutorial/custom-post-visibility)
  * Assign supplemental roles for custom post statuses
  * Date-limited membership in Permissions Groups - [video](http://presspermit.com/tutorial/date-limited-group-membership)
  * Moderation statuses control multi-step moderation - [video](http://presspermit.com/tutorial/multi-step-moderation)
  * Hidden Content Teaser - [video](http://presspermit.com/tutorial/privacy-statuses-teaser)
  * Edit Flow integration - [video](http://presspermit.com/tutorial/edit-flow-integration)
  * Revisionary and Post Forking: regulate moderated editing of published content - [video](http://presspermit.com/tutorial/published-content-revision)
  * BuddyPress Permission Groups for additional reading or editing access - [video](http://presspermit.com/tutorial/buddypress-content-permissions)
  * Circles: block access to content not authored by a fellow group member - [video](http://presspermit.com/tutorial/buddypress-content-permissions)
  * File URL Filter: regulate direct access to uploaded files - [video](http://presspermit.com/tutorial/regulate-file-url-access)
  * Customize bbPress forum permissions
  * WPML integration: mirror post/term permissions to translations
  * Import groups, roles and restrictions from Role Scoper
	
== Upgrade Notice ==
	
= 2.1.14 =
Initial production release

== Changelog ==

= 2.1.39 - 9 Jan 2014 =
* Fixed : Editing permissions were not propagated to newly created pages under some configurations on WP 3.8 (also requires PP Collaborative Editing 2.1.18)
* Fixed : User search ajax submission with blank search box returned users by creation date with oldest first (should be newest first)
* Fixed : Work around PHP Bug #52339 - SPL autoloader breaks class_exists()
* Fixed : PHP Notices on when updating extension plugins with strict error reporting

= 2.1.38 - 18 Dec 2013 =
* Compat : WP 3.8 - styling corrections on Edit Permissions, Settings screens
* Fixed : Post access blocked per-Role by a Universal Taxonomy Exception could not be enabled per-user or per-group by another Universal Taxonomy Exception
* Fixed : When a page is re-saved to a different parent, exceptions propagated to subpages from the previous parent were not cleared
* Fixed : When a page is re-saved to a different parent, exceptions from the new parent were not assigned to subpages
* Fixed : Pro - Customization of role capabilities for stock WP roles was not reflected in supplemental role assignment (since 2.1.33)
* Fixed : Pro - Editing exceptions remained partially active even if corresponding pro extensions disabled
* Feature : Pro - Support list_all_posts, list_all_pages, etc. capabilities (also requires PP Collaborative Editing 2.1.16)
* Change : Additional explanatory captions on Edit Permissions screen
* Change : Link on Edit Permissions screen to reload with propagated exceptions displayed
* Fixed : PHP Notices for non-static function definitions

= 2.1.37 - 14 Dec 2013 =
* Fixed : Pro - Assignment of Tags and other hierarchical taxonomies was not filtered based on "Only these" or "Not these" exceptions (also requires PP Collaborative Editing 2.1.15)
* Fixed : PHP warning when uploading configuration data from a network installation
* Doc : Corrected code comment for exceptions array in pp-user.php

= 2.1.36 - 11 Dec 2013 =
* Fixed : After saving changes to Universal Category Exceptions, redirect was back to Edit Category (Post Exceptions)
* Change : Edit Category screen - additional inline note regarding Universal Category Exceptions
* Fixed : Pro - Nav Menu Management exceptions were not not applied correctly in some configurations (also requires PP Collaborative Editing 2.1.14)
* Fixed : Pro - Term Management and Association exceptions assigned via Edit Category screen were not stored correctly (also requires PP Collaborative Editing 2.1.14)
* Fixed : Pro - Editors excluded from managing specific categories could still edit them via direct URL
* Fixed : Pro - On Edit Category screen, exceptions metabox for term management was incorrectly captioned as "Post Management"
* Change : Pro - On Edit Permissions screen, simplify captioning for currently stored term management and association exceptions

= 2.1.35 - 6 Dec 2013 =
* Fixed : When a Page exception was changed from "also subpages" to "selected only", subpage exceptions were retained but became inaccessable on Edit User/Group screen
* DB : Update to 2.1.35 exposes propagated exceptions whose base exception is deleted or no longer marked for propagation (and logs them to option ppc_exposed_eitem_orphans)
* Fixed : Page exceptions were propagated to attachments
* DB : Update to 2.1.35 deletes invalid / redundant attachment exceptions
* DB : Update to 2.1.35 exposes propagated attachment Edit Attachment exceptions
* DB : Update to 2.1.35 exposes propagated attachment "Read Attachment - Only These" exceptions

= 2.1.34 - 2 Dec 2013 =
* Fixed : With PP File URL Filter active, attachments to private posts were not visible unless user had editing capabilities (since 2.1.30)

= 2.1.33 - 15 Nov 2013 =
* Fixed : Improper filtering of get_tags() function
* Change : By default, Post Tag is enabled as a filtered taxonomy. Previously, it was default disabled yet front end tag filtering was implicitly forced.
* Change : If PP_GROUP_RESTRICTIONS constant is defined, allow the Editing Exceptions metabox on Edit Post screen to block Groups
* Change : If PP File URL Filter is not active, Reading Exceptions metabox on Edit Media screen displays notice about direct file access
* Change : Display warning if a supplemental role assignment will use default capabilities due to invalid customization of the role definition 
* Change : Include PP Group Membership in Permissions > Settings > Help > configuration data upload by default
* Fixed : Database error if external code calls pp_get_groups_for_user() with a metagroup_type argument
* Fixed : Fatal error on Permissions > Settings > Help > configuration data upload if RS/PP import data enabled and PP Import version number was deleted from database

= 2.1.32 - 8 Nov 2013 =
* Fixed : Exceptions could not be assigned on Edit Post screen if post type name contains a dash
* Change : If PP_GROUP_RESTRICTIONS constant is defined, allow Post editing Exceptions with "Not these" or "Only these" adjustment to be assigned to custom groups
* Change : Revised extension installation/update code to more closely mirror the core WP process; may resolve some rare installation errors

= 2.1.31 - 30 Oct 2013 =
* Fixed : Terms were not included in get_terms() output based on user's access to private posts (since 2.1.28)

= 2.1.30 - 29 Oct 2013 =
* Compat : WP 3.7 - Non-administrators could not access revisions viewer for unpublished posts
* Compat : WP 3.7 - PHP warnings for undefined capability properties
* Fixed : wp_list_pages() was not filtered if arguments included nonzero child_of and depth=1 arguments

= 2.1.29 - 25 Oct 2013 =
* Fixed : CMS Tree Page View - could not expand page tree (since 2.1.28)

= 2.1.28 - 24 Oct 2013 =
* Fixed : Propagating Category Exceptions were not correctly assigned to subcategories
* Fixed : Universal Category Exceptions (for all post types) were not applied correctly in some configurations
* Fixed : Category widget (and other get_terms output) was inconsistent with post reading access in some configurations
* Compat : JC Submenu and other plugin / theme code which requires get_pages() or get_terms() to order subpages or subcategories immediately after their parent
* Fixed : Post types not enabled for PP Filtering were stripped out of Nav Menus on the front end

= 2.1.27 - 23 Oct 2013 =
* Fixed : Nav Menu items (front end display) were not filtered

= 2.1.26 - 22 Oct 2013 =
* Change : Allow page reading exceptions to be assigned for the All or Anonymous metagroup, but display a warning regarding best practice

= 2.1.25 - 22 Oct 2013 =
* Feature : Filter WP image galleries based on attachment reading exceptions
* Feature : Remove unreadable posts from Nav Menus (previously required PP Collaborative Editing extension)
* Fixed : Permission Groups was inappropriately displayed as an available Post Type when on "Add Exceptions" tab of Edit Permissions screen
* Compat : Advanced Custom Fields - don't filter ajax queries
* API : New filter 'pp_exception_type' for use in applying exceptions to externally defined data sources

= 2.1.24 - 15 Oct 2013 =
* Compat : Eyes Only User Access Shortcode (requires v 1.6)
* Change : Allow post reading exceptions to be assigned for the All or Anonymous metagroup, but display a warning regarding best practice
* Fixed : Cannot add Permission Group membership via Edit User screen if that membership was previously expired by a PP Membership date limit but PP Membership plugin is now inactive

= 2.1.23 - 28 Sep 2013 =
* Fixed : Category/Term exceptions to grant additional access did not affect term listings if parent term was inaccessible
* Fixed : Add Exceptions UI on Edit Permissions screen inappropriately included "n/a" as a Post Type under some conditions
* Fixed : Several PHP Strict Notices, mostly for non-static functions

= 2.1.22 - 25 Sep 2013 =
* Feature : Bulk-assign roles or exceptions to multiple users (link on Permissions > Users screen)
* Fixed : Terms were not included in get_terms() output based on user's access to private posts if terms counts are not shown, or if taxonomy is hierarchical and there are no subterms
* Fixed : IE styling error on Edit Permissions screen
* Lang : Added .pot file
* Lang : Updated .po file

= 2.1.21 - 23 Sep 2013 =
* Fixed : Plugins screen indicated update available for Press Permit Core even if current version was installed
* Fixed : Automatic update for PP Core was not available from Plugins screen (though version update was flagged and could be installed from the plugin info popup or WordPress Updates screen)
* Fixed : Non-functional "update now" link for PP Core on Permissions > Settings > Install screen (though plugin could be updated through version details popup)

= 2.1.20 - 20 Sep 2013 =
* Fixed : Could not update plugin from Plugins screen without support key activation (although updates were available from Permissions > Settings > Install since 2.1.15)
* Change : Get PP Core updates from wordpress.org

= 2.1.19 - 20 Sep 2013 =
* Fixed : Exceptions metaboxes on Term Edit screen was not sized correctly
* Fixed : If exceptions were deleted on Edit Permissions screen, corresponding post/term edit links remained displayed
* Fixed : Prevent display of roles / exceptions stored for a custom status if associated extension plugins are inactive
* Compat : All in One Event Calendar - non-Administrators could not navigate or filter calendar
* API : New filters 'pp_ajax_post_types' and 'pp_ajax_required_operation' for disambiguation of third party Ajax queries
* Compat : Shopp and other plugins which call $wp_query->get_queried_object manually caused hierarchical post types to be filtered correctly
* Compat : bbPress (when PP Collaborative Editing and PP Compatibility extensions activated)
* Change : On Settings > Install, restore previous layout for extensions lists
* Lang : updated .po file

= 2.1.18 - 18 Sep 2013 =
* Fixed : PP filtering of taxonomies could not be enabled/disabled on Permissions > Settings > Core, if PP Collaborative Editing extension not activated
* Feature : Permissions > Settings > Advanced > Anonymous Users > "Disable all filtering for anonymous users"
* Change : Support PP_ALLOW_UNFILTERED_FRONT constant. When a logged user has pp_unfiltered capability, suppresses the front-end filtering which normally adds readable private posts to get_pages listing, post count, etc.
* Change : Add error message string related to new PP Pro 3-site package  
* Change : Improved layout of Support Key and Extensions sections on Permissions > Settings > Install
* Change : Improved layout of PP Capabilities section on Permissions > Settings > Advanced
* Change : Added margin between change log entries!

= 2.1.17 - 17 Sep 2013 =
* Fixed : On Edit Post screen, stored exceptions could not be changed from Enabled to Blocked (or vice versa) without first saving to default
* Change : Better styling for scrollbars in exception metaboxes on Edit Post screen
* Fixed : Email notification for comments was blocked under some configurations
* Fixed : User search - prefixing user search entry with a space did not cause results to be listed alphabetically (since 2.1.15)
* Change : Support constant PP_SUPPRESS_PRIVATE_PAGES to prevent get_pages() from including them
* Change : Support required_operation argument in query_posts() call
* Change : Removed debug comments and dead code from admin/plugin-update_pp.php
* API : 'pp_skip_the_terms_filtering' filter, used by PP Content Teaser extension

= 2.1.16 - 12 Sep 2013 =
* Fixed : Edit User/Group Permissions - exceptions with Post Type of "(all)" were not stored correctly
* Fixed : On Edit User screen, extra "Custom User Permissions" box with invalid link

= 2.1.15 - 11 Sep 2013 =
* Feature : Permissions > Settings > Advanced > Misc > "User Search: Filter by WP role" - adds a role dropdown below user search button
* Change : Support PP Core updates without support key activation

= 2.1.14 - 9/9 2013 =
* Initial production release
* Change : Info link on Install tab for screencast links and other PP Pro promotional info if support key inactive or expired
* Lang : updated .po file

== Installation ==

Press Permit Core can be installed automatically via Plugins > Add New in your wp-admin panel.

= To install manually instead: =

1. Upload press-permit-core.zip to the /wp-content/plugins/ directory
1. Extract the zip file. After extraction, you should have a "press-permit-core" folder in the plugins folder
1. Activate the plugin through the 'Plugins' menu in WordPress

After plugin activation, go to Permissions > Settings > Core. Select the post types and taxonomies for which permissions should be customized, then click the Update button.

Permissions can be modified from post edit screens, term edit screens, or the plugin's Edit Permissions screens. The Edit Permissions screens are linked from Users, User Profile, and Permission Groups.

== Frequently Asked Questions ==

= How does Press Permit compare to Capability Manager Enhanced, User Role Editor and other role editor plugins? =

Press Permit's functionality is different from and complementary to a basic role editor / user management plugin.  In terms of permissions, those plugins' primary function is to alter WordPress' definition of the capabilities included in each role.  In other words, they expose lots of knobs for the permissions control which WordPress innately supports. That's a valuable task, and in many cases will be all the role customization you need.  Since WP role definitions are stored in the main WordPress database, they remain even said plugin is deactivated. [Capability Manager Enhanced](http://wordpress.org/plugins/capability-manager-enhanced) is a WordPress role editor designed for integration with Press Permit.

Press Permit can assist you in turning the site-wide capability knobs for desired post types. But it also supercharges your permissions engine. Press Permit it is particularly useful when you want to customize access to a specific post, category or term.  Extension plugins add collaborative editing control, file filtering and other features which are not otherwise possible. The plugin will work with your WP roles as a starting point, whether customized by a role editor or not.  Users of the PP Collaborative Editing extension can (after activating advanced settings) navigate to Permissions > Settings > Role Usage to see (or modify) how Press Permit is using your WP role definitions. Press Permit's modifications remain only while it stays active.


= What extra access control would PP Pro give me? =

For a detailed comparison, see the [RS/PP Feature Grid](http://presspermit.com/pp-rs-feature-grid). Here are some highlights:

[youtube http://www.youtube.com/watch?v=0yOEBD8VE9c&rel=0&hd=1]
Customize editing permissions for specific posts.

&nbsp;

[youtube http://www.youtube.com/watch?v=QqvtxrqLPwY&rel=0&hd=1]
Control which categories or terms users can post to.

&nbsp;

[youtube http://www.youtube.com/watch?v=v8VyKP3rIvk&rel=0&hd=1]
Define custom post statuses for access-controlled multi-step moderation.

&nbsp;

[youtube http://www.youtube.com/watch?v=eeZ6CBC5kQI&rel=0&hd=1]
Edit Flow integration.

&nbsp;

[youtube http://www.youtube.com/watch?v=kVusrdlgSps&rel=0&hd=1]
Prevent inappropriate "back door" access by direct file url.

&nbsp;

= What about Role Scoper? =

Moving forward, I do not plan any major development of the Role Scoper code base.  That plugin's compatibility with WordPress versions 3.7 and beyond will depend on the extent of changes related WordPress code.  I will consider consulting requests but will encourage migration to Press Permit - a superior platform with a sustainable funding model.

If you encounter issues with Role Scoper and need to migrate to a different solution, [Press Permit Pro](http://presspermit.com) provides access to an import script which can (for most installations) automate the majority of your RS migration.

= Can Press Permit Pro do everything Role Scoper can do? =

[Press Permit Pro](http://presspermit.com) introduces some important new features, including custom post statuses, BuddyPress group role assignments and bbPress compatibility.  For most sites, it is a functional equivalent to Role Scoper, with major improvements in UI and performance. A few of Role Scoper's more obscure features are <strong>not</strong> currently provided by PP Pro:

  * HTTP Authentication for feeds
  * Supplemental roles and restrictions for links defined in wp-admin/link-manager.php
  * Customization of NextGEN Gallery editing permissions
  * Group membership requests and recommendations (but supplemental roles can be assigned to BuddyPress groups, inheriting any membership control)
  * Role assignment for limited content date range (but membership in custom-defined permission groups can be date-limited)

For a detailed comparison, see the [RS/PP Feature Grid](http://presspermit.com/pp-rs-feature-grid).

If you have a feature request, the plugin author may be available for custom consulting.

= Can I import settings from Role Scoper? =

Yes. [Press Permit Pro](http://presspermit.com) provides access to the PP Import extension.  This script can import the most Role Scoper groups, roles, restrictions and options.  Some manual followup may be required for some configurations.

= Is Press Permit an out-of-the-box membership solution? =

No, but it can potentially be used in conjunction with an e-commerce or membership plugin. If you have a way to sell users into a WordPress role or BuddyPress group, Press Permit can grant access based on that membership.

= Where does Press Permit store its settings?  How can I completely remove it from my database? =

Press Permit creates and uses the following tables: pp_groups, pp_group_members, ppc_roles, ppc_exceptions, ppc_exception_items. Press Permit options stored to the WordPress options table have an option name prefixed with "pp_". Due to the potential damage incurred by accidental deletion, no automatic removal is currently available. You can use a SQL editing tool such as phpMyAdmin to drop the tables and delete the pp options.

== Screenshots ==

1. Edit Page screen: enable or block groups
2. Edit Page screen: enable or block users
3. Edit Page screen: enable or block WP roles
4. Edit Category screen: enable or block groups
5. Permission Groups listing
6. Edit Permission Group (custom group enabled to read specific page)
7. Edit Permission Group (WP role group with supplemental roles)
8. Edit Permission Group (metagroup blocked from reading a category)
9. PP Core Settings
10. PP Advanced Settings
11. PP Editing Settings (with pro extension PP Collaborative Editing)

== Other Notes ==

= Support Key Activation =

Installation and updates of pro extensions, supplied directly from presspermit.com, are available for a specified duration after you activate the support key provided with a Press Permit Pro [purchase](http://presspermit.com/purchase). Single-site, 3-site and developer (25 sites) packages are available.

Simply browse to Permissions > Settings > Support, paste in the key indicated on your Order Receipt, and click the Activate button.

= Localization =

Press Permit's menus, onscreen captions and inline descriptive footnotes can be translated using [poEdit](http://weblogtoolscollection.com/archives/2007/08/27/localizing-a-wordpress-plugin-using-poedit/).  
	
I will gladly include any user-contributed languages!

= Plugin Compatibility Issues =

**Custom WP_Query calls** : Often, conflicts can be resolved by specifying a post_type argument. To prevent improper filtering of front-end ajax calls, pass required_operation=read

**WP Super Cache** : set WPSC option to disable caching for logged users (unless you only use Press Permit to customize editing access).

**WPML Multilingual CMS** : plugin creates a separate post / page / category for each translation.  PP for WPML extension plugin is required to filter the PP Exceptions item selection UI
  by language and (optionally) to enable mirroring of exceptions to translations

**QTranslate** : Press Permit ensures compatibility by disabling the caching of page and category listings.  To enable caching, change QTranslate get_pages and get_terms 
  filter priority to 2 or higher, then add the following line to wp-config.php: define('SCOPER_QTRANSLATE_COMPAT', true);

**Get Recent Comments** : not compatible due to direct database query. Use WP Recent Comments widget instead.

**The Events Calendar** : Not compatible as of TEV 1.6.3.  For unofficial workaround, change the-events-calendar.class.php as follows :

change:

    add_filter( 'posts_join', array( $this, 'events_search_join' ) );
    add_filter( 'posts_where', array( $this, 'events_search_where' ) );
    add_filter( 'posts_orderby',array( $this, 'events_search_orderby' ) );
    add_filter( 'posts_fields',	array( $this, 'events_search_fields' ) );
    add_filter( 'post_limits', array( $this, 'events_search_limits' ) );
  
	
to:

    if( ! is_admin() ) {
      add_filter( 'posts_join', array( $this, 'events_search_join' ) );
      add_filter( 'posts_where', array( $this, 'events_search_where' ) );
      add_filter( 'posts_orderby',array( $this, 'events_search_orderby' ) );
      add_filter( 'posts_fields',	array( $this, 'events_search_fields' ) );
      add_filter( 'post_limits', array( $this, 'events_search_limits' ) );
	}
  
	
**PHP Execution** : as of v1.0.0, mechanism to limit editing based on post author capabilities is inherently incompatible w/ Press Permit. 
  Edit php-execution-plugin/includes/class.php_execution.php as follows :

change:

    add_filter('user_has_cap', array(&$this,'action_user_has_cap'),10,3);
  
to:

    add_filter( 'map_meta_cap', array( &$this,'map_meta_cap' ), 10, 4 );
	
replace function action_user_has_cap with :

    function map_meta_cap( $caps, $meta_cap, $user_id, $args ) {
        $object_id = ( is_array($args) ) ? $args[0] : $args;
        if ( ! $post = get_post( $object_id ) )
           return $caps;

        if ( function_exists( 'get_post_type_object' ) ) {
            $type_obj = get_post_type_object( $post->post_type );
            $is_edit_cap = ( ( $type_obj->cap->edit_post == $meta_cap ) && in_array( $type_obj->cap->edit_others_posts, $caps ) );
        } else {
            $is_edit_cap = in_array( $meta_cap, array( 'edit_post', 'edit_page' ) ) && array_intersect( $caps, array( 'edit_others_posts', 'edit_others_pages' ) );
        }

        if ( $is_edit_cap ) {
            $id = $post->post_author;

            if ( isset( $this->cap_cache[$id] ) ) {
                $author_can_exec_php = $this->cap_cache[$id];
            } else {
                $author = new WP_User($id);
                $author_can_exec_php = ! empty( $author->allcaps[PHP_EXECUTION_CAPABILITY] );
                $this->cap_cache[$id] = $author_can_exec_php;
            }

            if ( $author_can_exec_php ) 
                $caps []= PHP_EXECUTION_CAPABILITY;
        }
 
        return $caps;	
    }