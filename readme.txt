=== Plugin Name ===
Contributors: znaeff, default.asp, shagimuratov, aleontiev
Tags: comments, spam, blocking, filter, autopublication, cleantalk, anti-spam, antispam, captcha, comment, approve, spambot, recaptcha
Requires at least: 3.1.2
License: GPLv2 
Tested up to: 3.5.1
Stable tag: trunk

Spam protection of the WordPress comments. With CleanTalk you can remove a CAPTCHA and forget about spam in the blog

== Description ==

Plugin use several simple tests to stop spam in comments

1. Blacklists checks by Email, IP in lists with several billions records.
1. JavaScript availability.
1. Comment submit time.
1. Relevance test for the comment.
1. Spam templates in comments

The plugin stops 99% spam in comments without move to yours trash or manual approval queue.

Main feature of the plugin is an automatic approval relevant, not spam comments. Every new comment plugin compares with article and previous comments. If the relevance of the comment is good enough it gets approval at the blog without manual approval.

= All features ot the plugin =
1. Stop spam in comments.
1. Not using CAPTCHA.
1. Stop comments with swear, negative words.
1. Automatic approval not spam comments.

This plugin is a client application for spam protection service cleantalk.org. It is free to use for small and medium sized blogs.

= Translation =

* English (en_EN)
* Russian (ru_RU)

== Installation ==

Please use <a href="http://cleantalk.org/install/wordpress" target="_blank">Setup manual</a> at the plugin's site

== Frequently Asked Questions ==

= Why do I need one more anti-spam plugin? =

1. This plugin use several tests to filter spam, that's why it have the best performance.
1. CleanTalk automatically approves relevant, not spam comments.

== Screenshots ==

1. Plug-in's work scheme 
1. CleanTalk stops spam comment
1. CleanTalk settings to filter spam in the blog 
1. Service Control panel at cleantalk.org 

== Changelog ==

= 2.1.2 =
  * Secured md5 string for JavaScript test
  * Added requests's timestamp to calculate request work time
  * Update base CleanTalk's PHP class

= 2.1.2 =
  * Improved perfomance for processing large comments (over 32kb size)
  * Improved perfomance for bulk operations with comments in Comments panel 
  * Added feedback request with URL to approved comment 

= 2.0.2 =
  * Fixed bug with JavaScript test and WordPress cache plugins 

= 2.0.1 =
  * Added option "Publicate relevant comments" to plugin's options. 
  * Added descriptions to plugin options

= 1.5.4 =
  * Fixed HTTP_REFERER transmission to the servers 
  * Improved JavaScript spam test
  * Optimized PHP code

= 1.4.4 =
  * Pingback, trackback comments has moved to manual moderataion
  * Added transmission to the serves comment type and URL
  * Post title, body and comments separated into individual data elements
  * Added priority for matched words in the comment with post title
  * Enabled stop words filtration as default option 

= 1.3.4 =
  * Removed PHP debugging.

= 1.3.3 =
  * Added notice at admin panel about empty Access key in plugin settings
  * Removed HTTP link to the site project from post page
  * Removed unused options from settings page
  * Tested up to WordPress 3.5

= 1.2.3 =
 * Fixed bug with session_start.

= 1.2.2 =
  * Plugin rename to CleanTalk. Spam prevent plugin
  * Integration Base Class version 0.7
  * Added fast submit check
  * Added check website in form
  * Added feedbacks for change comment status (Not spam, unapprove)
  * Added function move comment in spam folder if CleanTalk say is spam
  * Disable checking for user groups Administrator, Author, Editor
  * Marked red color bad words

= 1.1.2 =
  * Addition: Title of the post attached to the example text in auto publication tool.
  * Tested with WordPress 3.4.1.

= 1.1.1 =
  * HTTP_REFERER bug fixed

= 1.1.1 =
  * Added user locale support, tested up to WP 3.4

= 1.1.0 =
  * First version

== Upgrade Notice ==

= 1.1.2 =
* Addition: Title of the post attached to the example text in auto publication tool.
* Tested with WordPress 3.4.1.

= 1.1.1 =
* Added user locale support, tested up to WP 3.4

= 1.1.0 =
* First version
