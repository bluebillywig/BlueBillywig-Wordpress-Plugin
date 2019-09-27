<?php
    if(!BlueBillywig::instance()->test_stored_api_key()){
        echo "<div class='notice notice-error is-dismissible'>
                <h3>" . BB_STRINGS['VALIDATE_API_FAIL_TITLE'] . "</h3>
                <p>" . BB_STRINGS['VALIDATE_API_FAIL_LABEL'] .  "</p>
            </div>";
        die;
    }
?>

<div class='wrap'>
<h3><?php echo BB_SHORTCODE_STRINGS['SHORTCODE_TITLE']; ?></h3>
<p><?php echo BB_SHORTCODE_STRINGS['SHORTCODE_DESCRIPTION']; ?></p>
<span><?php echo BB_SHORTCODE_STRINGS['SHORTCODE_SELECT_PLAYOUT']; ?>:</span>
<select id="<?php echo BB_SHORTCODE_STRINGS['ELEMENT_ID_LIBRARY_SHORTCODE_PLAYOUT']; ?>">
<?php      
    $options = BlueBillywig::instance()->get_api_options();
    
    // Search action
    $playouts = json_decode(BlueBillywig::instance()->get_rpc()->curi('sapi/playout'));

    if($playouts->count > 0) {
        foreach($playouts->items as $result) { 
            $playout = '<option ';
            if($result->label == $options[BB_API_SETTINGS_DEFAULT_PLAYOUT]){
                $playout .= 'selected ';
            }
            $playout .= 'value="' . $result->label . '">' . $result->name . "</option>";
            echo $playout;
        }
    }
?>
</select>
	<?php require_once BB_PLUGIN_INC . 'library.php'; ?>
<input type="submit" id="<?php echo BB_SHORTCODE_STRINGS['ELEMENT_ID_SEARCH_RESET']; ?>" class="button button-secondary" value="Reset">
<br/><br/>
    </div>
    <div id="<?php echo BB_SHORTCODE_STRINGS['ELEMENT_ID_LIBRARY_SHORTCODE_WRAPPER']; ?>" style="display:none" >
        <h4><?php echo BB_SHORTCODE_STRINGS['SHORTCODE_GENERATED_TITLE']; ?>:</h4>
        <p><?php echo BB_SHORTCODE_STRINGS['SHORTCODE_GENERATED_LABEL']; ?></p>
        <input size="50" id="<?php echo BB_SHORTCODE_STRINGS['ELEMENT_ID_LIBRARY_SHORTCODE']; ?>">
    </div>
</div>