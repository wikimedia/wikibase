const filePrefix = 'data-bridge.';

module.exports = {
	configureWebpack: () => ( {
		output: {
			filename: `${filePrefix}[name].js`,
		},
	} ),
	chainWebpack: ( config ) => {
		config.optimization.delete( 'splitChunks' );

		if ( process.env.NODE_ENV === 'production' ) {
			config.plugin( 'extract-css' )
				.tap( ( [ options, ...args ] ) => [
					Object.assign( {}, options, { filename: `${filePrefix}[name].css` } ),
					...args,
				] );
		}
	},
};