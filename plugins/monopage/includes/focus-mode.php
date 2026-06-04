<?php
/**
 * Focused WordPress admin experience for Monopage.
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

/**
 * Remove distracting admin menus when Focus Mode is active.
 *
 * This is interface cleanup only. WordPress capabilities still control access.
 */
function monopage_prune_admin_menu() {
	if ( ! monopage_is_focus_active_for_current_user() ) {
		return;
	}

	foreach ( monopage_get_hidden_menu_slugs() as $menu_slug ) {
		remove_menu_page( $menu_slug );
	}
}

/**
 * Hide noisy admin-bar links while preserving account and site access.
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
 */
function monopage_prune_admin_bar( $wp_admin_bar ) {
	if ( ! monopage_is_focus_active_for_current_user() ) {
		return;
	}

	foreach ( array( 'comments', 'new-content', 'customize', 'themes', 'widgets', 'menus', 'edit', 'wp-logo', 'site-name', 'view-site', 'updates', 'search' ) as $node_id ) {
		$wp_admin_bar->remove_node( $node_id );
	}
}

/**
 * Hide the public-site admin bar for focused editors.
 *
 * @param bool $show Whether the admin bar should show.
 * @return bool
 */
function monopage_maybe_hide_frontend_admin_bar( $show ) {
	if ( monopage_is_focus_active_for_current_user() ) {
		return false;
	}

	return $show;
}

/**
 * Redirect generic admin surfaces to the Site Editor in Focus Mode.
 */
function monopage_maybe_redirect_admin() {
	if ( ! is_admin() || ! monopage_is_focus_active_for_current_user() ) {
		return;
	}

	if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	global $pagenow;

	if ( 'site-editor.php' === $pagenow ) {
		monopage_maybe_redirect_site_editor_to_canvas();
		return;
	}

	if ( in_array( $pagenow, monopage_get_allowed_focus_pages(), true ) ) {
		return;
	}

	wp_safe_redirect( monopage_get_site_editor_url() );
	exit;
}

/**
 * Send focused users to the Site Editor after login.
 *
 * @param string  $redirect_to           Requested redirect URL.
 * @param string  $requested_redirect_to Original requested redirect URL.
 * @param WP_User $user                  Logged-in user.
 * @return string
 */
function monopage_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( ! $user instanceof WP_User || ! user_can( $user, 'edit_theme_options' ) ) {
		return $redirect_to;
	}

	if ( monopage_get_focus_enabled() ) {
		return monopage_get_site_editor_url();
	}

	return $redirect_to;
}

/**
 * Enqueue Monopage admin styles.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function monopage_enqueue_admin_assets( $hook_suffix ) {
	if ( ! monopage_is_focus_active_for_current_user() ) {
		return;
	}

	wp_enqueue_style(
		'monopage-admin',
		MONOPAGE_URL . 'assets/admin.css',
		array(),
		MONOPAGE_VERSION
	);

	if ( monopage_is_site_editor_admin_screen( $hook_suffix ) && monopage_is_focus_active_for_current_user() ) {
		wp_enqueue_script(
			'monopage-site-editor',
			MONOPAGE_URL . 'assets/site-editor.js',
			array( 'wp-data', 'wp-dom-ready', 'wp-preferences' ),
			MONOPAGE_VERSION,
			true
		);
	}
}

/**
 * Add Monopage admin state classes.
 *
 * @param string $classes Space-separated admin body classes.
 * @return string
 */
function monopage_admin_body_class( $classes ) {
	if ( monopage_is_focus_active_for_current_user() ) {
		$classes .= ' monopage-focus-active monopage-sidebar-hidden';
	}

	return $classes;
}

/**
 * Check whether the current admin screen is the Site Editor.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return bool
 */
function monopage_is_site_editor_admin_screen( $hook_suffix ) {
	global $pagenow;

	if ( 'site-editor.php' === $hook_suffix || 'site-editor.php' === $pagenow ) {
		return true;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && ( false !== strpos( $screen->id, 'site-editor' ) || false !== strpos( $screen->base, 'site-editor' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Redirect attempts to edit the routing Home page to the editable front-page template.
 */
function monopage_maybe_redirect_home_page_editor() {
	if ( ! is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	global $pagenow;

	if ( 'post.php' !== $pagenow ) {
		return;
	}

	$post_id = absint( monopage_get_query_text( 'post' ) );
	$action  = monopage_get_query_key( 'action' );

	if ( ! $post_id || 'edit' !== $action || ! monopage_is_routing_home_page( $post_id ) ) {
		return;
	}

	wp_safe_redirect( monopage_get_site_editor_url() );
	exit;
}

/**
 * Check whether a page is Monopage's routing Home page.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function monopage_is_routing_home_page( $post_id ) {
	return MONOPAGE_CANVAS_THEME === get_stylesheet()
		&& monopage_site_uses_block_theme()
		&& 'page' === get_option( 'show_on_front' )
		&& absint( get_option( 'page_on_front' ) ) === absint( $post_id );
}

/**
 * Get global Focus Mode state.
 *
 * @return bool
 */
function monopage_get_focus_enabled() {
	return (bool) get_option( MONOPAGE_FOCUS_OPTION, '1' );
}

/**
 * Check whether Focus Mode applies to the current request user.
 *
 * @return bool
 */
function monopage_is_focus_active_for_current_user() {
	if ( ! monopage_get_focus_enabled() ) {
		return false;
	}

	return current_user_can( 'edit_theme_options' );
}

/**
 * Menu slugs hidden by Focus Mode.
 *
 * @return string[]
 */
function monopage_get_hidden_menu_slugs() {
	return array(
		'index.php',
		'edit.php',
		'edit.php?post_type=page',
		'edit-comments.php',
		'themes.php',
		'plugins.php',
		'users.php',
		'options-general.php',
		'tools.php',
	);
}

/**
 * Admin pages allowed during Focus Mode.
 *
 * @return string[]
 */
function monopage_get_allowed_focus_pages() {
	return array(
		'site-editor.php',
		'async-upload.php',
		'admin-ajax.php',
		'admin-post.php',
	);
}
