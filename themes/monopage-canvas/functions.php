<?php
/**
 * Monopage Canvas theme setup.
 *
 * @package Monopage_Canvas
 */

add_action( 'after_setup_theme', 'monopage_canvas_setup' );
add_action( 'wp_enqueue_scripts', 'monopage_canvas_enqueue_styles' );

/**
 * Register editor styles so the Site Editor matches the front-end template.
 */
function monopage_canvas_setup() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}

/**
 * Load Canvas theme styles on the public site.
 */
function monopage_canvas_enqueue_styles() {
	$theme = wp_get_theme();

	wp_enqueue_style(
		'monopage-canvas-style',
		get_stylesheet_uri(),
		array(),
		$theme->get( 'Version' )
	);
}
