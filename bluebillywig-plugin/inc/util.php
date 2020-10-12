<?php
    function get_top_level_plugin_dir_name($file){
        $path = explode('/', $file);
        
        if(is_array($path) && count($path) > 0){
            return $path[count($path) - 1];
        }
    }

    function is_on_plugins_page(){
        $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : NULL;

        if($script === null){
            return false;
        }

        return get_top_level_plugin_dir_name($script) === 'plugins.php';
    }

    function var_dump_pre($var, $addWPMenuPadding = true){
        echo "<pre" . ($addWPMenuPadding ? " style='padding-left: 200px;'" : '') . ">";
        var_dump($var);
        echo "</pre>";
    }

    function console_log($message){
        echo "<script>console.log('" . $message . "');</script>";
    }

    function has_required_form_fields($requiredFields, $request){
        foreach($requiredFields as $field){
            if(!isset($request[$field]) || empty($request[$field])){
                return false;
            }
        }
        return true;
    }

    function get_missing_form_fields($requiredFields, $request){
        $missing = array();

        foreach($requiredFields as $field){
            if(!isset($request[$field]) || empty($request[$field])){
                array_push($missing, $field);
            }
        }
        return $missing;
    }
    
    function update_mediaclip_metadata($clipId, $metadata){
        if(!isset($clipId) || is_numeric($clipId) && $clipId <= 0){
            return array(
                'error' => 'true',
                'code' => '400',
                'body' => 'No mediaclip id was specified'
            );
        }

        $xml = new SimpleXMLElement('<media-clip/>');
        $xml->addAttribute('id', $clipId);
        $xml->addAttribute('title', $metadata['title']);
        $xml->addAttribute('status', $metadata['status']);

        strip_mediaclip_metadata($metadata, array('title', 'status'));
        foreach($metadata as $metaKey => $metaValue){
            if(is_array($metaValue) && $metaKey === 'cat'){
                $catTree = $xml->addChild('categorization')->addChild('category-tree');
                $catTree->addAttribute('type', 'keywords');
                foreach($metaValue as $tag){
                    $catTree->addChild('category')->addAttribute('name', $tag);
                }
            }else{
                $xml->addChild($metaKey, $metaValue);
            }
        }
            
        $properties = array(
            'xml' => $xml->asXML()
        );
        $rpc = BlueBillywig::instance()->get_rpc();
        $response = $rpc->doAction('mediaclip', 'put', $properties);

        return $response;
    }

    function generate_request_array($excludeList = array()){
        $requestArray = array();
        foreach($_REQUEST as $requestKey => $requestValue){
            if(in_array($requestKey, $excludeList)){
                continue;
            }
            $requestArray[$requestKey] = $requestValue;

            if($requestKey === 'cat'){ //Format tags into array
                $requestArray[$requestKey] = tags_as_array($requestValue);
            }
        }
        return $requestArray;
    }

    function ensure_meta_data($metadata, $required ){
        foreach($required as $requiredKey){
            if(!isset($metadata[$requiredKey]) || $metadata[$requiredKey] === NULL){
                $metadata[$requiredKey] = $requiredKey === 'cat' ? array() : '';
            }
        }
        return $metadata;
    }

    function tags_as_array($tagString){
        $tags = explode(',', $tagString);
        for($i = 0; $i < count($tags); $i++){
            $tags[$i] = trim($tags[$i]);
        }
        return $tags;
    }

    function strip_mediaclip_metadata($metadata, $stripList){
        foreach($metadata as $metaKey => $metaValue){
            foreach($stripList as $targetKey){
                if($metaKey == $targetKey){
                    unset($metadata[$metaKey]);
                }
            }
        }
        return $metadata;
    }
?>