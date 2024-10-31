=== RainmakerMoxie ===
Contributors: nsimon
Tags: ajax, contacts, crm, email, favicon, facebook, flickr, foursquare, fullcontact, gmail, google, gravatar, i18n, identity, linkedin, myspace, plancast, profile, rainmaker, sendgrid, social, twitter, widget, yahoo, youtube
Requires at least: 2.8
Tested up to: 3.2.1
Stable tag: trunk

RainmakerMoxie (BETA-limited support) is an interactive sidebar widget. Enter an email address and it displays a photo, name, social links and more.

== Description ==

**Enter an email address and available contact information is displayed:**

* Photo
* Name
* Demographic information
* Personal interests
* Clickable social links (LinkedIn, Twitter, Facebook, ...)
* Recent [Twitter](http://twitter.com/) updates
* Recent [Plancast](http://plancast.com/) plans
* *Optionally*, emails the displayed contact information to any valid email address

Contact information provided by [FullContact](http://fullcontact.com).

Email services provided by [SendGrid](http://sendgrid.com/).

== Installation ==

**Plugin Requirements:**

* PHP 5.2.0 or greater.
* WordPress 2.8 or greater.
* cURL PHP extension (only if using the optional SendGrid emailer).

**API Keys:**

* REQUIRED: [FullContact API key](http://fullcontact.com/). To be entered on the RainmakerMoxie Plugin options page.
* OPTIONAL: [SendGrid Username and API key](http://sendgrid.com/user/signup/). To be entered on the RainmakerMoxie Plugin options page.

**Installation Instructions:**

1. Upload the rainmakermoxie folder to: `/wp-content/plugins/`

2. Login to your WordPress admin dashboard.

3. Select the **Plugins** link.

4. **Activate** RainmakerMoxie.

5. Select **click here to configure the Plugin**

6. Configure the Plugin options:
   - Enter your REQUIRED FullContact API key.
   - Update any of the REQUIRED defaults: Max Tweets, Max Plancast Past Plans, Max Plancast Upcoming Plans, Sidebar Title.
   - OPTIONALLY, enter your SendGrid Username and API key.
   - Press the **Save** button to save the options.

7. Setup as a sidebar widget:
   - Select the **Widgets** link.
   - Drag **RainmakerMoxie** from Available Widgets into the Sidebar.

8. That's it. Enjoy the RainmakerMoxie Plugin!

== Screenshots ==

1. Sidebar widget style 1: Lookup and display contact information.

2. Sidebar widget style 2: Lookup and display contact information AND email it to any email address.

3. Sidebar widget with example contact information displayed.

4. RainmakerMoxie Plugin options page.

== Changelog ==

= 1.1.9 : 2012-05-19 =
* Fixed social URLs ending in "/content" to not be displayed.
* Added social chicklet: disqus.

= 1.1.8 : 2012-05-19 =
* Updated API endpoint to support new verion of the FullContact API.
* Added Romanian language support, thanks to Alexander Ovsov of Web Geek Science and [Web Hosting Geeks](http://webhostinggeeks.com/)

= 1.1.7 : 2011-08-29 =
* Update registration link to go to new landing page.
* Added social chicklets: fullcontact, peerindex.

= 1.1.6 : 2011-08-08 =
* Updates: screenshot images, language files, fullcontact logo.

= 1.1.5 : 2011-08-07 =
* Update API key references to http://fullcontact.com

= 1.1.4 : 2011-07-09 =
* On flickr social link urls, replace 'people' with 'photos' to point to photostream page.

= 1.1.3 : 2011-07-09 =
* Improved selection of gravatar photo to be from a "secure" url only.

= 1.1.2 : 2011-07-09 =
* Added social clicklet googleplus.

= 1.1.1 : 2011-07-04 =
* Do not attempt to append blank organization titles.

= 1.1.0 : 2011-07-03 =
* Sort displayed interests.

= 1.0.9 : 2011-07-03 =
* Added social clicklet hi.im.
* Sort displayed social links.

= 1.0.8 : 2011-07-01 =
* appendSocialLinks(): Check for blank URLs before appending links.
* Added social clicklet livejournal, multiply, tripit, wordpress.

= 1.0.7 : 2011-07-01 =
* Social type: ignore 'MySpace', but still handle original 'Myspace'.
* Added social clicklets klout, googlereader, soundcloud.

= 1.0.6 : 2011-06-30 =
* Moved sgUsername/sgApiKey out of if() block in displayAjaxForm().

= 1.0.5 : 2011-06-29 =
* Ignore duplicate 'stack overflow' social link creation.
* Reduce textbox width from 16em to 15.5em.

= 1.0.4 : 2011-06-28 =
* Updated all language localization files for updated strings.
* Split-up the "Rainmaker API key required" string into start + get_one_here_url + end.

= 1.0.3 : 2011-06-28 =
* Shortened textbox text for both boxes.
* Adjusted css textbox width (to 16em) and font-size (to .85) to optimize same-line spinner.

= 1.0.2 : 2011-06-27 =
* Updated css textbox properties to be in proportional em's.

= 1.0.1 : 2011-06-27 =
* If Rainmaker API key was not entered, added sidebar link to "Get one here".

= 1.0.0 : 2011-06-26 =
* Added Spanish(MX), German, and Italian language localization files.
* Released as version 1.0.0.

= 0.6.7 : 2011-06-24 =
* Created css textbox width property for cross-browser compatibility. Tested on Firefox, IE 7, and Safari.

= 0.6.6 : 2011-06-24 =
* Modified sidebar and email gravatar ALT and TITLE text to be contact name.
* Modified email attribution line from "Powered by Rainmaker" to "Powered by Rainmaker and SendGrid".
* Now traps Rainmaker 403 return code (api key missing, invalid, or limit exceeded).
* Simplified "Tags:" in php and readme.

= 0.6.5 : 2011-06-23 =
* Added additional social link chicklets.

= 0.6.4 : 2011-06-22 =
* Fix to gracefully handle empty return buffer from Rainmaker.

= 0.6.3 : 2011-06-22 =
* Initial version.
