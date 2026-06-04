<?php
/**
 * Plugin Name:       Monopage
 * Plugin URI:        https://github.com/RegionallyFamous/monopage
 * Description:       AI-ready one-page WordPress: the Site Editor, blocks, real hosting, and no admin sprawl.
 * Version:           0.2.51
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Regionally Famous
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       monopage
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

define( 'MONOPAGE_VERSION', '0.2.51' );
define( 'MONOPAGE_FILE', __FILE__ );
define( 'MONOPAGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MONOPAGE_URL', plugin_dir_url( __FILE__ ) );
define( 'MONOPAGE_FOCUS_OPTION', 'monopage_focus_enabled' );
define( 'MONOPAGE_VERSION_OPTION', 'monopage_version' );
define( 'MONOPAGE_CANVAS_THEME', 'monopage-canvas' );

require_once MONOPAGE_DIR . 'includes/core.php';
require_once MONOPAGE_DIR . 'includes/setup.php';
require_once MONOPAGE_DIR . 'includes/focus-mode.php';
require_once MONOPAGE_DIR . 'includes/validation.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once MONOPAGE_DIR . 'includes/wp-cli.php';
}

register_activation_hook( MONOPAGE_FILE, 'monopage_activate' );
monopage_register_hooks();
