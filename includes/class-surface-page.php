<?php
defined( 'ABSPATH' ) || exit;

/**
 * The only screen. No admin menu, no admin bar, no sidebar.
 */
class Surface_Page {

	public static function init() {
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ), 1 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'bar_link' ), 5 );
		add_action( 'admin_menu', array( __CLASS__, 'register' ) );
	}

	/**
	 * A hidden page, so WordPress grants access. It never shows in the menu.
	 */
	public static function register() {
		$hook = add_submenu_page( '', 'Surface', 'Surface', 'edit_posts', 'surface', '__return_null' );
		if ( $hook ) {
			add_action( 'load-' . $hook, array( __CLASS__, 'render_and_exit' ) );
		}
	}

	public static function render_and_exit() {
		self::render();
		exit;
	}

	public static function url() {
		return admin_url( 'admin.php?page=surface' );
	}

	public static function login_redirect( $to, $requested, $user ) {
		// Only take over a plain login. If someone asked for a specific screen, honor it.
		$plain = empty( $requested ) || (bool) preg_match( '#/wp-admin/?(index\.php)?$#', strtok( $requested, '?' ) );
		if ( $plain && $user instanceof WP_User && $user->has_cap( 'edit_posts' ) ) {
			return self::url();
		}
		return $to;
	}

	public static function admin_init() {
		global $pagenow;

		if ( wp_doing_ajax() || wp_doing_cron() || ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// The dashboard becomes Surface. ?classic=1 gets you the old one.
		if ( 'index.php' === $pagenow && ! isset( $_GET['classic'] ) ) {
			wp_safe_redirect( self::url() );
			exit;
		}
	}

	public static function bar_link( $bar ) {
		$bar->add_node(
			array(
				'id'    => 'surface',
				'title' => 'Surface',
				'href'  => self::url(),
			)
		);
	}

	private static function greeting() {
		$h    = (int) current_time( 'G' );
		$part = $h < 12 ? 'morning' : ( $h < 18 ? 'afternoon' : 'evening' );
		$name = strtok( wp_get_current_user()->display_name, ' ' );
		return sprintf( 'Good %s, %s.', $part, $name );
	}

	private static function render() {
		$config = array(
			'root'    => esc_url_raw( rest_url( Surface_Rest::NS ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'hello'   => self::greeting(),
			'site'    => get_bloginfo( 'name' ),
			'classic' => admin_url( 'edit.php?classic=1' ),
			'logout'  => wp_logout_url( home_url( '/' ) ),
			'ai'      => Surface_AI::enabled(),
			'demo'    => Surface_Demo::on(),
			'canMode' => current_user_can( 'manage_options' ),
		);

		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		?>
<!doctype html>
<html lang="<?php echo esc_attr( get_bloginfo( 'language' ) ); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex">
	<title>Surface &middot; <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	<link rel="stylesheet" href="<?php echo esc_url( SURFACE_URL . 'assets/surface.css?v=' . SURFACE_VERSION ); ?>">
</head>
<body>
	<main id="surface" aria-live="polite"></main>
	<script>window.SURFACE = <?php echo wp_json_encode( $config ); ?>;</script>
	<script src="<?php echo esc_url( SURFACE_URL . 'assets/surface.js?v=' . SURFACE_VERSION ); ?>"></script>
</body>
</html>
		<?php
	}
}
