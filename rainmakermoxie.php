<?php

/*
Plugin Name: RainmakerMoxie
Plugin URI: http://wordpress.org/extend/plugins/rainmakermoxie/
Description: Enter an email address and the RainmakerMoxie Plugin retrieves contact information from <a href="http://fullcontact.com">FullContact</a> and displays it in the sidebar. <strong>After activation:</strong> <a href="options-general.php?page=rainmakermoxie/rainmakermoxie.php">click here to configure the Plugin</a>.
Version: 1.1.9
Author: Neil Simon
Author URI: http://solidcode.com/
*/

/*
 Copyright 2012 Solidcode.

 This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
 License as published by the Free Software Foundation; either version 2 of the License, or any later version.

 This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

 For a copy of the GNU General Public License, please write to the Free Software Foundation,
 51 Franklin St, 5th Floor, Boston, MA 02110 USA.
*/

main ();

/******************************************************************************/
/* Main execution control                                                     */
/******************************************************************************/

function main ()
    {
    // Internal plugin constants
    define ('RAINMAKER_MOXIE_PLUGIN_VERSION',                '1.1.9');
    define ('RAINMAKER_MOXIE_PLUGIN_VERSION_FOR_HIDDEN_DIV', 'RainmakerMoxie-' . RAINMAKER_MOXIE_PLUGIN_VERSION);
    define ('RAINMAKER_MOXIE_SETTINGS_OPTIONS',              'rainmakerMoxieOptions');

    // Rainmaker URLs
    define ('RAINMAKER_MOXIE_RAINMAKER_HOME_URL',            'http://fullcontact.com/');
    define ('RAINMAKER_MOXIE_RAINMAKER_REGISTER_URL',        'http://fullcontact.com/getkey/?appname=rainmakermoxie');
    define ('RAINMAKER_MOXIE_RAINMAKER_PERSON_URL',          'http://api.fullcontact.com/v2/person.json');

    // SendGrid URLs
    define ('RAINMAKER_MOXIE_SENDGRID_HOME_URL',             'http://sendgrid.com/');
    define ('RAINMAKER_MOXIE_SENDGRID_MAILSEND_URL',         'http://sendgrid.com/api/mail.send.json');
    define ('RAINMAKER_MOXIE_SENDGRID_PROFILEGET_URL',       'http://sendgrid.com/api/profile.get.json');
    define ('RAINMAKER_MOXIE_SENDGRID_SIGNUP_URL',           'http://sendgrid.com/user/signup/');

    // Twitter URLs
    define ('RAINMAKER_MOXIE_TWITTER_USERTIMELINE_URL',      'http://api.twitter.com/1/statuses/user_timeline.json');

    // Plancast URLs
    define ('RAINMAKER_MOXIE_PLANCAST_PLANSUSER_URL',        'http://api.plancast.com/02/plans/user.json');

    // Options constants
    define ('RAINMAKER_MOXIE_MAX_TWEETS',                    20);
    define ('RAINMAKER_MOXIE_MAX_PAST_PLANS',                20);
    define ('RAINMAKER_MOXIE_MAX_UPCOMING_PLANS',            20);

    // E.g. "http://solidcode.com/wp-content/plugins/rainmakermoxie/"
    define ('RAINMAKER_MOXIE_PLUGINDIR',
             WP_PLUGIN_URL . '/' . str_replace (basename (__FILE__), '', plugin_basename (__FILE__)));

    // Initialize for localized strings
    //
    // If (the WordPress config file "wp-config.php" has a language defined, e.g. ('WPLANG', 'it_IT'))
    //     {
    //     WordPress searches the plugin dir for a localized strings file and loads it (e.g. "rainmakermoxie-it_IT.mo")
    //     }
    load_plugin_textdomain ('rainmakermoxie', 'wp-content/plugins/rainmakermoxie');

    // Runs once at plugin activation time
    register_activation_hook (__FILE__, 'rainmakerMoxie_createOptions');

    // Runs once at plugin deactivation time
    register_deactivation_hook (__FILE__, 'rainmakerMoxie_deleteOptions');

    // Load as sidebar widget
    add_action ('plugins_loaded', 'rainmakerMoxie_initWidget');

    // Add RainmakerMoxie submenu to the admin Setting page
    add_action ('admin_menu', 'rainmakerMoxie_addSubmenu');

    // Add RainmakerMoxie submenu to the admin Setting page
    add_action ('admin_head', 'rainmakerMoxie_add_options_stylesheet');

    // Javascript/jQuery/Ajax triggered via "return key press" in #emailAddressToLookup or #emailAddressToSendResults
    add_action ('wp_ajax_get_rainmaker',        'get_rainmaker_ajax');
    add_action ('wp_ajax_nopriv_get_rainmaker', 'get_rainmaker_ajax');

    // Inject the rainmakermoxie.plugin.css into the page header
    add_action ('wp_head', 'rainmakerMoxie_register_head');

    // Ensure that jquery gets loaded (if already loaded, that's ok)
    wp_enqueue_script ('jquery');
    }

/******************************************************************************/
/* Action: Runs on RainmakerMoxie options page loads at Wordpress <head> time */
/******************************************************************************/

function rainmakerMoxie_add_options_stylesheet ()
    {
    // Add rainmakermoxie.options.css to the <head>
    $cssUrl = get_option ('siteurl') . '/wp-content/plugins/rainmakermoxie/rainmakermoxie.options.css';
    printf ("<link rel='stylesheet' type='text/css' media='screen' href='$cssUrl' />\n");
    }

/******************************************************************************/
/* Action: Runs on all page loads at Wordpress <head> time                    */
/******************************************************************************/

function rainmakerMoxie_register_head ()
    {
    // Add rainmakermoxie.plugin.css to the <head>
    $cssUrl = get_option ('siteurl') . '/wp-content/plugins/rainmakermoxie/rainmakermoxie.plugin.css';
    printf ("<link rel='stylesheet' type='text/css' media='screen' href='$cssUrl' />\n");

    // Addrainmakermoxie.plugin.js to the <head>
    $jsUrl = get_option ('siteurl') . '/wp-content/plugins/rainmakermoxie/rainmakermoxie.plugin.js';
    printf ("<script type='text/javascript' src='$jsUrl'></script>\n");

    // Add javascript var "wpurl" to the <head> (used in rainmakermoxie.plugin.js)
    printf ("<script type='text/javascript'>var wpurl=\"%s\"</script>\n", get_option ('siteurl'));

    // Add localized string to be accessible in the js
    $emailResultsToStr = __('Email to: (optional)', 'rainmakermoxie');
    printf ("<script type='text/javascript'>var emailResultsToStr=\"%s\"</script>\n", $emailResultsToStr);

    // Add localized string to be accessible in the js
    $enterEmailAddressToLookupStr = __('Look up email address:', 'rainmakermoxie');
    printf ("<script type='text/javascript'>var enterEmailAddressToLookupStr=\"%s\"</script>\n", $enterEmailAddressToLookupStr);

    // Add javascript var "dontWorryBeHappy" to the <head> (used in rainmakermoxie.plugin.js)
    // This varname is disguised to not draw attention
    printf ("<script type='text/javascript'>var dontWorryBeHappy=\"%s\"</script>\n",
            wp_create_nonce ('rainmakerMoxieVerificationToken'));
    }

/******************************************************************************/
/* Runs on all page loads during the widget rendering -- via initWidget()     */
/******************************************************************************/

function rainmakerMoxie_displayAjaxForm ()
    {
    // Initialize
    $poweredByHtml = "";

    // Extract option from wp database
    $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);
    if ($rainmakerMoxieOptions ['showPoweredBy'] == TRUE)
        {
        // Build this for later display
        $poweredByHtml .= '<p class="cPoweredBy"><a href="' . RAINMAKER_MOXIE_RAINMAKER_HOME_URL .
                          '" target="_blank">Powered by FullContact</a></p>';
        }

    // Extract (could be blank -- that's ok)
    $sgUsername = $rainmakerMoxieOptions ["sgUsername"];
    $sgApiKey   = $rainmakerMoxieOptions ["sgApiKey"];

    // This is the entire html for the sidebar display portion of the plugin
    // #iRainmakerMoxieOutput gets dynamically updated in the DOM via get_rainmaker_ajax()
    printf ("<div class='wrap'>\n");

    // OPTIONALLY, if both SendGrid fields are specified from the options screen...
    if (($sgUsername != "") && ($sgApiKey != ""))
        {
        // Display the emailAddressToSendResults textbox
        printf ("    <!-- Create the emailAddressToSendResults textbox  -->\n");
        printf ("    <p><input class='cEmailAddressToSendResults' type='text' id='emailAddressToSendResults' name='emailAddressToSendResults' " .
                "        value='' maxlength='64' />&nbsp;&nbsp;<span class='cEmailSent' id='emailSent'></span></p>\n");
        }

    // Display the emailAddressToLookup textbox
    printf ("    <!-- Create the emailAddressToLookup textbox  -->\n");
    printf ("    <p><input class='cEmailAddressToLookup' type='text' id='emailAddressToLookup' name='emailAddressToLookup' " .
            "        value='' maxlength='64' />&nbsp;&nbsp;<span class='cSpinner' id='spinner'></span></p><br />\n");

    // The div #iRainmakerMoxieOutput (the output results) is updated directly from the jQuery
    printf ("    <!-- Updated directly from the jquery in javascript function success (): -->\n");
    printf ("    <div id='iRainmakerMoxieOutput'></div>\n");

    // OPTIONALLY, display the "Powered by FullContact"
    printf ("    <!-- This buffer could be empty, if admin chose to not display the poweredBy string -->\n");
    printf ("    $poweredByHtml\n");

    printf ("</div>\n");
    }

/*********************************************************************************************/
/* Called from rainmakermoxie.plugin.js jQuery: If ('#emailAddressToLookup').keyup is return */
/*********************************************************************************************/

function get_rainmaker_ajax ()
    {
    // Receive the nonce value... bails automatically if it doesn't match
    check_ajax_referer ('rainmakerMoxieVerificationToken');

    // Must have an email address
    if ($_POST ['emailAddressToLookup'])
        {
        // Extract the value
        $email = $_POST ['emailAddressToLookup'];

        // Optionally, this value may be set
        $emailAddressToSendResults = $_POST ['emailAddressToSendResults'];
        
        // Extract option from wp database
        $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);
        $rainmakerApiKey = $rainmakerMoxieOptions ['rainmakerApiKey'];

        // Initialize the return JSON string
        $person = "";

        // Call the FullContact API
        if (($person = @file_get_contents (RAINMAKER_MOXIE_RAINMAKER_PERSON_URL . "?" . 
                                           "email=$email&"   . 
                                           "apiKey=$rainmakerApiKey&" . 
                                           "timeoutSeconds=30")) == FALSE)
            {
            $message = __('Unable to get contact info for that email address. Please try again.', 'rainmakermoxie');
            $buf = sprintf ("<p><span class='cError'>OOPS: </span>%s</p>\n", $message);

            echo $buf;
            }
        else
            {
            // Decode the json
            $person = json_decode ($person);

            // Create the html layout for output
            $buf = rainmakerMoxie_popuplateOutputBuffer ($person, $emailAddressToSendResults);

            // If the $buf contains "-1"...
            if ($buf == "-1")
                {
                $message = __('Unable to get contact info for that email address. Please try again.', 'rainmakermoxie');
                $buf = sprintf ("<p><span class='cError'>OOPS: </span>%s</p>\n", $message);
                }
            else
                {
                // Output it back to the success() function
                echo $buf;
                }
            }
        }

    // Required for Ajax to complete successfully
    die ();
    }

/******************************************************************************/
/* Output builder: main function that controls building the display buffer    */
/******************************************************************************/

function rainmakerMoxie_popuplateOutputBuffer ($person, $emailAddressToSendResults)
    {
    // Initialize
    $buf = '';

    if (($status = $person->status) == 403)      // STATUS 403: FORBIDDEN
        {
        $message = __('Unable to get contact info for that email address. ' .
                      'Blog administrator: The FullContact API key may be invalid, missing, or has exceeded its quota.', 'rainmakermoxie');
        printf ("<p><span class='cError'>OOPS: </span>%s (%d)</p>\n", $messsage, $status);
        }

    elseif (($status = $person->status) != 200)  // STATUS 200: OK
        {
        $message = __('Unable to get contact info for that email address', 'rainmakermoxie');
        printf ("<p><span class='cError'>OOPS: </span>%s (%d)</p>\n", $messsage, $status);
        }

    else
        {
        // Initialize
        $photoUrl     = "";
        $photoUrlType = "";

        // Find the "best" photo url
        rainmakerMoxie_extractBestPhotoUrl ($person, $photoUrl, $photoUrlType);

        // Extract contact info fields
        $fullName        = $person->contactInfo->fullName;
        $influencerScore = $person->demographics->influencerScore;
        $occupation      = $person->demographics->occupation;
        $householdIncome = $person->demographics->householdIncome;
        $age             = $person->demographics->age;
        $homeOwnerStatus = $person->demographics->homeOwnerStatus;
        $locationGeneral = $person->demographics->locationGeneral;
        $children        = $person->demographics->children;
        $gender          = $person->demographics->gender;
        $maritalStatus   = $person->demographics->maritalStatus;
        $education       = $person->demographics->education;

        // Extract interests array
        $interests = (array) $person->interests;

        // If there is a photoUrl...
        if ($photoUrl != "")
            {
            // Append it
            // Note: the "type" is used as the alt text, to know where the photo came from (e.g. twitter)
            $buf .= "<img class='cPhoto' src='$photoUrl' height='60' width='60' alt='$fullName' title='$fullName' /><br />";
            }

        // Append remaining non-blank contact info fields
        $buf .= "<p class='cPersonData'>\n";
        $contactInfoStr = __('Contact Info:', 'rainmakermoxie');
        $buf .= "<b>" . $contactInfoStr . "</b><br />\n";
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($fullName,        '');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($influencerScore, 'Influencer Score');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($occupation,      'Occupation');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($householdIncome, 'Household Income');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($age,             'Age');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($homeOwnerStatus, 'Homeowner Status');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($locationGeneral, '');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($children,        'Children');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($gender,          '');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($maritalStatus,   '');
        $buf .= rainmakerMoxie_appendTextFieldToBuf ($education,       '');

        // Append blank line after last contact info field
        $buf .= "<br />";

        // Append interests
        $buf .= rainmakerMoxie_appendInterestsToBuf ($interests);

        // Append orgs
        $buf .= rainmakerMoxie_appendOrganizationsToBuf ($person->organizations);

        // Append social profiles
        $buf .= rainmakerMoxie_appendSocialProfilesToBuf ($person->socialProfiles);

        // If there is a socialProfile twitter, append tweets
        $buf .= rainmakerMoxie_appendTwitterToBuf ($person->socialProfiles);

        // If there is a socialProfile plancast, append plans
        $buf .= rainmakerMoxie_appendPlancastToBuf ($person->socialProfiles);

        // End the buffer
        $buf .= "</p>";

        // Extract SendGrid email options
        $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);
        $sgUsername         = $rainmakerMoxieOptions ["sgUsername"];
        $sgApiKey           = $rainmakerMoxieOptions ["sgApiKey"];
        $sgDestEmailAddress = $emailAddressToSendResults;

        // If all 3 SendGrid fields are specified from the options screen...
        if (($sgUsername != "") && ($sgApiKey != "") && ($sgDestEmailAddress != ""))
            {
            // Send email
            rainmakerMoxie_packageAndSendEmail ($sgUsername, $sgApiKey, $sgDestEmailAddress, $fullName, $buf);
            }

        }

    // $buf is output as Ajax to div #iRainmakerMoxieOutput
    return ($buf);
    }

/******************************************************************************/
/* Package and send email                                                     */
/******************************************************************************/

function rainmakerMoxie_packageAndSendEmail ($sgUsername, $sgApiKey, $sgDestEmailAddress, $fullName, $buf)
    {
    // Attempt to send accompanying email
    $api_user = $sgUsername;
    $api_key  = $sgApiKey;
    $emailTo  = $sgDestEmailAddress;
    $subject  = "RainmakerMoxie: " . $fullName;
    $body     = $buf;

    // Append "Powered by FullContact and SendGrid" to the email body
    $body .= '<p class="cPoweredBy">Powered by <a href="' . RAINMAKER_MOXIE_RAINMAKER_HOME_URL . '" target="_blank">FullContact</a> and ' .
                                              '<a href="' . RAINMAKER_MOXIE_SENDGRID_HOME_URL  . '" target="_blank">SendGrid</a>' .
             '<br /><span class="cVersionNumber">v' . RAINMAKER_MOXIE_PLUGIN_VERSION . '</p>';

    $emailCss = @file_get_contents (RAINMAKER_MOXIE_PLUGINDIR . 'rainmakermoxie.email.css');

    // Prepend external html-email stylesheet to email body
    $body = '<style type="text/css"> ' . $emailCss . '</style>' . $body;

    rainmakerMoxie_sendEmail ($api_user, $api_key, $subject, $body, $emailTo);
    }

/******************************************************************************/
/* Output builder: append a single text field to the contact section          */
/******************************************************************************/

function rainmakerMoxie_appendTextFieldToBuf ($value, $name = "")
    {
    $htmlOut = "";

    // If the name is blank...
    if ($name == "")
        {
        // Commented-out for now... due to API change
        //// Append the value (if a value exists)
        //if ($value != "")
        //    {
        //    $htmlOut .= sprintf ("%s<br />\n", $value);
        //    }
        }

    // If the value is NOT blank...
    elseif ($value != "")
        {
        // The name is non-blank... append it
        // Always append something for the value, even if unknown
        $htmlOut .= sprintf ("%s: %s<br />\n", $name, $value);
        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Output builder: append all interests to the interests section              */
/******************************************************************************/

function rainmakerMoxie_appendInterestsToBuf ($interests)
    {
    $htmlOut = "";

    // If there is at least one interest...
    if (($interestsCount = count ($interests)) > 0)
        {
        // Sort alpha by key
        ksort ($interests);

        // Append the section title
        $interestsStr = __('Interests:', 'rainmakermoxie');
        $htmlOut .= sprintf ("<b>%s</b><br />\n", $interestsStr);

        // Append each interest
        foreach ($interests as $key => $interest)
            {
            // If the value is true...
            if ($interest == true)
                {
                // Append its "key name" (e.g. Music)
                $htmlOut .= sprintf ("%s<br />\n", $key);
                }
            }

        // Append blank line after last interest
        $htmlOut .= sprintf ("<br />\n");
        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Output builder: append all organizations to the organizations section      */
/******************************************************************************/

function rainmakerMoxie_appendOrganizationsToBuf ($organizations)
    {
    $htmlOut = "";

    // If there is at least one organization...
    if (($organizationsCount = count ($organizations)) > 0)
        {
        // Append the section title
        $organizationsStr = __('Organizations:', 'rainmakermoxie');
        $htmlOut .= sprintf ("<b>%s</b><br />\n", $organizationsStr);

        // Append each organization
        foreach ($organizations as $organization)
            {
            // Print the name
            $htmlOut .= sprintf ("%s", $organization->name);

            if ($organization->title != "")
                {
                // Append the title
                $htmlOut .= sprintf (", %s", $organization->title);
                }

            // br
            $htmlOut .= sprintf ("<br />\n");
            }

        // Append blank line after last organization
        $htmlOut .= sprintf ("<br />\n");
        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Output builder: append all social profiles to the social profiles section  */
/******************************************************************************/

function rainmakerMoxie_appendSocialProfilesToBuf ($socialProfiles)
    {
    $htmlOut = "";

    // If there is at least one social profile...
    if (($socialProfilesCount = count ($socialProfiles)) > 0)
        {
        // Initialize
        $socialLinks = array ();

        // Extract each socialProfile into $socialLinks array (discards dups and unsupported items)
        foreach ($socialProfiles as $socialProfile)
            {
            if ($socialProfile->message)
                {
                // skip: the presence of this field means there is no url/type info
                }

            elseif (($socialProfile->type == "other")          ||  // skip for now
                    ($socialProfile->type == "MySpace")        ||  // 'Myspace' is ok
                    ($socialProfile->type == "skype")          ||  // now handled in chats
                    ($socialProfile->type == "stack overflow") ||  // 'stackoverflow' is ok
                    ($socialProfile->type == "wordpress"))         // inconsistent return values
                {
                // Skip: this type not supported
                }

            elseif (($socialProfile->type == "linkedin") ||
                    ($socialProfile->type == "facebook") ||
                    ($socialProfile->type == "aboutme")  ||
                    ($socialProfile->type == "twitter"))
                {
                // Only add an entry that has a url and username
                // If a dup, it overwrites the existing array entry
                if (($socialProfile->url != "") && ($socialProfile->username != ""))
                    {
                    if (substr ($socialProfile->url, -7) == "content")
                        {
                        // skip: this url (as received from FullContact) will not resolve
                        }
                    else
                        {
                        $socialLinks [$socialProfile->type] = array ("type" => $socialProfile->type,
                                                                     "url"  => $socialProfile->url);
                        }
                    }
                }

            else
                {
                if (substr ($socialProfile->url, -7) == "content")
                    {
                    // skip: this url (as received from FullContact) will not resolve
                    }
                else
                    {
                    // Add social link
                    $socialLinks [$socialProfile->type] = array ("type" => $socialProfile->type,
                                                                 "url"  => $socialProfile->url);
                    }
                }
            }

        if (count ($socialLinks) > 0)
            {
            // Sort alpha by value
            asort ($socialLinks);

            // Append the social links
            $htmlOut .= rainmakerMoxie_appendSocialLinks ($socialLinks);
            }

        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Append social links                                                        */
/******************************************************************************/

function rainmakerMoxie_appendSocialLinks ($socialLinks)
    {
    $imageFolder = RAINMAKER_MOXIE_PLUGINDIR . "images";

    // Lookup table for rendering photo avatars
    $chicklets = array ("about.me"        => array ("png" => $imageFolder . "/aboutme.png",         "textToDisplay" => "About Me"),
                        "aboutme"         => array ("png" => $imageFolder . "/aboutme.png",         "textToDisplay" => "About Me"),
                        "aim"             => array ("png" => $imageFolder . "/aim.png",             "textToDisplay" => "Aim"),
                        "amazon"          => array ("png" => $imageFolder . "/amazon.png",          "textToDisplay" => "Amazon"),
                        "bebo"            => array ("png" => $imageFolder . "/bebo.png",            "textToDisplay" => "Bebo"),
                        "blogger"         => array ("png" => $imageFolder . "/blogger.png",         "textToDisplay" => "Blogger"),
                        "delicious"       => array ("png" => $imageFolder . "/delicious.png",       "textToDisplay" => "Delicious"),
                        "digg"            => array ("png" => $imageFolder . "/digg.png",            "textToDisplay" => "Digg"),
                        "disqus"          => array ("png" => $imageFolder . "/disqus.png",          "textToDisplay" => "Disqus"),
                        "dopplr"          => array ("png" => $imageFolder . "/dopplr.png",          "textToDisplay" => "Dopplr"),
                        "dribbble"        => array ("png" => $imageFolder . "/dribbble.png",        "textToDisplay" => "Dribbble"),
                        "ebay"            => array ("png" => $imageFolder . "/ebay.png",            "textToDisplay" => "Ebay"),
                        "ember"           => array ("png" => $imageFolder . "/ember.png",           "textToDisplay" => "ember"),
                        "facebook"        => array ("png" => $imageFolder . "/facebook.png",        "textToDisplay" => "Facebook"),
                        "feed"            => array ("png" => $imageFolder . "/feed.png",            "textToDisplay" => "Feed"),
                        "ffffound"        => array ("png" => $imageFolder . "/ffffound.png",        "textToDisplay" => "FFFFOUND"),
                        "fireeagle"       => array ("png" => $imageFolder . "/fireeagle.png",       "textToDisplay" => "Fire Eagle"),
                        "flickr"          => array ("png" => $imageFolder . "/flickr.png",          "textToDisplay" => "Flickr"),
                        "formspring"      => array ("png" => $imageFolder . "/formspring.png",      "textToDisplay" => "Formspring"),
                        "foursquare"      => array ("png" => $imageFolder . "/foursquare.png",      "textToDisplay" => "Foursquare"),
                        "friendfeed"      => array ("png" => $imageFolder . "/friendfeed.png",      "textToDisplay" => "FriendFeed"),
                        "friendster"      => array ("png" => $imageFolder . "/friendster.png",      "textToDisplay" => "Friendster"),
                        "fullcontact"     => array ("png" => $imageFolder . "/fullcontact.png",     "textToDisplay" => "FullContact"),
                        "geotag"          => array ("png" => $imageFolder . "/geotag.png",          "textToDisplay" => "Geotag"),
                        "getsatisfaction" => array ("png" => $imageFolder . "/getsatisfaction.png", "textToDisplay" => "Get Satisfaction"),
                        "github"          => array ("png" => $imageFolder . "/github.png",          "textToDisplay" => "Github"),
                        "goodreads"       => array ("png" => $imageFolder . "/goodreads.png",       "textToDisplay" => "Goodreads"),
                        "google reader"   => array ("png" => $imageFolder . "/googlereader.png",    "textToDisplay" => "Google Reader"),
                        "google profile"  => array ("png" => $imageFolder . "/google-profile.png",  "textToDisplay" => "Google Profile"),
                        "googleprofile"   => array ("png" => $imageFolder . "/google-profile.png",  "textToDisplay" => "Google Profile"),
                        "googleplus"      => array ("png" => $imageFolder . "/googleplus.png",      "textToDisplay" => "Google+"),
                        "gowalla"         => array ("png" => $imageFolder . "/gowalla.png",         "textToDisplay" => "Gowalla"),
                        "gravatar"        => array ("png" => $imageFolder . "/gravatar.png",        "textToDisplay" => "Gravatar"),
                        "hi.im"           => array ("png" => $imageFolder . "/hiim.png",            "textToDisplay" => "Hi, I'm"),
                        "huffduffer"      => array ("png" => $imageFolder . "/huffduffer.png",      "textToDisplay" => "Huffduffer"),
                        "identica"        => array ("png" => $imageFolder . "/identica.png",        "textToDisplay" => "Identica"),
                        "ilike"           => array ("png" => $imageFolder . "/ilike.png",           "textToDisplay" => "iLike"),
                        "imdb"            => array ("png" => $imageFolder . "/imdb.png",            "textToDisplay" => "Imdb"),
                        "itunes"          => array ("png" => $imageFolder . "/itunes.png",          "textToDisplay" => "iTunes"),
                        "klout"           => array ("png" => $imageFolder . "/klout.png",           "textToDisplay" => "Klout"),
                        "lanyrd"          => array ("png" => $imageFolder . "/lanyrd.png",          "textToDisplay" => "Lanyrd"),
                        "last.fm"         => array ("png" => $imageFolder . "/lastfm.png",          "textToDisplay" => "Last.fm"),
                        "lastfm"          => array ("png" => $imageFolder . "/lastfm.png",          "textToDisplay" => "Last.fm"),
                        "linkedin"        => array ("png" => $imageFolder . "/linkedin.png",        "textToDisplay" => "LinkedIn"),
                        "livejournal"     => array ("png" => $imageFolder . "/livejournal.png",     "textToDisplay" => "LiveJournal"),
                        "meetup"          => array ("png" => $imageFolder . "/meetup.png",          "textToDisplay" => "Meetup"),
                        "mixx"            => array ("png" => $imageFolder . "/mixx.png",            "textToDisplay" => "Mixx"),
                        "multiply"        => array ("png" => $imageFolder . "/multiply.png",        "textToDisplay" => "Multiply"),
                        "myspace"         => array ("png" => $imageFolder . "/myspace.png",         "textToDisplay" => "Myspace"),
                        "netvibes"        => array ("png" => $imageFolder . "/netvibes.png",        "textToDisplay" => "Netvibes"),
                        "newsvine"        => array ("png" => $imageFolder . "/newsvine.png",        "textToDisplay" => "Newsvine"),
                        "nikeplus"        => array ("png" => $imageFolder . "/nikeplus.png",        "textToDisplay" => "Nike Plus"),
                        "openid"          => array ("png" => $imageFolder . "/openid.png",          "textToDisplay" => "OpenID"),
                        "orkut"           => array ("png" => $imageFolder . "/orkut.png",           "textToDisplay" => "Orkut"),
                        "other"           => array ("png" => $imageFolder . "/other.png",           "textToDisplay" => "other"),
                        "paypal"          => array ("png" => $imageFolder . "/paypal.png",          "textToDisplay" => "PayPal"),
                        "picasa"          => array ("png" => $imageFolder . "/picasa.png",          "textToDisplay" => "Picasa"),
                        "peerindex"       => array ("png" => $imageFolder . "/peerindex.png",       "textToDisplay" => "PeerIndex"),
                        "pinboard"        => array ("png" => $imageFolder . "/pinboard.png",        "textToDisplay" => "Pinboard"),
                        "plancast"        => array ("png" => $imageFolder . "/plancast.png",        "textToDisplay" => "Plancast"),
                        "plaxo"           => array ("png" => $imageFolder . "/plaxo.png",           "textToDisplay" => "Plaxo"),
                        "posterous"       => array ("png" => $imageFolder . "/posterous.png",       "textToDisplay" => "Posterous"),
                        "quora"           => array ("png" => $imageFolder . "/quora.png",           "textToDisplay" => "Quora"),
                        "readernaut"      => array ("png" => $imageFolder . "/readernaut.png",      "textToDisplay" => "Readernaut"),
                        "reddit"          => array ("png" => $imageFolder . "/reddit.png",          "textToDisplay" => "Reddit"),
                        "share"           => array ("png" => $imageFolder . "/share.png",           "textToDisplay" => "Share"),
                        "skype"           => array ("png" => $imageFolder . "/skype.png",           "textToDisplay" => "Skype"),
                        "slideshare"      => array ("png" => $imageFolder . "/slideshare.png",      "textToDisplay" => "SlideShare"),
                        "soundcloud"      => array ("png" => $imageFolder . "/soundcloud.png",      "textToDisplay" => "SoundCloud"),
                        "spotify"         => array ("png" => $imageFolder . "/spotify.png",         "textToDisplay" => "Spotify"),
                        "stackoverflow"   => array ("png" => $imageFolder . "/stackoverflow.png",   "textToDisplay" => "Stack Overflow"),
                        "stumbleupon"     => array ("png" => $imageFolder . "/stumbleupon.png",     "textToDisplay" => "StumbleUpon"),
                        "tripit"          => array ("png" => $imageFolder . "/tripit.png",          "textToDisplay" => "Tripit"),
                        "tumblr"          => array ("png" => $imageFolder . "/tumblr.png",          "textToDisplay" => "Tumblr"),
                        "tungle.me"       => array ("png" => $imageFolder . "/tungle.png",          "textToDisplay" => "Tungle Me"),
                        "tungleme"        => array ("png" => $imageFolder . "/tungle.png",          "textToDisplay" => "Tungle Me"),
                        "twitter"         => array ("png" => $imageFolder . "/twitter.png",         "textToDisplay" => "Twitter"),
                        "upcoming"        => array ("png" => $imageFolder . "/upcoming.png",        "textToDisplay" => "Upcoming"),
                        "vcard"           => array ("png" => $imageFolder . "/vcard.png",           "textToDisplay" => "Vcard"),
                        "viddler"         => array ("png" => $imageFolder . "/viddler.png",         "textToDisplay" => "Viddler"),
                        "vimeo"           => array ("png" => $imageFolder . "/vimeo.png",           "textToDisplay" => "Vimeo"),
                        "website"         => array ("png" => $imageFolder . "/website.png",         "textToDisplay" => "Website"),
                        "wikipedia"       => array ("png" => $imageFolder . "/wikipedia.png",       "textToDisplay" => "Wikipedia"),
                        "wordpress.com"   => array ("png" => $imageFolder . "/wordpress.png",       "textToDisplay" => "Wordpress"),
                        "xbox"            => array ("png" => $imageFolder . "/xbox.png",            "textToDisplay" => "Xbox"),
                        "xing"            => array ("png" => $imageFolder . "/xing.png",            "textToDisplay" => "Xing"),
                        "yahoo"           => array ("png" => $imageFolder . "/yahoo.png",           "textToDisplay" => "Yahoo"),
                        "yahoo messenger" => array ("png" => $imageFolder . "/yahoo-messenger.png", "textToDisplay" => "Yahoo Messenger"),
                        "yelp"            => array ("png" => $imageFolder . "/yelp.png",            "textToDisplay" => "Yelp"),
                        "youtube"         => array ("png" => $imageFolder . "/youtube.png",         "textToDisplay" => "YouTube"),
                        "zootool"         => array ("png" => $imageFolder . "/zootool.png",         "textToDisplay" => "ZooTool"));

    // Append the section title
    $socialProfilesStr = __('Social Profiles:', 'rainmakermoxie');
    $htmlOut .= sprintf ("<b>%s</b><br />\n", $socialProfilesStr);

    foreach ($socialLinks as $socialLink)
        {
        // Extract the social link url
        if (($socialLinkUrl = $socialLink ["url"]) != "")
            {
            if ($socialLink ["type"] == "flickr")
                {
                // If flicker, change 'people' to 'photos' to point to photostream page
                $socialLinkUrl = str_ireplace ("people", "photos", $socialLinkUrl);
                }

            // Extract the png url using the socialLink "type"
            $chickletPngPath = $chicklets [$socialLink ["type"]] ["png"];
    
            // If a chicklet was NOT found...
            if ($chickletPngPath == "")
                {
                // Use "other" as the chickletPng
                $chickletPngPath = $chicklets ["other"] ["png"];
    
                // Use the socialLink type as the social link text to display
                $socialLinkTextToDisplay = $socialLink ["type"];
                }
            else
                {
                // Create the social link text to display
                $socialLinkTextToDisplay = $chicklets [$socialLink ["type"]] ["textToDisplay"];
                }
    
            // Format the social link url and chicklet image
            $htmlOut .= sprintf ("<a href='%s' rel='me' target='_blank'><img class='cChicklet' src='%s' border='0'></a> " . "&nbsp;",
                                 $socialLinkUrl, $chickletPngPath);
    
            // Format the social link url and chicklet text
            $htmlOut .= sprintf ("<a class='cChickletText' href='%s' rel='me' target='_blank'>%s</a><br />\n", 
                                 $socialLinkUrl, $socialLinkTextToDisplay);
            }
        }

    // Append blank line after last social link
    $htmlOut .= sprintf ("<br />\n");

    return ($htmlOut);
    }

/******************************************************************************/
/* Output builder: append Twitter info (if it exists) to buf                  */
/******************************************************************************/

function rainmakerMoxie_appendTwitterToBuf ($socialProfiles)
    {
    $htmlOut = "";

    // If there is at least one social profile...
    if (($socialProfilesCount = count ($socialProfiles)) > 0)
        {
        // Extract the options to get maxTweets
        $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);
        
        // Set max tweets to get
        $maxTweets = (int) $rainmakerMoxieOptions ["maxTweets"];

        // Inspect each social profile...
        foreach ($socialProfiles as $socialProfile)
            {
            // If twitter...
            if ($socialProfile->type == "twitter")
                {
                // Extract username from Rainmaker JSON
                $id = $socialProfile->username;

                // 2-dim array to hold tweets
                $tweets = array ();

                // Get the $tweets
                rainmakerMoxie_getTwitter ($id, $maxTweets, $tweets);

                // If at least 1 tweet was found...
                if (count ($tweets) > 0)
                    {
                    $htmlOut .= rainmakerMoxie_appendTweets ($id, $tweets);

                    // Exit the foreach, now that the twitter type has been processed
                    break;
                    }
                }
            }
        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Append tweets                                                              */
/******************************************************************************/

function rainmakerMoxie_appendTweets ($id, $tweets)
    {
    // Append the section title
    $twitterStr = __('Twitter:', 'rainmakermoxie');
    $htmlOut .= sprintf ("<b>%s</b><br />\n", $twitterStr);

    // Append each tweet to the buffer
    foreach ($tweets as $tweet)
        {
        // Adjust the tweet time
        $tweetTime = strtotime (str_replace ("+0000", "", $tweet ["created_at"]));

        // Calc the elapsedTime, returned as string "x-time ago"
        $elapsedTime = rainmakerMoxie_getElapsedTime ($tweetTime);

        // Extract tweet text
        $tweetText = $tweet ["text"];

        // Create hyperlinks from URLs
        $tweetText = preg_replace ("/http:\/\/([a-z0-9_\.\-\+\&\!\?\#\~\/\,]+)/i",
                                   "<a href='http://$1' target='_blank'>http://$1</a>", $tweetText);

        // Link @reply's (starts with @, then one (or more) characters in brackets
        $tweetText = preg_replace ("(@([a-zA-Z0-9_]+))",
                                   "<a href=\"http://www.twitter.com/\\1\" target=\"_blank\">\\0</a>", $tweetText);

        // Link hashtags (starts with #, then one (or more) characters in brackets
        $tweetText = preg_replace ("(#([a-zA-Z0-9_]+))",
                                   "<a href=\"http://www.twitter.com/#!/search?q=\\1\" target=\"_blank\">\\0</a>", $tweetText);

        // Append tweet to buffer
        // text ..... tweet text
        // source ... source html anchor with text link (e.g. Tweetdeck)
        $htmlOut .= sprintf ("%s (<i>%s</i>)<br />\n", $tweetText, $tweet ["source"]);

        // If an elapsedTime was calculated...
        if ($elapsedTime != "")
            {
            // Append it to buffer (show in very light color)
            $htmlOut .= sprintf ("<span class='cTweetElapsedTime'>%s</span><br />\n", $elapsedTime);
            }

        // Append blank line after last tweet
        $htmlOut .= sprintf ("<br />\n");
        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Call twitter user_timeline()                                               */
/******************************************************************************/

function rainmakerMoxie_getTwitter ($id, $maxTweets, &$tweets)
    {
    // Call the twitter api: user_timeline()
    if (($tweetInfo = @file_get_contents (RAINMAKER_MOXIE_TWITTER_USERTIMELINE_URL . "?" .
                                          "id=$id&" .
                                          "include_rts=true&" .
                                          "count=$maxTweets")) != FALSE)
        {
        // Decode
        $tweetInfo = json_decode ($tweetInfo);

        // Extract specific tweet fields into $tweets[]
        foreach ($tweetInfo as $tweet)
            {
            $tweets [] = array ("text"       => html_entity_decode ($tweet->text),
                                "source"     => $tweet->source,
                                "created_at" => $tweet->created_at,
                                "utc_offset" => $tweet->user->utc_offset);
            }
        }
    }

/******************************************************************************/
/* Calculate tweet elapsed time                                               */
/******************************************************************************/

function rainmakerMoxie_getElapsedTime ($tweetTime)
    {
    $elapsedTime = "";

    // Returns string "x-time ago"
    if (($totaldelay = (time () - $tweetTime)) > 0)
        {
        if ($days = (int) floor ($totaldelay / 86400))        // Seconds in 1 day
            {
            $elapsedTime = sprintf ("%d day%s ago", $days, $days > 1 ? "s" : "");
            }
        elseif ($hours = (int) floor ($totaldelay / 3600))    // Seconds in 1 hour
            {
            $elapsedTime = sprintf ("%d hour%s ago", $hours, $hours > 1 ? "s" : "");
            }
        elseif ($minutes = (int) floor ($totaldelay / 60))    // Seconds in 1 minute
            {
            $elapsedTime = sprintf ("%d minute%s ago", $minutes, $minutes > 1 ? "s" : "");
            }
        elseif ($seconds = (int) floor ($totaldelay / 1))     // Seconds in 1 second
            {
            $elapsedTime = sprintf ("%d second%s ago", $seconds, $seconds > 1 ? "s" : "");
            }
        }

    return ($elapsedTime);
    }

/******************************************************************************/
/* Output builder: append Plancast info (if it exists) to buf                 */
/******************************************************************************/

function rainmakerMoxie_appendPlancastToBuf ($socialProfiles)
    {
    $htmlOut = "";

    // If there is at least one social profile...
    if (($socialProfilesCount = count ($socialProfiles)) > 0)
        {
        // Extract the options to get maxPastPlans, maxUpcomingPlans
        $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);

        // Set max past plans to get
        $maxPastPlans = (int) $rainmakerMoxieOptions ["maxPastPlans"];

        // Set max upcoming plans to get
        $maxUpcomingPlans = (int) $rainmakerMoxieOptions ["maxUpcomingPlans"];

        // Inspect each social profile...
        foreach ($socialProfiles as $socialProfile)
            {
            // If plancast...
            if ($socialProfile->type == "plancast")
                {
                // Extract username from Rainmaker JSON
                $username = $socialProfile->username;

                // 2-dim array to hold plans
                $plans = array ();

                // Get past plans
                rainmakerMoxie_getPlancast ($username, "past", $maxPastPlans, $plans);

                // Get upcoming plans
                rainmakerMoxie_getPlancast ($username, "upcoming", $maxUpcomingPlans, $plans);

                // If at least 1 plan was found...
                if (count ($plans) > 0)
                    {
                    // Append the section title
                    $plancastStr = __('Plancast:', 'rainmakermoxie');
                    $htmlOut .= sprintf ("<b>%s</b><br />\n", $plancastStr);

                    // Append each plan to the buffer
                    foreach ($plans as $plan)
                        {
                        $htmlOut .= sprintf ("<a href='%s' target='_blank'>%s</a><br />\n", $plan ["plan_url"],  $plan ["what"]);
                        $htmlOut .= sprintf ("%s<br />\n", $plan ["when"]);
                        $htmlOut .= sprintf ("%s<br />\n", $plan ["where"]);
                        $htmlOut .= sprintf ("<br />\n");
                        }

                    // DO NOT ppend blank line after last plan -- this is the last section displayed
                    // before the "Powered by FullContact"

                    // Exit the foreach
                    break;
                    }
                }
            }
        }

    return ($htmlOut);
    }

/******************************************************************************/
/* Call plancast  plans.user()                                                */
/******************************************************************************/

function rainmakerMoxie_getPlancast ($username, $view_type, $maxPlans, &$plans)
    {
    // Call the plancast api: plans.user()
    if (($planInfo = @file_get_contents (RAINMAKER_MOXIE_PLANCAST_PLANSUSER_URL . "?" .
                                         "username=$username&"   .
                                         "view_type=$view_type&" .
                                         "count=$maxPlans")) != FALSE)
        {
        // Decode
        $planInfo = json_decode ($planInfo);

        // If at least 1 plan was found...
        if (count ($planInfo->plans) > 0)
            {
            // Append plan to the array
            foreach ($planInfo->plans as $plan)
                {
                $plans [] = array ("what"     => $plan->what,
                                   "plan_url" => $plan->plan_url,
                                   "when"     => $plan->when,
                                   "where"    => $plan->where);
                }

            // If "past" reverse the array to be in chronological order
            if ($view_type == "past")
                {
                // Reverse the order
                $plans = array_reverse ($plans);
                }
            }
        }
    }

/******************************************************************************/
/* Returns photoUrl, photoUrlType based on a best-fit algorithm               */
/******************************************************************************/

function rainmakerMoxie_extractBestPhotoUrl ($person, &$photoUrl, &$photoUrlType)
    {
    // If there are ANY photos...
    if (($numPhotos = count ($person->photos)) > 0)
        {
        // 1st: look for gravatar
        if (rainmakerMoxie_extractPhotoUrl ($person, "gravatar", $photoUrl, $photoUrlType) != 0)
            {
            // 2nd: look for facebook
            if (rainmakerMoxie_extractPhotoUrl ($person, "facebook", $photoUrl, $photoUrlType) != 0)
                {
                // 3rd: look for twitter
                if (rainmakerMoxie_extractPhotoUrl ($person, "twitter", $photoUrl, $photoUrlType) != 0)
                    {
                    // 4th: look for linkedin (this is watermarked with LinkedIn)
                    if (rainmakerMoxie_extractPhotoUrl ($person, "linkedin", $photoUrl, $photoUrlType) != 0)
                        {
                        // last: use first available
                        rainmakerMoxie_extractPhotoUrl ($person, "FIRST_FOUND", $photoUrl, $photoUrlType);
                        }
                    }
                }
            }
        }
    }

/******************************************************************************/
/* Try to find and return a photoUrl based on requested type                  */
/******************************************************************************/

function rainmakerMoxie_extractPhotoUrl ($person, $type, &$photoUrl, &$photoUrlType)
    {
    // Reset to 0 upon success
    $rc = 1;

    // This is the final case, when no other better photoUrl's were found
    if ($type == "FIRST_FOUND")
        {
        $photoUrl     = $person->photos [0]->url;
        $photoUrlType = $person->photos [0]->type;
        $rc = 0;
        }
    else
        {
        // Examine each photo to try to match on "type"
        for ($i = 0; ($i < count ($person->photos)) && ($photoUrl == ""); $i++)
            {
            // If type matches...
            if (strcasecmp ($person->photos [$i]->type, $type) == 0)
                {
                if (($type != "gravatar") || (($type == "gravatar") && (strstr ($person->photos [$i]->url, "secure"))))
                    {
                    // Use this photo url
                    $photoUrl     = $person->photos [$i]->url;
                    $photoUrlType = $person->photos [$i]->type;
                    $rc = 0;
                    }
                }
            }
        }

    return ($rc);
    }

/******************************************************************************/
/* SendGrid emailer                                                           */
/******************************************************************************/

function rainmakerMoxie_sendEmail ($api_user, $api_key, $subject, $body, $emailTo)
    {
    $rc = 1; // reset to 0 on success

    // Configure SendGrid params
    $json_string = array ("to" => array ($emailTo), "category" => "rainmakerMoxie_category");
    $params = array ("api_user"  => $api_user,
                     "api_key"   => $api_key,
                     "x-smtpapi" => json_encode ($json_string),
                     "to"        => $emailTo,
                     "subject"   => $subject,
                     "html"      => $body,
                     "text"      => $body,
                     "from"      => $emailTo);

    // Generate curl request
    if (($hCurl = curl_init (RAINMAKER_MOXIE_SENDGRID_MAILSEND_URL)) != FALSE)
        {
        // Set the curl options
        curl_setopt ($hCurl, CURLOPT_POST, true);             // POST
        curl_setopt ($hCurl, CURLOPT_POSTFIELDS, $params);    // Add the POST body
        curl_setopt ($hCurl, CURLOPT_HEADER, false);          // Do not return headers
        curl_setopt ($hCurl, CURLOPT_RETURNTRANSFER, true);   // Return the response
    
        // Exec curl
        if (($response = curl_exec ($hCurl)) != FALSE)
            {
            // Decode the response
            $decodedResponse = json_decode ($response);

            // If sent successfully...
            if ($decodedResponse->message == "success")
                {
                $rc = 0; // success
                }
            }
    
        // Close the handle
        curl_close ($hCurl);
        }

    return ($rc);
    }

/******************************************************************************/
/* Action: Runs on every blog page load as a sidebar widget                   */
/******************************************************************************/

function rainmakerMoxie_initWidget ()
    {
    // MUST be able to register the widget... else exit
    if (function_exists ('wp_register_sidebar_widget'))
        {
        // Declare function -- called from Wordpress -- during page-loads
        function rainmakerMoxie_widget ($args)
            {
            // Load existing options from wp database
            $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);

            // Accept parameter array passed-in from Wordpress (e.g. $before_widget, $before_title, etc.)
            extract ($args);

            // Display sidebar title
            echo $before_widget . $before_title . $rainmakerMoxieOptions ['sidebarTitle'] . $after_title;

            // Embed the plugin version number as a div tag
            printf ("<div id=\"%s\">", RAINMAKER_MOXIE_PLUGIN_VERSION_FOR_HIDDEN_DIV);

            // Load existing options from wp database
            $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);

            // If the api has not yet been entered in the admin plugin options...
            if ($rainmakerMoxieOptions ['rainmakerApiKey'] == "")
                {
                // Bail
//              $message = __('A FullContact API Key is required.', 'rainmakermoxie');
//              printf ("<p><span style='color:red;'>OOPS: </span>%s</p>\n", $message);

                $messageStart   = __('A FullContact API Key is required.', 'rainmakermoxie');
                $getOneHereLink = ' <a href="' . RAINMAKER_MOXIE_RAINMAKER_REGISTER_URL .'" target="_blank">You can get one here.</a> ';
                $messageEnd     = __('Then enter it on the RainmakerMoxie Plugin options page.', 'rainmakermoxie');

                printf ("<p><span style='color:red;'>OOPS: </span>%s%s%s</p>\n", $messageStart, $getOneHereLink, $messageEnd);
                }
            else
                {
                // Create and display the plugin interactive html
                rainmakerMoxie_displayAjaxForm ();
                }

            // Close the plugin version number div
            printf ("</div>");

            // Add the widget closing tags
            echo $after_widget;
            }

        // Register the widget function to be called from Wordpress on each page-load
        wp_register_sidebar_widget ('RainmakerMoxie_Id', 'RainmakerMoxie', 'rainmakerMoxie_widget');
        }
    }

/******************************************************************************/
/* Admin options page: runs from rainmakerMoxie_addSubmenu()                  */
/******************************************************************************/

function rainmakerMoxie_optionsPage ()
    {
    // Retrieve localized displayed strings
    $rainmakerMoxie_enterOptionsStr                  = __('Please enter your RainmakerMoxie Plugin options:',      'rainmakermoxie');
    $rainmakerMoxie_yourRainmakerApiKeyStr           = __('Your FullContact API Key:',                             'rainmakermoxie');
    $rainmakerMoxie_getRainmakerApiKeyStr            = __('Get a FullContact API Key',                             'rainmakermoxie');
    $rainmakerMoxie_sidebarTitleStr                  = __('Sidebar Title:',                                        'rainmakermoxie');
    $rainmakerMoxie_showPoweredByStr                 = __('Show Powered by FullContact',                           'rainmakermoxie');
    $rainmakerMoxie_hidePoweredByStr                 = __('Hide Powered by FullContact',                           'rainmakermoxie');
    $rainmakerMoxie_saveStr                          = __('Save',                                                  'rainmakermoxie');
    $rainmakerMoxie_optionsSavedStr                  = __('RainmakerMoxie options saved successfully.',            'rainmakermoxie');
    $rainmakerMoxie_pleaseEnterAllRequiredFieldsStr  = __('Please enter all required fields.',                     'rainmakermoxie');
    $rainmakerMoxie_sendGridUserErrorStr             = __('ERROR: Please re-enter SendGrid Username and Api Key.', 'rainmakermoxie');
    $rainmakerMoxie_sendGridOptionalStr              = __('<b>Optional:</b> SendGrid email sender.',               'rainmakermoxie');
    $rainmakerMoxie_optionalStr1                     = __('Enables optional emailing of displayed',                'rainmakermoxie');
    $rainmakerMoxie_optionalStr2                     = __('contacts by the blog visitor.',                         'rainmakermoxie');
    $rainmakerMoxie_yourSendGridUsernameStr          = __('Your SendGrid Username:',                               'rainmakermoxie');
    $rainmakerMoxie_yourSendGridApiKeyStr            = __('Your SendGrid API Key:',                                'rainmakermoxie');
    $rainmakerMoxie_getSendGridUsernameAndApiKeyStr  = __('Get a SendGrid API Key',                                'rainmakermoxie');
    $rainmakerMoxie_maxTweetsStr                     = __('Max Tweets To Display (1-20):',                         'rainmakermoxie');
    $rainmakerMoxie_maxPastPlansStr                  = __('Max Plancast Past Plans To Display (1-20):',            'rainmakermoxie');
    $rainmakerMoxie_maxUpcomingPlansStr              = __('Max Plancast Upcoming Plans To Display (1-20):',        'rainmakermoxie');

    // Load existing options from wp database
    $rainmakerMoxieOptions = get_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);

    // Upon POST: (all required fields must be set)
    if (isset ($_POST ['rainmakerApiKey'])  &&
        isset ($_POST ['maxTweets'])        &&
        isset ($_POST ['maxPastPlans'])     &&
        isset ($_POST ['maxUpcomingPlans']) &&
        isset ($_POST ['sidebarTitle']))
        {
        // If all POST fields are valid...
        if (rainmakerMoxie_POST_fieldsAreValid ($_POST))
            {
            // Copy POST fields to the persistent wp options array
            $rainmakerMoxieOptions ['rainmakerApiKey']  = $_POST ['rainmakerApiKey'];
            $rainmakerMoxieOptions ['maxTweets']        = $_POST ['maxTweets'];
            $rainmakerMoxieOptions ['maxPastPlans']     = $_POST ['maxPastPlans'];
            $rainmakerMoxieOptions ['maxUpcomingPlans'] = $_POST ['maxUpcomingPlans'];
            $rainmakerMoxieOptions ['sidebarTitle']     = $_POST ['sidebarTitle'];
            $rainmakerMoxieOptions ['showPoweredBy']    = $_POST ['showPoweredBy'];
            $rainmakerMoxieOptions ['sgUsername']       = $_POST ['sgUsername'];
            $rainmakerMoxieOptions ['sgApiKey']         = $_POST ['sgApiKey'];

            // Store changed options back to wp database
            update_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS, $rainmakerMoxieOptions);

            // Display update message
            echo '<div id="message" class="updated fade"><p>' . $rainmakerMoxie_optionsSavedStr . '</p></div>';
            }
        }

    // Initialize data fields for "showPoweredBy" radio button
    $showPoweredByRadio = "";
    $hidePoweredByRadio = "";

    // Set variable for form to use for "showPoweredBy" to show sticky-value for radio button
    if ($rainmakerMoxieOptions ['showPoweredBy'] == TRUE)
        {
        $showPoweredByRadio = "checked";
        }
    else
        {
        $hidePoweredByRadio = "checked";
        }

    // Display the RainmakerMoxie Options form
    echo
     '<div class="wrap">

      <!-- Link the FullContact logo -->
      <br /><a href="' . RAINMAKER_MOXIE_RAINMAKER_HOME_URL . '" target="_blank"><img src="' .
                         RAINMAKER_MOXIE_PLUGINDIR . 'images/fullcontact_logo.png" /></a>

      <h3>&nbsp;' . $rainmakerMoxie_enterOptionsStr . '</h3>

      <form action="" method="post">

      <table border="0" cellpadding="10">
        <tr>
          <td>' . $rainmakerMoxie_yourRainmakerApiKeyStr . '</td>
          <td>&nbsp;<span class="cOptionsRedAsterisk">*</span>&nbsp;<input style="border-color: #000000" type="text" name="rainmakerApiKey" value="' .
              $rainmakerMoxieOptions ['rainmakerApiKey'] . '" size="30" maxlength="30" />
          &nbsp;&nbsp;<a href="' . RAINMAKER_MOXIE_RAINMAKER_REGISTER_URL . '" target="_blank">' . $rainmakerMoxie_getRainmakerApiKeyStr . '</a></td>
        </tr>

        <!-- Horizontal line -->
        <tr><td colspan="2"><hr></td></tr>
        
        <!-- Max Tweets + textbox -->
        <tr>
          <td>' . $rainmakerMoxie_maxTweetsStr . '</td>
          <td>&nbsp;<span class="cOptionsRedAsterisk">*</span>&nbsp;<input style="border-color: #000000;" type="text" name="maxTweets" value="' .
              $rainmakerMoxieOptions ['maxTweets'] . '" size="2" maxlength="2" /></td>
        </tr>

        <!-- Max Plans + textbox -->
        <tr>
          <td>' . $rainmakerMoxie_maxPastPlansStr . '</td>
          <td>&nbsp;<span class="cOptionsRedAsterisk">*</span>&nbsp;<input style="border-color: #000000;" type="text" name="maxPastPlans" value="' .
              $rainmakerMoxieOptions ['maxPastPlans'] . '" size="2" maxlength="2" /></td>
        </tr>

        <!-- Max Upcoming Plans + textbox -->
        <tr>
          <td>' . $rainmakerMoxie_maxUpcomingPlansStr . '</td>
          <td>&nbsp;<span class="cOptionsRedAsterisk">*</span>&nbsp;<input style="border-color: #000000;" type="text" name="maxUpcomingPlans" value="' .
              $rainmakerMoxieOptions ['maxUpcomingPlans'] . '" size="2" maxlength="2" /></td>
        </tr>

        <!-- Horizontal line -->
        <tr><td colspan="2"><hr></td></tr>

        <!-- Sidebar title + textbox -->
        <tr>
          <td>' . $rainmakerMoxie_sidebarTitleStr . ' &nbsp;</td>
          <td>&nbsp;<span class="cOptionsRedAsterisk">*</span>&nbsp;<input style="border-color: #000000;" type="text" name="sidebarTitle"  value="' .
              $rainmakerMoxieOptions ['sidebarTitle']  . '" size="30" maxlength="30" /></td>
        </tr>

        <!-- Horizontal line -->
        <tr><td colspan="2"><hr></td></tr>

        <!-- Link the SendGrid logo -->
        <tr><td><a href="' . RAINMAKER_MOXIE_SENDGRID_HOME_URL . '" target="_blank"><img src="' .
                           RAINMAKER_MOXIE_PLUGINDIR . 'images/sendgrid_logo.jpeg" /></a></td></tr>

        <!-- SendGrid "optional" text -->
        <tr><td><i>' . $rainmakerMoxie_sendGridOptionalStr    . '</i></td><td></td></tr>
        <tr><td><i>' . $rainmakerMoxie_optionalStr1           . '</i></td><td></td></tr>
        <tr><td><i>' . $rainmakerMoxie_optionalStr2           . '</i></td><td></td></tr>

        <!-- Blank line -->
        <tr><td><br /></td><td><br /></td></tr>

        <!-- SendGrid Username + textbox -->
        <tr>
          <td>' . $rainmakerMoxie_yourSendGridUsernameStr . ' &nbsp;</td>
          <td>&nbsp;&nbsp;<input style="border-color: #000000;" type="text" name="sgUsername" value="' .
              $rainmakerMoxieOptions ['sgUsername'] . '" size="30" maxlength="128" />&nbsp;&nbsp;<a href="' .
              RAINMAKER_MOXIE_SENDGRID_SIGNUP_URL . '" target="_blank">' .
              $rainmakerMoxie_getSendGridUsernameAndApiKeyStr . '</a></td>
        </tr>

        <!-- SendGrid ApiKey + textbox -->
        <tr>
          <td>' . $rainmakerMoxie_yourSendGridApiKeyStr . '</td>
          <td>&nbsp;&nbsp;<input style="border-color: #000000;" type="text" name="sgApiKey" value="' .
              $rainmakerMoxieOptions ['sgApiKey'] . '" size="30" maxlength="30" /></td>
        </tr>

        <!-- Horizontal line -->
        <tr><td colspan="2"><hr></td>

      </table>

      <!-- Show/hide Powered by text -->
      <table border="0" cellpadding="10">
        <tr>
          <td width="300" valign="top"><input type="radio" name="showPoweredBy" value=1 ' . $showPoweredByRadio . ' />
          ' . $rainmakerMoxie_showPoweredByStr . '<br />
                                       <input type="radio" name="showPoweredBy" value=0 ' . $hidePoweredByRadio . ' />
          ' . $rainmakerMoxie_hidePoweredByStr . '</td>
        </tr>
      </table>

      <br />

      <!-- Submit ("save") button -->
      <p>&nbsp;<input type="submit" value="' . $rainmakerMoxie_saveStr . '" /><br /><br /></p>
      <p style="font-size: .65em;">&nbsp;&nbsp;v' . RAINMAKER_MOXIE_PLUGIN_VERSION .'</p>

      </form>
      </div>';
    }

/******************************************************************************/
/* If options POST fields are all valid                                       */
/******************************************************************************/

function rainmakerMoxie_POST_fieldsAreValid ($_POST)
    {
    $rc = 0; // reset to 1 if all fields are valid

    // FullContact API Key must exist
    if (!(rainmakerMoxie_rainmakerApiKeyExists ($_POST ['rainmakerApiKey'])))
        {
        $message = __('Unable to validate the FullContact API Key. Please retry.', 'rainmakermoxie');
        echo '<div id="message" class="error fade"><p>' . $message . '</p></div>';
        }

    // Max tweets must be in valid range
    elseif (($_POST ['maxTweets'] < 1) || ($_POST ['maxTweets'] > RAINMAKER_MOXIE_MAX_TWEETS))
        {
        $message = __('Max Tweets must be between 1 and ' . RAINMAKER_MOXIE_MAX_TWEETS . '. Please retry.', 'rainmakermoxie');
        echo '<div id="message" class="error fade"><p>' . $message . '</p></div>';
        }

    // Max past plans must be in valid range
    elseif (($_POST ['maxPastPlans'] < 1) || ($_POST ['maxPastPlans'] > RAINMAKER_MOXIE_MAX_PAST_PLANS))
        {
        $message = __('Max Past Plans must be between 1 and ' . RAINMAKER_MOXIE_MAX_PAST_PLANS . '. Please retry.', 'rainmakermoxie');
        echo '<div id="message" class="error fade"><p>' . $message . '</p></div>';
        }

    // Max upcoming plans must be in valid range
    elseif (($_POST ['maxUpcomingPlans'] < 1) || ($_POST ['maxUpcomingPlans'] > RAINMAKER_MOXIE_MAX_UPCOMING_PLANS))
        {
        $message = __('Max Upcoming Plans must be between 1 and ' . RAINMAKER_MOXIE_MAX_UPCOMING_PLANS . '. Please retry.', 'rainmakermoxie');
        echo '<div id="message" class="error fade"><p>' . $message . '</p></div>';
        }

    // Sidebar title required
    elseif ($_POST ['sidebarTitle'] == "")
        {
        $message = __('A Sidebar Title is required. Please retry.', 'rainmakermoxie');
        echo '<div id="message" class="error fade"><p>' . $message . '</p></div>';
        }

    else
        {
        // Init to no error
        $sendGridUserError = false;

        // If either SendGrid field is populated:
        if (($_POST ['sgUsername'] != "") || ($_POST ['sgApiKey'] != ""))
            {
            // Check for valid SendGrid username/apiKey combo...
            if (!(rainmakerMoxie_sendGridUserExists ($_POST ['sgUsername'], $_POST ['sgApiKey'])))
                {
                // Invalid combo
                $message = __('Unable to validate SendGrid Username and ApiKey. Please retry or leave both blank.', 'rainmakermoxie');
                echo '<div id="message" class="error fade"><p>' . $message . '</p></div>';

                // Do not save the options
                $sendGridUserError = true;
                }
            }

        if (!($sendGridUserError))
            {
            // All fields are valid
            $rc = 1;
            }
        }

    return ($rc);
    }

/******************************************************************************/
/* Check for valid FullContact API key                                        */
/******************************************************************************/

function rainmakerMoxie_rainmakerApiKeyExists ($rainmakerApiKey)
    {
    $rc = 0; // reset to 1 if user exists

    // Call the FullContact API
    if (($person = @file_get_contents (RAINMAKER_MOXIE_RAINMAKER_PERSON_URL . "?" . 
                                       "email=lorangb@gmail.com&" . // this is just a placeholder value
                                       "apiKey=$rainmakerApiKey&" .
                                       "timeoutSeconds=30")) != FALSE)
        {
        // Valid apikey
        $rc = 1;
        }

    return ($rc);
    }

/******************************************************************************/
/* Check for valid SendGrid username/apikey combo                             */
/******************************************************************************/

function rainmakerMoxie_sendGridUserExists ($sgUsername, $sgApiKey)
    {
    $rc = 0; // reset to 1 if user exists

    // Call the SendGrid API: profile.get.json()
    if (($sgPerson = @file_get_contents (RAINMAKER_MOXIE_SENDGRID_PROFILEGET_URL . "?" . 
                                         "api_user=$sgUsername&" . 
                                         "api_key=$sgApiKey")) != FALSE)
        {
        // Valid SendGrid username/apikey combo
        $rc = 1;
        }

    return ($rc);
    }

/******************************************************************************/
/* Activation hook: runs once at plugin activation time                       */
/******************************************************************************/

function rainmakerMoxie_createOptions ()
    {
    // Expose the wp version number
    global $wp_version;

    // The plugin uses json_decode(), which requires PHP 5.2.0 or higher
    if (version_compare (PHP_VERSION, '5.2.0', '<'))
        {
        // Deactivate this plugin
        deactivate_plugins (__FILE__);

        // Display (non-localized) error message
        die ('RainmakerMoxie requires PHP version 5.2.0 or greater.');
        }
    elseif (version_compare ($wp_version, '2.8', '<'))
        {
        // Deactivate this plugin
        deactivate_plugins (__FILE__);

        // Display (non-localized) error message
        die ('RainmakerMoxie requires WordPress version 2.8 or greater.');
        }
    elseif (function_exists ('curl_init') == FALSE)
        {
        // Deactivate this plugin
        deactivate_plugins (__FILE__);

        // Display (non-localized) error message
        die ('RainmakerMoxie requires the cURL PHP extension. Please consult your system administrator.');
        }
    else
        {
        // Create the initialSettingsOptions array of keys/values
        $rainmakerMoxieOptions = array ('sidebarTitle'     => 'FullContact',
                                        'rainmakerApiKey'  => '',
                                        'showPoweredBy'    => 1,
                                        'sgUsername'       => '',
                                        'sgApiKey'         => '',
                                        'maxTweets'        => '5',
                                        'maxPastPlans'     => '2',
                                        'maxUpcomingPlans' => '3');

        // Store the initial options to the wp database
        add_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS, $rainmakerMoxieOptions);
        }
    }

/******************************************************************************/
/* Deactivation hook: runs once at plugin deactivation time                   */
/******************************************************************************/

function rainmakerMoxie_deleteOptions ()
    {
    // Runs once, at plugin deactivation time

    // Remove the rainmakerMoxieOptions array from the wp database
    delete_option (RAINMAKER_MOXIE_SETTINGS_OPTIONS);
    }

/******************************************************************************/
/* Action: Adds the submenu "RainmakerMoxie" to the admin menu                */
/******************************************************************************/

function rainmakerMoxie_addSubmenu ()
    {
    // Define the options for the submenu page
    add_submenu_page ('options-general.php',          // Parent page
                      'RainmakerMoxie page',          // Page title, shown in titlebar
                      'RainmakerMoxie',               // Menu title
                      10,                             // Access level all
                      __FILE__,                       // This file displays the options page
                      'rainmakerMoxie_optionsPage');  // Function that displays options page
    }

?>
