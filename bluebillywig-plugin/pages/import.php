<br/>
<style>
#import-start-button{
    background: #329bd0;
    color: white;
}
</style>
<script>
    function editClip(clipId, event){
        window.location.href = '?page=' + "<?PHP echo $_GET['page'] ?>" + "&mediaclipId=" + clipId;
    }

    $ = jQuery;
    
    function fetch_import_status(assetURL){
        var fetchIntervalId = setInterval(function(){
            $.ajax({
                url:ajaxurl,
                data: {
                    'action': 'fetch_import_status_request',
                    'assetURL': encodeURI(assetURL)
                },
                success: function(response){
                    
                    //for some reason an extra 0 gets appended to the response
                    if(response[response.length - 1] === '0'){
                        response = response.slice(0, -1);
                    }

                    var json = JSON.parse(response);
                    if(json.numfound > 0){
                        clearInterval(fetchIntervalId);                        
                        window.location.href = '?page=bb-library' + "&mediaclipId=" + json.items[0].id;
                    }
                },
                error: function(response){
                    console.warn(response);
                }
            });
        }, 500);
    }
</script>

<?PHP
    $importAssetURL = isset($_POST['assetURL']) ? $_POST['assetURL'] : false;
    $autoPublish = isset($_POST['autoPublish']) ? $_POST['autoPublish'] : false;
    $waitingForClip = false;

    if($autoPublish == 0){
        $autoPublish = 'false';
    }

    if($importAssetURL){
        $rpc = BlueBillywig::instance()->get_rpc();
        $importResult = $rpc->sapi('import', null, 'POST', [
            'autoPublish' => $autoPublish,
            'url' => $importAssetURL
        ]);

        $waitingForClip = true;
    }

    render_notices();

    if($waitingForClip){        
    ?>
        <h1>Waiting for mediaclip..</h1>
        <p>
        Your import has been started. <br/>
        This may take several minutes depending on the amount of items currently in the import queue. <br/>
        If you close this screen the import will not be cancelled, You can check the media library for the status of the clip.
        </p>
        <?php
        echo "<script>fetch_import_status('$importAssetURL');</script>";
    }else{
        $bbAPIOptions = BlueBillywig::instance()->get_api_options();
        wp_localize_script('bb-mediaclip-library', 'BB_STRINGS', BB_SHORTCODE_STRINGS);
        wp_localize_script('bb-mediaclip-library', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
    
        echo '<h1>Import</h1>';
        render_form_start("");
        render_setting_group_row("By url", 'Currently supported platforms: Youtube, Facebook, Dropbox, Instagram, CloudSound, Twitch, Vimeo & Flickr');
        render_setting_string('URL', 'assetURL', '', 'example: https://www.youtube.com/watch?v=jNQXAC9IVRw');
        render_setting_boolean('Published', 'autoPublish', false, 'Should the mediaclip start as published or draft');
        render_setting_submit_button('Start import', 'import-start-button');
        render_form_end();
    }
    
?>