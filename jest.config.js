const defaultConfig = require( '@wordpress/scripts/config/jest-unit.config' );

module.exports = {
	...defaultConfig,
	testMatch: [
		'**/tests/js/**/*.spec.[jt]s?(x)',
	],
	roots: [ '<rootDir>' ],
	moduleNameMapper: {
		...defaultConfig.moduleNameMapper,
		'^@/(.*)$': '<rootDir>/app/$1',
	},
	transform: {
		'^.+\\.(ts|tsx)$': [
			'@wordpress/scripts/config/babel-transform',
			{
				presets: [
					'@wordpress/babel-preset-default',
					[
						'@babel/preset-typescript',
						{
							isTSX: true,
							allExtensions: true,
						},
					],
				],
			},
		],
		'^.+\\.(js|jsx)$': '@wordpress/scripts/config/babel-transform',
	},
	moduleFileExtensions: [ 'js', 'jsx', 'ts', 'tsx', 'json' ],
	setupFilesAfterEnv: [ '<rootDir>/tests/js/setup.js' ],
};
