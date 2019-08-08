<?php

//BBvideo shortcode
function bb_mediaclip_shortcode( $userAtts ) {
	$options = BlueBillywig::instance()->get_options();
	$atts = shortcode_atts( array(
		'clipid' => '0',
		'playout' => 'default',
		'autoplay' => 'false',
		'publication' => $options[BB_API_SETTINGS_PUBLICATION]
	), $userAtts, 'bbmediaclip');

	$bbOptions = get_option(BB_API_SETTINGS_GROUP);
	return "<script src='https://" . $atts['publication'] . ".bbvms.com/p/" . $atts['playout'] . "/c/" . $atts['clipid'] . ".js?autoPlay=" . $atts['autoplay'] . "'></script>";
}
//Register shortcode with WP
add_shortcode( 'bbmediaclip', 'bb_mediaclip_shortcode' );

function search_videos_request(){

    if ( isset($_REQUEST) ) {     
        $properties = array(
			'query' => 'type:mediaclip AND status:published AND (title:*' . $_REQUEST['query'] . '* OR id:*' . $_REQUEST['query'] . '* )',
			'limit' => '12',
			'sort' =>'createddate desc'
		);

		// Search action
		$search_result = json_decode(BlueBillywig::instance()->get_rpc()->json('search', null, $properties));
		$options = BlueBillywig::instance()->get_options();
		$width = isset($_REQUEST['width']) ? $_REQUEST['width'] : 300;
		$height = isset($_REQUEST['height']) ? $_REQUEST['height'] : 150;

		// Display search results
		if($search_result->count > 0) {
			foreach($search_result->items as $result) { 
				echo build_mediaclip_preview($result, $options, $width, $height);
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
    $options = BlueBillywig::instance()->get_options();
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
    $options = BlueBillywig::instance()->get_options();
	$playoutData = json_decode(BlueBillywig::instance()->get_rpc()->sapi('playout'));
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

function build_mediaclip_preview($mediaclip, $options, $width = 300, $height = 150){
	$thumbnailURL = '';
	if(isset($mediaclip->mainthumbnail_string)){
		$thumbnailURL = 'https://' . $options[BB_API_SETTINGS_PUBLICATION] . ".bbvms.com/image/" . $width . "/" . $height . "/" . $mediaclip->mainthumbnail_string;        
	}
	else{
		$thumbnailURL = BB_PLUGIN_IMG . 'placeholder.jpg';
	} 
	
	$preview = '<div onclick="selectClip(' . $mediaclip->id . ', event)" class="bb-thumbnail-wrapper" style="background: url(' . $thumbnailURL . ')">';
	$preview .= '<span style="pointer-events: none" class="bb-thumbnail-title">' . $mediaclip->title . '</span></div>';

	return $preview;
}
?>