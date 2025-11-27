const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const path = require('path');

module.exports = {
    ...defaultConfig,
    plugins: [
        ...defaultConfig.plugins,
        new CopyWebpackPlugin({
            patterns: [
                {
                    from: path.resolve(__dirname, 'src/style.css'),
                    to: path.resolve(__dirname, 'build/style.css'),
                },
                {
                    from: path.resolve(__dirname, 'src/index.css'),
                    to: path.resolve(__dirname, 'build/index.css'),
                },
            ],
        }),
    ],
}; 