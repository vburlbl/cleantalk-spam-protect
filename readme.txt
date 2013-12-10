=== Anti-spam by CleanTalk ===
Contributors: znaeff, shagimuratov
Tags: antispam, anti-spam, spam, captcha, comment, comments, wpmu, network, multisite, forms, registration, login, contact form, buddypress, admin, user, users, post, posts, wordpress, javascript, plugin, blacklists, cloud, math
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 2.27
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Cloud antispam for comments, signups and contacts.

== Description ==
1. Stops spam bots comments.
1. Stops spam bots signups.
1. Stops spam bots emails from contact forms.
1. No Captcha, no questions, no counting animals, no puzzles, no math.

= ANTISPAM for =
* WordPress comments and signups.
* <a href="http://wordpress.org/plugins/buddypress/" target="_blank">BuddyPress</a> signups.
* <a href="http://wordpress.org/plugins/formidable/" target="_blank">Formiadble forms</a>.
* <a href="http://wordpress.org/plugins/contact-form-7/" target="_blank">Contact form 7</a>.
* <a href="http://wordpress.org/plugins/jetpack/" target="_blank">JetPack Contact form</a>.

Spam comments moves to SPAM folder. First comment from a new author plugin compares with post and previous comments. If the relevance of the comment is good enough it gets approval at the blog without manual approval.

The plugin is a client application for anti-spam cloud service <a href="http://cleantalk.org" target="_blank">cleantalk.org</a>. CleanTalk.org daily prevents from spam 5000 blogs, blocks up to 500 000 spam bots attacks and approves up to 4000 not spam comments. 

The plugin is WordPress MultiUser (WPMU or WordPress network) compatible. Each blog in multisite environment has individual anitspam options for spam bots protection.


= Requirements =
WordPress 3.0 at least. PHP 4, 5 with CURL or file_get_contents() function and enabled 'allow_url_fopen' setting. <a href="http://cleantalk.org/register?platform=wordpress">Sign up</a> to get an Access key.

= Translations =
* English
* Russian (ru_RU)
* Spain (es_ES) - thanks to Andrew Kurtis and <a href="http://www.webhostinghub.com/index-c.html?utm_expid=31925339-46.KEGZK8A6Q3yfZW0EUfEw5Q.1">WebHostingHub</a>

== Installation ==

1. Download, install and activate the plugin. 
1. Get and enter the Access key in the settings
<a href="http://cleantalk.org/register?platform=wordpress" target="_blank">http://cleantalk.org/register</a>
1. Enjoy the blog without spam.

== Frequently Asked Questions ==

= How plugin stops spam? =
Plugin uses several simple tests to stop spammers.

* Spam bots signatures.
* Blacklists checks by Email, IP, web-sites domain names.
* JavaScript availability.
* Comment submit time.
* Relevance test for the comment.

= How plugin works? =

Plugin sends a comment's text and several previous approved comments to the cloud. Cloud evaluates the relevance of the comment's text on the topic, tests on spam and finaly provides a solution - to publish or put on manual moderation of comments. If a comment is placed on manual moderation, the plugin adds to the text of a comment explaining the reason for the ban server publishing.

= Will plugin works with my theme? =

Plugin works with all WordPress themes. With some themes may not works JavaScript antispam method, but it's not crucial to protect your blog from spam.

= How can I test antispam protection? =
Please use test email stop_email@example.com for comments. Also you can see comments proccessed by plugin for last 7 days at <a href="http://cleantalk.org/my/show_requests">Control panel</a> or look at folder "Spam" for banned comments.

= How the plugin is effective against spam bots? =
Plugin Antispam by CleanTalk stops about 99.99% of spam comments by spam bots. More over, by determining the relevance of the comment text, the plugin stops about 96% spam comments submitted manually via browser. 

= What about pingback, trackback spam? = 
Plugin by default pass not spam pingbacks/trackbacks (sender host clear at <a href="http://cleantalk.org/blacklists">Blacklists IP</a> database) from third-party sites to the blog. If the pingback has more then 3 records in the Blacklists and not relevant to the blog the pingback will be stopped by CleanTalk.

= Why do I need one more anti-spam plugin? =
1. The plugin is more effective than CAPTCHA because use several methods to stop spammers.
1. This plugin stops spam bots automatically, plugin invisible for blog visitors and admins.
1. CleanTalk automatically approves relevant, not spam comments.

= Should I use another antispam plugins? =
Use other antispam plugins not necessarily, because CleanTalk stops 99.99% of spam comments. But if necessary, the plugin can work together with Akismet, Captcha and etc.

== Screenshots ==
1. The comment from spammer (sender blacklisted by IP/Email, comment text not relevant for the post) prohibited to place in the queue WordPress.
1. Not spam, not relevant to article comment has moved to approval. 
1. Antispam stoppped spam bot at the registration form. 
1. Spam bot stopped at Formidable contact form. 
1. Spam bot stopped at Contact form 7. 

== Changelog ==

= 2.27 2013-12-06 =
  * New: Added protection against spam bots for JetPack Contact form. 
  * Fixed: JavaScript antispam logic for registrations and Contact form 7.

= 2.25 2013-11-27 =
  * New: Added protection against spam bots for BuddyPress registrations. 
  * New: Added protection against spam bots for Contact form 7. 
  * New: Added Spanish (es_ES) translation. 

= 2.23 2013-11-20 =
  * New: Added automatic training blacklists on spam bot account deletion. 
  * New: Added URL to project homepage at plugin options. 
  * Changed: Improved antispam logic. 

= 2.21 2013-11-13 =
  * Changed: WordPress blacklists settings get priority over plugin's antispam settings 
  * Changed: Disabled management approval comments for regular commentators of the blog. Automatically approved for publication only the comments of the new blog authors. 
  * Changed: Removed form submit time test. Imporved JavaScript spam test. 
  * Changed: PHP code optimizations 

= 2.19 2013-11-08 =
  * New: Antispam protection from spam bots at the registration form
  * Changed: Russian localization for admin panel 
  * Changed: PHP code optimizations 

= 2.5.18 2013-11-01 =
  * Fixed: Bug with selection of the last comments for post
  * New: Antispam protection for Formiadble feedback forms
  * New: Automatic deletion of outdated spam comments 
  * New: On/Off option for comments spam filtration 
  * Tested with WordPress 3.7.1

= 2.4.15 2013-09-26 =
  * Fixed: Bug with mass comments deletion 
  * Changed: Russian localization for admin panel 
  * Tested with mulitsite setup (WordPress network or WPMU) 

= 2.4.14 2013-08-29 =
  * Changed: Removed feedback requests to the servers for banned (spam) comments. 

= 2.4.13 2013-08-19 =
  * Changed: Switched HTTP requests from file_get_contents() to CURL. Added file_get_contens() as backup connection to the servers. 
  * Changed: Removed feedback requests for comments moved to trash. 
  * Fixed: "Fail connect to servers..." error on hostings with disabled 'allow_url_fopen' PHP option.

= 2.4.12 2013-08-12 =
  * Removed RPC::XML library from plugin. 
  * Switched plugin to HTTP+JSON connection with servers.
  * Fixed bug with comments antispam tests with non UTF8 codepage.

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

== Upgrade Notice ==
= 2.27 2013-12-06 =
  * New: Added protection against spam bots for JetPack Contact form. 
  * Fixed: JavaScript antispam logic for registrations and Contact form 7.

= 2.25 2013-11-27 =
  * New: Added protection against spam bots for BuddyPress registrations. 
  * New: Added protection against spam bots for Contact form 7. 
  * New: Added Spanish (es_ES) translation. 

= 2.23 2013-11-20 =
  * New: Added automatic training blacklists on spam bot account deletion. 
  * New: Added URL to project homepage at plugin options. 
  * Changed: Improved antispam logic. 

= 2.21 2013-11-13 =
  * Changed: WordPress blacklists settings get priority over plugin's antispam settings 
  * Changed: Disabled management approval comments for regular commentators of the blog. Automatically approved for publication only the comments of the new blog authors. 
  * Changed: PHP code optimizations

= 2.19 2013-11-08 =
  * New: Antispam protection from spam bots at the registration form
  * Changed: Russian localization for admin panel 
  * Changed: PHP code optimizations 

= 2.5.18 2013-11-01 =
  * Fixed: Bug with selection of the last comments for post
  * New: Antispam protection for Formiadble feedback forms
  * New: Automatic deletion of outdated spam comments 
  * New: On/Off option for comments spam filtration 
  * Tested with WordPress 3.7.1

= 2.4.15 2013-09-26 =
  * Fixed: Bug with mass comments deletion 
  * Changed: Russian localization for admin panel 
  * Tested with mulitsite setup (WordPress network or WPMU) 

= 2.4.14 2013-08-29 =
  * Changed: Removed feedback requests to the servers for banned (spam) comments.

= 2.4.13 2013-08-19 =
  * Fixed: "Fail connect to servers..." error on hostings with disabled 'allow_url_fopen' PHP option.

