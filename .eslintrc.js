const defaultConfig = require( '@wordpress/scripts/config/.eslintrc.js' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	rules: {
		...defaultConfig.rules,
		'import/no-extraneous-dependencies': [
		'error',
			{
				devDependencies: true,
				peerDependencies: true,
				optionalDependencies: false,
				packageDir: [
					__dirname,
					path.dirname( require.resolve( '@wordpress/scripts/package.json' ) ),
					path.dirname( require.resolve( '@wordpress/components/package.json' ) ),
				],
			},
		],
		'no-console': 'warn',
	},
};
