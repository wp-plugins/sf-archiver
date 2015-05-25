<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Cheatin\' uh?' );
}


/*-----------------------------------------------------------------------------------*/
/* !BACK COMPAT' =================================================================== */
/*-----------------------------------------------------------------------------------*/

// !Take the old option to build the new one. Then, delete the old option.

add_action( 'admin_init', 'sfar_migrate' );

function sfar_migrate() {
	$old = get_option( '_w3p_acpt', array() );

	if ( ! $old || ! is_array( $old ) ) {
		return;
	}

	$new = array();

	foreach( $old as $post_type => $atts ) {
		if ( ! empty( $atts['ppp'] ) && (int) $atts['ppp'] > 0 ) {
			$out[ $post_type ] = array(
				'posts_per_archive_page' => (int) $atts['ppp'],
			);
		}
	}

	if ( $new ) {
		add_option( 'sf_archiver', $new, true );
	}

	delete_option( '_w3p_acpt' );
}


/*-----------------------------------------------------------------------------------*/
/* !POSTS/CPTS LISTS =============================================================== */
/*-----------------------------------------------------------------------------------*/

// !On posts/CPTs list, display a link to frontend archive.
// http://www.screenfeed.fr/blog/afficher-dans-administration-lien-vers-archive-cpts-02542/

add_action( 'admin_footer-edit.php', 'sfar_print_post_type_archive_link_script' );

function sfar_print_post_type_archive_link_script() {
	global $typenow;

	if ( ! $typenow || ! ( $typenow === 'post' || array_key_exists( $typenow, sfar_get_post_types() ) ) ) {
		return;
	}

	$href = $typenow === 'post' ? get_page_for_posts( true ) : get_post_type_archive_link( $typenow );

	echo '<script>jQuery(document).ready( function( $ ) {
	$( ".add-new-h2" ).first().before( "<a class=\"post-type-archive-link dashicons dashicons-external\" href=\"' . esc_url( $href ) . '\" style=\"vertical-align: middle; margin-right: 8px;\"><span class=\"screen-reader-text\">' . __( 'Visit Site' ) . '</span></a>" );
} );</script>' . "\n";
}


/*-----------------------------------------------------------------------------------*/
/* !SETTINGS ======================================================================= */
/*-----------------------------------------------------------------------------------*/

// !In the plugins list, add a link to the settings page.

add_filter( 'plugin_action_links_' . plugin_basename( SFAR_FILE ), 'sfar_add_settings_action_link', 10, 2 );

function sfar_add_settings_action_link( $links, $file ) {
	$links['settings'] = '<a href="' . admin_url( 'options-reading.php' ) . '">' . __( 'Reading Settings' ) . '</a>';
	return $links;
}


// !Add settings section and fields.

add_action( 'load-options-reading.php', 'sfar_settings_fields' );

function sfar_settings_fields() {
	$post_types = sfar_get_post_types();

	if ( empty( $post_types ) ) {
		return;
	}

	add_settings_section( 'custom-post-types', __( 'Post types', 'sf-archiver' ), null, 'reading' );

	foreach ( $post_types as $post_type => $atts ) {
		$args = array(
			'post_type'  => $post_type,
			'label_for'  => $post_type . '|posts_per_archive_page',
			'setting'    => 'posts_per_archive_page',
			'after'      => $atts->label,
			'attributes' => array(
				'type'   => 'number',
				'class'  => 'small-text',
				'min'    => 1,
				'step'   => 1,
			),
		);
		add_settings_field( $post_type . '-posts_per_archive_page', sprintf( _x( '%s per page', 's: post type name (plural form)', 'sf-archiver' ), $atts->label ), 'sfar_field', 'reading', 'custom-post-types', $args );
	}
}


// !Input field html.

function sfar_field( $args ) {
	if ( empty( $args['setting'] ) || empty( $args['post_type'] ) ) {
		return;
	}

	$name  = ! empty( $args['label_for'] ) ? str_replace( '|', '][', $args['label_for'] ) : $args['setting'];
	$value = sfar_get_settings();
	$value = ! empty( $value[ $args['post_type'] ] ) ? $value[ $args['post_type'] ] : array();
	$value = ! empty( $value[ $args['setting'] ] )   ? $value[ $args['setting'] ]   : '';

	$attributes = ! empty( $args['attributes'] ) ? $args['attributes'] : array();
	$attributes = array_map( 'esc_attr', $attributes );
	$attributes = array_merge( array(
		'type'  => 'text',
		'name'  => 'sf_archiver[' . $name . ']',
		'value' => $value,
	), $attributes );

	if ( ! empty( $args['label_for'] ) ) {
		$attributes['id'] = esc_attr( $args['label_for'] );
	}

	echo '<input' . build_html_atts( $attributes ) . '/> ';
	echo ! empty( $args['after'] ) ? $args['after'] : '';
}


/*-----------------------------------------------------------------------------------*/
/* !MENUS ========================================================================== */
/*-----------------------------------------------------------------------------------*/

// !Add a metabox for the CPTs archives in Appearance -> Menus.

add_action( 'load-nav-menus.php', 'sfar_add_nav_menu_metabox' );

function sfar_add_nav_menu_metabox() {
	if ( $post_types = sfar_get_post_types() ) {
		add_meta_box( 'add-cpt-archive', __( 'Post types', 'sf-archiver' ), 'sfar_nav_menu_metabox', 'nav-menus', 'side', 'default', $post_types );
	}
}


// !The metabox.

function sfar_nav_menu_metabox( $post = null, $metabox = array() ) {
	global $nav_menu_selected_id;

	$post_types = $metabox['args'];

	foreach ( $post_types as &$post_type ) {
		$post_type->db_id            = 0;
		$post_type->object           = $post_type->name;
		$post_type->object_id        = $post_type->name;
		$post_type->menu_item_parent = 0;
		$post_type->type             = 'cpt-archive';
		$post_type->title            = $post_type->labels->name;
		$post_type->url              = get_post_type_archive_link( $post_type->name );
		$post_type->target           = '';
		$post_type->attr_title       = '';
		$post_type->classes          = array();
		$post_type->xfn              = '';
	}

	$walker = new Walker_Nav_Menu_Checklist( array() );

	?>
	<div id="cpt-archive" class="cpt-archivediv posttypediv">
		<div id="cpt-archive-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
			<ul id="ctp-archivechecklist" class="list:cpt-archive categorychecklist form-no-clear">
				<?php
				$checkbox_items = walk_nav_menu_tree( array_map( 'wp_setup_nav_menu_item', $post_types ), 0, (object) array( 'walker' => $walker ) );

				if ( isset( $_REQUEST['cpt-archive-tab'] ) && $_REQUEST['cpt-archive-tab'] === 'all' && ! empty( $_REQUEST['selectall'] ) ) {
					$checkbox_items = preg_replace( '/(type=([\'"])checkbox(\2))/', '$1 checked=$2checked$2', $checkbox_items );
				}

				echo $checkbox_items;
				?>
			</ul>
		</div>

		<p class="button-controls">
			<span class="list-controls">
				<a href="<?php echo esc_url( add_query_arg( array( 'selectall' => 1, 'cpt-archive-tab' => 'all' ) ) ); ?>#cpt-archive" class="select-all"><?php _e( 'Select All' ); ?></a>
			</span>

			<span class="add-to-menu">
				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to Menu' ); ?>" name="add-ctp-archive-menu-item" id="submit-cpt-archive" />
				<span class="spinner"></span>
			</span>
		</p>
	</div>
	<?php
}


/*-----------------------------------------------------------------------------------*/
/* !TOOLS ========================================================================== */
/*-----------------------------------------------------------------------------------*/

// !Build a string for html attributes (means: separated by a space) : array( 'width' => '200', 'height' => '150', 'yolo' => 'foo' ) ==> ' width="200" height="150" yolo="foo"'

if ( ! function_exists( 'build_html_atts' ) ) :
function build_html_atts( $attributes, $quote = '"' ) {
	$out = '';
	if ( ! is_array( $attributes ) || empty( $attributes ) ) {
		return '';
	}
	foreach( $attributes as $att_name => $att_value ) {
		$out .= ' ' . esc_attr( $att_name ) . '=' . $quote . $att_value . $quote;
	}
	return $out;
}
endif;


if ( ! function_exists( 'get_page_for_posts' ) ) :
function get_page_for_posts( $permalink = false ) {
	static $out;

	if ( ! isset( $out ) ) {
		$out            = array( 'ID' => false, 'permalink' => '', );
		$show_on_front  = get_option( 'show_on_front' );

		if ( $show_on_front === 'page' ) {
			$page_for_posts = absint( get_option( 'page_for_posts' ) );
			$page_for_posts = $page_for_posts ? get_post( $page_for_posts ) : false;

			if ( $page_for_posts ) {
				$out        = array(
					'ID'        => $page_for_posts->ID,
					'permalink' => get_permalink( $page_for_posts ),
				);
			}
		}
		else {
			$out['permalink'] = user_trailingslashit( home_url() );
		}
	}

	return $permalink ? $out['permalink'] : $out['ID'];
}
endif;


// !Get public post types that have an archive.

function sfar_get_post_types() {
	$post_types = get_post_types( array(
		'public'      => true,
		'show_ui'     => true,
		'has_archive' => true,
	), 'objects' );

	return apply_filters( 'sf_archiver_post_types', $post_types );
}

/**/