<?php
/*
 * Plugin Name: SF Archiver
 * Plugin URI: http://www.screenfeed.fr/archi/
 * Description: A simple way to display archive pages for your custom post types
 * Version: 1.1.2
 * Author: Grégory Viguier
 * Author URI: http://www.screenfeed.fr/greg/
 * License: GPLv3
 * Require: WordPress 3.3+
 * Text Domain: w3p-acpt
 * Domain Path: /languages/
*/

define( 'W3P_ACPT_PLUGIN_NAME',	'SF Archiver' );
define( 'W3P_ACPT_PAGE_NAME',	'w3p_acpt_config' );
define( 'W3P_ACPT_VERSION',		'1.1.2' );
define( 'W3P_ACPT_FILE',		__FILE__ );
define( 'W3P_ACPT_DIRNAME',		basename( dirname( W3P_ACPT_FILE ) ) );
define( 'W3P_ACPT_PLUGIN_URL',	plugin_dir_url( W3P_ACPT_FILE ) );
define( 'W3P_ACPT_PLUGIN_DIR',	plugin_dir_path( W3P_ACPT_FILE ) );


/* Language support */
add_action( 'init', 'w3p_acpt_lang_init' );
function w3p_acpt_lang_init() {
	load_plugin_textdomain( 'w3p-acpt', false, W3P_ACPT_DIRNAME . '/languages/' );
}


/* Init */
if ( is_admin() && !defined('DOING_AJAX') ) {

	include_once(W3P_ACPT_PLUGIN_DIR.'/admin/w3p-acpt-admin.inc.php');								// Admin

} elseif ( !is_admin() && !defined( 'XMLRPC_REQUEST' ) && !defined( 'DOING_CRON' ) ) {

	add_filter( 'wp_get_nav_menu_items', 'cpt_archive_menu_filter', 10, 3 );						// Alter the URL for cpt-archive objects
	function cpt_archive_menu_filter( $items, $menu, $args ) {
		foreach ( $items as &$item ) {
			if ( $item->object != 'cpt-archive' )
				continue;
			$item->url = get_post_type_archive_link( $item->type );
			if ( get_query_var( 'post_type' ) == $item->type ) {
				$item->classes []= 'current-menu-item';
				$item->current = true;
			}
		}
		return $items;
	}

	add_action('pre_get_posts', 'w3p_acpt_ppp');													// Posts per page limit and CPTs on posts page
	function w3p_acpt_ppp($q) {
		if ( !$q->is_main_query() )
			return;

		if ( $q->is_home ) {																											// CPTs on posts page

			global $wp_post_types;
			$sets = get_option('_w3p_acpt');
			$pts = array('post');
			if ( is_array($sets) && count($sets) ) {
				foreach ($sets as $type => $set) {
					if (isset($set['hp']) && $set['hp'] && isset($wp_post_types[$type]))
						$pts[] = $type;
				}
				$pts = apply_filters( 'w3p-acpt-types-in-posts-page', $pts);
				$q->set('post_type', $pts);
			}

		} elseif ( isset($q->query_vars['post_type']) && $q->query_vars['post_type'] && $q->is_post_type_archive ) {					// Posts per page limit for archives

			$pt = $q->query_vars['post_type'];
			$sets = get_option('_w3p_acpt');

			if ( isset($sets[$pt]['enabled'], $sets[$pt]['ppp']) && $sets[$pt]['enabled'] && $sets[$pt]['ppp'] )
				$q->query_vars['posts_per_page'] = $sets[$pt]['ppp'];

		}
	}

	add_filter( 'request', 'w3p_acpt_feed' );														// Add Custom Post Types to the main feed
	function w3p_acpt_feed( $qv ) {
		if ( isset($qv['feed']) && !isset($qv['post_type']) ) {
			global $wp_post_types;
			$sets = get_option('_w3p_acpt');
			$pts = array('post');
			if ( is_array($sets) && count($sets) ) {
				foreach ($sets as $type => $set) {
					if (isset($set['mainrss']) && $set['mainrss'] && isset($wp_post_types[$type]))
						$pts[] = $type;
				}
				$pts = apply_filters( 'w3p-acpt-types-in-main-feed', $pts);
				$qv['post_type'] = $pts;
			}
		}
		return $qv;
	}

	add_action('wp_head', 'w3p_acpt_feed_links');													// Add feed links in head
	function w3p_acpt_feed_links() {
		$sets = get_option('_w3p_acpt');
		if ( !is_array($sets) || !count($sets) )
			return;

		global $wp_post_types;
		$feed_type = feed_content_type();
		foreach ( $sets as $pt => $set ) {
			if ( isset($set['enabled'], $set['rss']) && $set['enabled'] && $set['rss'] && isset($wp_post_types[$pt]->label) ) {
				$feed_title = apply_filters( 'post_type_feed_title', __( 'Subscribe to %s via RSS', 'w3p-acpt' ), $pt );
				echo '<link rel="alternate" type="'.$feed_type.'" title="'.sprintf( esc_html($feed_title), $wp_post_types[$pt]->label ).'" href="'.get_post_type_archive_feed_link($pt).'" />';
			}
		}
	}

}


// Enable archive page and rewrite for CPT
add_action('registered_post_type', 'w3p_acpt_archive_cpt', 10, 2);
function w3p_acpt_archive_cpt($post_type, $args) {
	global $wp_post_types, $wp_rewrite;
	$sets = get_option('_w3p_acpt');

	if ( !isset($sets[$post_type]['enabled']) || !$sets[$post_type]['enabled'] )
		return;

	if ( is_admin() || '' != get_option('permalink_structure') ) {									// Well, main of the code from here is just a copy/paste of a part of the register_post_type() function.

		$args->has_archive	= is_string($args->has_archive) && strlen($args->has_archive) ? $args->has_archive : true;		// Force archive page
		unset($args->rewrite['feeds']);																						// Force RSS feed

		if ( ! is_array( $args->rewrite ) )
			$args->rewrite = array();
		if ( isset($sets[$post_type]['p_slug']) && $sets[$post_type]['p_slug'] )					// Check for settings first (post slug)
			$args->rewrite['slug'] = strtolower(esc_attr($sets[$post_type]['p_slug']));
		elseif ( empty( $args->rewrite['slug'] ) )
			$args->rewrite['slug'] = $post_type;
		if ( ! isset( $args->rewrite['with_front'] ) )
			$args->rewrite['with_front'] = true;
		if ( ! isset( $args->rewrite['pages'] ) )
			$args->rewrite['pages'] = true;
		if ( ! isset( $args->rewrite['feeds'] ) || ! $args->has_archive )
			$args->rewrite['feeds'] = (bool) $args->has_archive;
		if ( ! isset( $args->rewrite['ep_mask'] ) ) {
			if ( isset( $args->permalink_epmask ) )
				$args->rewrite['ep_mask'] = $args->permalink_epmask;
			else
				$args->rewrite['ep_mask'] = EP_PERMALINK;
		}

		if ( $args->hierarchical )
			$wp_rewrite->add_rewrite_tag("%$post_type%", '(.+?)', $args->query_var ? "{$args->query_var}=" : "post_type=$post_type&name=");
		else
			$wp_rewrite->add_rewrite_tag("%$post_type%", '([^/]+)', $args->query_var ? "{$args->query_var}=" : "post_type=$post_type&name=");

		if ( $args->has_archive ) {
			if ( isset($sets[$post_type]['a_slug']) && $sets[$post_type]['a_slug'] ) {				// Check for settings first (archive slug)
				$archive_slug = strtolower(esc_attr($sets[$post_type]['a_slug']));
				$args->has_archive = $archive_slug;
			} elseif ( $args->has_archive === true )
				$archive_slug = $args->rewrite['slug'];
			else
				$archive_slug = $args->has_archive;
//			$archive_slug = $args->has_archive === true ? $args->rewrite['slug'] : $args->has_archive;		// The original line
			if ( $args->rewrite['with_front'] )
				$archive_slug = substr( $wp_rewrite->front, 1 ) . $archive_slug;
			else
				$archive_slug = $wp_rewrite->root . $archive_slug;

			$wp_rewrite->add_rule( "{$archive_slug}/?$", "index.php?post_type=$post_type", 'top' );
			if ( $args->rewrite['feeds'] && $wp_rewrite->feeds ) {
				$feeds = '(' . trim( implode( '|', $wp_rewrite->feeds ) ) . ')';
				$wp_rewrite->add_rule( "{$archive_slug}/feed/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top' );
				$wp_rewrite->add_rule( "{$archive_slug}/$feeds/?$", "index.php?post_type=$post_type" . '&feed=$matches[1]', 'top' );
			}
			if ( $args->rewrite['pages'] )
				$wp_rewrite->add_rule( "{$archive_slug}/{$wp_rewrite->pagination_base}/([0-9]{1,})/?$", "index.php?post_type=$post_type" . '&paged=$matches[1]', 'top' );
		}

		$wp_rewrite->add_permastruct($post_type, "{$args->rewrite['slug']}/%$post_type%", $args->rewrite );

		// Great, stop the copy/paste and just add the final touch. Easy, isn't it? ;)
		$wp_post_types[$post_type] = $args;
	}
}
