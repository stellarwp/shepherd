/**
 * The default configuration coming from the @wordpress/scripts package.
 * Customized following the "Advanced Usage" section of the documentation:
 * See: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#advanced-usage
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

const entryPoints = {
	main: __dirname + '/app/index.tsx',
};

module.exports                       = {
	...defaultConfig,
	...{
		entry: (buildType) => {
			const defaultEntryPoints = defaultConfig.entry( buildType );
			return {
				...defaultEntryPoints, ...entryPoints,
			};
		},
	},
};
