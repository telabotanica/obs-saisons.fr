/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
// require('../css/app.css');
require('../css/lib/reset.css');
require('../css/lib/tools.css');
require('../css/font-faces.css');
require('../css/ods-common.css');
require('../css/layout/form.css');
require('../css/layout/overlay.css');
require('../css/layout/saisie-obs.css');
require('../css/layout/menu.css');
require('../css/layout/topbar.css');
require('../css/layout/footer.css');
require('../css/pages/accueil.css');
require('../css/pages/stations.css');
require('../css/pages/news.css');
require('../js/styles.js');

// Need jQuery? Install it with "yarn add jquery", then uncomment to require it.
const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;

// console.log('Hello Webpack Encore! Edit me in assets/js/app.js');
