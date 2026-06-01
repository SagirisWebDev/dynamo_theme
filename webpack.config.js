const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

module.exports = [
    Object.assign({}, defaultConfig, {
        entry: { options: path.resolve(__dirname, 'src/admin/options.js') },
        output: Object.assign({}, defaultConfig.output, {
            path: path.resolve(__dirname, 'assets/js/options'),
        }),
    }),
    Object.assign({}, defaultConfig, {
        entry: { 'token-presets': path.resolve(__dirname, 'src/editor/token-presets.js') },
        output: Object.assign({}, defaultConfig.output, {
            path: path.resolve(__dirname, 'assets/js/editor'),
        }),
    }),
];
