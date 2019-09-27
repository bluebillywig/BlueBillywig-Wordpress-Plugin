<?php

function create_media_clip_request(){
        $filename = $_REQUEST['filename'];
        $title = $_REQUEST['title'];
        $status = $_REQUEST['status'];
        $description = $_REQUEST['description'];

        $properties = array(
            'srcid'=> $filename,
            'xml' => '<media-clip title="' . $title . '" status="' . $status . '" sourceid="'.$filename.'">
                      <description>' . $description . '</description></media-clip>'
        );
        $rpc = BlueBillywig::instance()->get_rpc();
        echo json_encode($rpc->doAction('mediaclip', 'put', $properties));
        
        die();
    }

    function fetch_upload_endpoint_request(){
        $rpc = BlueBillywig::instance()->get_rpc();
        echo '{ "rpctoken" : "' . $rpc->calculateRequestToken() . '", "awsResponse" : ' . 
            $rpc->curi('sapi/awsupload?mediaclipId=' . $_REQUEST['mediaclipId'] . '&type=mediaclip') . '}';
        die();
    }
?>