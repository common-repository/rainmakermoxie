// Plugin ... RainmakerMoxie
// Module ... rainmakermoxie.plugin.js

// Run immediately upon document.load:
jQuery (document).ready (function ()
    {
    // keyCode constants
    var ENTER_KEY = 13;
    var TAB_KEY = 9;

    // Create the ajaxurl path  -- wpurl was set in rainmakerMoxie_register_head()
    ajaxurl = wpurl + '/wp-admin/admin-ajax.php';

    // Create the spinner (animated gif) image path
    spinnerImagePath = '<img src="' + wpurl + '/wp-admin/images/wpspin_light.gif' + '" />';

    // Create the email_sent image path
    emailSentImagePath = '<img src="' + wpurl + '/wp-content/plugins/rainmakermoxie/images/email_sent.png' + '" />';

    // Create the email_not_sent image path
    emailNotSentImagePath = '<img src="' + wpurl + '/wp-content/plugins/rainmakermoxie/images/email_not_sent.png' + '" />';

    //************************************************************************//
    // #emailAddressToLookup is always present -- setup the default values   *//
    //************************************************************************//

    // (1) Preset the text in the emailAddressToLookup textbox (var is set in the PHP)
    jQuery ('#emailAddressToLookup').val (enterEmailAddressToLookupStr);

    // (2) HOOK: emailAddressToLookup textbox clicked-into
    jQuery ('#emailAddressToLookup').click (function ()
        {
        // Clear any existing text
        jQuery ('#emailAddressToLookup').val ('');

        // Clear existing "email_sent" image
        jQuery ('#emailSent').html ('');
        });

    // (3) HOOK: <enter> pressed in emailAddressLookup field
    jQuery ('#emailAddressToLookup').keydown (function (e)
        {
        // If enter key...
        if (e.keyCode == ENTER_KEY)
            {
            // Process request
            processRequest (ajaxurl, spinnerImagePath, emailSentImagePath, emailNotSentImagePath);
            }
        });

    // (4) HOOK: <tab> pressed in emailAddressLookup field
    jQuery ('#emailAddressToLookup').keydown (function (e)
        {
        // If tab key...
        if (e.keyCode == TAB_KEY)
            {
            // Ignore
            return (false);
            }
        });

    //************************************************************************//
    // #emailAddressToSendResults is OPTIONAL... if present, use it          *//
    //************************************************************************//

    // If the optional "emailAddressToSendResults" id was rendered...
    if (jQuery ('#emailAddressToSendResults').length > 0)
        {
        // (1) Preset the text in the emailAddressToSendResults textbox (var is set in the PHP)
        jQuery ('#emailAddressToSendResults').val (emailResultsToStr);

        // (2) HOOK: emailAddressToSendResults textbox clicked-into
        jQuery ('#emailAddressToSendResults').click (function ()
            {
            // Clear any existing text
            jQuery ('#emailAddressToSendResults').val ('');

            // Clear existing "email_sent" image
            jQuery ('#emailSent').html ('');
            });

        // (3) HOOK: <enter> pressed in emailAddressToSendResults field
        jQuery ('#emailAddressToSendResults').keydown (function (e)
            {
            // If enter key...
            if (e.keyCode == ENTER_KEY)
                {
                // Set field focus to #emailAddressToLookup
                jQuery ('#emailAddressToLookup').focus ();
                }
            });
        }
    });


function processRequest (ajaxurl, spinnerImagePath, emailSentImagePath, emailNotSentImagePath)
    {
    // Display the spinner
    jQuery ('#spinner').html (spinnerImagePath);

    // Get the person.json (using 'emailAddressToLookup'), returned as 
    jQuery.ajax
        ({
        // Causes get_rainmaker_ajax() to recieve the data via POST
        type: 'POST',

        // The WP ajax php file that creates an ajax session
        url: ajaxurl,

        // Invoke get_rainmaker_ajax()
        // Send the emailAddressToLookup
        // Send the nonce for validation
        data:
            {
            // Call get_rainmaker_ajax ()
            action: 'get_rainmaker',

            // Sends get_rainmaker_ajax (): emailAddressToLookup as $_POST var
            emailAddressToLookup: jQuery ('#emailAddressToLookup').val (),

            // Sends get_rainmaker_ajax (): emailAddressToSendResults as $_POST var
            emailAddressToSendResults: jQuery ('#emailAddressToSendResults').val (),

            // Sends get_rainmaker_ajax () the nonce value set in rainmakerMoxie_register_head()
            _ajax_nonce: dontWorryBeHappy
            },

        // Remove any existing output before refreshing
        beforeSend: function ()
            {
            // fadeOut exisiting output
            jQuery ('#iRainmakerMoxieOutput').fadeOut ('fast');
            },

        // On return from get_rainmaker_ajax(), push the output to the DOM
        success: function (htmlFormattedOutput)
            {
            // Display the spinning gif while retrieving the data
            jQuery ('#spinner').html ('');

            // Store data in the #iRainmakerMoxieOutput div
            jQuery ('#iRainmakerMoxieOutput').html (htmlFormattedOutput);

            // "email_sent.png" display logic
            var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
            var emailAddressToSendResultsVal = jQuery ('#emailAddressToSendResults').val ();

            // If blank...
            if (emailAddressToSendResultsVal == '')
                {
                // do not display anything
                }

            // If it has the default message...
            else if (emailAddressToSendResultsVal == emailResultsToStr)
                {
                // do not display anything
                }

            // If NOT a valid formatted email address...
            else if (!emailReg.test (emailAddressToSendResultsVal))
                {
                // Display "email_not_sent" image
                jQuery ('#emailSent').html (emailNotSentImagePath);
                }

            else
                {
                // Display "email_sent" image -- really an "attempt to send" image
                jQuery ('#emailSent').html (emailSentImagePath);
                }

            // fadeIn after data is retrieved
            jQuery ('#iRainmakerMoxieOutput').fadeIn ('fast');
            }
        });
    }
