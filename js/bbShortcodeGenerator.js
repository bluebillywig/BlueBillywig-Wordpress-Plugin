var $ = jQuery;
$( document ).ready( onload );

// Begin Script

var searchButton, searchInput, playoutSelection, searchResetButton;
var shortcodeContainer, shortcodeWrapper;
var initialized = false;

//selection
var selectedClipID;

function onload () {
    if ( initialized ) return;

    searchResetButton = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_SEARCH_RESET' ] );
    shortcodeContainer = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_SHORTCODE' ] );
    shortcodeWrapper = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_SHORTCODE_WRAPPER' ] );
    playoutSelection = document.getElementById( BB_STRINGS[ 'ELEMENT_ID_LIBRARY_SHORTCODE_PLAYOUT' ] );

    libraryWrapper.addEventListener( 'clipselected', onClipSelected );
    focusClipOnClick = true;

    searchResetButton.addEventListener( "click", function ( e ) {
        clearLibraryWrapper();
        selectPlayout( defaultPlayout );
        searchInput.value = "";
        searchVideos( "" );
    } );

    playoutSelection.addEventListener( "change", function ( e ) {
        selectedPlayout = e.target.value;
        updateShortcode();
    } );

    selectedPlayout = defaultPlayout;
    initialized = true;
}

function selectPlayout ( playout ) {
    selectedPlayout = playout;
    playoutSelection.value = selectedPlayout;
}

function onClipSelected ( event ) {
    selectedClipID = event.clipID;
    updateShortcode();
}

function updateShortcode () {
    if ( !selectedClipID ) { return; }
    var shortcode = '[bbmediaclip clipID="' + selectedClipID + '" playout="' + selectedPlayout + '"';
    if ( defaultAutoplay ) {
        shortcode += ' autoplay="true"'
    }
    shortcode += ']';
    shortcodeContainer.innerHTML = shortcode;
    shortcodeWrapper.style.display = "block";
}