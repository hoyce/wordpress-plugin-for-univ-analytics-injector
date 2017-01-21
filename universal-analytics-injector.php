<?php
/*
 Plugin Name: Universal Analytics Injector
 Plugin URI: http://www.hoyce.com/wordpress-plugins/
 Description: Universal Analytics Injector for WordPress will help you add Google Analytics to your WordPress blog.
 This will not only add basic Google Analytics tracking but also tracking for outbound links, YouTube, Vimeo, mail.
 Just add your Google Analytics tracking code and your domain and you are done!
 Version: 1.0.2
 Author: Niklas Olsson
 Author URI: http://www.hoyce.com
 License: GPL 3.0, @see http://www.gnu.org/licenses/gpl-3.0.html
 */

/**
 * Loads jQuery if not loaded.
 */
wp_enqueue_script('jquery');

/**
 * WP Hooks
 **/
add_action('init', 'load_ua_injector_translation_file');
add_action('wp_head', 'insert_ua_code_and_domain');
add_action('admin_head', 'admin_register_ua_for_wordpress_head');
add_action('admin_menu', 'add_ua_injector_options_admin_menu');

/**
 * Loads the translation file for this plugin.
 */
function load_ua_injector_translation_file() {
    $plugin_path = basename(dirname(__FILE__));
    load_plugin_textdomain('ua-injector', null, $plugin_path . '/languages/');
}

/**
 * Insert the stylesheet and javascripts in the admin head.
 */
function admin_register_ua_for_wordpress_head() {
    $wp_content_url = get_option('siteurl');
    if(is_ssl()) {
        $wp_content_url = str_replace('http://', 'https://', $wp_content_url);
    }

    $plugin_url = $wp_content_url . '/wp-content/plugins/' . basename(dirname(__FILE__));
    $css_url = $plugin_url.'/css/ua-injector.css';
    $js_url = $plugin_url.'/js/ua-injector.js';
    echo "<link rel='stylesheet' type='text/css' href='$css_url' />\n".
        "<script type='text/javascript' src='$js_url'></script>\n";
}

/**
 * Inserts the Google Analytics tracking code and domain.
 */
function insert_ua_code_and_domain() {
    if (!current_user_can('edit_posts')  && get_option('ua_tracking_code') != "") {
        echo "<!-- Universal Analytics Injector for Wordpress from http://www.hoyce.com/blog/wordpress-plugins/ -->\n";
        echo get_ua_tracking_code();
        echo "\n<!-- / Universal Analytics Injector for Wordpress -->\n";
    }
}

/**
 * Get the Universal Analytics tracking code based on the users given values for the UA tracking code
 * and the domain url.
 *
 * @param ua_tracking_code the UA-xxxx-x code from your Google Analytics account.
 * @param site_domain_url the url to use to determine the domain of the tracking.
 * @return the tracking code to render.
 */
function get_ua_tracking_code() {

    $code = "<script type='text/javascript'>
      (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
      (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
      m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
      })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

      ga('create', '".get_option('ua_tracking_code')."', '".get_option('site_domain_url')."');";

    if (get_option('anonymizeip') == 'on') {
      $code .= "
      ga('set', 'anonymizeIp', true);";
    }

    $code .= "
      ga('send', 'pageview');";

    if (get_option('track_downloads') != 'on' || get_option('track_mailto_links') != 'on' || get_option('track_mailto_links') != 'on' || get_option('track_outbound_links') != 'on') {
      $code .= "
      jQuery(document).ready(function(e) {
        jQuery('a').on('mousedown', function(e) {
          var that = jQuery(this);
          var href = that.prop('href').split('?')[0];
          var ext = href.split('.').pop();";

      if (get_option('track_downloads') != 'on') {
          $code .= "
          if ('xls,xlsx,doc,docx,ppt,pot,pptx,pdf,pub,txt,zip,rar,tar,7z,gz,exe,wma,mov,avi,wmv,wav,mp3,midi,csv,tsv,jar,psd,pdn,ai,pez,wwf,torrent,cbr,fla,swf,js,css,m,nb,dot,pot,dotx,erl,mat,3ds,adi,dwg'.split(',').indexOf(ext) !== -1) {";
              $download_cat = "Download";
              if(!ua_injector_isNullOrEmpty(get_option('downloads_category'))) {
                $download_cat = get_option('downloads_category');
              }
              $code .= "
              ga('send', 'event', '" . $download_cat . "', ext, href);
              ga('send', 'pageview', '/virtual/download/' + ext + '/' + href);
              console.log('Sending event and pageview for " . $download_cat . ": /virtual/download/' + ext + '/' + href);
          }";
      }

      if (get_option('track_mailto_links') != 'on') {
        $code .= "
        if(href.toLowerCase().indexOf('mailto:') === 0) {
                    var email = href.substr(7);";
                    $mailto_cat = "Mailto";
                    if(!ua_injector_isNullOrEmpty(get_option('mailto_links_category'))) {
                      $mailto_cat = get_option('mailto_links_category');
                    }
                    $code .= "
                    ga('send', 'event', '" . $mailto_cat . "', email);
                    ga('send', 'pageview', '/virtual/contact/email/' + email);
                    console.log('Sending event and pageview for " . $mailto_cat . ":' + email);
                }";
      }

      if (get_option('track_outbound_links') != 'on') {
        $code .= "
        if ((this.protocol === 'http:' || this.protocol === 'https:') && this.hostname.indexOf(document.location.hostname) === -1) {";
          $outbound_cat = "Outbound";
          if(!ua_injector_isNullOrEmpty(get_option('outbound_links_category'))) {
            $mailto_cat = get_option('outbound_links_category');
          }
          $code .= "
          ga('send', 'event', '" . $outbound_cat . "', this.hostname, this.pathname);
                    ga('send', 'pageview', '/virtual/outbound/' + href);
                    console.log('Sending event for " . $outbound_cat . ": /virtual/outbound/' + href);
                }";
      }
      $code .= "});
      });";
    }

    if (get_option('track_youtube') != 'on') {
      $code .= ua_injector_render_youtube_tracking_option(get_option('track_youtube'), get_option('youtube_category'));
    }

    if (get_option('track_vimeo') != 'on') {
      $code .= ua_injector_render_vimeo_tracking_option(get_option('track_vimeo'), get_option('vimeo_category'));
    }

    $code .= "</script>";

    return $code;
}

/**
 * Render YouTube tracking code.
 *
 * @param string $option option if this tracking should be disabled or not.
 * @param string $category custom category label.
 */
function ua_injector_render_youtube_tracking_option($option, $category) {
    $result = "";
    if (ua_injector_isNullOrEmpty($option)) {
        $result .= "
        /* * * * * * * * * YouTube Tracking script * * * * * * * * */

        jQuery(document).ready(function() {
            // Enable JSAPI if it's not already on the URL for Youtube videos
            for (var e = document.getElementsByTagName('iframe'), x = e.length; x--;) {
              if (/youtube.com\/embed/.test(e[x].src)) {
                  console.log('Found YouTube iframe')
                if(e[x].src.indexOf('enablejsapi=') === -1) {
                  e[x].src += (e[x].src.indexOf('?') ===-1 ? '?':'&') + 'enablejsapi=1';
                }
              }
            }
        });

        var yTListeners = []; // support multiple players on the same page

        // The API will call this function when the page has finished downloading the JavaScript for the player API.
        // attach our YT listener once the API is loaded
        function onYouTubeIframeAPIReady() {
            for (var e = document.getElementsByTagName('iframe'), x = e.length; x--;) {
                if (/youtube.com\/embed/.test(e[x].src)) {
                    yTListeners.push(new YT.Player(e[x], {
                        events: {
                            onStateChange: onPlayerStateChange,
                            onError: onPlayerError
                        }
                    }));
                    YT.lastAction = 'p';
                }
            }
        }

        // listen for play/pause actions
        function onPlayerStateChange(e) {
            e['data'] == YT.PlayerState.PLAYING && setTimeout(onPlayerPercent, 1000, e['target']);
            var video_data = e.target['getVideoData'](),
                label = 'https://www.youtube.com/watch?v='+ video_data.video_id;
            if (e['data'] == YT.PlayerState.PLAYING && YT.lastAction == 'p') {
                ga('send', 'event', '" . $category . "', 'play', label);
                console.log('ga(send, event, " . $category . ", play, ' + label);

                YT.lastAction = '';
            }
            if (e['data'] == YT.PlayerState.PAUSED) {
                // Send PAUSE event to UA
                ga('send', 'event', '" . $category . "', 'pause', label);
                console.log('ga(send, event, " . $category . ", pause, ' + label);
                YT.lastAction = 'p';
            }
        }

        // catch all to report errors through the GTM data layer
        // once the error is exposed to GTM, it can be tracked in UA as an event!
        function onPlayerError(e) {
            ga('send', 'event', '" . $category . "', 'error', e);
            console.log('ga(send, event, " . $category . ", error, ' + e);
        }

        // report the % played if it matches 0%, 25%, 50%, 75% or 100%
        function onPlayerPercent(e) {
            if (e['getPlayerState']() == YT.PlayerState.PLAYING) {
                var t = e['getDuration']() - e['getCurrentTime']() <= 1.5 ? 1 : (Math.floor(e['getCurrentTime']() / e['getDuration']() * 4) / 4).toFixed(2);
                if (!e['lastP'] || t > e['lastP']) {
                    var video_data = e['getVideoData'](),
                        label = video_data.video_id+':'+video_data.title;
                    e['lastP'] = t;
                    ga('send', 'event', '" . $category . "', t * 100 + '%', label);
                    console.log('ga(send, event, " . $category . " ' + t * 100 + '%, ' + label);
                }
                e['lastP'] != 1 && setTimeout(onPlayerPercent, 1000, e);
            }
        }

        // load the Youtube JS api and get going
        var j = document.createElement('script'),
            f = document.getElementsByTagName('script')[0];
        j.src = '//www.youtube.com/iframe_api';
        j.async = true;
        f.parentNode.insertBefore(j, f);
        ";
    }
    return  $result;
}

/**
 * Render Vimeo tracking code.
 *
 * @param string $option option if this tracking should be disabled or not.
 * @param string $category custom category label.
 */
function ua_injector_render_vimeo_tracking_option($option, $category) {
    $result = "";
    if (ua_injector_isNullOrEmpty($option)) {
        $result .= "
        /* * * * * * * * * Vimeo Tracking script * * * * * * * * */

        /*!
         * Modified script from https://github.com/sanderheilbron/vimeo.ga.js
         * vimeo.ga.js | v0.4
         * MIT licensed
         */
         jQuery(document).ready(function() {
          (function() {
            'use strict';
              // Add the ?api=1 if missing in the iframe tags.
              for (var e = document.getElementsByTagName('iframe'), x = e.length; x--;) {
                if (/player.vimeo.com\/video/.test(e[x].src)) {
                  if(e[x].src.indexOf('api=') === -1) {
                    e[x].src += (e[x].src.indexOf('?') ===-1 ? '?':'&') + 'api=1';
                  }
                }
              }

              var f = jQuery('iframe[src*=\"player.vimeo.com\"]');
              if(typeof f !== 'undefined' && typeof f.attr('src') !== 'undefined') {
                var url = f.attr('src').split('?')[0],      // Source URL
                    trackSeeking = f.data('seek'),          // Data attribute to enable seek tracking
                    protocol = document.URL.split(':')[0],  // Domain protocol (http or https)
                    trackProgress = true,                   // f.data('progress') Data attribute to enable progress tracking
                    gaTracker,
                    progress25 = false,
                    progress50 = false,
                    progress75 = false,
                    progress90 = false,
                    videoPlayed = false,
                    videoPaused = false,
                    videoResumed = false,
                    videoSeeking = false,
                    videoCompleted = false,
                    timePercentComplete = 0;
              }

              // Match protocol with what is in document.URL
              if (typeof url !== 'undefined' && url.match(/^http/) === null) {
                url = protocol + ':' + url;
              }


              // Universal Analytics (universal.js)
              if (typeof ga === 'function') {
                gaTracker = 'ua';
                // console.info('Universal Analytics');
              }

              // Listen for messages from the player
              if (window.addEventListener) {
                window.addEventListener('message', onMessageReceived, false);
              } else {
                window.attachEvent('onmessage', onMessageReceived, false);
              }

              // Send event to Universal Analytics
              function sendEvent(action) {
                ga('send', 'event', '" . $category . "', action, url);
                console.log('ga(send, event, " . $category . ", ' + action + ', ' + url);
              }

              // Handle messages received from the player
              function onMessageReceived(e) {
                // Filter out other events
                if (e.origin.replace('https:', 'http:') !== 'http://player.vimeo.com' || typeof gaTracker === 'undefined') {
                  return;
                }
                var data = JSON.parse(e.data);
                switch (data.event) {
                  case 'ready':
                    onReady();
                    break;

                  case 'playProgress':
                    onPlayProgress(data.data);
                    break;

                  case 'seek':
                    if (trackSeeking && !videoSeeking) {
                      sendEvent('Skipped video forward or backward');
                      videoSeeking = true; // Avoid subsequent seek trackings
                    }
                    break;

                  case 'play':
                    if (!videoPlayed) {
                      sendEvent('play');
                      videoPlayed = true; // Avoid subsequent play trackings
                    } else if (!videoResumed && videoPaused) {
                      sendEvent('resume');
                      videoResumed = true; // Avoid subsequent resume trackings
                    }
                    break;

                  case 'pause':
                    onPause();
                    break;

                  case 'finish':
                    if (!videoCompleted) {
                      sendEvent('finish');
                      videoCompleted = true; // Avoid subsequent finish trackings
                    }
                    break;
                }
              }

              // Helper function for sending a message to the player
              function post(action, value) {
                var data = {
                  method: action
                };

                if (value) {
                  data.value = value;
                }

                if(typeof f !== 'undefined' && typeof url !== 'undefined') {
                  f[0].contentWindow.postMessage(JSON.stringify(data), url);
                }
              }

              function onReady() {
                post('addEventListener', 'play');
                post('addEventListener', 'seek');
                post('addEventListener', 'pause');
                post('addEventListener', 'finish');
                post('addEventListener', 'playProgress');
              }

              function onPause() {
                if (timePercentComplete < 99 && !videoPaused) {
                  sendEvent('pause');
                  videoPaused = true; // Avoid subsequent pause trackings
                }
              }

              // Tracking video progress
              function onPlayProgress(data) {
                timePercentComplete = Math.round((data.percent) * 100); // Round to a whole number

                if (!trackProgress) {
                  return;
                }

                var progress;

                if (timePercentComplete > 24 && !progress25) {
                  progress = '25%';
                  progress25 = true;
                }

                if (timePercentComplete > 49 && !progress50) {
                  progress = '50%';
                  progress50 = true;
                }

                if (timePercentComplete > 74 && !progress75) {
                  progress = '75%';
                  progress75 = true;
                }

                if (timePercentComplete > 89 && !progress90) {
                  progress = '90%';
                  progress90 = true;
                }

                if (progress) {
                  sendEvent(progress);
                }
              }
          })(jQuery);
        });
        ";
    }
    return  $result;
}

/**
 * Check if the given value is null or an empty string.
 * @param string the given string to evaluate.
 */
function ua_injector_isNullOrEmpty($val) {
    if(is_null($val)) {
        return true;
    } else if ($val == "") {
        return true;
    } else {
        return false;
    }
}

/**
 * Add the plugin options page link to the dashboard menu.
 */
function add_ua_injector_options_admin_menu() {
    add_options_page(__('UA Injector', 'ua-injector'), __('UA Injector', 'ua-injector'), 'manage_options', basename(__FILE__), 'ua_injector_plugin_options_page');
}

/**
 * The main function that generate the options page for this plugin.
 */
function ua_injector_plugin_options_page() {

    $tracking_code_err = "";
    if(!isset($_POST['update_ua_for_wordpress_plugin_options'])) {
        $_POST['update_ua_for_wordpress_plugin_options'] == 'false';
    }

    if ($_POST['update_ua_for_wordpress_plugin_options'] == 'true') {

        $errors = ua_injector_plugin_options_update();

        if (is_wp_error($errors)) {
            $tracking_code_err = $errors->get_error_message('tracking_code');
        }
    }
    ?>
    <div class="wrap">
        <div class="gai-col1">
            <div id="icon-themes" class="icon32"><br /></div>
            <h2><?php echo __('Universal Analytics Injector for WordPress', 'ua-injector'); ?></h2>
            <?php
            if (!ua_injector_isNullOrEmpty(get_option('ua_tracking_code'))) {
                if(!ua_injector_is_valid_ga_code()) {
                    echo "<div class='errorContainer'>
                               <h3 class='errorMsg'>".__('Multiple Google Analytics scripts detected!.', 'ua-injector')."</h3>
                               <p class='errorMsg'>".__('Maybe you have several Google analytics plugins active or a hard coded Google Analytics script in your theme (header.php).', 'ua-injector')."</p>
                             </div>";
                }
            }
            ?>
            <form method="post" action="">

                <h4 style="margin-bottom: 0px;"><?php echo __('Google Analytics tracking code (UA-xxxx-x)', 'ua-injector'); ?></h4>
                <?php
                if ($tracking_code_err) {
                    echo '<div class="errorMsg">'.$tracking_code_err.'</div>';
                }
                ?>
                <input type="text" name="ua_tracking_code" id="ua_tracking_code" value="<?php echo get_option('ua_tracking_code'); ?>" />

                <h4 style="margin-bottom: 0px;"><?php echo __('Your domain eg. .mydomain.com (default value is auto)', 'ua-injector'); ?></h4>
                <input type="text" name="site_domain_url" id="site_domain_url" value="<?php echo get_option('site_domain_url'); ?>" />
                <br>
                <h2><?php echo __('Optional settings', 'ua-injector'); ?></h2>

                <?php
                ua_injector_render_admin_tracking_option("track_outbound_links", 'outbound_links_category', get_option('outbound_links_category'), __('Disable tracking of outbound links', 'ua-injector'), __('(Default label is "Outbound")', 'ua-injector'));
                ua_injector_render_admin_tracking_option("track_mailto_links", "mailto_links_category", get_option('mailto_links_category'), __('Disable tracking of mailto links', 'ua-injector'), __('(Default label is "Mailto")', 'ua-injector'));
                ua_injector_render_admin_tracking_option("track_downloads", "downloads_category", get_option('downloads_category'), __('Disable tracking of downloads', 'ua-injector'), __('(Default label is "Download")', 'ua-injector'));
                ua_injector_render_admin_tracking_option("track_youtube", "youtube_category", get_option('youtube_category'), __('Disable tracking of Youtube video', 'ua-injector'), __('(Default label is "Youtube Video")', 'ua-injector'));
                ua_injector_render_admin_tracking_option("track_vimeo", "vimeo_category", get_option('vimeo_category'), __('Disable tracking of Vimeo video', 'ua-injector'), __('(Default label is "Vimeo Video")', 'ua-injector'));
                ?>

                <h2><?php echo __('Anonymize IP', 'ua-injector'); ?></h2>

                <div class="uaOption">
                    <h4><input name="anonymizeip" type="checkbox" id="anonymizeip" <?php echo ua_injector_get_checked(get_option('anonymizeip')); ?> /> <?php echo __('Activate anonymized ip address', 'ua-injector'); ?></h4>
                    <p><?php echo __('The anonymize ip option truncate the visitors ip address, eg. anonymize the information sent by the tracker before storing it in Google Analytics.', 'ua-injector'); ?></p>
                </div>

                <input type="hidden" name="update_ua_for_wordpress_plugin_options" value="true" />
                <p><input type="submit" name="search" value="<?php echo __('Update Options', 'ua-injector'); ?>" class="button" /></p>

            </form>
        </div>
        <div class="gai-col2">

            <div class="description">
                <h3><?php echo __('Get going', 'ua-injector'); ?></h3>
                <?php
                $images_path = path_join(WP_PLUGIN_URL, basename(dirname(__FILE__))."/images/");
                $external_icon = '<img src="'.$images_path.'external_link_icon.png" title="External link" />';
                printf(__('Enter the tracking code from the Google Analytics account you want to use for this site. None of the java script code will be inserted if you leave this field empty. (eg. the plugin will be inactive)  Go to <a href="http://www.google.com/analytics/" target="_blank">Google Analytics</a> %s and get your tracking code.', 'ua-injector'), $external_icon);
                ?>
            </div>

            <div class="description">
                <?php echo __('This plugin exclude the visits from the Administrator if he/she is currently logged in.', 'ua-injector'); ?>
            </div>

            <div class="description">
                <h4><?php echo __('Optional settings', 'ua-injector'); ?></h4>
                <?php echo __('With the optional settings you can specify which of these different tracking features you want to use. All methods are active as default. You can also add custom labels for the categories i Google Analytics.', 'ua-injector'); ?>
            </div>

            <div class="description">
                <h4><?php echo __('Author', 'ua-injector'); ?></h4>
                <?php printf(__('This plugin is created by @niklasolsson. Find more plugins at <a href="http://www.hoyce.com/wordpress-plugins/">Hoyce.com</a> %s', 'ua-injector'), $external_icon); ?>
            </div>

        </div>
    </div>
<?php
}

/**
 * Gets the 'checked' string if the given option value is 'on'.
 * @param $value the option value to check
 */
function ua_injector_get_checked($value) {
    if($value=='on') {
        return 'checked';
    } else {
        return $value;
    }
}

/**
 * Gets the 'disabled' string if the given option value is 'on'.
 * @param $value the option value to check
 */
function ua_injector_is_disabled($value) {
    if($value=='on') {
        return "disabled";
    } else {
        return "";
    }
}

/**
 * Render the option markup for the given tracking option.
 *
 * @param string $checkboxOpt name and id of the input checkbox.
 * @param string $category name and id of the text input.
 * @param string $categoryOpt name of the given cutom category.
 * @param string $label the checkbox label for current tracking option.
 * @param string $defaultCategory description for the default category.
 */
function ua_injector_render_admin_tracking_option($checkboxOpt, $category, $categoryOpt, $label, $defaultCategory) {
    echo "<div class='uaOption'>".
        "<div class='trackBox'><input class='cBox' name='".$checkboxOpt."' type='checkbox' id='".$checkboxOpt."' ".ua_injector_get_checked(get_option($checkboxOpt))." /> <span class='checkboxLabel'>".$label."</span></div>".
        "<span class='label ".ua_injector_is_disabled(get_option($checkboxOpt))."'>".__('Custom label:', 'ua-injector')."</span>".
        "<input type='text' name='".$category."' id='".$category."' value='".$categoryOpt."' class='".ua_injector_is_disabled(get_option($checkboxOpt))."' ".ua_injector_is_disabled(get_option($checkboxOpt))." />".
        "<span class='categoryText ". ua_injector_is_disabled(get_option($checkboxOpt))."'>".$defaultCategory."</span>".
        "</div>";
}

/**
 * Update the Universal Analytics Injector plugin options.
 */
function ua_injector_plugin_options_update() {

    if(isset($_POST['ua_tracking_code'])) {
        update_option('ua_tracking_code', $_POST['ua_tracking_code']);
    }

    if(isset($_POST['ua_tracking_code']) && !ua_injector_isValidUaCode($_POST['ua_tracking_code'])) {
        $errors = new WP_Error('tracking_code', __('The tracking code is on the wrong format', 'ua-injector'));
    }

    if(isset($_POST['site_domain_url']) && $_POST['site_domain_url'] != "") {
        update_option('site_domain_url', $_POST['site_domain_url']);
    } else {
      update_option('site_domain_url', 'auto');
    }

    if(isset($_POST['outbound_links_category'])) {
        update_option('outbound_links_category', $_POST['outbound_links_category']);
    }

    if(isset($_POST['mailto_links_category'])) {
        update_option('mailto_links_category', $_POST['mailto_links_category']);
    }

    if(isset($_POST['downloads_category'])) {
        update_option('downloads_category', $_POST['downloads_category']);
    }

    if(isset($_POST['youtube_category'])) {
        update_option('youtube_category', $_POST['youtube_category']);
    }

    if(isset($_POST['vimeo_category'])) {
        update_option('vimeo_category', $_POST['vimeo_category']);
    }

    update_option('track_outbound_links', $_POST['track_outbound_links']);
    update_option('track_mailto_links', $_POST['track_mailto_links']);
    update_option('track_downloads', $_POST['track_downloads']);
    update_option('track_youtube', $_POST['track_youtube']);
    update_option('track_vimeo', $_POST['track_vimeo']);
    update_option('anonymizeip', $_POST['anonymizeip']);

    return $errors;
}

/**
 * Validate the format of the given Google Analytics tracking code.
 * @param $ua_tracking_code the given Google Analytics tracking code to validate.
 */
function ua_injector_isValidUaCode($ua_tracking_code) {
    if($ua_tracking_code == "" || preg_match('/^UA-\d{4,9}-\d{1,2}$/', $ua_tracking_code)) {
        return true;
    }
    return false;
}

/**
 * Make sure we only load Google Analytics one time.
 */
function ua_injector_is_valid_ga_code() {

    $body_content = ua_injector_get_site_content();
    $numRes = preg_match_all("/".get_option('ua_tracking_code')."/", $body_content, $matches);

    if($numRes > 1) {
        return false;
    } else {
        return true;
    }
}

/**
 * Get the site content.
 *
 * @param $url the given url.
 */
function ua_injector_get_site_content() {

    if (!function_exists('curl_init')){
        die(__('cURL is not installed', 'ua-injector'));
    }

    $connection = curl_init();

    curl_setopt($connection,CURLOPT_URL, site_url());
    curl_setopt($connection,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($connection,CURLOPT_CONNECTTIMEOUT, 6);

    $content = curl_exec($connection);
    curl_close($connection);

    return $content;
}
