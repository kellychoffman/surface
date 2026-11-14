<?php
/**
 * Plugin Name:       Surface
 * Plugin URI:        https://github.com/kellychoffman/surface
 * Description:       Replaces the WordPress admin with a single quiet screen of things to do. Reply to a comment, update plugins, write a post: do it yourself, or let Surface do it for you.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            kellychoffman
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       surface
 *
 * @package Surface
 */

defined( 'ABSPATH' ) || exit;

define( 'SURFACE_VERSION', '0.1.0' );
define( 'SURFACE_FILE', __FILE__ );
define( 'SURFACE_DIR', plugin_dir_path( __FILE__ ) );
define( 'SURFACE_URL', plugin_dir_url( __FILE__ ) );

require_once SURFACE_DIR . 'includes/class-surface-ai.php';
require_once SURFACE_DIR . 'includes/class-surface-demo.php';
require_once SURFACE_DIR . 'includes/class-surface-actions.php';
require_once SURFACE_DIR . 'includes/class-surface-rest.php';
require_once SURFACE_DIR . 'includes/class-surface-page.php';

Surface_Page::init();
Surface_Rest::init();
