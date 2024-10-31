=== Plugin Name ===
Contributors: learningvalley
Donate link: http://www.presentations2go.eu
Tags: Rich media, Video, streaming, Weblectures, Lecturecapture, online lectures, lecture archive
Requires at least: 4.0
Tested up to: 4.1
Stable tag: 1.0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html



== Description ==

The Presentations 2Go WordPress Plugin enables you to list multiple videos from your Presentations 2Go streaming video server. The plugin will use a specific Presentations 2Go group to find password restricted (and public) videos from the Presentations 2Go video server. Visitors of the WordPress site do not need to sign-in to the Presentations 2Go video server to watch the restricted multimedia content. 

The single sign-on links to the video content can be time restricted to e.g. 1 hour or more, so visitors need to return to your WordPress page to be able to watch the videos at a later time. 

Title, contributors, view-count and thumbnails are automatically retrieved and passed to the WordPress page for every video that meets your search query.

Content can be presented in three ways
1. Inline; a thumbnail of the video (and slide) with the title above and contributors and views below. Size can be set to small (25% content of the width), medium (50% content of the width) or large (100% content of the width); multiple results are placed below each other.
2. Thumbs; per video a thumbnail is presented; title + contributors are displayed on mouse-over. Below each thumbnail the views are placed.
3. Text; Title, contributors and views are listed in text only.



== Installation ==


1. Upload the `plugin` folder to the `/wp-content/plugins/` directory or install via the Add New Plugin menu
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Open Settings | Presentations 2Go
1. Enter your Presentations 2Go server address
1. Enter the Presentations 2Go group name for authentication
1. Enter the Presentations 2Go secret key for the used group
1. Open a WordPress page
1. Click on P2GO button and create a search query
 


== Frequently Asked Questions ==

= Where do i get my Presentations 2Go secret key =

Ask your Presentions 2Go videoserver webmaster for the key of your group

= Is there a sample videoserver to use for testing =

Yes if you want to test, you can use the following settings:
 
Server Address: https://demo.presentations2go.eu
Group: Open
Secret Key: 2rE9oE8Fu/tGfMRUWONzAR1RaCQbFQ4kSIZ+jxkmeDo=





== Screenshots ==


1. Settings form
2. Add content to WordPress page
3. An example of inline content
4. An example of thumbs content
5. An example of text based content



== Changelog ==

= 1.0.6 =
* Bug fix; newly created blogsites in network installation could not save to database. Table is now always created when missing at first save.

= 1.0.5 =
* First public release

= 1.0.4 =
* Network support

== Upgrade Notice ==

= 1.0.4 =
Upgrade to 1.0.4 if you want to work with network installations.


