=== CaPa Protect ===
Contributors: namja
Donate link: http://www.smatern.de/
Tags: restrict, restriction, category, categories, page, pages, protect, comments, security, Post, admin, plugin, posts, invisible, hide comments, comments private, hide comments from non-members, members, membership, sicherheit, begrenzung
Requires at least: 2.7.1
Tested up to: 3.2.1
Stable tag: 0.5.8.2

Protects Categories, Pages and Posts for specific users & anonymous visitor

== Description ==

CaPa provides Category & Pages protection on a roles &amp; user basis.

Posts in a protected Category or Pages will not be visible unless the user or role has privileges to see it.


For Information [CaPa Role & User Access](http://www.smatern.de/2009/03/09/teaser-capa-v054/ "CaPa Protect").
Further Information coming soon…


= Partial Feature List =
* Customize access for specific Pages, Posts, Categories
* Customize access for roles & users
* Protect RSS Feed (Entries &amp; Comments)
* Protect XMLRPC (Shows only allowed recent Posts)
* Keep Settings by deactivate CaPa

In some cases some Features won't work properly (less security) with older Wordpress Versions ( older than WP 2.9.2 ).

= If you find any Bugs! = 
Please send a message or use the Menupoint "Capa Support" 

== Installation ==

= Installation =

1. Upload the 'CaPa' folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. All categories and pages will be hidden by default
4. Go to the new Menupoint "CaPa" for set up.

== Upgrade Notice ==

Just Upgrade. If something won't work or looks strange, use the Menupoint "Capa Support".


== Language Support ==

* english
* german
* france (out of date)

== Known bugs ==

If you find any bug, please send me a message.

== Inspired == 

* category-access plugin by David Coppit http://www.coppit.org

== Changelog ==

= v0.5.8.2 =
* Bugfix "Search No Result"
* Bugfix "Notice Error CAPA_DBUG"
* Bugfix "Warning array_flip"

= v0.5.8.1 =
* Bugfix "Invalid argument supplied for foreach"

= v0.5.8 =
* CleanUp - Pushed up the CaPa Admin Menu
* CleanUp - Merge Submenu Visitor &amp; User Roles
* Add Feature "Show only allowed Attachments"
* Add Standart Settings - "Show only allowed Attachments"
* BugFix "Show protected Pages" - When activating the settings, it allowed Roles to see/edit protected Pages in the Backend 
* Bugfix "Media Library" - No Media Items for Editor ( Thanks to Eduard for info )
* Bugfix "Count Comments Warning" - PHP Warning appears by returning wrong parameter type
* Bugfix "array_flip" - Shouldn't appear again
* BugFix "get_catname Deprecade Error"

= v0.5.7 =
* Bugfix "list_terms_exclusions" - if GLOBALS[wp_filter] doesn't exists, it won't work 
* Update Language "French" - Thanks to Pierre for the efforts
* Add Feature "Posted in .." - Remove Protected Categories from "Posted in .." sentence
* Add Feature "Category Structure" - Remove Protected Categories from Category Permalink Structure
* Add Feature "Add New Parent" - Remove Protected Pages from Dropdown "Parent"
* Add Feature "Nav Menu Check" - Removed Protected Categories &amp; Pages from custom menus  
* Bugfix "undefined variable" - missing variable cause a low priority php error msg
* Bugfix "has_cap" - in rare situation the instance 'has_cap' isn't defined
* Add Feature "result" - alter only the comment Querys to receive only allow comments 
* Add Feature "Dasboard Comments" - affected by the Feature "result"
* CleanUp - Interface ( diverse fixes )
* Add Feature "recent posts" - restrict the result of get recent posts of the XMLRPC API
* Bugfix "list_terms_exclusions" - Dashboard Count Tags
* Bugfix "get_value_tags" - optimise and fixed this function

= v0.5.6.2 =
* CleanUp - Interface ( Huge thanks to Aleksandar for advice &amp; help )
* Bugfix "preg_match_all Error" - missing letter cause an error (php lower then 5.2 is affected)

= v0.5.6.1 =

* Add Help Info "Serialize Pages"
* Bugfix "Wrong Datatype" - PHP Error appears when few Categories are allowed but no Pages ( Thanks to Greg for info )

= v0.5.6 =

* Add Feature "Keep Settings" - In the Case that CaPa is disabled but admin wants to keep the Settings
* Add Feature "Tree Structure" - Tree-List format for Categories / Pages
* Add Feature "Check/Uncheck All" - add to the 'Roles' Area &amp; adapt the existing Check/Uncheck All
* CleanUp - rewrite JS Functions for check/uncheck all &amp; others
* CleanUp - Layout ( Adaption to the Wordpress Design )
* BugFix "Edit Pages missing Pages" - CaPa didn't shows all allows Pages for editing ( Thanks to Ninava for info )
* Bugfix "Comment Counter always shows 'No Comments'"
* Bugfix "RSS Comment hide all"
* Bugfix "RSS Comment hide creator name"

= v0.5.5 =

* Bugfix "default category is activ -> pages are visible through direct link"
* Bugfix "w/o settings shows the categories under Menu Posts > Categories"
* Bugfix "Entries (RSS)" - Feed didn't shows private Entries with titel, when allowed 
* Add Language - Thanks to Mathie efforts CaPa speaks French
* Add Feature - Comments are finally entirely hidden ( Comments number too )
* Bugfix "Padlock incorrect" - shows the padlock on allows categories
* CleanUp - Layout/Display

= v0.5.4.5 =

* Bugfix "User Categories" - fix up the issue, when all categories are marked for a role and doesn't appear ( Thanks to Ray for info )
* Bugfix "Wrong Page Protect" ( Thanks to Joe for info )
* Bugfix "Show single Page" ( Thanks to Djerk for info ) 
* Bugfix "Shows protect Comments"
* Bugfix "Show private Pages" - fix up the implode error ( Thanks to Tim for info )

= v0.5.4.4 =

* Bugfix "Padlock Taxo/Term"
* Bugfix "Comment for Admin invisible"
* Bugfix "Comment empty Name/Body"
* Bugfix "Show Comment Author"

= v0.5.4.3 =

* Bugfix "Page One" - Shows Page One
* Bugfix "Double Padlock"

= v0.5.4.2 =

* Don't Ask …

= v0.5.4.1 =

* Bugfix "Taxonomy" - prevent and stop the Taxonomy ID &amp; Term ID mix up

= v0.5.4 =

* Improving the Code
* Add Language - German
* Add Menu - CaPa Menu
* CleanUp - CaPa Interface
* Remove "New User Settings" - replaced by Roles Settings
* Bugfix "htaccess soft link" - Combination of an empty SERVER Variable &amp; htaccess cause error ( Thanks to Helena for info )
* Bugfix "Anonymous User Save" - anonymous User settings can't be saved ( Thanks to Raymon for info )
* Bugfix "Padlock Protect Category" - cause an error, when no categories are protect ( Thanks to Joe for info )

= v0.5.3 =

* Bugfix "Page Error" - Cause an error, when just page(s) are protect

= v0.5.2 =

* Add Feature "Protect Tag Clouds" ~ Hiding Tags from Protect Posts
* Add Feature Backend Categories List showed only allow Categories at the "Write Post" Area
* CleanUp of the Backend Layout 
* Optimization the Code

= v0.5.1 =

* BugFix Comments ~ Protect Comments on Public Posts
* BugFix Tags ~ Empty Tag Clouds

= v0.5 =

* First public version of plugin