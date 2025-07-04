import domReady from '@wordpress/dom-ready';
import { createRoot } from '@wordpress/element';
import { ShepherdTable } from './components/ShepherdTable';

import "./style.scss";

domReady( (): void => {
	const root = createRoot(
		document.getElementById( 'shepherd-app' )
	);
	root.render( <ShepherdTable /> );
} );
