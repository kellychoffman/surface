<?php
defined( 'ABSPATH' ) || exit;

/**
 * Demo mode: fake tasks and fake "AI" so the prototype works with no data,
 * no API key and no side effects. Nothing here touches the database except
 * the mode toggle itself.
 */
class Surface_Demo {

	const OPTION = 'surface_demo';

	/** On by default. Turn off from the footer to use real site data. */
	public static function on() {
		if ( defined( 'SURFACE_DEMO' ) ) {
			return (bool) SURFACE_DEMO;
		}
		return 'off' !== get_option( self::OPTION, 'on' );
	}

	public static function set( $on ) {
		update_option( self::OPTION, $on ? 'on' : 'off', false );
	}

	private static function classic() {
		return admin_url( 'edit.php?classic=1' );
	}

	public static function actions() {
		return array(
			array(
				'id'     => 'core',
				'kind'   => 'core',
				'title'  => 'WordPress 6.9.1 is available',
				'detail' => 'Core updates are worth watching, so this one is yours.',
				'weight' => 95,
				'meta'   => array( 'version' => '6.9.1' ),
				'self'   => array( 'type' => 'compose', 'label' => 'Review' ),
				'auto'   => null,
			),
			array(
				'id'     => 'plugins',
				'kind'   => 'plugins',
				'title'  => '3 plugins need updating',
				'detail' => 'Jetpack 14.2, Yoast SEO 25.1, WooCommerce 9.5.1',
				'weight' => 90,
				'meta'   => array(
					'plugins' => array(
						array( 'file' => 'jetpack/jetpack.php', 'name' => 'Jetpack', 'from' => '14.1', 'to' => '14.2' ),
						array( 'file' => 'wordpress-seo/wp-seo.php', 'name' => 'Yoast SEO', 'from' => '25.0', 'to' => '25.1' ),
						array( 'file' => 'woocommerce/woocommerce.php', 'name' => 'WooCommerce', 'from' => '9.5.0', 'to' => '9.5.1' ),
					),
				),
				'self'   => array( 'type' => 'compose', 'label' => 'Review them' ),
				'auto'   => array( 'label' => 'Update them all', 'bulk' => true ),
			),
			array(
				'id'     => 'comment-1',
				'kind'   => 'comment',
				'title'  => 'Maya Lin commented on Notes from Tiger Mountain',
				'detail' => 'Loved this. Do you have a tip for getting started with enduro racing?',
				'weight' => 80,
				'self'   => array( 'type' => 'compose', 'label' => 'Reply myself' ),
				'auto'   => array( 'label' => 'Draft and send a reply', 'bulk' => true ),
			),
			array(
				'id'     => 'comment-2',
				'kind'   => 'comment',
				'title'  => 'Dev Patel commented on Why I stopped using a bike computer',
				'detail' => 'Is this compatible with a phone mount? Mine rattles on rough descents.',
				'weight' => 79,
				'self'   => array( 'type' => 'compose', 'label' => 'Reply myself' ),
				'auto'   => array( 'label' => 'Draft and send a reply', 'bulk' => true ),
			),
			array(
				'id'     => 'draft-1',
				'kind'   => 'draft',
				'title'  => 'Finish "Notes on trail building"',
				'detail' => 'Sitting in drafts for 12 days.',
				'weight' => 50,
				'meta'   => array(
					'title'   => 'Notes on trail building',
					'content' => "Most of what I know about building trail I learned by doing it badly first.\n\nThe first rule is water. If you do not move it off the trail, it will move the trail for you.\n\nThe second is patience. A good line takes a season to settle.",
				),
				'self'   => array( 'type' => 'compose', 'label' => 'Edit it' ),
				'auto'   => array( 'label' => 'Publish it as is', 'bulk' => false ),
			),
			array(
				'id'     => 'write',
				'kind'   => 'write',
				'title'  => 'Write a post',
				'detail' => 'Your last post went out 9 days ago.',
				'weight' => 40,
				'self'   => array( 'type' => 'compose', 'label' => 'Write it myself' ),
				'auto'   => array( 'label' => 'Draft one for me', 'bulk' => false ),
			),
		);
	}

	/** "Do it for me", faked. */
	public static function run( $id ) {
		usleep( 1100000 ); // Feels like work.

		if ( 'plugins' === $id ) {
			return array( 'message' => 'Updated 3 of 3.', 'undo' => array( 'type' => 'demo' ) );
		}
		if ( 'comment-1' === $id ) {
			return array(
				'message' => 'Replied and approved: "Thanks, Maya. Start with one local race and treat it as practice. The first one is mostly about learning how you handle nerves." ',
				'undo'    => array( 'type' => 'demo' ),
			);
		}
		if ( 'comment-2' === $id ) {
			return array(
				'message' => 'Replied and approved: "Good question. A quad-lock style mount with a rubber dampener stopped mine rattling. Worth the ten dollars." ',
				'undo'    => array( 'type' => 'demo' ),
			);
		}
		if ( 'draft-1' === $id ) {
			return array( 'message' => 'Published "Notes on trail building".', 'undo' => array( 'type' => 'demo' ) );
		}
		if ( 'write' === $id ) {
			return array(
				'message' => 'Saved a draft of "What a season of enduro racing taught me about slowing down".',
				'undo'    => array( 'type' => 'demo' ),
			);
		}
		return new WP_Error( 'surface_unknown', 'That one is yours to do.', array( 'status' => 400 ) );
	}

	/** "Suggest a draft" inside a composer, faked. */
	public static function suggest( $id ) {
		usleep( 900000 );
		if ( 'comment-1' === $id ) {
			return array( 'content' => 'Thanks, Maya. Start with one local race and treat it as practice. The first one is mostly about learning how you handle nerves.' );
		}
		if ( 'comment-2' === $id ) {
			return array( 'content' => 'Good question. A quad-lock style mount with a rubber dampener stopped mine rattling. Worth the ten dollars.' );
		}
		if ( 'write' === $id ) {
			return array(
				'title'   => 'What a season of enduro racing taught me about slowing down',
				'content' => "I spent most of this year trying to go faster. The surprise was that the biggest gains came from doing less.\n\nOn the stages where I rode relaxed, looking two corners ahead instead of two feet, I was quicker than the ones where I charged. Smooth is not slow. It just feels slow until you check the clock.\n\nThe same turned out to be true off the bike. The weeks I protected rest were the weeks I rode best. Next season I am planning the recovery before I plan the races.",
			);
		}
		return new WP_Error( 'surface_unknown', 'Nothing to suggest.', array( 'status' => 400 ) );
	}

	/** "Do it myself" composers, faked. */
	public static function submit( $id, $data ) {
		usleep( 500000 );

		if ( 'plugins' === $id ) {
			return array( 'message' => 'Updated.' );
		}
		if ( 'core' === $id ) {
			return array( 'message' => 'WordPress updated to 6.9.1.' );
		}
		if ( 0 === strpos( $id, 'draft-' ) ) {
			$publish = ! empty( $data['publish'] );
			return array(
				'message' => ( $publish ? 'Published' : 'Saved' ) . ' "' . ( trim( (string) ( $data['title'] ?? '' ) ) ?: 'Untitled' ) . '".',
				'undo'    => $publish ? array( 'type' => 'demo' ) : null,
			);
		}

		if ( 0 === strpos( $id, 'comment-' ) ) {
			$text = trim( (string) ( $data['content'] ?? '' ) );
			if ( '' === $text ) {
				return new WP_Error( 'surface_empty', 'Write something first.', array( 'status' => 400 ) );
			}
			return array( 'message' => 'Replied and approved: "' . wp_trim_words( $text, 14 ) . '"', 'undo' => array( 'type' => 'demo' ) );
		}
		if ( 'write' === $id ) {
			$title = trim( (string) ( $data['title'] ?? '' ) );
			if ( '' === $title && '' === trim( (string) ( $data['content'] ?? '' ) ) ) {
				return new WP_Error( 'surface_empty', 'Write something first.', array( 'status' => 400 ) );
			}
			$verb = ! empty( $data['publish'] ) ? 'Published' : 'Saved a draft of';
			return array( 'message' => $verb . ' "' . ( $title ?: 'Untitled' ) . '".', 'undo' => array( 'type' => 'demo' ) );
		}
		return new WP_Error( 'surface_unknown', 'Unknown action.', array( 'status' => 400 ) );
	}

	public static function undo() {
		return array( 'message' => 'Undone.' );
	}
}
