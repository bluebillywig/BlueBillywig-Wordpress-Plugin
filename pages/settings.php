<div class='wrap'>
<?php
    $notices = '';

    //Save api settings if submitted in a post request
    if( isset($_POST[BB_API_SETTINGS_PUBLICATION]) && 
        isset($_POST[BB_API_SETTINGS_SECRET]) && 
        isset($_POST[BB_API_SETTINGS_ID]) &&
        isset($_POST[BB_API_SETTINGS_DEFAULT_PLAYOUT])){
        
        $publication = $_POST[BB_API_SETTINGS_PUBLICATION];
        $secret = $_POST[BB_API_SETTINGS_SECRET];
        $id = $_POST[BB_API_SETTINGS_ID];
        $defaultPlayout = $_POST[BB_API_SETTINGS_DEFAULT_PLAYOUT];

        BlueBillywig::instance()->save_options(array(BB_API_SETTINGS_PUBLICATION => $publication,
                                                    BB_API_SETTINGS_SECRET => $secret, 
                                                    BB_API_SETTINGS_ID => $id,
                                                    BB_API_SETTINGS_DEFAULT_PLAYOUT => $defaultPlayout));
        $notices .= notice_saved();
    }

    if(BlueBillywig::instance()->test_stored_api_key()){
        $notices .= notice_test_success();
    }else{
        $notices .= notice_test_fail();
    }

    //Fetch up to date settings
    $bbOptions = BlueBillywig::instance()->get_options();
    
    function notice_saved(){
        return '<div class="notice notice-success is-dismissible">
                <p>' . BB_STRINGS['API_SETTINGS_SAVED'] . '</p>
            </div>';
    }

    function notice_test_success(){
        return '<div class="notice notice-success is-dismissible">
                <p class="odd">' . BB_STRINGS['VALIDATE_API_SUCCESS_TITLE'] . '</p>
            </div>';
    }

    function notice_test_fail(){
        return '<div class="notice notice-error is-dismissible">
                <p class="odd">' . BB_STRINGS['VALIDATE_API_FAIL_TITLE'] . '</p>
            </div>';
    }
?>

<h1><?php echo BB_STRINGS['API_SETTINGS_TITLE']; ?></h1>
<form method="post" action="">

<div>
    <h2><?php echo BB_STRINGS['API_SETTINGS_SHORTCODE_DEFAULTS_TITLE']; ?></h2>
    <p><?php echo BB_STRINGS['API_SETTINGS_SHORTCODE_DEFAULTS_LABEL']; ?></p>
    <span><?php echo BB_STRINGS['API_SETTINGS_PUBLICATION_TITLE']; ?>: </span>
    <input name="<?php echo BB_API_SETTINGS_PUBLICATION ?>" type="text" size="50" value="<?php echo $bbOptions[BB_API_SETTINGS_PUBLICATION] ?>"><br/>
    <span><?php echo BB_STRINGS['API_SETTINGS_PLAYOUT_TITLE']; ?>: </span>
    <input name="<?php echo BB_API_SETTINGS_DEFAULT_PLAYOUT ?>"type="text" size="50" value="<?php echo $bbOptions[BB_API_SETTINGS_DEFAULT_PLAYOUT] ?>">
</div>
<div>
    <h2><?php echo BB_STRINGS['API_SETTINGS_API_TITLE']; ?></h2>
    <p><?php echo BB_STRINGS['API_SETTINGS_API_LABEL']; ?></p>
    <span><?php echo BB_STRINGS['API_SETTINGS_API_SECRET_TITLE']; ?>: </span>
    <input name="<?php echo BB_API_SETTINGS_SECRET ?>" type="text" size="50" value="<?php echo $bbOptions[BB_API_SETTINGS_SECRET] ?>"><br/>
    <span><?php echo BB_STRINGS['API_SETTINGS_API_ID_TITLE']; ?>: </span>
    <input name="<?php echo BB_API_SETTINGS_ID ?>"type="number" size="5" value="<?php echo $bbOptions[BB_API_SETTINGS_ID] ?>">
</div>
    <br/><br/>
    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
</form>
<br/>

<?php 
    echo $notices;
?>
</div>