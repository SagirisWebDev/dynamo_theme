( function () {
    if ( dynamoPreview && dynamoPreview.initialCss && ! document.getElementById( 'dynamo-dynamic-css' ) ) {
        const styleEl = document.createElement( 'style' );
        styleEl.id = 'dynamo-dynamic-css';
        styleEl.textContent = dynamoPreview.initialCss;
        document.head.appendChild( styleEl );
    }

    const fallbackTokens = [
        'colors_primary',
        'colors_secondary',
        'colors_accent',
        'colors_background',
        'colors_text',
        'colors_link',
        'colors_section_alt',
        'spacing_header_padding_top',
        'spacing_header_padding_bottom',
        'spacing_footer_padding_top',
        'spacing_footer_padding_bottom',
        'spacing_content_padding_top',
        'spacing_content_padding_bottom',
        'spacing_content_padding_x',
        'layout_container_max_width',
        'layout_content_width',
        'layout_sidebar_width',
    ];

    let tokens;
    if ( dynamoPreview && dynamoPreview.initialCss ) {
        const matches = dynamoPreview.initialCss.match( /--dynamo-([a-z0-9]+(?:-[a-z0-9]+)*)/g ) || [];
        tokens = matches
            .map( function ( m ) { return m.replace( '--dynamo-', '' ).replace( /-/g, '_' ); } )
            .filter( function ( t, i, arr ) { return arr.indexOf( t ) === i; } );
        if ( ! tokens.length ) {
            tokens = fallbackTokens;
        }
    } else {
        tokens = fallbackTokens;
    }

    tokens.forEach( function ( token ) {
        const prop = '--dynamo-' + token.replace( /_/g, '-' );
        wp.customize( 'dynamo_' + token, function ( value ) {
            value.bind( function ( newval ) {
                document.documentElement.style.setProperty( prop, newval );
            } );
        } );
    } );

    function hexToRgbTriplet( hex ) {
        if ( ! hex ) {
            return null;
        }
        hex = String( hex ).replace( '#', '' );
        if ( hex.length === 3 ) {
            hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
        }
        if ( ! /^[0-9a-fA-F]{6}$/.test( hex ) ) {
            return null;
        }
        return parseInt( hex.substr( 0, 2 ), 16 ) + ' '
            + parseInt( hex.substr( 2, 2 ), 16 ) + ' '
            + parseInt( hex.substr( 4, 2 ), 16 );
    }

    [ 'sm', 'md' ].forEach( function ( size ) {
        const lengthId  = 'dynamo_shadows_' + size + '_length';
        const colorId   = 'dynamo_shadows_' + size + '_color';
        const opacityId = 'dynamo_shadows_' + size + '_opacity';
        const prop      = '--dynamo-shadows-' + size;

        function recompose() {
            wp.customize( lengthId, function ( lengthSetting ) {
                wp.customize( colorId, function ( colorSetting ) {
                    wp.customize( opacityId, function ( opacitySetting ) {
                        const rgb = hexToRgbTriplet( colorSetting.get() );
                        if ( rgb === null ) {
                            return;
                        }
                        const colorFn = 'rgb(' + rgb + ' / ' + opacitySetting.get() + ')';
                        const layers  = String( lengthSetting.get() )
                            .split( ',' )
                            .map( function ( layer ) { return layer.trim() + ' ' + colorFn; } );
                        document.documentElement.style.setProperty( prop, layers.join( ', ' ) );
                    } );
                } );
            } );
        }

        [ lengthId, colorId, opacityId ].forEach( function ( id ) {
            wp.customize( id, function ( setting ) {
                setting.bind( function () { recompose(); } );
            } );
        } );
    } );

    wp.customize( 'dynamo_header_menu_cart', function ( value ) {
        value.bind( function ( newval ) {
            const wrapper = document.querySelector( '.dynamo-header-menu-cart' );
            if ( ! wrapper ) {
                return;
            }
            wrapper.className = wrapper.className
                .replace( /\bdynamo-header-menu-cart--[a-z-]+\b/g, '' )
                .replace( /\s+/g, ' ' )
                .trim();
            wrapper.classList.add( 'dynamo-header-menu-cart--' + newval );
        } );
    } );
} )();
