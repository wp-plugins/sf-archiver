<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}


/*-----------------------------------------------------------------------------------*/
/* !FRONTEND ======================================================================= */
/*-----------------------------------------------------------------------------------*/

// Alter the URL in menu items for cpt-archive objects.

add_filter( 'wp_get_nav_menu_items', 'sfar_cpt_archive_menu_filter', 10, 3 );

function sfar_cpt_archive_menu_filter( $items, $menu, $args ) {

	if ( ! empty( $items ) ) {
		foreach ( $items as &$item ) {
			// Back compat'
			if ( $item->object === 'cpt-archive' ) {
				$item->object = $item->type;
				$item->type   = 'cpt-archive';
			}

			if ( $item->type !== 'cpt-archive' ) {
				continue;
			}

			$item->url = post_type_exists( $item->object ) ? get_post_type_archive_link( $item->object ) : home_url();

			if ( is_post_type_archive( $item->object ) && ! is_search() ) {
				$item->classes[] = 'current-menu-item';
				$item->current   = true;
			}
		}
	}

	return $items;
}


// !Posts per page limit.

add_action( 'pre_get_posts', 'sfar_pre_get_posts' );

function sfar_pre_get_posts( $query ) {

	if ( ! $query->is_main_query() ) {
		return;
	}

	remove_action( 'pre_get_posts', 'sfar_pre_get_posts' );

	$post_type = array_filter( (array) $query->get( 'post_type' ) );

	if ( count( $post_type ) === 1 && $query->is_post_type_archive() && ! $query->get( 'posts_per_archive_page' ) ) {

		$post_type = reset( $post_type );
		$settings  = sfar_get_settings();

		if ( ! $settings || empty( $settings[ $post_type ]['posts_per_archive_page'] ) ) {
			return;
		}

		$query->set( 'posts_per_archive_page', $settings[ $post_type ]['posts_per_archive_page'] );

	}

}

/**/