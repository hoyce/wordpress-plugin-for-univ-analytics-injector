=== Google Analytics Injector ===

Contributors: hoyce
Donate link:
Tags: google, google analytics, universal analytics, analytics, statistics, stats, javascript, ga, web analytics
Requires at least: 3.4
Tested up to: 4.0
Stable tag: 1.0.1

== Description ==

Universal Analytics Injector for Wordpress is a plugin which makes it easy for you to start collecting statistics with Google Analytics on your WordPress blog.
This plugin make use of additional functionality for more advanced analytics tracking eg. outbound links, videos, mailto links, downloads etc.
After the installation of the plugin you just click on the "US Injector" in the "Settings" menu and add your
Google Tracking code (eg. UA-xxxxx-1) and your domain (eg. .mydomain.com) in the admin form.


This plugin also exclude the visits from the Administrator if he/she is currently logged in.

== Installation ==

Using the Plugin Manager

1. Click Plugins
2. Click Add New
3. Search for "universal analytics injector"
4. Click Install
5. Click Install Now
6. Click Activate Plugin

Manually

1. Download and unzip the plugin
2. Upload the `universal-analytics-injector` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Settings for the Universal Analytics Injector

== Changelog ==

= 1.0.1 =
* Added plugin name in readme.txt
* Added screen shot

= 1.0.0 =
* Initial release
* Added a disable function for each tracking option with jQuery based gui disable functionallity.
* Added a custom category label for each tracking option.
* Added option for using _anonymizeIp in the script
* Added detection for multiple loading of the same Google Analytics script e.g same UA-account multiple times
* Excluding logged in admins
* Added this plugin to Github https://github.com/hoyce/universal-analytics-injector
* Tested stability up to Wordpress 4.0
