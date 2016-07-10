=== Simple Download Monitor ===
Contributors: Tips and Tricks HQ, Ruhul Amin, josh401, mbrsolution
Donate link: https://www.tipsandtricks-hq.com
Tags: download, downloads, count, counter, tracker, tracking, hits, logging, monitor, manager, files, media, digital, download monitor, download manager, downloadmanager, file manager, protect downloads, password, download category, file tree, ajax, download template, grid, documents, ip address
Requires at least: 3.0
Tested up to: 4.6
Stable tag: 3.3.2
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

https://www.youtube.com/watch?v=L-mXbs7kp0s

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
* Option to upload a thumbnail image for each of your downloadable files.
* Option to use a nice looking template to show your download now buttons.
* Ability to search and sort your downloadable files in the admin dashboard.
* Track the number of downloads for each of your files.
* Track the visitors country.
* WordPress Shortcode for embedding a download link for a file.
* Tinymce button in the WordPress post/page editor so you can easily add the shortcode.
* You can customize the "Download Now" button text of an item to anything you want.
* Ability to add the download now buttons to your sidebar widget.
* Create password protected download now buttons. Users will only be able to download the file if they enter the correct password. [View the tutorial](http://www.tipsandtricks-hq.com/create-a-password-protected-download-file-6838)
* Shortcode to show the download counter of a file. Use it to show off your file download count.
* Shortcode to show all the downloads from a particular category.
* Shortcode to embed a file tree browser for your downloadable files. The file browser is ajax based and it shows the files structured by categories.
* Ability to open the downloads in new browser window or tab. When your users click on a download button, it will open in a new window or tab.
* Ability to show your downloads in a grid display. [View the tutorial](https://www.tipsandtricks-hq.com/show-file-downloads-in-a-nice-grid-display-7273)
* Export all the file download logs to a CSV file.
* Ability to reset the log entries.
* Shortcode to show a number of latest downloads to your visitors.
* Ability to disable the download monitoring (logging) for certain items (or all items).
* You can also choose to only monitor downloads from unique IP address only.
* Option to specify file size info so it can be shown to your visitors.
* Option to specify version number info for the download item so it can be shown to your visitors.

View more details on the [download monitor plugin](https://www.tipsandtricks-hq.com/simple-wordpress-download-monitor-plugin) page.

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

**D) Download logs**

You can check the download stats from the "Downloads->Logs" interface. It shows the number of downloads for each files, IP address of the user who downloaded it, date and time of the download.

= Detailed Usage Documentation =

View more usage instructions on the [Download Monitor Plugin](https://www.tipsandtricks-hq.com/simple-wordpress-download-monitor-plugin) page.

= Github Repository =

https://github.com/Arsenal21/simple-download-monitor

If you need some extra action hooks or filters for this plugin then let us know.

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
