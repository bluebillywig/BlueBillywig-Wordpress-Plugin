$ = jQuery;

// Store value currently stored in database
$( 'select,input' ).each( function () {
    var originalValue = $( this ).val();
    $( this ).attr( 'stored-value', originalValue );
} );

// Mark as an unsaved setting
$( 'select,input' ).on( 'change', function ( ev ) {

    var storedValue = $( this ).attr( 'stored-value' );
    var val = $( this ).val();

    var isUnsaved = val !== storedValue;

    $( this ).attr( 'unsaved-setting', isUnsaved );
} );