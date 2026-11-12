<?php
defined( 'ABSPATH' ) || exit;

/**
 * Optional Claude drafting. Without a key, Surface falls back to plain templates.
 */
class Surface_AI {

	const OPTION = 'surface_anthropic_key';

	public static function key() {
		if ( defined( 'SURFACE_ANTHROPIC_KEY' ) && SURFACE_ANTHROPIC_KEY ) {
			return SURFACE_ANTHROPIC_KEY;
		}
		return (string) get_option( self::OPTION, '' );
	}

	public static function enabled() {
		return '' !== self::key();
	}

	/**
	 * Returns text, or null when no key is set / the call fails.
	 */
	public static function complete( $system, $prompt, $max_tokens = 900 ) {
		$key = self::key();
		if ( ! $key ) {
			return null;
		}

		$res = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			array(
				'timeout' => 45,
				'headers' => array(
					'x-api-key'         => $key,
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model'      => apply_filters( 'surface_model', 'claude-haiku-5-5' ),
						'max_tokens' => $max_tokens,
						'system'     => $system,
						'messages'   => array( array( 'role' => 'user', 'content' => $prompt ) ),
					)
				),
			)
		);

		if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		$text = $data['content'][0]['text'] ?? '';
		return '' !== trim( $text ) ? trim( $text ) : null;
	}
}
