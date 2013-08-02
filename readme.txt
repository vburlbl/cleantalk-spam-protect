=== Plugin Name ===
Contributors: znaeff, default.asp, shagimuratov, aleontiev
Tags: spam, anti-spam, antispam, spambot, spam-bot, block spam, spammers, spamfree, captcha, recaptcha, comment, comments 
Requires at least: 3.1.2
License: GPLv2 
Tested up to: 3.6
Stable tag: 2.4.11 

No spam in the comments. Smart, simple anti-spam app without CAPTCHA.

== Description ==
1. Stops spam bots in the comments.
1. Invisible spam protection for visitors.
1. Anti-spam without CAPTCHA.

Plug-in filters spam bots in the comments of a blog without move to trash or approval in the queue. The plugin is not visible for visitors and administrators of a blog. The plug-in not uses CAPTCHA or Q&A to stop spam bots. It's simple and clever antispam for your blog.

Every new comment compares with article and previous comments. If the relevance of the comment is good enough it gets approval at the blog without manual approval.

This plugin is a client application for anti-spam service cleantalk.org. It is free to use for small and medium sized blogs.

== Installation ==

Please use <a href="http://cleantalk.org/install/wordpress" target="_blank">Setup manual</a> at the plugin's site

== Frequently Asked Questions ==

= How plugin stops spam? =
Plugin uses several simple tests to stop spammers.

* Spam bots signatures.
* Blacklists checks by Email, IP, web-sites domain names.
* JavaScript availability.
* Comment submit time.
* Relevance test for the comment.

= How plugin works? =

Plugin sends a comment's text and several previous approved comments to the servers. Servers evaluates the relevance of the comment's text on the topic, tests on spam and finaly provides a solution - to publish or put on manual moderation of comments. If a comment is placed on manual moderation, the plugin adds to the text of a comment explaining the reason for the ban server publishing.

= Why do I need one more anti-spam plugin? =

1. The plugin is more effective than CAPTCHA because use several methods to stop spammers.
1. This plugin stops spam bots automatically, plugin invisible for blog visitors and admins.
1. CleanTalk automatically approves relevant, not spam comments.

== Screenshots ==

1. Plug-in's anti-spam work scheme 
1. CleanTalk stops spammer comment
1. CleanTalk settings to filter spam bots

== Changelog ==

= 2.4.11 2013-08-02 =
  * Removed spam tests for self-made pingbacks 
  * Tested up to WP 3.6

= 2.4.10 2013-07-24 =
  * Fixed warning in PHP 5.4
  * Fixed bug with disabling comments test for Administrators, Authors and Editors 
  * "Stop words" settings moved to <a href="http://cleantalk.org/my">Control panel</a> of the service
  * "Response language" settings moved <a href="http://cleantalk.org/my">Control panel</a> of the service

= 2.4.9 =
  * Fixed extra debugging in base class 

= 2.4.8 =
  * Enabled convertion to UTF8 for comment and example text 
  * Optimized PHP code 

= 2.3.8 =
  * Enabled selection the fastest server in the pool 
  * Fixed work server in plugin's config

= 2.2.3 =
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
