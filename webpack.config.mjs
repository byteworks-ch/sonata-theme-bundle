import Encore from '@symfony/webpack-encore';
import sidebarHold from './assets/postcss/sidebar-hold.mjs';

// Built assets are committed (public/), so `composer require` works without Node. Fixed file names (no
// versioning): Sonata's asset package adds a version from the file's modification time.
Encore.setOutputPath('./public')
    .setPublicPath('.')
    .setManifestKeyPrefix('bundles/byteworkssonatatheme')

    .cleanupOutputBeforeBuild()
    .enableVersioning(false)
    .enableSourceMaps(false)
    .disableSingleRuntimeChunk()
    .enableSassLoader((options) => {
        options.sassOptions = {
            quietDeps: true,
            silenceDeprecations: ['import', 'global-builtin', 'color-functions', 'if-function'],
        };
    })
    // rules for keeping the collapsed sidebar open across a page load, generated from its :hover rules
    .enablePostCssLoader((options) => {
        options.postcssOptions = { plugins: [sidebarHold()] };
    })
    .configureImageRule({ filename: 'images/[name][ext]' })
    .configureFontRule({ filename: 'fonts/[name][ext]' })

    .addEntry('app', './assets/js/app.js')
    // loaded blocking in <head>, before the first paint: color mode and the held sidebar
    .addEntry('head', './assets/js/head.js')
    // AdminLTE's color palettes, one of them loaded after app.css (option "palette")
    .addStyleEntry('colors', './assets/scss/colors.scss')
    .addStyleEntry('colors-v3', './assets/scss/colors-v3.scss');

export default await Encore.getWebpackConfig();
