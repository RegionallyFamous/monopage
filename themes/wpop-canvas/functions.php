<?php
/**
 * WPOP Canvas theme setup.
 *
 * @package WPOP_Canvas
 */

add_action( 'after_setup_theme', 'wpop_canvas_setup' );

/**
 * Register editor styles so the Site Editor matches the front-end template.
 */
function wpop_canvas_setup() {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
