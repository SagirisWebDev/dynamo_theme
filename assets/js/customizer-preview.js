( function () {
    var colourTokens = [
        'colors_primary',
        'colors_secondary',
        'colors_accent',
        'colors_background',
        'colors_text',
        'colors_link',
        'colors_section_alt',
    ];

    colourTokens.forEach( function ( token ) {
        var prop = '--dynamo-' + token.replace( /_/g, '-' );
        wp.customize( 'dynamo_' + token, function ( value ) {
            value.bind( function ( newval ) {
                document.documentElement.style.setProperty( prop, newval );
            } );
        } );
    } );

    var spacingTokens = [
        'spacing_header_padding_top',
        'spacing_header_padding_bottom',
        'spacing_footer_padding_top',
        'spacing_footer_padding_bottom',
        'spacing_content_padding_top',
        'spacing_content_padding_bottom',
        'spacing_content_padding_x',
    ];

    spacingTokens.forEach( function ( token ) {
        var prop = '--dynamo-' + token.replace( /_/g, '-' );
        wp.customize( 'dynamo_' + token, function ( value ) {
            value.bind( function ( newval ) {
                document.documentElement.style.setProperty( prop, newval );
            } );
        } );
    } );

    var layoutTokens = [
        'layout_container_max_width',
        'layout_content_width',
        'layout_sidebar_width',
    ];

    layoutTokens.forEach( function ( token ) {
        var prop = '--dynamo-' + token.replace( /_/g, '-' );
        wp.customize( 'dynamo_' + token, function ( value ) {
            value.bind( function ( newval ) {
                document.documentElement.style.setProperty( prop, newval );
            } );
        } );
    } );
} )();
