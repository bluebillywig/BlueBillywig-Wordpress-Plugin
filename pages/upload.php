<?php
    $defaultStatus = BlueBillywig::instance()->get_plugin_option(BB_PLUGIN_SETTING_AUTOPUBLISH);

    echo "<div id='bb-upload-notice'>notice<span>close</span></div>";
    render_form_start("Upload mediaclip");
        render_setting_group_row('Mediaclip Metadata', 'Upload a mediaclip directly into the Blue Billywig Online Video Platform');
        render_setting_string('Mediaclip title', 'bb-upload-title', '', '(required) Title of your mediaclip');
        render_setting_string('Mediaclip Description', 'bb-upload-description', '', '(optional) description of your mediaclip');
        render_setting_dropdown('Status', 'status', array(
            'Published' => 'published',
            'Draft' => 'draft'
        ), $defaultStatus ? 'published' : 'draft', 'Visibility status of the mediaclip');
        render_setting_file('Video file', 'bb-upload-file', '(required) Source file for the mediaclip');
    render_form_end();

    render_setting_button('Start Mediaclip upload', 'bb-start-upload-button', '', true);

    echo "<div id='bb-upload-controls'>";
        // render_setting_button('Pause upload', 'bb-pause-upload-button'); There are some problems with pausing ATM so the button is disabled for now
        render_setting_button('Cancel upload', 'bb-cancel-upload-button');
        echo '<span id="bb-progressbar-action"></span>';
    echo "</div>";
?>

<div id="bb-progress-wrapper">
    <br/>
    <div id="bb-progressbar-wrapper">
        <span id="bb-progressbar-amount"></span>
        <div id="bb-progressbar-fill"></div>
    </div>
</div>