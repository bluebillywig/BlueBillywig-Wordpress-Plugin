<?php
    $notices = [];

    //Save api settings if submitted in a post request
    if( isset($_POST[BB_API_SETTINGS_PUBLICATION]) && 
        isset($_POST[BB_API_SETTINGS_SECRET]) && 
        isset($_POST[BB_API_SETTINGS_ID]) &&
        isset($_POST[BB_API_SETTINGS_DEFAULT_PLAYOUT])){
        
        $publication = $_POST[BB_API_SETTINGS_PUBLICATION];
        $secret = $_POST[BB_API_SETTINGS_SECRET];
        $id = $_POST[BB_API_SETTINGS_ID];
        $defaultPlayout = $_POST[BB_API_SETTINGS_DEFAULT_PLAYOUT];

        BlueBillywig::instance()->save_api_options(array(BB_API_SETTINGS_PUBLICATION => $publication,
                                                    BB_API_SETTINGS_SECRET => $secret, 
                                                    BB_API_SETTINGS_ID => $id,
                                                    BB_API_SETTINGS_DEFAULT_PLAYOUT => $defaultPlayout));
        notice_success(BB_STRINGS['API_SETTINGS_SAVED']);
    }
    //TODO: update to compare $_POST values with BB_PLUGIN_SETTINGS_KEYS to automatically update al settings

    //Notice settings
    if(isset($_POST[BB_PLUGIN_SETTING_SUPPRESS_NOTICES])){
        $suppressNotices = $_POST[BB_PLUGIN_SETTING_SUPPRESS_NOTICES];

        BlueBillywig::instance()->save_plugin_option(BB_PLUGIN_SETTING_SUPPRESS_NOTICES, $suppressNotices);
    }

    //Autoplay
    if(isset($_POST[BB_PLUGIN_SETTING_AUTOPLAY])){
        $autoplay = $_POST[BB_PLUGIN_SETTING_AUTOPLAY];

        BlueBillywig::instance()->save_plugin_option(BB_PLUGIN_SETTING_AUTOPLAY, $autoplay);
    }

    //Autopublish
    if(isset($_POST[BB_PLUGIN_SETTING_AUTOPUBLISH])){
        $autoPublish = $_POST[BB_PLUGIN_SETTING_AUTOPUBLISH];

        BlueBillywig::instance()->save_plugin_option(BB_PLUGIN_SETTING_AUTOPUBLISH, $autoPublish);
    }

    //Fetch up to date settings
    $bbAPIOptions = BlueBillywig::instance()->get_api_options();
    $suppressNotices = BlueBillywig::instance()->get_plugin_option(BB_PLUGIN_SETTING_SUPPRESS_NOTICES);
    $autoplay = BlueBillywig::instance()->get_plugin_option(BB_PLUGIN_SETTING_AUTOPLAY);
    $autoPublish = BlueBillywig::instance()->get_plugin_option(BB_PLUGIN_SETTING_AUTOPUBLISH);

    if(!$suppressNotices){
        if(BlueBillywig::instance()->test_stored_api_key()){
            notice_success(BB_STRINGS['VALIDATE_API_SUCCESS_TITLE']);
        }else{
            notice_error(BB_STRINGS['VALIDATE_API_FAIL_TITLE']);
        }
    }
?>
<div class='wrap'>
<?php

    render_form_start(BB_STRINGS['API_SETTINGS_TITLE']);
        render_setting_group_row(BB_STRINGS['API_SETTINGS_API_TITLE'], BB_STRINGS['API_SETTINGS_API_LABEL']);
        render_setting_string(  BB_STRINGS['API_SETTINGS_API_SECRET_TITLE'], BB_API_SETTINGS_SECRET, $bbAPIOptions[BB_API_SETTINGS_SECRET],
                                'Secret key used for authentication on API requests');
        render_setting_number(  BB_STRINGS['API_SETTINGS_API_ID_TITLE'], BB_API_SETTINGS_ID, $bbAPIOptions[BB_API_SETTINGS_ID]);
        render_setting_string(  BB_STRINGS['API_SETTINGS_PUBLICATION_TITLE'], BB_API_SETTINGS_PUBLICATION, $bbAPIOptions[BB_API_SETTINGS_PUBLICATION],
                                'The stub of your Blue Billywig OVP publication ([PUBLICATION].bbvms.com/OVP)');

        render_setting_group_row(BB_STRINGS['API_SETTINGS_SHORTCODE_DEFAULTS_TITLE'], BB_STRINGS['API_SETTINGS_SHORTCODE_DEFAULTS_LABEL']);
        render_setting_string(  BB_STRINGS['API_SETTINGS_PLAYOUT_TITLE'], BB_API_SETTINGS_DEFAULT_PLAYOUT, $bbAPIOptions[BB_API_SETTINGS_DEFAULT_PLAYOUT],
                                'The default playout used when embedding videos or generating shortcodes');
        render_setting_boolean( BB_STRINGS['SETTINGS_PLUGIN_AUTOPLAY_TITLE'], BB_PLUGIN_SETTING_AUTOPLAY, $autoplay);

        render_setting_group_row(BB_STRINGS['SETTINGS_PLUGIN_TITLE'], BB_STRINGS['SETTINGS_PLUGIN_LABEL']);
        render_setting_boolean( BB_STRINGS['SETTINGS_PLUGIN_SUPRESS_NOTICE_TITLE'], BB_PLUGIN_SETTING_SUPPRESS_NOTICES, $suppressNotices);
        render_setting_dropdown(BB_STRINGS['SETTINGS_PLUGIN_AUTOPUBLISH_TITLE'], BB_PLUGIN_SETTING_AUTOPUBLISH, array(
            'Published' => 1,
            'Draft' => 0
        ), $autoPublish, BB_STRINGS['SETTINGS_PLUGIN_AUTOPUBLISH_LABEL']);

        render_setting_submit_button('Save Settings');
    render_form_end();
    
    render_notices();
    ?>
</div>