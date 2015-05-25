=== SF Archiver ===
Contributors: GregLone
Tags: content, archive, post types
Requires at least: 3.5
Tested up to: 4.2.2
Stable tag: trunk
License: GPLv3
License URI: http://www.screenfeed.fr/gpl-v3.txt

A simple way to manage archive pages for your Custom Post Types.

== Description ==
This plugin adds a few things to let you handle the archive page or your Custom Post Types.

1. Set the number of posts per page to list for each post type.
1. Use a new box to add a link to your archives in your menus.
1. Quickly visit your archives by using a handy link in your administration area.

= Translations =
* English
* French

== Installation ==

1. Extract the plugin folder from the downloaded ZIP file.
1. Upload SF Archiver folder to your `/wp-content/plugins/` directory.
1. Activate the plugin from the "Plugins" page.
1. Go to *Settings* -> *Reading* and set the number of posts per page for each Custom Post Type.
1. Go to *Appearance* -> *Menus* and add the links to your menus.

== Frequently Asked Questions ==
= Why some of my Custom Post Types don't appear in the settings? =
They're probably not public, or do not have an archive page.

= Why remove all those features in version 2.0? =
Custom Post Types are not "a new thing" anymore, their support in themes and plugins is much better now. That is why I consider the previous options obsolete, and removed them.

Eventually, check out [my blog](http://www.screenfeed.fr/archi/) for more infos or tips (sorry guys, it's in French, but feel free to leave a comment in English).

== Screenshots ==
1. The settings, at the bottom of the "Reading Settings" page.
2. The meta box in the Menus admin page.
3. A link to the post type archive page.

== Changelog ==

= 2.0 =
* 2015/05/25
* Trash it, rebuild it: this version is a complete rewrite.
* **PLEASE NOTE BEFORE UPDATING**: some features have been removed. No URL customization, RSS feed, nor archive page activation anymore. The "Posts per page" setting still remains though.
* **WordPress 3.5+ is now required.**
* The settings are now located in `Settings` -> `Reading`.
* The box in `Appearance` -> `Menus` is still there and I got rid of some old bugs.
* New: in the administration area, now you can find a link to your Post Types archives (look at the title when visiting `wp-admin/edit.php?post_type=my-cpt`).

= 1.1.3 =
* 2013/09/13
* Small security fix.

= 1.1.2 =
* 2012/12/04
* Small change for WP 3.5 compatibility.
* Code improvements.
* Use string as domain for gettext.

= 1.1.1 =
* 2012/08/14
* Small bugfix due to some core changes in WP 3.4.1.

= 1.1 =
* 2012/03/08
* Meta box rebuild in nav menu admin page. Delete your old archive links in your menus and add them again. Now you won't need to change them again if you change the archives slug.
* Minor changes in French translation.

= 1.0 =
* 2012/02/24
* First public release

== Upgrade Notice ==

READ THE CHANGELOG BEFORE UPDATING.