<?php

/* Settings for the administration page */
add_action( 'admin_init', 'w3p_acpt_register_settings' );
function w3p_acpt_register_settings() {
	register_setting( 'w3p-acpt-settings', '_w3p_acpt', 'w3p_acpt_satanize' );
}


/* When unistall the plugin */
register_uninstall_hook( W3P_ACPT_FILE, 'w3p_acpt_uninstaller' );
function w3p_acpt_uninstaller() {
	delete_option( '_w3p_acpt' );
}


/* Menu item */
add_action( 'admin_menu', 'w3p_acpt_menu' );
function w3p_acpt_menu() {
	add_submenu_page( 'options-general.php', W3P_ACPT_PLUGIN_NAME, W3P_ACPT_PLUGIN_NAME, 'manage_options', W3P_ACPT_PAGE_NAME, 'w3p_acpt_settings_page' );
}


/* Just adds a "Settings" link in the plugins list */
add_filter( 'plugin_action_links', 'w3p_acpt_settings_action_links', 10, 2 );
function w3p_acpt_settings_action_links( $links, $file ) {
	if ( strstr( W3P_ACPT_FILE, $file ) != '' ) {
		$settings_link = '<a href="' . admin_url( 'options-general.php?page='.W3P_ACPT_PAGE_NAME ) . '">' . __("Settings") . '</a>';
		array_unshift( $links, $settings_link );
	}
	return $links;
}


/* Settings page */
function w3p_acpt_settings_page() {
	$acpt	= '_w3p_acpt';
	$sets	= get_option($acpt);
	$cpts	= get_post_types( array('show_ui' => true, '_builtin' => false) );							// Get the public custom post types
	if ( isset($_GET['settings-updated']) )																// Flush rules only on settings save
		flush_rewrite_rules();
	?>
<div class="wrap">
	<div id="icon-<?php echo W3P_ACPT_PAGE_NAME; ?>" class="icon32" style="background: url(<?php echo W3P_ACPT_PLUGIN_URL; ?>/admin/icon32.png) 0 0 no-repeat"><br/></div>

	<h2><?php echo esc_html( W3P_ACPT_PLUGIN_NAME ); ?></h2>

	<p><?php _e("Here you can choose to enable the archive capability for the public custom post types.", W3P_ACPT_DOMAIN); ?></p>

	<?php
	if ( !count($cpts) )
		echo '<p><i>'.__("Wait, where are my custom post types?! Why did you installed me if you don&#8217;t have custom post types? ;)", W3P_ACPT_DOMAIN).'</i></p>';
	else { ?>
	<form name="w3p_acpt" method="post" action="options.php" id="w3p_acpt">
		<?php settings_fields( 'w3p-acpt-settings' ); ?>
		<table class="form-table">
		<?php
		global $wp_post_types;
		$no_icon = '<span style="display:inline-block;width:16px;height:16px;background:url('.admin_url('images/menu.png').') -276px -8px no-repeat;">&#160;</span>';
		foreach ( $cpts as $cpt ) { ?>
			<tr valign="top">
				<th scope="row">
					<?php
					$icon = isset($wp_post_types[$cpt]->menu_icon) && $wp_post_types[$cpt]->menu_icon ? '<img alt="&#8226; " src="'.esc_url($wp_post_types[$cpt]->menu_icon).'"/>' : $no_icon;
					echo $icon.' '.$wp_post_types[$cpt]->label; ?>
				</th>
				<td>
					<?php
					$ha_def = $wp_post_types[$cpt]->has_archive && (!isset($sets[$cpt]['enabled']) || (int) $sets[$cpt]['enabled'] == 2);		// Has archive but not in settings : this CPT already has an archive page before the plugin install
					if ( $ha_def ) {
						echo '<input type="hidden" name="'.$acpt.'['.$cpt.'][enabled]" value="2"/>'
							.'<span class="description">'.__("This <abbr title=\"Custom Post Type\">CPT</abbr> already has an archive page.", W3P_ACPT_DOMAIN).'</span><br/>';
					} else {
						echo '<label>'
								.'<input type="checkbox" name="'.$acpt.'['.$cpt.'][enabled]" value="1"'.(isset($sets[$cpt]['enabled']) && $sets[$cpt]['enabled'] ? ' checked="checked"' : '' ).'/> '
								.__("Enable archive page", W3P_ACPT_DOMAIN)
							.'</label><br/>';
					}
					$p_slug		= isset($wp_post_types[$cpt]->rewrite['slug']) ? $wp_post_types[$cpt]->rewrite['slug'] : $cpt;
					$a_slug		= isset($wp_post_types[$cpt]->has_archive) && is_string($wp_post_types[$cpt]->has_archive) ? $wp_post_types[$cpt]->has_archive : $p_slug;
					// Display default value for the CPTs that already have an archive page before the plugin install
					$disp_o		= !isset($sets[$cpt]['enabled']) || (int) $sets[$cpt]['enabled'] == 2;
					$disp_o_a	= $disp_o && (!isset($sets[$cpt]['a_slug']) || !$sets[$cpt]['a_slug']);
					$disp_o_p	= $disp_o && (!isset($sets[$cpt]['p_slug']) || !$sets[$cpt]['p_slug']);
					// For all CPTs now
					$disp_def_a	= $disp_o_a || !isset($sets[$cpt]['enabled']) || !$sets[$cpt]['enabled'];
					$disp_def_p	= $disp_o_p || !isset($sets[$cpt]['enabled']) || !$sets[$cpt]['enabled'];
					echo '<label>'
							.'<input type="checkbox" name="'.$acpt.'['.$cpt.'][rss]" value="1"'.(isset($sets[$cpt]['rss']) && $sets[$cpt]['rss'] ? ' checked="checked"' : '' ).'/> '
							.__("Add the RSS feed <code>&lt;link/&gt;</code> in head", W3P_ACPT_DOMAIN)
						.'</label>'
						.(isset($sets[$cpt]['enabled']) && $sets[$cpt]['enabled'] ? ' - <a target="_blank" href="'.get_post_type_archive_feed_link($cpt).'">'.__("Feed url", W3P_ACPT_DOMAIN).'</a>' : '').'<br/>';
					echo '<label>'
							.sprintf(__("%s per page:", W3P_ACPT_DOMAIN), $wp_post_types[$cpt]->label)
							.' <input type="text" class="small-text" name="'.$acpt.'['.$cpt.'][ppp]" value="'.(isset($sets[$cpt]['ppp']) && $sets[$cpt]['ppp'] ? (int) $sets[$cpt]['ppp'] : '').'"/>'
						.'</label><br/>';
					echo '<label>'
							.__("Custom archive slug:", W3P_ACPT_DOMAIN)
							.' <input type="text" name="'.$acpt.'['.$cpt.'][a_slug]" value="'.(isset($sets[$cpt]['a_slug']) && $sets[$cpt]['a_slug'] ? strtolower(esc_attr($sets[$cpt]['a_slug'])) : '').'"/>'
						.'</label> '
						.($disp_def_a ? '<span class="description">('.__("default value:", W3P_ACPT_DOMAIN).' "'.$a_slug.'")</span>' : '')
						.(isset($sets[$cpt]['enabled']) && $sets[$cpt]['enabled'] ? ' <a target="_blank" href="'.get_post_type_archive_link($cpt).'">'.__("Go to the archive page", W3P_ACPT_DOMAIN).'</a>' : '').'<br/>';
					echo '<label>'
							.__("Custom posts slug:", W3P_ACPT_DOMAIN)
							.' <input type="text" name="'.$acpt.'['.$cpt.'][p_slug]" value="'.(isset($sets[$cpt]['p_slug']) && $sets[$cpt]['p_slug'] ? strtolower(esc_attr($sets[$cpt]['p_slug'])) : '').'"/>'
						.'</label> '
						.($disp_def_p ? '<span class="description">('.__("default value:", W3P_ACPT_DOMAIN).' "'.$p_slug.'")</span>' : '').'<br/>';
					echo '<label>'
							.'<input type="checkbox" name="'.$acpt.'['.$cpt.'][hp]" value="1"'.(isset($sets[$cpt]['hp']) && $sets[$cpt]['hp'] ? ' checked="checked"' : '' ).'/> '
							.__("Add this post type to the posts page", W3P_ACPT_DOMAIN)
						.'</label><br/>';
					echo '<label>'
							.'<input type="checkbox" name="'.$acpt.'['.$cpt.'][mainrss]" value="1"'.(isset($sets[$cpt]['mainrss']) && $sets[$cpt]['mainrss'] ? ' checked="checked"' : '' ).'/> '
							.__("Add this post type to the main RSS feed", W3P_ACPT_DOMAIN)
						.'</label><br/>';
					?>
				</td>
			</tr>
		<?php } ?>
			<tr valign="top">
				<th scope="row">
					<input type="submit" name="submit" class="button-primary" value="<?php _e("Save Changes"); ?>" />
				</th>
				<td style="vertical-align: middle;">
					<label><input type="checkbox" name="<?php echo $acpt; ?>[delete_options]" value="1" /> <?php _e("Panic room: delete all the plugin settings", W3P_ACPT_DOMAIN); ?></label>
				</td>
		</table>
	</form>
</div>
<?php }
}


/* Satanize options */
function w3p_acpt_satanize($options) {
	if ( isset($options['delete_options']) && (int) $options['delete_options'] == 1 )
		return;

	$options = w3p_cleanup_array($options);
	if (count($options)) {
		global $wp_post_types;
		foreach ($options as $cpt => $opt) {
			if (!isset($wp_post_types[$cpt]))
				unset($options[$cpt]);
			else {
				if (isset($opt['a_slug']) && $opt['a_slug'])
					$options[$cpt]['a_slug'] = trim($options[$cpt]['a_slug'], '/');
				if (isset($opt['p_slug']) && $opt['p_slug'])
					$options[$cpt]['p_slug'] = trim($options[$cpt]['p_slug'], '/');
			}
		}
	}

	return $options;
}


/* Clean up an array by removing empty values */
if ( !function_exists( 'w3p_cleanup_array' ) ) {
	function w3p_cleanup_array($input, $keep0 = false) {
		if (!is_array($input)) {
			return trim($input);
		}
		$non_empty_items = array();
		foreach ($input as $key => $value) {
			if(($keep0 && $value != '') || (!$keep0 && $value)) {
				$non_empty_items[$key] = w3p_cleanup_array($value, $keep0);
				if (!count($non_empty_items[$key]))
					unset($non_empty_items[$key]);
			}
		}
		return $non_empty_items;
	}
}


/* Contextual help for the Settings page */
add_action( 'contextual_help', 'w3p_acpt_contextual_help',10,3);
function w3p_acpt_contextual_help($help, $screen_id, $screen) {
	if ($screen_id != 'settings_page_'.W3P_ACPT_PAGE_NAME)
		return $help;

	$helpArr = array();

	$txt1 = '<p>'.__('Each post type must have the &#171;&#160;Archive capability&#160;&#187; if you want to link to a page listing them.', W3P_ACPT_DOMAIN).'</p>'.
			'<p>'.__('To enable the archive page, tick the &#171;&#160;Enable archive page&#160;&#187; checkbox in front of your custom post type. If the custom post type already has this capability, the checkbox won&#8217; show up.', W3P_ACPT_DOMAIN).'</p>';
	$helpArr[] = array(
		'id'		=> 'archive-page',
		'title'		=> __('Archive page', W3P_ACPT_DOMAIN),
		'content'	=> $txt1
	);

	$txt2 = '<p>'.__('By enabling the &#171;&#160;Archive capability&#160;&#187;, a RSS feed is also created. If you want to add this feed to your site, tick the checkbox and a <code>&lt;link/&gt;</code> tag will be inserted in the head of your site. But first, you should look if it isn&#8217;t already there.', W3P_ACPT_DOMAIN).'</p>'.
			'<p>'.sprintf(__("The plugin also gives you an url to this feed: just copy the &#171;&#160;Feed url&#160;&#187; link url. You can also get this url by using the appropriate WordPress function:<br/> %s.", W3P_ACPT_DOMAIN), "<code>&lt;?php echo get_post_type_archive_feed_link('my-custom-post-type'); ?&gt;</code>").'</p>';
	$helpArr[] = array(
		'id'		=> 'rss-feed',
		'title'		=> __('RSS feed', W3P_ACPT_DOMAIN),
		'content'	=> $txt2
	);

	$txt3 = '<p>'.sprintf(__('You can customize the number of posts per page for each custom post type. Otherwise, this number will be the same than what you specified for your normal posts: %d.', W3P_ACPT_DOMAIN), get_option('posts_per_page')).'</p>'.
			'<p>'.__('Hint: -1 to display all of them in the same page.', W3P_ACPT_DOMAIN).'</p>';
	$helpArr[] = array(
		'id'		=> 'posts-per-page',
		'title'		=> __('Posts per page', W3P_ACPT_DOMAIN),
		'content'	=> $txt3
	);

	$txt4 = '<p>'.__('You can even specify a new slug for the archive page or the single pages.', W3P_ACPT_DOMAIN).'</p>'.
			'<p>'.__("Hint: you can use sub-slugs like &#8217;sub-slug/slug&#8217;, &#8217;collections/summer/tee-shirts&#8217;...", W3P_ACPT_DOMAIN).'</p>'.
			'<p>'.__("Don&#8217;t forget that the RSS url change at the same time, and the menu link does&#8217;t update by itself.", W3P_ACPT_DOMAIN).'</p>';
	$helpArr[] = array(
		'id'		=> 'slugs',
		'title'		=> __('Slugs', W3P_ACPT_DOMAIN),
		'content'	=> $txt4
	);

	$txt5 = '<p>'.__('You can add your custom post types to your posts page, with your normal posts. Just tick the &#171;&#160;Add this post type to the posts page&#160;&#187; checkbox, the archive page is still available.', W3P_ACPT_DOMAIN).'</p>'.
			'<p>'.__('You can do the same thing with the main RSS feed: you can add your custom post types to your normal posts in the main RSS feed by ticking the &#171;&#160;Add this post type to the main RSS feed&#160;&#187; checkbox.', W3P_ACPT_DOMAIN).'</p>';
	$helpArr[] = array(
		'id'		=> 'posts-page-and-feed',
		'title'		=> __('Posts page and main RSS feed', W3P_ACPT_DOMAIN),
		'content'	=> $txt5
	);

	$txt6 = '<p>'.sprintf(__('Go to Appearance -> %s, look for the box called &#171;&#160;Post types&#160;&#187;, add your custom post type to your menu :)', W3P_ACPT_DOMAIN), '<a href="'.admin_url('nav-menus.php').'">'.__('Menus').'</a>').'</p>'.
			'<p>'.__("Don&#8217;t forget to change your menu link if you change your archive slug (delete the link in the menu and add it again).", W3P_ACPT_DOMAIN).'</p>';
	$helpArr[] = array(
		'id'		=> 'whats-next',
		'title'		=> __('What&#8217;s next?', W3P_ACPT_DOMAIN),
		'content'	=> $txt6
	);

	$credits = '<p>'.sprintf(__('This plugin was created by %1$s and reviewed by %2$s for a security check.', W3P_ACPT_DOMAIN), 'Grégory Viguier', "<a title='Boite A Web' target='_blank' href='http://www.boiteaweb.fr'>Julio Potier</a>").'</p>'.
			   '<p>'.sprintf(__('Plugin icon by %s.', W3P_ACPT_DOMAIN), "<a title='Double-J designs' target='_blank' href='http://www.doublejdesign.co.uk/'>Double-J designs</a>").'</p>';
	$helpArr[] = array(
		'id'		=> 'credits',
		'title'		=> __('Credits', W3P_ACPT_DOMAIN),
		'content'	=> $credits
	);

	$helpSide = '<p><strong>'.__('For more information:').'</strong></p>'.
				"<p><a title='Screenfeed' target='_blank' href='http://www.screenfeed.fr/archi/'>".__('My blog (french)', W3P_ACPT_DOMAIN).'</a></p>';

	
	foreach($helpArr as $helpItem) {
		$screen->add_help_tab($helpItem);
	}

	$screen->set_help_sidebar( $helpSide );
}


/* Metabox for CPTs in Apperance -> Menus */
add_action('admin_head-nav-menus.php', 'w3p_acpt_add_nav_menu_metabox');
function w3p_acpt_add_nav_menu_metabox() {
	$post_types = get_post_types( array( 'public' => true, 'show_in_nav_menus' => true, 'has_archive' => true ), 'object' );
	if ( count($post_types) )
		add_meta_box( 'post-types', __('Post types', W3P_ACPT_DOMAIN), 'w3p_acpt_nav_menu_metabox', 'nav-menus', 'side', 'default', $post_types );
}


/* The metabox */
function w3p_acpt_nav_menu_metabox( $object, $post_types ) {
	global $_nav_menu_placeholder, $nav_menu_selected_id, $locale;
	$types = $post_types['args'];
	?>
	<div id="post-types" class="posttypesdiv">

		<div id="tabs-panel-post-types-all" class="tabs-panel tabs-panel-view-all tabs-panel-active" style="border-style:solid;border-width:1px;overflow:auto;padding:.5em .9em;">
			<ul id="post-typeschecklist" class="list:post-types categorychecklist form-no-clear form-no-clear" style="margin:0">
				<?php
				$old_locale = $locale;
				if ( class_exists('SitePress') ) {		// WPML support : if we're building a menu in another language, switch to this one
					global $sitepress;
					$locale = $sitepress->get_locale($sitepress->get_current_language());
				}
				$checked = isset($_GET['post-types-tab'], $_GET['selectall']) && $_GET['post-types-tab'] == 'all' && $_GET['selectall'] == 1 ? ' checked="checked"' : '';
				foreach ( $types as $type => $obj ) {
					$_nav_menu_placeholder = ( 0 > $_nav_menu_placeholder ) ? intval($_nav_menu_placeholder) - 1 : -1; ?>
					<li>
						<label class="menu-item-title">
							<input type="checkbox" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object-id]" class="menu-item-checkbox" value="<?php echo $type; ?>"<?php echo $checked; ?>/> <?php echo $obj->labels->name; ?>
						</label>
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-object]" class="menu-item-object" value="post-type"/>
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-type]" class="menu-item-type" value="custom"/>
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-title]" class="menu-item-title" value="<?php echo $obj->labels->name; ?>"/>
						<input type="hidden" name="menu-item[<?php echo $_nav_menu_placeholder; ?>][menu-item-url]" class="menu-item-url" value="<?php echo get_post_type_archive_link( $type ); ?>"/>
					</li>
					<?php
				}
				$locale = $old_locale;					// Back to the administration language
				?>
			</ul>
		</div><!-- /.tabs-panel -->

		<p class="button-controls">
			<span class="list-controls">
				<a href="<?php echo esc_url(add_query_arg( array( 'selectall' => 1, 'post-types-tab' => 'all' ) )); ?>#post-types" class="select-all"><?php _e('Select All'); ?></a>
			</span>

			<span class="add-to-menu">
				<img class="waiting" src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" />
				<input type="submit"<?php disabled( $nav_menu_selected_id, 0 ); ?> class="button-secondary submit-add-to-menu" value="<?php esc_attr_e('Add to Menu'); ?>" name="add-post-types-menu-item" id="submit-post-types" />
			</span>
		</p>

	</div>
	<?php
}


?>