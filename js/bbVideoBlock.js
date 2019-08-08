
( function ( blocks, editor, i18n, element ) {
    var el = element.createElement;
    var __ = i18n.__;

    var blockStyle = {
        backgroundColor: '#ececec',
        color: '#020202',
        padding: '20px',
    };

    blocks.registerBlockType( BB_STRINGS[ "BLOCK_NAME" ], {
        title: __( BB_STRINGS[ "BLOCK_TITLE" ] ),
        description: BB_STRINGS[ "BLOCK_DESCRIPTION" ],
        icon: BB_STRINGS[ "BLOCK_ICON" ],
        category: 'embed',
        attributes: {
            clipID: {
                type: 'number'
            },
            playout: {
                type: 'string'
            }
        },
        edit: function ( props ) {
            let clipID = props.attributes.clipID;
            let playout = props.attributes.playout == null ? "default" : props.attributes.playout;
            window.bbVideoProperties = props;

            if ( clipID && clipID !== 0 ) {
                var content = el( 'div', { style: blockStyle, class: BB_STRINGS[ "ELEMENT_ID_VIDEO_WRAPPER" ], id: ( BB_STRINGS[ "ELEMENT_ID_CLIP" ] + clipID ) } );

                insertPlayer( clipID, playout );

                return content;
            } else {
                //Create selectionElement with DOM reference
                var selectElement = el( 'select', {
                    class: 'bbPlayoutSelect',
                    onChange: function ( e ) {
                        props.setAttributes( { playout: e.target.value } )
                    }
                }, createOptions( allPlayouts, element ) );

                return ( el(
                    'div',
                    { style: blockStyle, class: BB_STRINGS[ "ELEMENT_ID_VIDEO_WRAPPER" ] },
                    el( 'span', {}, BB_STRINGS[ "BLOCK_SELECT_PLAYOUT" ] ),
                    selectElement,
                    el( 'span', {}, BB_STRINGS[ "BLOCK_SEARCH_CLIP_LABEL" ] ),
                    el( 'input', {
                        placeholder: BB_STRINGS[ "BLOCK_SEARCH_PLACEHOLDER" ], class: 'bbVideoSearch', type: "text", autoFocus: true, onKeyDown: function ( e ) {
                            if ( e.keyCode === 13 ) {
                                onSubmit( e.target.parentElement );
                            }
                        }
                    } ),
                    el( 'input', {
                        value: BB_STRINGS[ "BLOCK_SEARCH_SUBMIT_LABEL" ], type: "submit", onClick: function ( e ) {
                            onSubmit( e.target.parentElement );
                        }
                    } ),
                    el( 'div', { class: BB_STRINGS[ "ELEMENT_ID_LIBRARY_WRAPPER" ] } )
                ) );
            }
        },
        save: function ( props ) {
            let clipID = props.attributes.clipID;
            let playout = props.attributes.playout;

            if ( clipID && clipID !== 0 ) {
                var content = el( 'div', { style: blockStyle, class: BB_STRINGS[ "ELEMENT_ID_VIDEO_WRAPPER" ], id: ( BB_STRINGS[ "ELEMENT_ID_CLIP" ] + clipID ) },
                    el( 'script', { type: 'text/javascript', src: getClipScriptURL( clipID, playout ) } ) );
                return content;
            }
            return el(
                'p',
                { style: blockStyle },
                BB_STRINGS[ "FEEDBACK_INVALID_ID" ]
            );
        },
    } );
}(
    window.wp.blocks,
    window.wp.editor,
    window.wp.i18n,
    window.wp.element
) );

function onSubmit ( rootElement ) {
    var inputs = $( rootElement ).find( 'input[type=text]' );
    var query = '';
    if ( inputs.length > 0 ) {
        query = inputs[ 0 ].value;
    }

    searchVideos( rootElement, query );
}

function clearVideos ( rootElement ) {
    var wrapper = $( rootElement ).find( '.' + BB_STRINGS[ "ELEMENT_ID_LIBRARY_WRAPPER" ] )[ 0 ];
    while ( wrapper.firstChild ) {
        wrapper.removeChild( wrapper.firstChild );
    }
}

function searchVideos ( rootElement, query ) {
    var wrapper = $( rootElement ).find( '.' + BB_STRINGS[ "ELEMENT_ID_LIBRARY_WRAPPER" ] );
    $.ajax( {
        url: ajaxurl,
        data: {
            'action': 'search_videos_request',
            'query': query
        },
        success: function ( data ) {
            clearVideos( rootElement );

            if ( data == '' ) {
                wrapper.append( "<pre>" + BB_STRINGS[ "FEEDBACK_NO_VIDEOS" ] + "</pre>" );
            } else {
                wrapper.append( data );
            }
        },
        error: function ( errorThrown ) {
            clearVideos( rootElement );
            wrapper.append( "<pre>" + BB_STRINGS[ "FEEDBACK_ERROR" ] + errorThrown + "</pre>" );
        }
    } );
}

function selectClip ( clipID ) {
    if ( window.bbVideoProperties ) {
        window.bbVideoProperties.setAttributes( { clipID: clipID } );
    }
}

function insertPlayer ( clipID, playout ) {
    var videoElementID = 'bb-wr-' + playout + '-' + clipID;
    var videoWrapper = document.getElementById( videoElementID );

    if ( !videoWrapper ) {
        var player = document.createElement( 'script' );
        player.src = getClipScriptURL( clipID, playout );

        //Give React some time to add the wrapper to the page and insert the player
        setTimeout( function () {
            var wrapper = document.getElementById( 'bb-video-' + clipID );
            wrapper.append( player );
        }, 10 );
    }
}

function createOptions ( playouts, element ) {
    var defaultPlayoutEnd = playouts.indexOf( ',' );
    var defaultPlayout = playouts.substring( 0, defaultPlayoutEnd );
    defaultPlayout = { label: defaultPlayout.split( ':' )[ 0 ], value: defaultPlayout.split( ':' )[ 1 ], selected: true };
    var otherPlayouts = playouts.substring( defaultPlayoutEnd + 1 ).split( ',' );
    var optionElements = [ element.createElement( "option", defaultPlayout ) ];

    for ( let i = 0; i < otherPlayouts.length; i++ ) {
        var currentPlayout = otherPlayouts[ i ].split( ":" );
        optionElements.push( element.createElement( "option", { label: currentPlayout[ 1 ], value: currentPlayout[ 0 ] } ) );
    }
    return element.concatChildren( optionElements );
}

function getClipScriptURL ( clipID, playout ) {
    return 'https://' + publication + '.bbvms.com/p/' + playout + '/c/' + clipID + '.js?autoPlay=false';
}