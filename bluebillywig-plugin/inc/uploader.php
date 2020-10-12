<?php

use BlueBillywig\VMSRPC\VMSUtil;

function create_media_clip_request()
{
    $filename = $_REQUEST['filename'];
    $title = $_REQUEST['title'];
    $status = $_REQUEST['status'];
    $description = $_REQUEST['description'];

    $properties = array(
        'srcid' => $filename,
        'xml' => '<media-clip title="' . $title . '" status="' . $status . '" sourceid="' . $filename . '">
                      <description>' . $description . '</description></media-clip>'
    );
    $rpc = BlueBillywig::instance()->get_rpc();
    echo json_encode($rpc->doAction('mediaclip', 'put', $properties));

    // $properties = array(
    //     'originalfilename' => $filename,
    //     'mediatype' => 'video',
    //     'title' => $title,
    //     // 'description' => $description,
    //     // 'status' => $status,
    //     // 'softsafe' => 'true'

    //     // 'xml' => '<media-clip title="' . $title . '" status="' . $status . '" sourceid="'.$filename.'">
    //     //           <description>' . $description . '</description></media-clip>'
    // );
    // $rpc = BlueBillywig::instance()->get_rpc();
    // // echo json_encode($rpc->doAction('mediaclip', 'put', $properties));
    // $result = json_encode($rpc->sapi('mediaclip', null, 'POST', $properties));
    // VMSUtil::debugmsg($result, "bbplugin");

    // echo $result;
    // // $rpcToken = $rpc->calculateRequestToken();
    // // echo json_encode($rpc->curi("sapi/mediaclip?originalfilename=$filename&mediatype=video&title=$title&rpctoken=$rpcToken", 'POST'));
    die();
}

function fetch_upload_endpoint_request()
{
    $rpc = BlueBillywig::instance()->get_rpc();
    echo '{ "rpctoken" : "' . $rpc->calculateRequestToken() . '", "awsResponse" : ' .
        $rpc->curi('sapi/awsupload?mediaclipId=' . $_REQUEST['mediaclipId'] . '&type=mediaclip&autoPublish=false') . '}';

    die();
}

function fetch_import_status_request()
{
    $rpc = BlueBillywig::instance()->get_rpc();
    $assetURL = $_REQUEST['assetURL'];
    $url = 'sapi/mediaclip/?q=sourceid:"imported-from:' . $assetURL . '"';
    echo $rpc->curi($url);
}