var popup, selectedClipID;

( function () {
    tinymce.PluginManager.add( BB_STRINGS[ "WIDGET_ID" ], function ( editor, url ) {
        editor.addButton( BB_STRINGS[ "WIDGET_ID" ], {
            text: 'Mediaclip',
            image: iconURL, //localized through bb plugin page
            onclick: function () {
                // editor.insertContent( '[bbmediaclip]' );
                popup = editor.windowManager.open( {
                    title: BB_STRINGS[ "WIDGET_TITLE" ],
                    width: 500,
                    height: 200,
                    body: [
                        {
                            type: 'label',
                            label: BB_STRINGS[ "WIDGET_SEARCH_LABEL" ]
                        },
                        {
                            type: 'textbox',
                            name: 'searchQuery',
                            classes: BB_STRINGS[ "ELEMENT_CLASS_SEARCH_INPUT" ]
                        },
                        {
                            type: 'button',
                            name: 'searchSubmit',
                            text: BB_STRINGS[ "WIDGET_SEARCH_BUTTON_LABEL" ],
                            classes: 'primary',
                            onClick: function () {
                                searchVideos();
                            }
                        },
                        {
                            type: 'label',
                            text: BB_STRINGS[ "WIDGET_NO_VIDEO_SELECTED" ],
                            classes: BB_STRINGS[ "ELEMENT_CLASS_FOUND_VIDEO" ] + " " + BB_STRINGS[ "ELEMENT_CLASS_NO_VIDEO_SELECTED" ]
                        },
                        {
                            type: 'listbox',
                            name: 'playout',
                            label: BB_STRINGS[ "WIDGET_PLAYOUT_LABEL" ],
                            'values': fetchOptions()
                        }

                    ],
                    onsubmit: function ( e ) {
                        if ( selectedClipID == null ) { return; }

                        var selectedPlayout = e.data[ 'playout' ];

                        editor.focus();
                        editor.selection.setContent( editor.selection.getContent() + '[bbmediaclip clipID="' + selectedClipID + '" playout="' + selectedPlayout + '"]' );
                    }
                } );
            }
        } );
    } );
} )();

function fetchOptions () {
    var playoutsStartIndex = playouts.indexOf( ',' );
    var defaultPlayout = playouts.substring( 0, playoutsStartIndex );
    defaultPlayout = { text: defaultPlayout.split( ':' )[ 0 ], value: defaultPlayout.split( ':' )[ 1 ], selected: true };
    var otherPlayouts = playouts.substring( playoutsStartIndex + 1 ).split( ',' );
    var optionElements = [ defaultPlayout ];

    for ( let i = 0; i < otherPlayouts.length; i++ ) {
        var currentPlayout = otherPlayouts[ i ].split( ":" );
        optionElements.push( { text: currentPlayout[ 1 ], value: currentPlayout[ 0 ] } );
    }
    return optionElements;
}

function fetchVideos ( query, onSuccess ) {
    $.ajax( {
        url: ajaxurl,
        data: {
            'action': 'search_single_video_request',
            'query': query
        },
        success: function ( data ) {
            onSuccess( data );
        },
        error: function ( errorThrown ) {
            console.log( errorThrown );
        }
    } );
}

function searchVideos () {
    var searchInput = $( ".mce-" + BB_STRINGS[ "ELEMENT_CLASS_SEARCH_INPUT" ] );
    var foundVideoLabel = $( ".mce-" + BB_STRINGS[ "ELEMENT_CLASS_FOUND_VIDEO" ] );
    var searchQuery = searchInput.val();

    foundVideoLabel.html( BB_STRINGS[ "WIDGET_SEARCHING" ] );
    foundVideoLabel.css( "width", "100%" );
    foundVideoLabel.toggleClass( "mce-" + BB_STRINGS[ "ELEMENT_CLASS_NO_VIDEO_SELECTED" ], false );

    fetchVideos( searchQuery, function ( data ) {
        if ( data.message == "ERROR" ) { //Error while trying to fetch videos
            foundVideoLabel.toggleClass( "mce-" + BB_STRINGS[ "ELEMENT_CLASS_NO_VIDEO_SELECTED" ], true );
            foundVideoLabel.html( BB_STRINGS[ "WIDGET_SEARCH_ERROR" ] );
            return;
        }
        else if ( data == "404" ) { //No videos where found
            foundVideoLabel.toggleClass( "mce-" + BB_STRINGS[ "ELEMENT_CLASS_NO_VIDEO_SELECTED" ], true );
            foundVideoLabel.html( BB_STRINGS[ "WIDGET_SEARCH_NO_RESULT" ] + searchQuery );
            return;
        }
        data = data.split( ":" );
        var title = data[ 0 ];
        var id = data[ 1 ];
        foundVideoLabel.html( "<span style='font-weight: 600'>" + title + "</span><span style='font-weight: 200'> (ClipID:" + id + ")</span>" );

        selectedClipID = id;
    } );
}