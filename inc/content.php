<?php

//BBvideo shortcode
function bb_mediaclip_shortcode( $userAtts ) {
	$options = BlueBillywig::instance()->get_api_options();
	$atts = shortcode_atts( array(
		'clipid' => '0',
		'playout' => 'default',
		'autoplay' => 'false',
		'publication' => $options[BB_API_SETTINGS_PUBLICATION]
	), $userAtts, 'bbmediaclip');

	$bbOptions = get_option(BB_API_SETTINGS_GROUP);
	return "<script src='https://" . $atts['publication'] . ".bbvms.com/p/" . $atts['playout'] . "/c/" . $atts['clipid'] . ".js?autoPlay=" . $atts['autoplay'] . "'></script>";
}

function search_videos_request(){

    if ( isset($_REQUEST) ) {     
        $properties = array(
			'query' => 'mediatypeSort:"video" AND (title:*' . $_REQUEST['query'] . '* OR id:*' . $_REQUEST['query'] . '* )',
			'limit' => '12',
			'sort' =>'createddate desc'
		);

		$action = isset($_REQUEST['previewClickAction']) ? $_REQUEST['previewClickAction'] : 'selectClip';

		// Search action
		$search_result = json_decode(BlueBillywig::instance()->get_rpc()->json('search', null, $properties), true);
		$options = BlueBillywig::instance()->get_api_options();
		$width = isset($_REQUEST['width']) ? $_REQUEST['width'] : 300;
		$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : 150;

		// Display search results
		if($search_result['count'] > 0) {
			foreach($search_result['items'] as $result) { 
				echo build_mediaclip_preview($result, $options, $width, $height, $action);
			}
		}
     
    }
   die();
}

function search_single_video_request(){

	if(!BlueBillywig::instance()->test_stored_api_key()){
        header('API Key failed');
        header('Content-Type: application/json; charset=UTF-8');
        die(json_encode(array('message' => 'ERROR', "code" => 500)));
	}

    if ( isset($_REQUEST) ) {     
        $properties = array(
			'query' => 'type:mediaclip AND status:published AND (title:*' . $_REQUEST['query'] . '* OR id:*' . $_REQUEST['query'] . '* )',
			'limit' => '12',
			'sort' =>'createddate desc'
		);

		// Search action
		$search_result = json_decode(BlueBillywig::instance()->get_rpc()->json('search', null, $properties));

		if($search_result->count > 0){
			$result = $search_result->items[0];
			echo $result->title . ":" . $result->id;
		}else{
			echo "404";
		}
    }
   die();
}

function search_playouts_request(){
	$parameterrs = null;
	if(isset($_POST['playout-name'])){
		$parameterrs['filterset'] = '[{"filters":[{"type":"playout","field":"fulltext","operator":"contains","value":["' . $_POST['playout-name'] . '"]}]}]';
	}
    
    // Search action
    $options = BlueBillywig::instance()->get_api_options();
    $playouts = json_decode(BlueBillywig::get_rpc()->sapi('playout', null, 'GET', null, null, $parameterrs));
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
	exit();
}

function fetch_all_playouts(){    
    // Search action
    $options = BlueBillywig::instance()->get_api_options();
	$playoutData = json_decode(BlueBillywig::instance()->get_rpc()->curi('sapi/playout'));
	$defaultPlayout = "";
	$playouts = "";

	if(isset($playoutData->count) && $playoutData->count > 0) {
        foreach($playoutData->items as $result) { 
            if($result->label == $options[BB_API_SETTINGS_DEFAULT_PLAYOUT]){
				$defaultPlayout = $result->label . ":" . $result->name;
				continue;
			}
			$playouts .= "," . $result->label . ":" . $result->name;
        }
	}
	$result = $defaultPlayout . $playouts;
	
	if($result == ""){
		$result = "default:default";
	}

	return $result;
}

function build_mediaclip_preview($mediaclip, $options, $width = 300, $height = 150, $clickAction = 'selectClip', $class = 'bb-thumbnail-wrapper'){
	$clipId = is_array($mediaclip) ? $mediaclip['id'] : $mediaclip->id;
	$clipTitle = isset($mediaclip->title) ? $mediaclip->title : 
				(is_array($mediaclip) && isset($mediaclip['title']) ? $mediaclip['title'] : 'Untitled video');
	$clipThumbnail = isset($mediaclip->mainthumbnail_string) ? $mediaclip->mainthumbnail_string : 
					(is_array($mediaclip) && isset($mediaclip['mainthumbnail_string']) ? $mediaclip['mainthumbnail_string'] : NULL);
	
	// Final thumbnail faillback
	if(!isset($clipThumbnail) && is_array($mediaclip) && isset($mediaclip['thumbnails']) && count($mediaclip['thumbnails']) > 0){
		$clipThumbnail = $mediaclip['thumbnails'][0]['src'];
	}

	if(isset($clipThumbnail) && $clipThumbnail !== NULL){
		$thumbnailURL = 'https://' . $options[BB_API_SETTINGS_PUBLICATION] . ".bbvms.com/image/" . $width . "/" . $height . "/" . $clipThumbnail;        
	}
	else{
		$thumbnailURL = BB_PLUGIN_IMG . 'placeholder.jpg';
	} 
	
	$preview = '<div onclick="' . $clickAction . '(' . $clipId. ', event)" class="' . $class . '" style="background: url(' . $thumbnailURL . ')">';
	$preview .= '<span style="pointer-events: none" class="bb-thumbnail-title">' . 	$clipTitle . '</span></div>';

	return $preview;
}

// NOTICE
function notice_success($message){
	if(!isset($GLOBALS['bb-notices'])){
		$GLOBALS['bb-notices'] = array();
	}
	array_push($GLOBALS['bb-notices'], '<div class="notice notice-success is-dismissible">
			<p>' . $message . '</p>
		</div>');
}

function notice_message($message){
	if(!isset($GLOBALS['bb-notices'])){
		$GLOBALS['bb-notices'] = array();
	}
	array_push($GLOBALS['bb-notices'], '<div class="notice is-dismissible">
			<p>' . $message . '</p>
		</div>');
}

function notice_warning($message){
	if(!isset($GLOBALS['bb-notices'])){
		$GLOBALS['bb-notices'] = array();
	}
	array_push($GLOBALS['bb-notices'], '<div class="notice notice-warning is-dismissible">
			<p>' . $message . '</p>
		</div>');
}

function notice_error($message){
	if(!isset($GLOBALS['bb-notices'])){
		$GLOBALS['bb-notices'] = array();
	}
	array_push($GLOBALS['bb-notices'], '<div class="notice notice-error is-dismissible">
			<p class="odd">' . $message . '</p>
		</div>');
}

function render_notices(){
	if(!isset($GLOBALS['bb-notices'])){ return; }
    $noticeHTML = '';
	foreach($GLOBALS['bb-notices'] as $notice){
        $noticeHTML .= $notice;
    }
    echo $noticeHTML;
}

// SETTINGS / FORM

function render_form_start($title){
?>
    <h1><?php echo $title; ?></h1>
	<form method="post" action="">
	<table class="form-table">
	<tbody>
<?php
}

function render_form_end(){
?>
	</tbody>
	</table>
	</form>
<?php
}


function render_setting_group_row($title, $label){
?>
<tr>
	<td colspan="3">
		<h2><?php echo $title; ?></h2>
		<p><?php echo $label; ?></p>
	</td>
</tr>
<?php
}

function render_setting_string($title, $name, $value, $description){
if($value === NULL){
	$value = '';
}
?>
	<tr>
		<th><span><?php echo $title ?></span></th>
		<td><input id="<?php echo $name; ?>" name="<?php echo $name; ?>" type="text" size="50" value="<?php echo $value; ?>"></td>            
		<td class="description"><?php echo $description; ?></td>
	</tr>   
<?php
}
function render_setting_dropdown($title, $name, $values, $selectedValue, $description){
if(!isset($values) || !is_array($values)){
	return false;
}
?>
	<tr>
		<th><span><?php echo $title ?> </span></th>
		<td>
			<select id="<?php echo $name; ?>" name="<?php echo $name; ?>">
			<?php
				foreach($values as $key => $value){
					echo '<option value="' . $value . '"' . ($value == $selectedValue ? ' selected' : '') . '>';
					echo $key . "</option>";
				}
			?>
			</select>
		</td>         
		<td class="description"><?php echo $description; ?></td>
	</tr>
<?php
}
function render_setting_number($title, $name, $value){
?>
	<tr>
		<th><span><?php echo $title; ?> </span></th>
		<td><input id="<?php echo $name; ?>" name="<?php echo $name; ?>"type="number" size="5" value="<?php echo $value; ?>"></td>
	</tr> 
<?php
}

function render_setting_boolean($title, $name, $value){
?>
	<tr>
		<th><span><?php echo $title ?> </span></th>
		<td>
			<select id="<?php echo $name; ?>" name="<?php echo $name; ?>">
				<option value="1" <?php if($value) echo 'selected'; ?>>On</option>
				<option value="0" <?php if(!$value) echo 'selected'; ?>>Off</option>
			</select>
		</td>
	</tr>
<?php
}

function render_setting_file($title, $name, $description){
?>
	<tr>
		<th><span><?php echo $title ?> </span></th>
		<td>
			<input id="<?php echo $name; ?>" type="file" name="<?php echo $name; ?>"/>
		</td>       
		<td class="description"><?php echo $description; ?></td>
	</tr>
<?php
}

function render_setting_submit_button($buttonText){
?>
	<tr>
		<th class="no-background">
			<br/>
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $buttonText; ?>">
		</th>
	</tr>
<?php
}

function render_setting_button($buttonText, $id, $description = '', $isPrimary = false){
?>
	<tr>
		<th class="no-background">
			<button type="button" id="<?php echo $id ?>" class="button <?php if($isPrimary) echo 'button-primary' ?>"><?php echo $buttonText;?></button>
		</th>   
		<td class="description"><?php echo $description; ?></td>
	</tr>
<?php
}
?>
