import { render, useState, useEffect } from '@wordpress/element';
import { TabPanel, SelectControl, ToggleControl, Button, Notice, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const TABS = [
    { name: 'layout',      title: 'Layout' },
    { name: 'features',    title: 'Features' },
    { name: 'performance', title: 'Performance' },
];

const LAYOUT_OPTIONS = [
    { label: 'Full Width',     value: 'full-width' },
    { label: 'Boxed',          value: 'boxed' },
    { label: 'Sidebar Left',   value: 'sidebar-left' },
    { label: 'Sidebar Right',  value: 'sidebar-right' },
];

const FEATURE_LABELS = {
    sticky_header:  'Sticky Header',
    breadcrumbs:    'Breadcrumbs',
    scroll_to_top:  'Scroll to Top Button',
};

const PERFORMANCE_LABELS = {
    disable_google_fonts:  'Disable Google Fonts',
    disable_emoji:         'Disable Emoji Scripts',
    remove_jquery_migrate: 'Remove jQuery Migrate',
};

function DynamoOptions() {
    const [ options, setOptions ]   = useState( null );
    const [ saving, setSaving ]     = useState( false );
    const [ notice, setNotice ]     = useState( null );

    useEffect( () => {
        apiFetch( { path: '/wp/v2/settings' } ).then( ( settings ) => {
            const saved = settings.dynamo_options || {};
            setOptions( {
                layout_mode: saved.layout_mode || 'full-width',
                features: {
                    sticky_header: saved.features?.sticky_header ?? true,
                    breadcrumbs:   saved.features?.breadcrumbs   ?? true,
                    scroll_to_top: saved.features?.scroll_to_top ?? true,
                },
                performance: {
                    disable_google_fonts:  saved.performance?.disable_google_fonts  ?? false,
                    disable_emoji:         saved.performance?.disable_emoji          ?? false,
                    remove_jquery_migrate: saved.performance?.remove_jquery_migrate  ?? false,
                },
            } );
        } );
    }, [] );

    function save() {
        setSaving( true );
        setNotice( null );
        apiFetch( {
            path:   '/wp/v2/settings',
            method: 'POST',
            data:   { dynamo_options: options },
        } )
            .then( () => {
                setNotice( { type: 'success', message: 'Settings saved.' } );
            } )
            .catch( () => {
                setNotice( { type: 'error', message: 'Could not save settings.' } );
            } )
            .finally( () => setSaving( false ) );
    }

    if ( ! options ) {
        return <Spinner />;
    }

    function setLayoutMode( value ) {
        setOptions( { ...options, layout_mode: value } );
    }

    function setFeature( key, value ) {
        setOptions( {
            ...options,
            features: { ...options.features, [ key ]: value },
        } );
    }

    function setPerformance( key, value ) {
        setOptions( {
            ...options,
            performance: { ...options.performance, [ key ]: value },
        } );
    }

    return (
        <div style={ { maxWidth: 680, margin: '2rem auto' } }>
            { notice && (
                <Notice
                    status={ notice.type }
                    isDismissible
                    onRemove={ () => setNotice( null ) }
                >
                    { notice.message }
                </Notice>
            ) }
            <TabPanel tabs={ TABS }>
                { ( tab ) => {
                    if ( tab.name === 'layout' ) {
                        return (
                            <SelectControl
                                label="Layout Mode"
                                value={ options.layout_mode }
                                options={ LAYOUT_OPTIONS }
                                onChange={ setLayoutMode }
                            />
                        );
                    }
                    if ( tab.name === 'features' ) {
                        return (
                            <>
                                { Object.entries( FEATURE_LABELS ).map( ( [ key, label ] ) => (
                                    <ToggleControl
                                        key={ key }
                                        label={ label }
                                        checked={ options.features[ key ] }
                                        onChange={ ( val ) => setFeature( key, val ) }
                                    />
                                ) ) }
                            </>
                        );
                    }
                    if ( tab.name === 'performance' ) {
                        return (
                            <>
                                { Object.entries( PERFORMANCE_LABELS ).map( ( [ key, label ] ) => (
                                    <ToggleControl
                                        key={ key }
                                        label={ label }
                                        checked={ options.performance[ key ] }
                                        onChange={ ( val ) => setPerformance( key, val ) }
                                    />
                                ) ) }
                            </>
                        );
                    }
                    return null;
                } }
            </TabPanel>
            <Button
                variant="primary"
                onClick={ save }
                disabled={ saving }
                style={ { marginTop: '1rem' } }
            >
                { saving ? 'Saving\u2026' : 'Save Settings' }
            </Button>
        </div>
    );
}

const root = document.getElementById( 'dynamo-options-root' );
if ( root ) {
    render( <DynamoOptions />, root );
}
