const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');

// Define JavaScript entry points

module.exports = {
	...defaultConfig,
	entry: {
		admin: path.resolve(process.cwd(), 'packages/admin', 'index.js'),
	},
};
