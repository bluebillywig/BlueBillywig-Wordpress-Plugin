<?php
define('BB_STRINGS', array(
    "VALIDATE_API_SUCCESS_TITLE" => "API key Successfully validated",
    "VALIDATE_API_FAIL_TITLE" => "Could not Validate API key",
    "VALIDATE_API_FAIL_LABEL" => "Please check that your Blue Billywig API settings are correct before generating shortcodes",
    "API_SETTINGS_SAVED" => "Settings Saved",
    "API_SETTINGS_TITLE" => "Blue Billywig Plugin Options",
    "API_SETTINGS_SHORTCODE_DEFAULTS_TITLE" => "Shortcode defaults",
    "API_SETTINGS_SHORTCODE_DEFAULTS_LABEL" => "These values are used as default values when embedding mediaclips",
    "API_SETTINGS_PUBLICATION_TITLE" => "Publication",
    "API_SETTINGS_PLAYOUT_TITLE" => "Playout",
    "API_SETTINGS_API_TITLE" => "API Options",
    "API_SETTINGS_API_LABEL" => "You can find your API key in Blue Billywig OVP > User (top-right) > Publication  > API keys (left sidebar) > (select api key)",
    "API_SETTINGS_API_SECRET_TITLE" => "API-Secret",
    "API_SETTINGS_API_ID_TITLE" => "API-ID"
));

define('BB_SHORTCODE_STRINGS', array(
    "SHORTCODE_TITLE" => "Generate shortcode",
    "SHORTCODE_DESCRIPTION" => "Use this page to generate shortcodes to embed mediaclips into your posts.<br/>
                                You can alternatively use the button in the TinyMCE editor but that doesn't support thumbnail previewing in the current version.<br/>
                                The Gutenberg editor does support thumbnail previewing the same way as this page does.",
    "SHORTCODE_SELECT_PLAYOUT" => "Select Playout",
    "SHORTCODE_SEARCH_CLIP" => "Seach for clip (title/id)",
    "SHORTCODE_GENERATED_TITLE" => "Generated shortcode",
    "SHORTCODE_GENERATED_LABEL" => "Supported attributes are clipID, playout, autoplay & publication",
    "ELEMENT_ID_SEARCH_INPUT" => "bb-library-search-input",
    "ELEMENT_ID_SEARCH_SUBMIT" => "bb-library-search-submit",
    "ELEMENT_ID_SEARCH_RESET" => "bb-library-search-reset",
    "ELEMENT_ID_LIBRARY_WRAPPER" => "bb-video-library-wrapper",
    "ELEMENT_ID_LIBRARY_SHORTCODE" => "bb-video-library-shortcode",
    "ELEMENT_ID_LIBRARY_SHORTCODE_WRAPPER" => "bb-video-library-shortcode-wrapper",
    "ELEMENT_ID_LIBRARY_SHORTCODE_PLAYOUT" => "bb-video-library-playout",
    "FEEDBACK_SEARCHING" => "Searching...",
    "FEEDBACK_NO_VIDEOS" => "No videos found",
    "FEEDBACK_SELECT_MEDIACLIP" => "Select a mediaclip to embed",
    "FEEDBACK_ERROR" => "Error while trying to find videos",
));

define('BB_BLOCK_STRINGS', array(
    "BLOCK_NAME" => "bb-plugin/bb-mediaclip-block",
    "BLOCK_TITLE" => "Blue Billywig Mediaclip",
    "BLOCK_DESCRIPTION" => "An embedded mediaclip in you Wordpress post",
    "BLOCK_ICON" => "video-alt3",
    "BLOCK_SEARCH_PLACEHOLDER" => "Enter clipname..",
    "BLOCK_SEARCH_SUBMIT_LABEL" => "Search",
    "BLOCK_SELECT_PLAYOUT_LABEL" => "Select playout: ",
    "BLOCK_SEARCH_CLIP_LABEL" => " Search clip: ",
    "ELEMENT_ID_VIDEO_WRAPPER" => "bb-video-wrapper",
    "ELEMENT_ID_CLIP" => "bb-video-",
    "ELEMENT_ID_CLIP_WRAPPER" => "bb-wr-",
    "ELEMENT_ID_LIBRARY_WRAPPER" => BB_SHORTCODE_STRINGS["ELEMENT_ID_LIBRARY_WRAPPER"],
    "FEEDBACK_INVALID_ID" => "Video can't be found",
    "FEEDBACK_NO_VIDEOS" => BB_SHORTCODE_STRINGS["FEEDBACK_NO_VIDEOS"],
    "FEEDBACK_ERROR" => BB_SHORTCODE_STRINGS["FEEDBACK_ERROR"],
)); 

define('BB_WIDGET_STRINGS', array(
    "WIDGET_ID" => "bb_mce_mediaclip",
    "WIDGET_TITLE" => "Embed a Blue Billywig Mediaclip",
    "WIDGET_SEARCH_LABEL" => "Clipname or ID",
    "WIDGET_SEARCH_BUTTON_LABEL" => "Search for clip",
    "WIDGET_PLAYOUT_LABEL" => "Playout",
    "WIDGET_SEARCH_NO_RESULT" => "Could not find any mediaclips that match given name or ID ",
    "WIDGET_SEARCH_ERROR" => "Error while searching for videos. Make sure API settings are correct",
    "WIDGET_SEARCHING" => "Searching..",
    "WIDGET_NO_VIDEO_SELECTED" => "No video selected",
    "ELEMENT_CLASS_SEARCH_INPUT" => "bb-widget-search-input",
    "ELEMENT_CLASS_FOUND_VIDEO" => "bb-widget-found-video",
    "ELEMENT_CLASS_NO_VIDEO_SELECTED" => "bb-widget-no-video-selected"
));
?>