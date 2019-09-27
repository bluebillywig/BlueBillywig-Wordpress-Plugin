<br/>
<script>
    function editClip(clipId, event){
        window.location.href = '?page=' + "<?PHP echo $_GET['page'] ?>" + "&mediaclipId=" + clipId;
    }

    $ = jQuery;
    $(document).ready(function(){
        $('#bb-delete-media-clip-button').click(function(){
            if(confirm('Are you sure you want to delete this mediaclip?')){
                window.location.href = window.location.href + '&action=delete';
            }
        });
        $('#bb-purge-media-clip-button').click(function(){
            if(confirm('Are you sure you want to PERMANENTLY delete this mediaclip?')){
                window.location.href = window.location.href + '&action=purge';
            }
        });
    });
</script>

<?PHP
    $mediaclipId = isset($_GET['mediaclipId']) ? $_GET['mediaclipId'] : -1;

    if($mediaclipId > 0){
        if(isset($_GET['action'])){
            switch( $_GET['action']){
                case 'delete':
                    $actionResponse = BlueBillywig::instance()->get_rpc()->curi('api/mediaclip?action=remove&id=' . $mediaclipId);
                break;
                case 'purge':
                    $actionResponse = BlueBillywig::instance()->get_rpc()->curi('api/mediaclip?action=purge&absolutelySureWhatIAmDoing=true&id=' . $mediaclipId);
                break;
            }

            if(isset($actionResponse)){
                notice_success($actionResponse);
            }
        }else{
            $properties = array(
                'query' => 'type:mediaclip AND status:published AND id:' . $mediaclipId . '',
                'limit' => '1'
            );
            
            // Search action
            $metadata = json_decode(BlueBillywig::instance()->get_rpc()->json('mediaclip', $mediaclipId), true);
        }
    }

    // A clip is selected, show meta data fields
    if(isset($metadata)){

        if(isset($metadata['transcodingFinished']) && !$metadata['transcodingFinished']){
            notice_message('This mediaclip is currently still transcoding');
        }

        // A submission was made with metadata
        if(isset($_REQUEST['submit'])){
            $updateResponse = update_mediaclip_metadata($mediaclipId, generate_request_array(array('page', 'mediaclipId', 'submit')));

            // Metadata update request was made and we received a response
            if(isset($updateResponse)){
                
                if($updateResponse['error'] == 'false'){
                    notice_success('Mediaclip has been successfully updated!');
                }else{
                    notice_error('Something went wrong try to update mediaclip. <b>' . $updateResponse['code'] . '</b>: <i>' . $updateResponse['body'] . '</i>');
                }
            }
            
            //Populate metadata with request values in case the update was still processing when retreiving the video's metadata
            $metadata['cat'] = tags_as_array($_REQUEST['cat']);
            $metadata['title'] = $_REQUEST['title'];
            $metadata['description'] = $_REQUEST['description'];
            $metadata['author'] = $_REQUEST['author'];
            $metadata['copyrightSort'] = $_REQUEST['copyright'];
            $metadata['status'] = $_REQUEST['status'];
        }

        // Make sure the meta data fields are known
        $metadata = ensure_meta_data($metadata, array('cat', 'title', 'description', 'author', 'copyrightSort', 'status'));

        echo '<div class="bb-action-bar">
                <a class="bb-back" href="?page=' . $_GET['page'] . '">< Back to overview</a>
                <a class="bb-view" target="_blank" href="' . $metadata['gendeeplink'] . '">Preview Mediaclip</a>
            </div>';
        echo build_mediaclip_preview($metadata, BlueBillywig::instance()->get_api_options(), 300, 150, '', 'bb-thumbnail-wrapper static');
        
        // Render meta data form
        render_form_start("Edit clip metadata");
            render_setting_group_row('Mediaclip Metadata', '');
            render_setting_string('Mediaclip title', 'title', $metadata['title'], 'Title of your mediaclip');
            render_setting_string('Mediaclip description', 'description', $metadata['description'], 'Description of your mediaclip');
            render_setting_string('Author', 'author', $metadata['author'], 'The author of the mediaclip');
            render_setting_string('Copyright', 'copyright', $metadata['copyrightSort'], 'Copyright holder of the mediaclip');
            render_setting_string('Tags', 'cat', implode(', ', $metadata['cat']), 'Mediaclip tags (seperated by a comma)');
            render_setting_dropdown('Status', 'status', array(
                'Published' => 'published',
                'Draft' => 'draft'
            ), $metadata['status'], 'Visibility status of the mediaclip');
            render_setting_submit_button('Save Metadata');
            render_setting_group_row('Mediaclip actions', '');
            render_setting_button('Delete Mediaclip', 'bb-delete-media-clip-button', 'Deletes the Mediaclip from the Blue Billywig OVP');
            render_setting_button('Purge Mediaclip', 'bb-purge-media-clip-button', '<b>(PERMANENT)</b> Purges the mediaclip from the OVP and marks the source file for deletion');
        render_form_end();
        
        render_notices();        
    }else{
        render_notices();
        
        // No mediaclip was specified so we display the media library
        $bbAPIOptions = BlueBillywig::instance()->get_api_options();
        wp_enqueue_script('bb-mediaclip-library', BB_PLUGIN_JS . 'bbMediaclipLibrary.js');
        wp_localize_script('bb-mediaclip-library', 'defaultPlayout', $bbAPIOptions[BB_API_SETTINGS_DEFAULT_PLAYOUT]);
        wp_localize_script('bb-mediaclip-library', 'BB_STRINGS', BB_SHORTCODE_STRINGS);
        wp_localize_script('bb-mediaclip-library', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
        wp_localize_script('bb-mediaclip-library', 'previewClickAction', 'editClip' );

        echo '<h1>Media Library</h1>';
        require_once BB_PLUGIN_INC . 'library.php';
    }
?>