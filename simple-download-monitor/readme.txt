=== Simple Download Monitor ===
Contributors: Tips and Tricks HQ, Ruhul Amin, josh401, mbrsolution, alexanderfoxc
Donate link: https://www.tipsandtricks-hq.com
Tags: download, downloads, count, counter, tracker
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 4.0.0
License: GPLv2 or later

Easily manage downloadable files and monitor downloads of your digital files from your WordPress site.

== Description ==

I developed the Simple Download Monitor plugin because I needed a nice way to manage my digital downloads and monitor the number of downloads of my files and documents.

This plugin is very useful for managing and tracking your digital file download counts.

You can password protect your downloadable files and documents too (visitors will require a password before downloading the file when you use this option).

You can configure downloadable files from your WordPress admin dashboard via an elegant user interface. Then allow your visitors to download the files and this plugin will monitor which files get downloaded how many times.

The plugin will log the IP addresses of the users who download your digital files and documents. It will also log the date and time of each download.

It has a very user-friendly interface for uploading, managing, monitoring and tracking file downloads.

https://www.youtube.com/watch?v=SjVaanbulRU

= Simple Download Monitor Features =

* Add, edit and remove downloads from an easy to use interface.
* Drag and drop file and document uploads.
* Assign categories and tags to your downloadable files and documents.
* Rich text editor for editing your download item description.
* Use shortcodes to display a download now button on a WordPress post or page.
* Show trackable download now buttons for your files anywhere on your site.
* Shortcode to create a direct download link for a file. Useful for hotlinking.
* Download counter for each file.
* Ability to set a download count offset for each file.
* Track IP addresses of the users who downloaded your files.
* Track date and time of each file downloads.
* Track the usernames of the users downloading the files.
* Track the User Agent of the visitors downloading the files.
* Track the Referrer URL to see which page the downloads are coming from.
* Option to setup secure downloads for your files (the URL of the downloadable file will be hidden).
* Option to upload a thumbnail image for each of your downloadable files.
* Option to use a nice looking template to show your download now buttons.
* Ability to search and sort your downloadable files in the admin dashboard.
* Ability to create a search page and allow your visitors to search your downloads.
* Track the number of downloads for each of your files.
* Track the visitors country.
* View the daily download counts of your items in a chart.
* WordPress Shortcode for embedding a download link for a file.
* Tinymce button in the WordPress post/page editor so you can easily add the shortcode.
* You can customize the "Download Now" button text of an item to anything you want.
* Ability to add the download now buttons to your sidebar widget.
* Create password protected download now buttons. Users will only be able to download the file if they enter the correct password. [View the tutorial](https://www.tipsandtricks-hq.com/create-a-password-protected-download-file-6838)
* Option to add additional security to your downloadable files with the [Enhanced File Protection Feature](https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/).
* Shortcode to show the download counter of a file. Use it to show off your file download count.
* Shortcode to show all the downloads from a particular category.
* Shortcode to embed a file tree browser for your downloadable files. The file browser is ajax based and it shows the files structured by categories.
* Ability to open the downloads in new browser window or tab. When your users click on a download button, it will open in a new window or tab.
* Ability to show your downloads in a grid display. [View the tutorial](https://www.tipsandtricks-hq.com/show-file-downloads-in-a-nice-grid-display-7273)
* Export all the file download logs to a CSV file.
* Ability to reset the log entries.
* Ability to trim the download log entries.
* Shortcode to show a number of latest downloads to your visitors.
* Shortcode to show a number of popular downloads to your visitors.
* Ability to disable the download monitoring (logging) for certain items (or all items).
* You can also choose to only monitor downloads from unique IP address only.
* Option to specify file size info so it can be shown to your visitors. [View the tutorial](https://simple-download-monitor.com/how-to-show-file-size-info-of-your-downloads/)
* Option to specify version number info for the download item so it can be shown to your visitors.
* Option to show the download published date.
* Option to restrict downloads to logged-in users only. [View the tutorial](https://simple-download-monitor.com/offering-downloads-to-logged-in-users-members-only/)
* There is an option to show an ajax file tree browser so your visitors can browse all your files and download the ones they want.
* Option to ignore download count from bots.
* Option to add Google reCAPTCHA to your download buttons.
* Option to add Terms and Condtions to your download buttons.
* Ability to easily clone/copy your existing download items.
* Ability to insert Adsense or other Ad code inside the download item display.
* Gutenberg block to insert download now buttons on a post or page.
* Option to override the default template by placing a custom template file in the active theme’s directory.
* The stats menu can show you the top downloads, downloads by date, country, browser etc.

View more details on the [download monitor plugin](https://simple-download-monitor.com/) page.

= Language Translations =

The following language translations are already available for the download monitor plugin:

* English
* German
* Spanish
* French
* Italian
* Russian
* Dutch
* Portuguese (Brasil)
* Hebrew

= Simple Download Monitor Plugin Usage =

Once you have installed the plugin, go to "Downloads -> Settings" interface to configure some options

**A) Configure Download Monitor basic settings**

* Admin Options: Remove Tinymce Button - Removes the SDM Downloads button from the WP content editor (default: unchecked).
* Color Options: Download Button Color - Select a default color of the download button (default: green).

**B) Add a new download**

To configure a new download follow these steps:

1. Go to "Downloads->Add New" interface in your WP admin
1. Enter a title for your download
1. Add a description for the download
1. Select the file from your computer and upload it (or use an URL of the file)
1. Select an image for the download (it will be displayed as a thumbnail on the front end)
1. Publish it

You can view all of your existing downloads from the "Downloads->Downloads" interface.

**C) Create a download button**

Create a new post/page and click the "SDM Downlaods" TinyMCE button to insert a shortcode (This button will only show up if you haven't unchecked it in the settings). You can choose to display your download with a nice looking box or just a plain download link/button.

Example Shortcode Usage:

`[sdm_download id="271" fancy="1"]`  (embed a download button inside a box with other information e.g. Thumbnail, Title and Description)

`[sdm_download id="271" fancy="0"]`  (embed a plain download button/link for a file)

`[sdm_download id="271" fancy="0" color="blue"]`  (embed a plain download button/link for a file with a blue color)

**D) Download logs**

You can check the download stats from the "Downloads->Logs" interface. It shows the number of downloads for each files, IP address of the user who downloaded it, date and time of the download.

== 3rd Party or External Libraries/Services ==

The plugin uses the Google Charts library to show the download count charts in the admin interface (if you use the stats menu of the plugin). You can see more details about this library at the following URL:
https://developers.google.com/chart/

== Detailed Usage Documentation ==

View more usage instructions on the [Download Monitor Plugin](https://simple-download-monitor.com/) page.

== Github Repository ==

https://github.com/Arsenal21/simple-download-monitor

If you need extra action hooks or filters for this plugin then let us know.

== Installation ==

1. Go to the Add New plugins screen in your WordPress admin area
1. Click the upload tab
1. Browse for the plugin file (simple-download-monitor.zip)
1. Click Install Now and then activate the plugin

== Frequently Asked Questions ==

= Where can I find complete documentation for this plugin? =
You can find the full documentation for this plugin on the [Simple Download Monitor plugin documentation](https://simple-download-monitor.com/download-monitor-tutorials/) page.

= Can this plugin be used to offer free downloads to the users? =
Yes.

= What file formats can I upload? =
You can pretty much upload all common file types.

= Can I use external file URLs? =
Yes, you can use both local paths and external URLs.

= Can I password protect a downloadable file? =
Yes.

= Can I add additional security to protect my downloadable files? =
You can add additional security to your downloadable files with the [Enhanced File Protection Feature](https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/).

= Can I show the file download counts to my visitors? =
Yes.

= Can I show all downloads from a category? =
Yes.

= Can I show an ajax file tree browser using this plugin? =
Yes.

= Can I show a number of latest downloads to my usrs? =
Yes.

= Can I track downloads from unique IP address only? =
Yes

== Screenshots ==
1. The download item display shortcode in action with the fancy 1 template.
2. The download item display shortcode in action with the fancy 2 template.
3. The Downloads menu in the WordPress admin dashboard.
4. The download item add/edit page in the WordPress admin dashboard.

== Changelog ==

= 4.0.0 =
- Added functionality to override the template by using a custom template file in the active theme's folder.
- Amazon bot filter added to the bot detection function.
- Added a filter hook 'sdm_get_ip_address' to allow modification of the detected IP address.
- Category shortcode output for fancy 0 has been moved into a dedicated function.
- Added {ip_address} email merge tag to the email notification addon.
- Added a new filter hook 'sdm_download_window_target' to allow customization of the download link's window target.

= 3.9.35 =
- Added output escaping to a parameter in the sdm_download_link shortcode.

= 3.9.34 =
- Enhanced security by adding proper sanitization and escaping to the sort order and orderBy parameters in the export logs feature.

= 3.9.33 =
- Added escaping to the download thumbnail field for better security.

= 3.9.32 =
- Readme file updated to fix the formatting of some sections.
- Added Hebrew translation to the plugin.
- Download via direct link feature is now compatible with the various CAPTCHA options.

= 3.9.31 =
- Cloudflare Turnstile CAPTCHA support added. View the [Cloudflare Turnstile CAPTCHA documentation](https://simple-download-monitor.com/using-cloudflare-turnstile-captcha-with-the-simple-download-monitor/) for more details.
- Updated the ip address retrieval method for better server compatibility.
- Added a new filter hook 'sdm_ip_address_header_order' to allow customization of the order of the IP address header.

= 3.9.30 =
- The Google reCAPTCHA v3 feature is now also available for single download posts.

= 3.9.29 =
- Added Google reCaptcha v3 support. It can be enabled from the advanced settings menu of the plugin.
- Added a new filter hook 'sdm_download_button_text_filter' to allow customization of the download button text via custom code.
- Added a new filter hook 'sdm_shortcode_meta_box_content' to allow addons to add content to the shortcode meta box.
- Added a new filter hook 'sdm_before_download_button' to allow addons to add content before the download button.
- Added a new action hook 'sdm_download_via_direct_post' to allow addons to do tasks when download request via direct post is received.
- Added hooks that will allow us to add support for Cloudflare Turnstile in the future.

= 3.9.28 =
- Fixed a minor issue in the newly added admin-side JavaScript code for the logs export feature.
- Updated the translation POT file.

= 3.9.27 =
- Added a search field to the logs table, enabling users to search for specific log entries.
- Introduced an option to export logs to a CSV file.
- Removed a PHP warning from the 'pass_text' request parameter for password protected downloads.
- Added integration with the WP eMember plugin's access control to allow downloads to be restricted to members only.
- Download process request is now handled using the 'wp' hook.

= 3.9.26 =
- The update for excluding hidden attachment media queries will not occur if the enhanced file protection feature is disabled.
- When using the 'pre_get_posts' hook, it will now check if another plugin has already modified the query and if so, it will append to it.
- Removed the trailing slash from the download URL in the download now button shortcode to prevent double slashes in the URL. Thanks to @expforex for pointing this out.
- Renamed a function to add the 'sdm' keyword to prevent a potential conflict with another plugin.
- Added an isset check to remove a PHP notice in the download count shortcode.
- Added sanitization to the row_id variable in the log entry delete function.

= 3.9.25 =
- New [Enhanced File Protection](https://simple-download-monitor.com/enhanced-file-protection-securing-your-downloads/) feature added.
- Thumbnails in the Downloads menu's admin interface will no longer appear squished.
- Added an isset check for the "download_id" variable in the download request handler.
- Fixed $id variable not defined warning in category shortcode.
- Thumbnails in the Downloads menu's admin interface will no longer appear squished.
- Minor CSS improvements to the information box for better readability.

= 3.9.24 =
- New settings added to allow the admin to specify if other WP User roles can view the plugin's admin dashboard.
- Added a new shortcode parameter (more_detail_url) that can bused to show a link below the description section of the fancy 1 and 2 templates.
- The 'more details' Shortcode usage example is available in the [documentation here](https://simple-download-monitor.com/miscellaneous-shortcodes-and-shortcode-parameters/#showing-a-more-details-link)
- Fixed an issue with the single entry log delete function.
- Fixed the custom download button text issue with the fancy 1 & 2 templates.
- Fixed an issue with the specific items logs menu now showing correctly.
- Bulk delete issue fixed in specific item logs tab.
- Logs menu tittle has been moved above to the menu tabs.

= 3.9.23 =
- Improved the [sdm_download_counter id="ALL"] shortcode's query parameter handling.
- Download button text for category shortcode fixed.
- New stats tab added to display top users by download count.
- Download template 2 display fixed for block themes.

= 3.9.22 =
- Removed one of the newly added filter hooks.

= 3.9.21 =
- Compatibility improvement - Updated the newly added title sanitization to check if the post id is present to improve compatibility with some themes.

= 3.9.20 =
- Allow WP audio shortcode to also work in the template 1 description field.
- New window feature now works with fancy 0 template with the shortcode.
- Added a new filter hook to allow customization of the single download post title.
- Added sanitization to the SDM post type title.
- Added the 'Direct Download URL without Tracking Count (Ignore Logging)' option to the download edit page.

= 3.9.19 =
- The audio player shortcode can be used in template 1 download display shortcode.

= 3.9.18 =
- New URL query parameter "sdm_ignore_logging" added to ignore download logging and counting for direct download link.
- SDM block editor's sidebar styling issue fixed.
- Updated the download query argument value from 'smd_process_download' to 'sdm_process_download' to match the plugin's slug. The old query arg will continue to work for backwards compatibility.

= 3.9.17 =
- Block inserter for download item updated for WP 6.2.

= 3.9.16 =
- Added the fancy 1 shortcode in the "Shortcodes" section of the download configuration interface (for easy copy and paste).
- Updated link to the documentation.
- Added "alt" tag to the item thumbnail image.
- Fix for Stored XSS in the Logs menu.
- The thumbnail alt filters now also passes the download ID as an argument.
- Removed a PHP notice related to PHP8.

= 3.9.15 =
- Fixed an issue with the "Quick Edit" link in the downloads menu hanging.
- Added a new utility function in the debug logging class.

= 3.9.14 =
- Get download by date query has been updated.
- Added filters for categories and tags to change the slug (or other parameters). Thanks to @cfoellmann.
- Added a filter to the fancy2 shortcode output so it can be customized.
- Added a filter to the fancy1 shortcode output so it can be customized.

Full changelog available [at change-log-of-old-versions.txt](https://plugins.svn.wordpress.org/simple-download-monitor/trunk/change-log-of-old-versions.txt)

== Upgrade Notice ==

Download monitor 2.0 uses a completely new architecture (it is much simpler and user friendly) for configuring and monitoring downloads. Read the usage section to understand how it works.
