const config = require('forumkit-webpack');
const { merge } = require('webpack-merge');

module.exports = merge(config(), {
  output: {
    library: 'forumkit.core',
  },
});
