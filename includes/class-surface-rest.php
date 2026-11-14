<?php
defined( 'ABSPATH' ) || exit;

class Surface_Rest {

	const NS = 'surface/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function can() {
		return current_user_can( 'edit_posts' );
	}

	public static function can_admin() {
		return current_user_can( 'manage_options' );
	}

	public static function mode( WP_REST_Request $req ) {
		Surface_Demo::set( (bool) $req['demo'] );
		return array( 'demo' => Surface_Demo::on() );
	}

	public static function routes() {
		$perm = array( __CLASS__, 'can' );

		register_rest_route( self::NS, '/actions', array( 'methods' => 'GET', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'actions' ) ) );
		register_rest_route( self::NS, '/do', array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'do_it' ) ) );
		register_rest_route( self::NS, '/submit', array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'submit' ) ) );
		register_rest_route( self::NS, '/draft', array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'draft' ) ) );
		register_rest_route( self::NS, '/mode', array( 'methods' => 'POST', 'permission_callback' => array( __CLASS__, 'can_admin' ), 'callback' => array( __CLASS__, 'mode' ) ) );
		register_rest_route( self::NS, '/undo', array( 'methods' => 'POST', 'permission_callback' => $perm, 'callback' => array( __CLASS__, 'undo' ) ) );
	}

	public static function actions() {
		return array( 'actions' => Surface_Demo::on() ? Surface_Demo::actions() : Surface_Actions::collect() );
	}

	public static function draft( WP_REST_Request $req ) {
		$id = sanitize_text_field( (string) $req['id'] );
		return rest_ensure_response( Surface_Demo::on() ? Surface_Demo::suggest( $id ) : Surface_Actions::suggest( $id ) );
	}

	public static function do_it( WP_REST_Request $req ) {
		if ( Surface_Demo::on() ) {
			return rest_ensure_response( Surface_Demo::run( sanitize_text_field( (string) $req['id'] ) ) );
		}
		return rest_ensure_response( Surface_Actions::run( sanitize_text_field( (string) $req['id'] ) ) );
	}

	/**
	 * The "do it myself" composers post here.
	 */
	public static function submit( WP_REST_Request $req ) {
		$id   = sanitize_text_field( (string) $req['id'] );
		$data = (array) $req['data'];

		if ( Surface_Demo::on() ) {
			return rest_ensure_response( Surface_Demo::submit( $id, $data ) );
		}

		if ( 0 === strpos( $id, 'comment-' ) ) {
			$c = get_comment( (int) substr( $id, 8 ) );
			if ( ! $c ) {
				return new WP_Error( 'surface_nope', 'Comment not found.', array( 'status' => 404 ) );
			}
			return rest_ensure_response( Surface_Actions::send_reply( $c, (string) ( $data['content'] ?? '' ) ) );
		}

		if ( 'plugins' === $id ) {
			return rest_ensure_response( Surface_Actions::update_plugins( array( (string) ( $data['plugin'] ?? '' ) ) ) );
		}

		if ( 'core' === $id ) {
			return rest_ensure_response( Surface_Actions::update_core() );
		}

		if ( 0 === strpos( $id, 'draft-' ) ) {
			return rest_ensure_response( Surface_Actions::save_draft( (int) substr( $id, 6 ), (string) ( $data['title'] ?? '' ), (string) ( $data['content'] ?? '' ), ! empty( $data['publish'] ) ) );
		}

		if ( 'write' === $id ) {
			$title = trim( (string) ( $data['title'] ?? '' ) );
			$body  = trim( (string) ( $data['content'] ?? '' ) );
			if ( '' === $title && '' === $body ) {
				return new WP_Error( 'surface_empty', 'Write something first.', array( 'status' => 400 ) );
			}
			return rest_ensure_response( Surface_Actions::create_post( $title ?: 'Untitled', $body, ! empty( $data['publish'] ) ? 'publish' : 'draft' ) );
		}

		if ( 'connect' === $id ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return new WP_Error( 'surface_nope', 'Not allowed.', array( 'status' => 403 ) );
			}
			$key = trim( (string) ( $data['key'] ?? '' ) );
			if ( 0 !== strpos( $key, 'sk-ant-' ) ) {
				return new WP_Error( 'surface_key', 'That does not look like an Anthropic key.', array( 'status' => 400 ) );
			}
			update_option( Surface_AI::OPTION, $key, false );
			return rest_ensure_response( array( 'message' => 'Connected. Drafts will be written by Claude from now on.' ) );
		}

		return new WP_Error( 'surface_unknown', 'Unknown action.', array( 'status' => 400 ) );
	}

	public static function undo( WP_REST_Request $req ) {
		if ( Surface_Demo::on() ) {
			return rest_ensure_response( Surface_Demo::undo() );
		}
		return rest_ensure_response( Surface_Actions::undo( (array) $req['undo'] ) );
	}
}
