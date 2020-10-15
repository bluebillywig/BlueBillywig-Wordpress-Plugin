var $ = jQuery;
$( document ).ready( initBBLibrary );

// Begin Script

const CLIP_SELECT_EVENT = new CustomEvent( 'clipselected', { clipID: 0 } );

var libraryWrapper;
var focusClipOnClick = false;

function initBBLibrary () {
    searchInput = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_SEARCH_INPUT' ] );
    searchButton = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_SEARCH_SUBMIT' ] );
    libraryWrapper = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_WRAPPER' ] );

    searchInput.addEventListener( "keydown", function ( e ) {
        if ( e.keyCode === 13 ) {
            searchVideos( searchInput.value );
        }
    } );

    searchButton.addEventListener( "click", function ( e ) {
        searchVideos( searchInput.value );
    } );

    searchVideos( "" );
}

function clearLibraryWrapper () {
    // shortcodeWrapper.style.display = "none";
    // selectedClipID = null;

    while ( libraryWrapper.firstChild ) {
        libraryWrapper.removeChild( libraryWrapper.firstChild );
    }
}

function searchVideos ( query ) {
    clearLibraryWrapper();
    libraryWrapper.append( BB_STRINGS[ 'FEEDBACK_SEARCHING' ] );
    $.ajax( {
        url: ajaxurl,
        data: {
            'action': 'search_videos_request',
            'query': query,
            'previewClickAction': typeof previewClickAction != 'undefined' ? previewClickAction : 'selectClip'
        },
        success: function ( data ) {
            if ( data == '' ) {
                libraryWrapper.innerHTML = "<h4>" + BB_STRINGS[ 'FEEDBACK_NO_VIDEOS' ] + "</h4>";
            } else {
                libraryWrapper.innerHTML = "<h4>" + BB_STRINGS[ 'FEEDBACK_SELECT_MEDIACLIP' ] + ":</h4>" + data;
            }
        },
        error: function ( errorThrown ) {
            libraryWrapper.innetHTML = "<pre>" + BB_STRINGS[ 'FEEDBACK_ERROR' ] + ": " + errorThrown + "</pre>";
        }
    } );
}

function clearLibraryWrapper () {
    // shortcodeWrapper.style.display = "none";
    // selectedClipID = null;

    while ( libraryWrapper.firstChild ) {
        libraryWrapper.removeChild( libraryWrapper.firstChild );
    }
}

function selectClip ( clipID, event ) {
    var selectedClip = event.target.tagName == "SPAN" ? event.target.parentElement : event.target;

    if ( focusClipOnClick ) {
        clearLibraryWrapper();
        libraryWrapper.append( selectedClip );
    }

    CLIP_SELECT_EVENT.clipID = clipID;
    libraryWrapper.dispatchEvent( CLIP_SELECT_EVENT );
}