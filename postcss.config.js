module.exports = {
	plugins: {
		'postcss-import': {},
		'postcss-mixins': {},
		'postcss-nested': {},
		'postcss-preset-env': {
			stage: 3,
			features: {
				'custom-properties': false,
			},
		},
		'postcss-inline-svg': {
			paths: [ './app' ],
		},
	},
};