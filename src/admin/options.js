import { render, useState, useEffect } from '@wordpress/element';
import { TabPanel, SelectControl, ToggleControl, Button, Notice, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import {
    LAYOUT_OPTIONS,
    FEATURE_LABELS,
    PERFORMANCE_LABELS,
    buildOptions,
    setLayoutMode,
    setFeature,
    setPerformance,
} from './options-helpers';

const TABS = [
    { name: 'layout',      title: 'Layout' },
    { name: 'features',    title: 'Features' },
    { name: 'performance', title: 'Performance' },
];

function DynamoOptions() {
    const [ options, setOptions ] = useState( null );
    const [ saving, setSaving ]   = useState( false );
    const [ notice, setNotice ]   = useState( null );

    useEffect( () => {
        apiFetch( { path: '/wp/v2/settings' } ).then( ( settings ) => {
            setOptions( buildOptions( settings.dynamo_options || {} ) );
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
                { ( tab ) => (
                    <div style={ { marginTop: '2rem' } }>
                        { tab.name === 'layout' && (
                            <SelectControl
                                label="Layout Mode"
                                value={ options.layout_mode }
                                options={ LAYOUT_OPTIONS }
                                onChange={ ( value ) => setOptions( setLayoutMode( options, value ) ) }
                            />
                        ) }
                        { tab.name === 'features' && Object.entries( FEATURE_LABELS ).map( ( [ key, label ] ) => (
                            <ToggleControl
                                key={ key }
                                label={ label }
                                checked={ options.features[ key ] }
                                onChange={ ( val ) => setOptions( setFeature( options, key, val ) ) }
                            />
                        ) ) }
                        { tab.name === 'performance' && Object.entries( PERFORMANCE_LABELS ).map( ( [ key, label ] ) => (
                            <ToggleControl
                                key={ key }
                                label={ label }
                                checked={ options.performance[ key ] }
                                onChange={ ( val ) => setOptions( setPerformance( options, key, val ) ) }
                            />
                        ) ) }
                    </div>
                ) }
            </TabPanel>
            <Button
                variant="primary"
                onClick={ save }
                disabled={ saving }
                style={ { marginTop: '1rem' } }
            >
                { saving ? 'Saving…' : 'Save Settings' }
            </Button>
        </div>
    );
}

const root = document.getElementById( 'dynamo-options-root' );
if ( root ) {
    render( <DynamoOptions />, root );
}
