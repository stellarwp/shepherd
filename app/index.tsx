import React from 'react';
import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { ShepherdTable } from './components/ShepherdTable';

import './style.scss';

const getContainer = (): HTMLElement => {
	const el = document.getElementById( 'shepherd-app' );
	if ( ! el ) {
		throw new Error( 'Container not found' );
	}
	return el;
};

domReady( (): void => {
	const root = createRoot( getContainer() );
	root.render( <ShepherdTable /> );
} );
