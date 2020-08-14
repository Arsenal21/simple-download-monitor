=== Simple Download Monitor ===
Contributors: Tips and Tricks HQ, Ruhul Amin, josh401, mbrsolution, alexanderfoxc
Donate link: https://www.tipsandtricks-hq.com
Tags: download, downloads, count, counter, tracker, tracking, hits, logging, monitor, manager, files, media, digital, download monitor, download manager, downloadmanager, file manager, protect downloads, password, download category, file tree, ajax, download template, grid, documents, ip address
Requires at least: 4.1.0
Tested up to: 5.5
Stable tag: 3.8.9
License: GPLv2 or later

Easily manage downloadable files and monitor downloads of your digital files from your WordPress site.

== Description ==

I developed the Simple Download Monitor plugin because I needed a nice way to manage my digital downloads and monitor the number of downloads of my files and documents.

This plugin is very useful for managing and tracking your digital file download counts.

You can password protect your downloadable files and documents too (visitors will require a password before downloading the file when you use this option).

There is an option to show an ajax file tree browser so your visitors can browse all your files and download the ones they want.

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
* Option to ignore download count from bots.
* Option to add Google reCAPTCHA to your download buttons.
* Option to add Terms and Condtions to your download buttons.
* Ability to easily clone/copy your existing download items.
* Ability to insert Adsense or other Ad code inside the download item display.
* Gutenberg block to insert download now buttons on a post or page.

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

** 3rd Party or External Libraries/Services **

The plugin uses the Google Charts library to show the download count charts in the admin interface (if you use the stats menu of the plugin). You can see more details about this library at the following URL:
https://developers.google.com/chart/

** Detailed Usage Documentation **

View more usage instructions on the [Download Monitor Plugin](https://simple-download-monitor.com/) page.

** Github Repository **

https://github.com/Arsenal21/simple-download-monitor

If you need extra action hooks or filters for this plugin then let us know.

== Installation ==

1. Go to the Add New plugins screen in your WordPress admin area
1. Click the upload tab
1. Browse for the plugin file (simple-download-monitor.zip)
1. Click Install Now and then activate the plugin

== Frequently Asked Questions ==

= Can this plugin be used to offer free downloads to the users? =
Yes.

= What file formats can I upload? =
You can pretty much upload all common file types.

= Can I use external file URLs? = 
Yes, you can use both local paths and external URLs.

= Can I password protect a downloadable file? = 
Yes.

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

For screenshots please visit the [download monitor plugin page](https://www.tipsandtricks-hq.com/simple-wordpress-download-monitor-plugin)

== Changelog ==

= 3.8.9 =
- WordPress 5.5 compatiblity.
- Added sanitization to the "User Agent" field.
- Removed the "TinyMCE" button option. It is no longer needed in current WordPress version.

= 3.8.8 =
- Added a new option to capture the Referrer URL of the downloads in the "Logs" menu.
- The "Do Not Capture Referrer URL" option can be used to disable the "Referrer URL" capture.

= 3.8.7 =
- Added a new filter for the visitor name tracking.
- Added two new bot strings that will be filtered out from the log (when using the "Do Not Count Downloads from Bots" option).
- Updated the 'sdm_fancy1_below_download_description' filter hook to also pass the Download ID via additional params.
- Updated the addon auto-updater library.
- Added more sanitization to the "Logs" interface.

= 3.8.6 =
- Added a new feature to ignore the "Only Allow Logged-in Users to Download" option on a per download item basis.
- Added a new settings option to disable capturing of the "User Agent" value in the logs.
- Added a new feature in the "Logs" menu to allow trimming of log entries. This option can be used to delete log entries older than 3 months for example.
- Fixed a minor JavaScript issue.

= 3.8.5 =
- Removed the usage of the get_browser() function since it is not supported in some servers. Instead it will just log the full user agent data.

= 3.8.4 =
- Added a new feature to log the user agent data.
- Added a new filter to allow overriding of the File Download field's URL validation.
- Fixed a header already sent warning when using the "customizer"

= 3.8.3 =
- Spanish translation file updated.
- Better handling of the stats admin menu interface in mobile devices.
- Addon updates are now handled by the main plugin.

= 3.8.2 =
- Added action hooks for password protected downloads (when a download request is submitted without a password or incorrect password).
- Added the Norwegian language translation files. Thanks to Tore Østerlie.
- Regenerated the translation POT file.
- Wordpress 5.3 compatibility.

= 3.8.1 =
- Added a new tab in the stats menu to list/show the download by country in a table.

= 3.8.0 =
- "Downloads by country" stats tab now requires Google Maps API Key to work properly.

= 3.7.9.1 =
- Improved the after login redirection feature so it is compatible with login via wp-login.php.
- Added a new filter hook.

= 3.7.9 =
- Added a new option in the download configuration to disable the download button on the individual download page of an item.
- Added a new utility function to read the current page's URL.
- Moved the user login related options under a new settings section called "User Login Related".
- Added a new feature to enable redirection of users back to the download page when they click on the login link and log into the site.

= 3.7.8 = 
- Added a new option in the download configuration to disable the individual download page of an item. Can be useful if you only want to allow downloading of the item from where you embed it.

= 3.7.7 =
- Fixed password protected download error when reCapcha is enabled.
- Added CSS class to the "Enter Password" label (for password protected downloads).

= 3.7.6 =
- Added "SDM Download" Gutenberg block.
- Fixed "color" shortcode parameter was ignored for default template (fancy 0).
- Added a new compact template (fancy3). This can be useful in displaying a list of downloads in a compact form (maybe in a sidebar widget).

= 3.7.5 =
- Added an option to configure "after download redirect" in the hidden downloads addon.

= 3.7.4.2 =
- Fixed [sdm_popular_downloads] shortcode was ignoring button_text parameter for fancy 1 and fancy 2 templates.

= 3.7.4.1 =
- Updated the popular downloads shortcode to factor in the download counter offset setting.

= 3.7.4 =
- Added new shortcode [sdm_popular_downloads] to display popular downloads.
- Updated broken i18n string to properly use printf w/ variable replacement. Thanks to Garrett for fixing this.

= 3.7.3 =
- Added a new feature in the "Logs" menu that allows you to view the log entries of a specific download item.

= 3.7.2 =
- Bugfix: the new custom button text option did not work when used with the recaptcha feature.

= 3.7.1 =
- There is a new option to specify a custom button text for the download item. Check the "Download Button Text" field under the other details section.

= 3.7.0 =
- Fixed "Open in new window" option was ignored when for downloads with reCaptcha, password and/or Terms and Conditions.

= 3.6.9 =
- Added two filter hooks for template 1 and template 2 to allow customization of the thumbnail output.
- Changed the "logs" admin menu slug to "sdm-logs" to make it unique.
- WordPress 5 compatibility.

= 3.6.8 =
- Fixed Terms and Conditions box conflict with Hidden Downloads addon (requires addon version 1.3+).

= 3.6.7 =
- You can configure the "new_window" property by editing the download item from the admin dashboard.
- Added a new feature to show the download published date in the fancy display. You can edit a download and check the "Publish Date" option to show this info.
- There are also checkboxes to show the file size and version number.

= 3.6.6 =
- Added a new feature to show adsense or other ad code below the download description.
- Added the sdm_downloads_description filter to the description output. Other plugins can apply customization to the description output using this hook.
- Added a new filter sdm_cpt_below_download_description

= 3.6.5 =
- Added a new feature to allow easy copying/cloning of your existing download item. Useful if you are trying to create a lot of similar downloads.
- The list downloads from category can now accept multiple category slugs (or IDs).
- The pagination arrow has been changed to use &raquo;

= 3.6.4 =
- Switched the column location of "Title" and "Image" in the downloads admin interface. This helps with the downloads admin interface working better in all devices.
- Fixed a CSS class name in the "Image" column.
- French language file updated.

= 3.6.3 =
- German translation file updated. Thanks to Thorsten.
- When there are multiple download now buttons on a page and the terms checkbox is enabled, it needs to be accepted on every download button.

= 3.6.2 =
- Added new feature that allows you to show a terms and conditions checkbox for the download buttons.

= 3.6.1 =
- Tweaked a newly added function's parameter to make it compatible with an older PHP version.

= 3.6.0 =
- Added a new feature to allow addition of Google reCAPTCHA to your download buttons.
- This new option can be found in the Advanced Settings tab of the plugin.
- French translation updated. Thanks to @momo-fr.

= 3.5.9 =
- Added a new option in the settings that allows you to disable capturing of visitor's IP address in the download logs menu. Helpful for GDPR 

= 3.5.8 =
- Added a new shortcode to allow listing of all downloads on a post/page.

= 3.5.7 =
- Fixed an issue with the new shortcode that was added. Usage documentation of the shortcode: https://simple-download-monitor.com/showing-specific-details-of-a-download-item-using-a-shortcode/

= 3.5.6 =
- Added a new shortcode to show any info/details of the download item. Example shortcode: [sdm_show_download_info id="123" download_info="title"] 

= 3.5.5 =
- Fixed a PHP warning.
- Added sanitization to the input fields that needed it.
- Listed the 3rd party libraries/services used in the readme file.

= 3.5.4 =
- Fixed stored-XSS bug. Thanks to d4wner.

= 3.5.3 =
- Added "Text Domain" and "Domain Path" to the File Header.

= 3.5.2 =
- Renamed the "langs" folder to "languages"

= 3.5.1 = 
- Added a few more user-agents check in the is_bot function.
- Search shortcode has been improved so it performs the search using each keyword of a multi-word search phrase. It will ignore any word that are less than 4 characters long.
- Includes some missing translation strings to the POT file.

= 3.5.0 =
- Added check for a couple of user-agents in the is_bot function.
- Added a filter that can be used to override what you consider bot via your own custom function.

= 3.4.9 =
- Added a new option in the settings menu to ignore downloads from bots. The name of the new settings field is "Do Not Count Downloads from Bots".
- Updated the settings menu slug to make it unique.
- Enhancement to the password protected download function.

= 3.4.8 =
- Fixed an issue with the shortcode inserter interface showing the last 5 items only.
- The "show_size" shortcode parameter will work correctly with the fancy template now.

= 3.4.7 =
- Reworked the file upload interface to make it more user-friendly. 
- Delete plugin Data button now also deletes taxonomies and rewrite rules related to plugin.

= 3.4.6 =
- Added option to delete plugin's settings, data and tables from database.
- Added "Login Page URL" option to optionally specify a login page URL when user is required to be logged in to download.

= 3.4.5 =
- Updated the slug of the new stats menu in this plugin so it doesn't conflict with jetpack's stat menu.

= 3.4.4 =
- Added "Stats" menu in the plugin that shows download count using a chart.
- Removed the "modal" class definition from the admin CSS file.

= 3.4.3 =
- The [sdm_search_form] can take the fancy template as a shortcode argument to display the search result using that template.

= 3.4.2 =
- Added a new option "Only Allow Logged-in Users to Download".
- Added [sdm_search_form] shortcode to display a search form for searching SDM downloads only.

= 3.4.1 =
- The password protected download button will use the "button_text" specified in the shortcode (if any).
- The download now log will track WP eMember plugin username if the user is logged in as a member.

= 3.4.0 =
- Added a new hook to allow plugin extensions to hook into download request handling.
- Added empty index file to the plugin folder.
- Fixed potential XSS vulnerability. Thanks to Neven Biruski (Defensecode) for pointing it out.
- Minor typo fix.

= 3.3.7 =
- Added an improvement for the password protected download. If a user goes directly to the download link without entering a password, it will point the users to go to a page where they can enter a password for the download item.

= 3.3.6 =
- Fix fancy template shortcode attributes: some shortcode attributes were ignored in the previous release.

= 3.3.5 =
- Download button color can now be specified in the shortcode for fancy 1 and the standard download button.
- Added clearfix to .sdm_download_link container for better rendering in mobile devices.
- The button text color CSS has been sharpened a little to make it look nicer.
- Fixed an undefined variable notice.
- Updated TinyMCE button icon to a better one.
- Minimum WordPress version requirement changed to WP4.1

= 3.3.4 =
- Replace deprecated get_currentuserinfo() with wp_get_current_user()
- Improve remote IP and location detection
- Added a new shortcode to show a simple list of the download categories
- Fix: avoid undefined variable notices in sdm_pop_cats_ajax_call()

= 3.3.3 =
- New feature - local download items can now be dispatched via PHP. This way, the actual URL of the downloaded file is not exposed (offers secure download).
- Minimum required WP version raised to WordPress v3.3
- WordPress 4.6 compatibility.

= 3.3.2 =
- Added an option to specify the file size info when editing the item. Size info can be shown in the fancy display template using a shortcode parameter (show_size).
- Added an option to specify the version number info when editing the item. Version info can be shown in the fancy display template using a shortcode parameter (show_version).
- Added French language translation. Translation file submitted by Laurent Jaunaux.
- The stats metabox in the download edit page will now appear before the shortcodes metabox.
- Added more usage instructions in the download file upload section.

= 3.3.1 =
- Added a new feature to hide the download counts that is shown in some of the fancy templates. This new option can be found in the settings menu of the plugin.
- Added delete confirm dialogue in the individual download logs delete option.
- Simplified the settings menu page style.
- Fixed multiple vulnerabilities (thanks to NCSC-NL).

= 3.3.0 =
- Better implementation of the export log data to CSV file.
- Added a new filter in the download logs menu so the items per page value can be customized.
- Fixed a bug in the logs menu sorting. Sorting for some columns weren't working correctly.
- Added sanitization for the order and orderby columns in the logs list table.
- Added sanitization for the log entry delete functionality.

= 3.2.9 =
- Renamed the label of categories and tags of this plugin to "Download Categories" and "Download Tags".
- Better implementation of password protected download items. The ajax method of checking password has been completely replaced with a more robust implementation.
- Fixed a bug with the remove thumbnail ajax query.
- Thanks to @James Golovich for pointing out the issues.

= 3.2.8 =
- WordPress 4.4 compatibility.

= 3.2.7 =
- Added a new feature to specify the new window parameter when using the shortcode inserter (from TinyMCE).
- Minor CSS style fix for the "Logs" interface.
- The log entries will be sorted by "date" field (by default).

= 3.2.6 =
- The language slug has been changed to "simple-download-monitor" so it can be imported to the WordPress language pack.

= 3.2.5 =
- Modified the 'sdm_download_shortcode_output' filter to pass the arguments array also (allows greater customization option via this filter).
- Added a new option to show X number of latest downloads from a specified category.

= 3.2.4 =
- Added a new feature to show pagination in the display all downloads from a category shortcode.
- Added validation checks while processing a download request to make sure the download item ID is valid and the item has a download link.

= 3.2.3 =
- Addressed some warning/notice messages that shows when debug is enabled.

= 3.2.2 =
- Added a new shortcode to create a direct download link for the file. Useful if you want to create a custom download link.
- WordPress 4.2 compatibility.

= 3.2.1 =
- Fixed an issue with the ajax category browser shortcode.
- Improved the ajax category downloads shortcode to show an up/down arrow icon next to the category name.

= 3.2.0 =
- Added a textbox to manually type in an image thumbnail URL when editing a download item.
- Minor javascript improvements for the select and remove image option.
- Improved the image thumbnail preview in the admin side.
- Added placeholder text in the URL input fields.

= 3.1.9 =
- Fixed a compatibility issue with WooCommerce's latest release.

= 3.1.8 =
- Enabled shortcode filtering in standard text widget.
- Fixed an intermittent issue with the rewrite rules flushing.

= 3.1.7 =
- Added Portuguese language translation. Translation file submitted by Visto Marketing.
- New feature to disable the download monitoring (logging) for certain items (or all items).
- New option to only monitor downloads from unique IP address only.

= 3.1.6 =
- Improved the queries in the "Logs" interface to be more efficient.
- Added a new feature to reset/empty the download log entries. You can find it in the "Logs" menu of the plugin.

= 3.1.5 =
- Added Spanish language translation. Translation file submitted by Manuel.
- Added Russian language translation. Translation file submitted by Балашов Алекс.
- Added Dutch language translation. Translation file submitted by Paul Tuinman.
- Added a new feature to show X number of latest downloads.

= 3.1.4 = 
- New feature to track the usernames of the WP Users downloading the files. You can view the username info in the "Logs" menu.
- Enabled the "View" link in the all downloads list table.
- Some refactoring work to move the admin menu handling code to a separate file.

= 3.1.3 =
- Added an option to use "orderby" and "order" parameters in the display downloads from a category shortcode to allow sorting the download items display list.
- The download item description field has been converted to a rich text editor. So you can customize the download description with rich text.

= 3.1.2 = 
- Removed the link from the download item name in the template 2 display.

= 3.1.1 =
- Fixed a bug with the file download password entry field.

= 3.1 =
- Added a new fancy template to display the downloads.
- You can now show a grid display of your downloads.
- Added some error validation in the download shortcode entry.
- Refactored some code and added a couple of new filters.
- Added CSS classes around the download count shortcode output.
- Modified the styles of the simple downloads post type output.
- The fancy 1 template now shows the download count of each item.
- Updated the POT file for language translation.

= 3.0 =
* Added an option to specify a download count offset for each download. This will allow you to set a starting download count for each item. 
* Added a new parameter in the shortcode to allow customization of the button text. You can now customize the "Download Now" button text to anything you want from the shortcode.
* Added a new parameter in the shortcode to allow opening the download in a new window or tab.

= 2.9 =
* Added visitor Country to database table; allowing tracking of each download visitors Country.
* Visitor Country is also seen in the "Logs" page; can be sorted; and exported.

= 2.8 = 
* Added the ability to use shortcodes in the description area of the downloads. For example: you can use the download counter shortcode in the description field to show the current counter to your visitors.
* Bug-fix for the "show downloads from a category" shortcode. It will now correctly show all items in a download category.
* Added a better dashicons icon for the downloads menu in the admin dashborad.

= 2.7 =
* Added a new feature to show an ajax file tree browser.
* Added some missing translation strings in the plugin.
* Added German language translation. Translation file submitted by Meinhard
* Changed the password input field to a type of "password"
* Fixed a minor but that would preven the "view" links from displaying in WordPress posts

= 2.6 =
* Added a new shortcode to show all downloads from a download category.
* Added a filter to handle the URL of SDM downloads post type.

= 2.5 =
* Added a new feature to password protect a downloadable file.
* Added a new shortcode to show the download count to your visitors.

= 2.4 =
* Fixed an issue with file download using external file URL.

= 2.3 =
* Wordpress 3.8 compatibility

= 2.2 =
* Fixed the plugin language translation issue. Now it can be translated to any language.

= 2.1 = 
* Minor bug fixes with the stylesheet file URL.

= 2.0 =
* Complete new architecture for the download monitor plugin
* You can download the old version with old architecture here:
http://wordpress.org/plugins/simple-download-monitor/developers/

== Upgrade Notice ==

Download monitor 2.0 uses a completely new architecture (it is much simpler and user friendly) for configuring and monitoring downloads. Read the usage section to understand how it works.
