const { merge } = require('webpack-merge');
const common = require('./webpack.common.js');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const TerserPlugin = require('terser-webpack-plugin');

const terserPlugin = new TerserPlugin({
    parallel: true,
    sourceMap: false,
    cache: true,
    extractComments: false,
    terserOptions: {
        output: {
            comments: /translators:/i,
        },
        mangle: {
            reserved: ["__"],
        }
    },
});

module.exports = merge(common, {
    mode: 'production',
    devtool: '',
    plugins: [
        new VueLoaderPlugin(),
    ],
    optimization: {
        minimize: true,
        minimizer: [terserPlugin]
    },
    resolve: {
        alias: {
            'vue$': 'vue/dist/vue.min',
            'Swiper$': 'swiper/js/swiper.min.js'
        }
    }
});
