<?php
/**
 * Homepage setup and Canvas template seeding for Monopage.
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register default plugin options and run the first-pass setup.
 */
function monopage_activate() {
	add_option( MONOPAGE_FOCUS_OPTION, '1' );
	update_option( MONOPAGE_VERSION_OPTION, MONOPAGE_VERSION );

	monopage_setup_one_pager();
}

/**
 * Run Monopage one-page setup.
 *
 * @param array $args Setup arguments.
 * @return array|WP_Error
 */
function monopage_setup_one_pager( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'force_home'     => false,
			'force_template' => false,
			'home_title'     => __( 'Home', 'monopage' ),
			'seed_template'  => true,
		)
	);

	$current_front_id     = absint( get_option( 'page_on_front' ) );
	$has_static_frontpage = 'page' === get_option( 'show_on_front' ) && $current_front_id > 0;

	if ( $has_static_frontpage && ! $args['force_home'] ) {
		$template_id = 0;
		if ( $args['seed_template'] && $args['force_template'] ) {
			$template_id = monopage_seed_default_front_page_template( true );
			if ( is_wp_error( $template_id ) ) {
				return $template_id;
			}
		}

		return array(
			'changed'     => (bool) $template_id,
			'home_id'     => $current_front_id,
			'template_id' => absint( $template_id ),
			'message'     => $template_id ? __( 'Existing static front page preserved; front-page template refreshed.', 'monopage' ) : __( 'Existing static front page preserved.', 'monopage' ),
		);
	}

	$home_page = monopage_get_or_create_home_page( $args['home_title'] );
	if ( is_wp_error( $home_page ) ) {
		return $home_page;
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_page->ID );
	update_option( 'page_for_posts', 0 );

	$template_id = 0;
	if ( $args['seed_template'] ) {
		$template_id = monopage_seed_default_front_page_template( $args['force_template'] );
		if ( is_wp_error( $template_id ) ) {
			return $template_id;
		}
	}

	return array(
		'changed'     => true,
		'home_id'     => $home_page->ID,
		'template_id' => absint( $template_id ),
		'message'     => __( 'Monopage homepage setup complete.', 'monopage' ),
	);
}

/**
 * Seed Monopage defaults when the companion Canvas theme is activated after the plugin.
 */
function monopage_maybe_setup_canvas_defaults() {
	if ( MONOPAGE_CANVAS_THEME !== get_stylesheet() ) {
		return;
	}

	monopage_setup_one_pager();
}

/**
 * Save the Monopage marketing homepage as the editable front-page template.
 *
 * Existing saved front-page templates are preserved unless explicitly refreshed.
 *
 * @param bool $force_template Whether to overwrite an existing saved template.
 * @return int|WP_Error Template post ID, 0 when unavailable, or WP_Error on failure.
 */
function monopage_seed_default_front_page_template( $force_template = false ) {
	if ( MONOPAGE_CANVAS_THEME !== get_stylesheet() || ! monopage_site_uses_block_theme() ) {
		return 0;
	}

	if ( ! post_type_exists( 'wp_template' ) || ! taxonomy_exists( 'wp_theme' ) ) {
		return 0;
	}

	$theme = get_stylesheet();
	$existing_template = monopage_get_saved_front_page_template( $theme );
	if ( $existing_template instanceof WP_Post && ! $force_template ) {
		return $existing_template->ID;
	}

	$content = monopage_get_default_front_page_template_content();
	if ( '' === trim( $content ) ) {
		return new WP_Error( 'monopage_missing_template', __( 'Monopage Canvas front-page template is missing.', 'monopage' ) );
	}

	$template_excerpt = __( 'A focused one-page campaign canvas with anchor navigation, proof, pricing, answers, and a clear next step.', 'monopage' );

	if ( $existing_template instanceof WP_Post ) {
		$updated_template_id = wp_update_post(
			array(
				'ID'           => $existing_template->ID,
				'post_status'  => 'publish',
				'post_title'   => __( 'Front Page', 'monopage' ),
				'post_excerpt' => $template_excerpt,
				'post_content' => $content,
			),
			true
		);

		if ( is_wp_error( $updated_template_id ) ) {
			return $updated_template_id;
		}

		update_post_meta( $existing_template->ID, 'origin', 'theme' );

		return $existing_template->ID;
	}

	$template_id = wp_insert_post(
		array(
			'post_type'    => 'wp_template',
			'post_status'  => 'publish',
			'post_title'   => __( 'Front Page', 'monopage' ),
			'post_name'    => 'front-page',
			'post_excerpt' => $template_excerpt,
			'post_content' => $content,
		),
		true
	);

	if ( is_wp_error( $template_id ) ) {
		return $template_id;
	}

	$terms = wp_set_object_terms( $template_id, $theme, 'wp_theme' );
	if ( is_wp_error( $terms ) ) {
		return $terms;
	}

	update_post_meta( $template_id, 'origin', 'theme' );

	return $template_id;
}

/**
 * Get an existing saved front-page template for a theme.
 *
 * @param string $theme Theme stylesheet slug.
 * @return WP_Post|null
 */
function monopage_get_saved_front_page_template( $theme ) {
	$template = function_exists( 'get_block_template' ) ? get_block_template( $theme . '//front-page', 'wp_template' ) : null;

	if ( ! $template || empty( $template->wp_id ) ) {
		return null;
	}

	$post = get_post( $template->wp_id );

	return $post instanceof WP_Post ? $post : null;
}

/**
 * Read the default template content from Monopage Canvas.
 *
 * @return string
 */
function monopage_get_default_front_page_template_content() {
	$theme = wp_get_theme( MONOPAGE_CANVAS_THEME );

	if ( ! $theme->exists() ) {
		return '';
	}

	$template_path = trailingslashit( $theme->get_stylesheet_directory() ) . 'templates/front-page.html';

	if ( ! file_exists( $template_path ) || ! is_readable( $template_path ) ) {
		return '';
	}

	$content = file_get_contents( $template_path );

	return false === $content ? '' : $content;
}

/**
 * Get or create the routing Home page.
 *
 * @param string $title Desired page title.
 * @return WP_Post|WP_Error
 */
function monopage_get_or_create_home_page( $title ) {
	$slug = sanitize_title( $title ? $title : 'Home' );
	$page = get_page_by_path( $slug, OBJECT, 'page' );

	if ( $page instanceof WP_Post ) {
		if ( 'publish' !== $page->post_status ) {
			$updated = wp_update_post(
				array(
					'ID'          => $page->ID,
					'post_status' => 'publish',
				),
				true
			);

			if ( is_wp_error( $updated ) ) {
				return $updated;
			}

			$page = get_post( $page->ID );
		}

		monopage_maybe_seed_home_page_placeholder( $page->ID );

		return $page;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_title'     => $title ? $title : __( 'Home', 'monopage' ),
			'post_name'      => $slug,
			'post_content'   => monopage_get_home_page_placeholder_content(),
			'comment_status' => 'closed',
			'ping_status'    => 'closed',
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		return $page_id;
	}

	return get_post( $page_id );
}

/**
 * Add a short explanation to empty routing Home pages.
 *
 * @param int $page_id Page ID.
 */
function monopage_maybe_seed_home_page_placeholder( $page_id ) {
	$page = get_post( $page_id );

	if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || '' !== trim( $page->post_content ) ) {
		return;
	}

	wp_update_post(
		array(
			'ID'           => $page_id,
			'post_content' => monopage_get_home_page_placeholder_content(),
		)
	);
}

/**
 * Get explanatory content for the routing Home page.
 *
 * @return string
 */
function monopage_get_home_page_placeholder_content() {
	return '<!-- wp:paragraph --><p>' . esc_html__( 'Monopage uses the Site Editor front-page template for visible homepage content.', 'monopage' ) . '</p><!-- /wp:paragraph -->';
}
