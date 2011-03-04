=== HL Twitter ===
Contributors: Dachande663
Donate link: http://hybridlogic.co.uk/code/wordpress-plugins/hl-twitter/
Tags: twitter, tweet, post, auto tweet, social, social media, backup, hybridlogic
Requires at least: 2.9.2
Tested up to: 3.1
Stable tag: trunk

HL Twitter stores tweets from multiple accounts and displays them via a customisable widget as well as supprting auto-tweeting new posts.

== Description ==

HL Twitter lets you display your tweets as a widget in your sidebar. But it also does a whole lot more. You can track multiple Twitter accounts and store all of the tweets on your blog indefinitely (currently Twitter only keep your 3,200 most recent tweets) as well as pulling in any tweets that you reply to for future reference. You can then tweet from your Dashboard or have HL Twitter automatically tweet your new posts with a customisable message.

== Installation ==

1. Upload hl_twitter directory to your /wp-content/plugins directory
2. Activate plugin in WordPress admin
3. In WordPress admin, go to Twitter. You will be asked to link the plugin to your Twitter account.
4. Add the user(s) you wish to store tweets for. You can also import their most recent tweets.
5. To tweet from within WordPress visit the WordPress Admin Dashboard or look at the bottom of a Post page

To modify the widget theme:

1. Copy the hl_twitter_widget.php file from /wp-content/plugins/hl_twitter to /wp-content/themes/*your-current-theme*/
2. Edit the new hl_twitter_widget.php file in your theme directory
3. You can now update the plugin as normal and your changes will not be overwritten

== Frequently Asked Questions ==

= The link to Twitter button is stuck on loading / never loads? =

You must make sure that your server supports cURL, and more explicitly multi_curl, in PHP. Google will provide more information.

= Why can I only import 3,200 tweets? =

Twitter currently limit access to the 3,200 most recent tweets for an account. If they increase this limit, HL Twitter will also increase.

= Why aren't all my tweets being pulled in? =

Twitter limits applications to a set number of requests per hour. If you are tracking a lot of people you may hit this limit before HL Twitter has finished importing all new tweets.

= How do I enable auto-tweeting? =

Auto-tweeting, having HL Twitter tweet a new message whenever you publish a post or page, is disabled by default. To enable it go to Twitter -> Settings in your WordPress admin. You can also change the default text that is shown in the tweet. When publishing a new post or page, you will not be able to choose whether or not to tweet for this post.

== Screenshots ==

1. Example user list showing tweet, follower and friend counts.
2. Default widget styling with the WordPress TwentyTen theme.

== Changelog ==

= 2011.3.1 =
* Auto-tweet now checks to make sure tweet isn't empty
* Added multi_curl support info

= 2010.7.3 =
* Initial development

= 2010.7.4 =
* Importer now loads all tweets for user

= 2010.7.18 =
* Importer now pulls multiple twitter accounts

= 2010.7.28 =
* Switch to OAuth
* Import now works asynchronously across users

= 2010.9.1 =
* Major bug fixes, admin design tweaks

= 2010.9.3 =

* First public release
* Added widget + controls
* Added WordPress event scheduling handlers

= 2010.9.12 =

* Added auto-tweet ability
* Added Feedback panel

= 2010.9.13 =

* Avatars are now resized and cached locally (thanks to Scotts for the heads up)

= 2010.9.15 =

* Emergency fix; a regression bug was present in 2010.9.13 that affected all plugin users.

= 2010.9.15b =

* Updated the auto-tweet feature to support more fields and improve performance
* Widget now has more options including setting a title and hiding avatars
* Added support for WordPress 2.9.2

== Upgrade Notice ==

= 2010.9.3 =
First public release

= 2010.9.15 =
Fixes a bug caused by 2010.9.13 update. Very sorry to anyone who downloaded the plugin and was affected in this interim period.