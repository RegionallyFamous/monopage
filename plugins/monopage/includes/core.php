<?php
/**
 * Core helpers and hook registration for Monopage.
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register Monopage runtime hooks.
 */
function monopage_register_hooks() {
	add_action( 'admin_menu', 'monopage_prune_admin_menu', 999 );
	add_action( 'admin_init', 'monopage_maybe_redirect_home_page_editor', 1 );
	add_action( 'admin_init', 'monopage_maybe_redirect_admin' );
	add_action( 'admin_enqueue_scripts', 'monopage_enqueue_admin_assets' );
	add_action( 'admin_bar_menu', 'monopage_prune_admin_bar', 999 );
	add_action( 'after_switch_theme', 'monopage_maybe_setup_canvas_defaults' );
	add_filter( 'admin_body_class', 'monopage_admin_body_class' );
	add_filter( 'login_redirect', 'monopage_login_redirect', 10, 3 );
	add_filter( 'show_admin_bar', 'monopage_maybe_hide_frontend_admin_bar' );
}

/**
 * Get a scalar query parameter as a sanitized key.
 *
 * @param string $key Query parameter name.
 * @return string
 */
function monopage_get_query_key( $key ) {
	$value = monopage_get_request_value( $_GET, $key ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return '' === $value ? '' : sanitize_key( $value );
}

/**
 * Get a scalar query parameter as sanitized text.
 *
 * @param string $key Query parameter name.
 * @return string
 */
function monopage_get_query_text( $key ) {
	$value = monopage_get_request_value( $_GET, $key ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return '' === $value ? '' : sanitize_text_field( $value );
}

/**
 * Get a scalar request value without assuming PHP's superglobal shape.
 *
 * @param array  $source Request source.
 * @param string $key    Request key.
 * @return string
 */
function monopage_get_request_value( $source, $key ) {
	if ( ! isset( $source[ $key ] ) || ! is_scalar( $source[ $key ] ) ) {
		return '';
	}

	return wp_unslash( (string) $source[ $key ] );
}

/**
 * Check if the active theme is a block theme.
 *
 * @return bool
 */
function monopage_site_uses_block_theme() {
	if ( function_exists( 'wp_is_block_theme' ) ) {
		return wp_is_block_theme();
	}

	return file_exists( get_stylesheet_directory() . '/templates/index.html' );
}

/**
 * Get the Site Editor URL.
 *
 * @return string
 */
function monopage_get_site_editor_url() {
	return add_query_arg( monopage_get_site_editor_query_args(), admin_url( 'site-editor.php' ) );
}

/**
 * Keep Focus Mode in the Site Editor canvas instead of the navigation sidebar.
 */
function monopage_maybe_redirect_site_editor_to_canvas() {
	$query_args = monopage_get_site_editor_query_args();
	$is_target  = true;

	foreach ( $query_args as $key => $expected ) {
		$actual = monopage_get_query_text( $key );
		if ( $expected !== $actual ) {
			$is_target = false;
			break;
		}
	}

	if ( $is_target ) {
		return;
	}

	wp_safe_redirect( add_query_arg( $query_args, admin_url( 'site-editor.php' ) ) );
	exit;
}

/**
 * Get the Site Editor query args for the editable homepage template.
 *
 * @return array
 */
function monopage_get_site_editor_query_args() {
	$template_id = get_stylesheet() . '//front-page';

	if ( version_compare( get_bloginfo( 'version' ), '6.8', '>=' ) ) {
		return array(
			'p'      => '/wp_template/' . $template_id,
			'canvas' => 'edit',
		);
	}

	return array(
		'postType' => 'wp_template',
		'postId'   => $template_id,
		'canvas'   => 'edit',
	);
}
