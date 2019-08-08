var $ = jQuery;

$( document ).ready( onload );

var searchButton, searchInput, playoutSelection, searchResetButton;
var libraryWrapper;
var shortcodeContainer, shortcodeWrapper;
var initialized = false;

//selection
var selectedClipID, selectedPlayout;

function onload () {
    if ( initialized ) return;

    searchInput = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_SEARCH_INPUT' ] );
    searchButton = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_SEARCH_SUBMIT' ] );
    searchResetButton = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_SEARCH_RESET' ] );
    libraryWrapper = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_WRAPPER' ] );
    shortcodeContainer = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_SHORTCODE' ] );
    shortcodeWrapper = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_SHORTCODE_WRAPPER' ] );
    playoutSelection = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_SHORTCODE_PLAYOUT' ] );

    searchInput.addEventListener( "keydown", function ( e ) {
        if ( e.keyCode === 13 ) {
            searchVideos( searchInput.value );
        }
    } );

    searchButton.addEventListener( "click", function ( e ) {
        searchVideos( searchInput.value );
    } );

    searchResetButton.addEventListener( "click", function ( e ) {
        clearLibraryWrapper();
        selectedPlayout = defaultPlayout;
        playoutSelection.value = selectedPlayout;
        searchInput.value = "";
    } );

    playoutSelection.addEventListener( "change", function ( e ) {
        selectedPlayout = e.target.value;
        updateShortcode();
    } );

    searchVideos( "" );

    initialized = true;
}

function clearLibraryWrapper () {
    shortcodeWrapper.style.display = "none";
    selectedClipID = null;

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
            'query': query
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

function selectClip ( clipID, event ) {
    var selectedClip = event.target.tagName == "SPAN" ? event.target.parentElement : event.target;

    clearLibraryWrapper();
    libraryWrapper.append( selectedClip );
    selectedClipID = clipID;

    updateShortcode();
}

function updateShortcode () {
    if ( !selectedClipID ) { return; }
    var shortcode = '[bbmediaclip clipID="' + selectedClipID + '" playout="' + selectedPlayout + '"]';
    shortcodeContainer.value = shortcode;
    shortcodeWrapper.style.display = "block";
}