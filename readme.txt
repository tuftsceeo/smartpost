=== SmartPost Templates ===
Contributors: rafdizzle86
Donate link: http://rafilabs.com/
Tags: templates, forms, front end editor, video, ffmpeg, picture gallery, media, uploader
Stable tag: 2.3.8
Requires at least: 3.8
Tested up to: 4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

SmartPost builds beautiful form templates that makes it quick and easy to generate posts on the front-end of your WordPress site.

== Description ==

= What is SmartPost? =

It is a template builder and authoring tool that is user centered. No programming or coding knowledge required.

= Why use SmartPost? =

*   Create new posts on the front-end, get instant feedback to the look and feel of your post.
*   Break up your posts into different sections - i.e. a picture gallery, a video, and richtext sections.
*   SmartPost can use ffmpeg to rotate and compress uploaded video files for a better video-streaming experience.
*   Extend the capabilities of SmartPost by developing your own post section.

= How does SmartPost Work? =

*Build Templates to be used on the front-end of your WordPress site:*

SmartPost enhances WordPress categories by applying templates to them. Users can create new category templates and build them up
by dragging and dropping components into the template builder. Once category templates have been setup, users can utilize
them on the front end to create new posts. SmartPost also allows you to copy existing templates into new ones.

*Authors use pre-defined templates to build rich posts on the front-end of WordPress:*

No more going back and forth between the dashboard. Build rich posts one component at a time with immediate feedback on
how your post will look like once published. SmartPost utilizes intuitive actions such as dragging and dropping to add
pictures and videos to your post.

**Note: SmartPost is still in the Beta stage, check out the "Beta Testing" tab for more info!**

[For more information, check out the plugin site here](http://sptemplates.org).

== Installation ==

1. Download the plugin in zip format.
2. Uncompress the smartpost.zip file.
3. Move the `smartpost` directory under the `/wp-content/plugins/` directory in your WordPress instance.
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Go to the SmartPost admin page and create a new template (or use the default SP QuickPost template)
6. Add the `[sp-quickpost]` shortcode to a page or add the "SP QuickPost" widget to a widget area
7. Go to the front end of your site and create beautiful, clean looking posts with instant feedback

== Frequently Asked Questions ==

= How do I create posts on the front-end like the plugin says it does?  =

The easiest way to start using the templates is to embed the `[sp-quickpost]` shortcode in a post, page, or some place
that is able to parse WordPress shortcodes. The shortcode looks something like `[sp-quickpost template_ids="1"]` or
 `[sp-quickpost template_ids="1,2,3"]`. The `template_ids` attribute allows the shortcode to know which template form should be used.
In the SmartPost admin page, the shortcode for each template is provided with the template's associated ID, however, you can
allow your users to pick from a selection of templates by providing the shortcode with multiple template ids separated by commas as it
is shown in the example above. The IDs in the shortcode have to be associated with SmartPost templates, otherwise the shortcode will
not render properly.

The second way to start create posts on the front end is by adding the "SP QuickPost" widget to one of your widget areas / sidebars.
The widget works better if you have a "category header" widget area where you can place the SP QuickPost widget.
If your theme doesn't have a "category header" widget area, then generating new posts on the front-end may look broken as the form
itself is squeezed into narrow sidebars.

However, it's fairly easy to create a category widget-area for you to place your SP QuickPost widget in:

1. Create a `sidebar-category.php` file in your theme file.
2. Once you created this file, read up on how to register a new sidebar area [here](http://codex.wordpress.org/Function_Reference/register_sidebar).
3. In your `category.php` file, place the sidebar somewhere under the category title, right before the post loop.

= How do I get HTML5 video encoding to work? =

**Note: SmartPost only supports video encoding on Linux servers! We are hoping to extend support for Windows servers soon.**

[FFmpeg](http://www.ffmpeg.org/) is required for video encoding to work! The server that your WordPress instance resides on needs to have
ffmpeg and ffprobe executables present in the same directory. If ffmpeg is already installed, these executables usually reside in /usr/local/bin/,
but sometimes not. To find the full path of where ffmpeg lives, you can use the shell command `command -v ffmpeg`.

Once you've found the full path, then in the WordPress dashboard go to SmartPost -> Settings and click on the "Video" link on the right hand side.
Copy and paste the full path of where the ffmpeg executable resides in the text input (make sure there is a trailing "/" at the end of the path).
Click on the "Test" button to see if SmartPost is able to properly invoke the ffmpeg executable. **Note: PHP needs to be configured to allow
SmartPost to use the [shell_exec()](http://us3.php.net/manual/en/function.shell-exec.php), [exec()](http://us3.php.net/manual/en/function.exec.php), and
[system()](http://us3.php.net/manual/en/function.system.php) commands for this work!**

If you're not confident in installing ffmpeg on your server, the easiest thing to do is to download static builds from the [ffmpeg website](http://www.ffmpeg.org/download.html).
In order to download the right build, you will need to know the type of operating system of the server your WordPress site is hosted on. If it's a Linux server (in most
cases it is), then you will need to know the appropriate kernel version. There are various ways to look up the kernal version, the easiest being via the shell command "uname -r".
You can also look it up via phpinfo() or use the [php_uname()](http://us3.php.net/manual/en/function.php-uname.php) command.

Once you've downloaded the appropriate static builds, un-compress them, and upload them to a directory where the "apache" user has permisions to execute them.
A good place might be inside the SmartPost folder under wp-content/plugins/smartpost-templates/components/video/ffmpeg/ (you would have to create the ffmpeg folder).

= How do I uninstall SmartPost? =

We are currently working on putting in place an uninstall script! If you want to completely purge your WordPress instance of SmartPost, then you will have
to delete the following:

1. The tables 'sp_postComponents', 'sp_catComponents', 'sp_compTypes'
2. The options: 'sp_categories', 'sp_db_version', 'sp_defaultCat', 'sp_cat_icons', 'sp_responseCats', 'sp_cat_save_error' from the wp_options table.

== Screenshots ==

1. Build category templates across your taxonomy - each category can have its own template. Posts created using the "SP QuickPost" widget will follow the structure of the template.
2. SmartPost comes with a wide variety of useful widgets that makes post management easier on the front-end of WordPress.
2. Drag and drop images, videos, and files from your desktop to create rich posts with videos and picture galleries with immediate feedback. No more jumping back and forth between the dashboard and the front-end of your site.
3. How a post looks after submitting it via a SmartPost template.

== Changelog ==
= 2.3.8 =
* Fixes a bug where the SmartPost Components were not being properly installed on activation
* Adds sp_admin_add_submenu_pages action to add submenu items to the SmartPost parent menu item

= 2.3.7 =
* Introduced encode_via_ffmpeg() function that single handedly handles video encoding
* Renamed some variables and functions to fit with WordPress conventions

= 2.3.6 =
* Fixed a bug with the video player not rendering properly

= 2.3.5 =
* Added "button_txt" attribute to the sp-quickpost shortcode
* Better/more hardened SQL statements using wpdb->prepare()
* Fixed an issue with EXIF function reading PNGs
* More renaming of functions to wp-style conventions
* Fixes an error that's thrown when the plugin is installed
* Fixes other minor problems such as foreach() array checks

= 2.3.4 =
* Fixes a compatibility issue with the Relevanssi plugin
* Fixes an issue with video rotation
* Adds .m4v video compatibility to the video component
* Better error handling for video uploads and video conversions
* Fixes a buffer output bug with the SP QuickPost widget

= 2.3.3 =
* Removes an include on wp-load.php for the video conversion script

= 2.3.2 =
* Fixed an update bug that did not update WPMU sites correctly
* New [sp-quickpost] shortcode allows users to place a SP Template form anywhere on the site!

= 2.3.1 =
* Fixed a bug where older versions of required_once() could not resolve relative paths properly
* Fixed bug with exif_read_data() being called on incompatible file formats
* Added extra check for update script from 2.x to 2.3+
* Fixed a bug where updating from v2.2->2.3 would break component icons,
* Moved away from hard-coding component icons
* Updated content component, not using nicEditor  anymore
* Updated gallery and photo components to reflect use of magnific-popup
* Removed jQuery UI css - moved over to using thickbox for new template form

= 2.3 =
* Fixed a shortcode bug where SmartPost would strip all shortcodes from a post
* Added new shortcode called [sp-components] that wraps around all [sp_components] shortcodes
* Add new shortcodes functions to handle shortcode logic
* Modified update logic and design to a more simple design that relies on the version numbers
* Fixed upload bug where apostrophes were not being properly handled
* Fixed bug where .mp4 files were not being properly encoded and uploaded

= 2.2 =
* Initial release into to the WordPress plugin repository.

== Upgrade Notice ==
= 2.3.8 =
* Fixes a bug where SmartPost components were not being installed on activation

= 2.3.7 =
* Better video encoding

= 2.3.6 =
* Fixes a bug where the video player was not rendering properly

= 2.3.5 =
* Better/hardened SQL statements
* Introduces a "button_txt" attribute to the [sp-quickpost] shortcode
* More stable error handling

= 2.3.4 =
* Better video handling and fixes a compatibility bug with the Relevanssi plugin

= 2.3.3 =
* Removes an include on wp-load.php for the video conversion script

= 2.3.2 =
* Fixed an update bug that did not update WPMU sites correctly
* New [sp-quickpost] shortcode allows users to place a SP Template form anywhere on the site!

= 2.3.1 =
* Users with older versions of PHP should update to 2.3.1 as require_once() may not resolve relative paths properly

= 2.3 =
Users should update to 2.3 due to a few major bugs that were fixed:

* Uploaded files with apostrophes were not being handled properly
* .mp4 video files were not being uploaded properly
* Other shortcodes in post content were being destroyed

= 2.2 =
* First release to WordPress plugin repository.

== Beta testing ==

The SmartPost plugin is still in a "beta" stage. There are still a few kinks to work out and we are looking for people
to provide us with feedback! We apologize for any "unstable-ness" you may experience throughout the use of this plugin, and
we encourage you to continually back-up your WordPress database and any content you may generate through the use
of this plugin!

If you are a developer and are interested in helping out with the development on this plugin, check out the [github
repository](https://github.com/tuftsceeo/smartpost) and feel free to contact the developer. Also, check out
the [plugin site](http://sptemplates.org) while you're at it.

Thanks!
- SmartPost Dev Team