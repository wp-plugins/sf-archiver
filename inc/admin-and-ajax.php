<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}


/*-----------------------------------------------------------------------------------*/
/* !I18N =========================================================================== */
/*-----------------------------------------------------------------------------------*/

add_action( 'init', 'sfar_i18n' );

function sfar_i18n() {
	load_plugin_textdomain( 'sf-archiver', false, basename( dirname( SFAR_FILE ) ) . '/languages/' );
}


/*-----------------------------------------------------------------------------------*/
/* !MENUS ITEMS ==================================================================== */
/*-----------------------------------------------------------------------------------*/

// !Modify the "type_label" + add the "Original" link url

add_filter( 'wp_setup_nav_menu_item', 'sfar_nav_menu_type_label' );

function sfar_nav_menu_type_label( $menu_item ) {

	if ( isset( $menu_item->object, $menu_item->type ) ) {
		// Back compat'
		if ( $menu_item->object === 'cpt-archive' ) {
			$menu_item->object = $menu_item->type;
			$menu_item->type   = 'cpt-archive';
		}

		if ( $menu_item->type === 'cpt-archive' ) {
			$menu_item->type_label = __( 'Archive' );
			$menu_item->url        = post_type_exists( $menu_item->object ) ? get_post_type_archive_link( $menu_item->object ) : home_url();
		}
	}

	return $menu_item;
}


// !Add the "original title" into the "Original" link

add_filter( 'wp_edit_nav_menu_walker', 'sfar_edit_nav_menu_walker', 10, 2 );

function sfar_edit_nav_menu_walker( $class, $menu_id ) {

	if ( ! class_exists( 'SFAR_Walker_Nav_Menu_Edit' ) ) :
	class SFAR_Walker_Nav_Menu_Edit extends Walker_Nav_Menu_Edit {

		public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
			if ( isset( $item->object, $item->type ) && $item->type === 'cpt-archive' ) {

				$out = '';
				parent::start_el( $out, $item, $depth, $args, $id );

				$original_title = post_type_exists( $item->object ) ? get_post_type_object( $item->object )->labels->name : __( 'Unknown post type.' );
				$empty_link     = sprintf( __( 'Original: %s' ), '<a href="' . esc_attr( $item->url ) . '"></a>' );
				$new_link       = sprintf( __( 'Original: %s' ), '<a href="' . esc_url( $item->url ) . '">' . esc_html( $original_title ) . '</a>' );
				$output        .= str_replace( $empty_link, $new_link, $out );

			}
			else {
				parent::start_el( $output, $item, $depth, $args, $id );
			}
		}

	}
	endif;

	return 'SFAR_Walker_Nav_Menu_Edit';
}

/**/