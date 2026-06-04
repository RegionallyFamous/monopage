<?php
/**
 * Status and validation helpers for Monopage.
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

function monopage_get_status() {
	$front_id       = absint( get_option( 'page_on_front' ) );
	$theme          = wp_get_theme();
	$front_template = ( post_type_exists( 'wp_template' ) && taxonomy_exists( 'wp_theme' ) ) ? monopage_get_saved_front_page_template( $theme->get_stylesheet() ) : null;

	return array(
		'version'          => MONOPAGE_VERSION,
		'focus_enabled'    => monopage_get_focus_enabled(),
		'show_on_front'    => get_option( 'show_on_front' ),
		'page_on_front'    => $front_id,
		'home_title'       => $front_id ? get_the_title( $front_id ) : '',
		'active_theme'     => $theme->get_stylesheet(),
		'block_theme'      => monopage_site_uses_block_theme(),
		'front_template_id' => $front_template instanceof WP_Post ? $front_template->ID : 0,
		'front_template_saved' => $front_template instanceof WP_Post,
		'site_editor_url'  => monopage_get_site_editor_url(),
		'home_url'         => home_url( '/' ),
	);
}

/**
 * Return Monopage validation checks for CLI and deployment workflows.
 *
 * @param array $args Validation arguments.
 * @return array[]
 */
function monopage_get_validation_checks( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'allow_custom_theme' => false,
			'require_focus'      => false,
			'check_http'         => false,
		)
	);

	$status        = monopage_get_status();
	$checks        = array();
	$front_id      = absint( $status['page_on_front'] );
	$front_page    = $front_id ? get_post( $front_id ) : null;
	$canvas_active = MONOPAGE_CANVAS_THEME === $status['active_theme'];

	monopage_add_validation_check(
		$checks,
		'plugin_version',
		'pass',
		'info',
		sprintf(
			/* translators: %s: Monopage version. */
			__( 'Monopage %s is active.', 'monopage' ),
			MONOPAGE_VERSION
		)
	);

	monopage_add_validation_check(
		$checks,
		'static_front_page',
		'page' === $status['show_on_front'] ? 'pass' : 'fail',
		'error',
		'page' === $status['show_on_front'] ? __( 'WordPress is configured to use a static front page.', 'monopage' ) : __( 'WordPress is not configured to use a static front page.', 'monopage' )
	);

	monopage_add_validation_check(
		$checks,
		'routing_home_page',
		$front_page instanceof WP_Post && 'page' === $front_page->post_type ? 'pass' : 'fail',
		'error',
		$front_page instanceof WP_Post ? sprintf(
			/* translators: %d: Home page ID. */
			__( 'Routing Home page exists. ID: %d.', 'monopage' ),
			$front_page->ID
		) : __( 'No routing Home page is assigned.', 'monopage' )
	);

	if ( $front_page instanceof WP_Post ) {
		monopage_add_validation_check(
			$checks,
			'routing_home_published',
			'publish' === $front_page->post_status ? 'pass' : 'fail',
			'error',
			'publish' === $front_page->post_status ? __( 'Routing Home page is published.', 'monopage' ) : __( 'Routing Home page is not published.', 'monopage' )
		);
	}

	monopage_add_validation_check(
		$checks,
		'block_theme',
		$status['block_theme'] ? 'pass' : 'fail',
		'error',
		$status['block_theme'] ? __( 'The active theme supports the Site Editor.', 'monopage' ) : __( 'The active theme does not support the Site Editor.', 'monopage' )
	);

	$theme_status = $canvas_active ? 'pass' : ( $args['allow_custom_theme'] ? 'warn' : 'fail' );
	monopage_add_validation_check(
		$checks,
		'canvas_theme',
		$theme_status,
		$args['allow_custom_theme'] ? 'warning' : 'error',
		$canvas_active ? __( 'Monopage Canvas is the active theme.', 'monopage' ) : sprintf(
			/* translators: %s: Active theme stylesheet. */
			__( 'Active theme is %s, not Monopage Canvas.', 'monopage' ),
			$status['active_theme']
		)
	);

	$template_status = $status['front_template_saved'] ? 'pass' : ( $canvas_active ? 'fail' : 'warn' );
	monopage_add_validation_check(
		$checks,
		'front_page_template',
		$template_status,
		$canvas_active ? 'error' : 'warning',
		$status['front_template_saved'] ? sprintf(
			/* translators: %d: Saved front-page template post ID. */
			__( 'Saved front-page template exists. ID: %d.', 'monopage' ),
			$status['front_template_id']
		) : __( 'Saved front-page template was not found.', 'monopage' )
	);

	$template_content = monopage_get_validation_template_content( $status['active_theme'] );
	if ( '' !== trim( $template_content ) ) {
		$link_result = monopage_validate_template_links( $template_content );
		monopage_add_validation_check(
			$checks,
			'template_links',
			$link_result['valid'] ? 'pass' : 'fail',
			'error',
			$link_result['valid'] ? sprintf(
				/* translators: %d: Number of anchors found in the front-page template. */
				__( 'Front-page template links stay on-page and target %d anchors.', 'monopage' ),
				$link_result['anchor_count']
			) : implode( ' ', $link_result['messages'] )
		);
	}

	$focus_status = $status['focus_enabled'] ? 'pass' : ( $args['require_focus'] ? 'fail' : 'warn' );
	monopage_add_validation_check(
		$checks,
		'focus_mode',
		$focus_status,
		$args['require_focus'] ? 'error' : 'warning',
		$status['focus_enabled'] ? __( 'Focus Mode is enabled.', 'monopage' ) : __( 'Focus Mode is disabled.', 'monopage' )
	);

	monopage_add_validation_check(
		$checks,
		'site_editor_url',
		false !== strpos( $status['site_editor_url'], 'site-editor.php' ) && false !== strpos( $status['site_editor_url'], 'front-page' ) ? 'pass' : 'fail',
		'error',
		$status['site_editor_url']
	);

	monopage_add_validation_check(
		$checks,
		'home_url',
		! empty( $status['home_url'] ) ? 'pass' : 'fail',
		'error',
		$status['home_url'] ? $status['home_url'] : __( 'Home URL is unavailable.', 'monopage' )
	);

	if ( $args['check_http'] ) {
		monopage_add_http_validation_check( $checks, $status['home_url'] );
	}

	return $checks;
}

/**
 * Append a normalized validation check.
 *
 * @param array  $checks   Check collection.
 * @param string $check    Machine-readable check name.
 * @param string $status   pass, warn, or fail.
 * @param string $severity info, warning, or error.
 * @param string $message  Human-readable message.
 */
function monopage_add_validation_check( &$checks, $check, $status, $severity, $message ) {
	if ( 'pass' === $status ) {
		$severity = 'info';
	} elseif ( 'warn' === $status ) {
		$severity = 'warning';
	}

	$checks[] = array(
		'check'    => $check,
		'status'   => $status,
		'severity' => $severity,
		'message'  => $message,
	);
}

/**
 * Add an optional HTTP homepage validation check.
 *
 * @param array  $checks   Check collection.
 * @param string $home_url Home URL.
 */
function monopage_add_http_validation_check( &$checks, $home_url ) {
	$response = wp_remote_get(
		$home_url,
		array(
			'redirection' => 3,
			'timeout'     => 10,
		)
	);

	if ( is_wp_error( $response ) ) {
		monopage_add_validation_check( $checks, 'home_http', 'fail', 'error', $response->get_error_message() );
		return;
	}

	$status_code = absint( wp_remote_retrieve_response_code( $response ) );
	monopage_add_validation_check(
		$checks,
		'home_http',
		$status_code >= 200 && $status_code < 400 ? 'pass' : 'fail',
		'error',
		sprintf(
			/* translators: %d: HTTP response status code. */
			__( 'Homepage HTTP response code: %d.', 'monopage' ),
			$status_code
		)
	);
}

/**
 * Check whether validation checks contain failures.
 *
 * @param array[] $checks Validation checks.
 * @return bool
 */
function monopage_validation_has_failures( $checks ) {
	foreach ( $checks as $check ) {
		if ( isset( $check['status'] ) && 'fail' === $check['status'] ) {
			return true;
		}
	}

	return false;
}

/**
 * Get template content for link validation.
 *
 * @param string $theme Theme stylesheet slug.
 * @return string
 */
function monopage_get_validation_template_content( $theme ) {
	$template = ( post_type_exists( 'wp_template' ) && taxonomy_exists( 'wp_theme' ) ) ? monopage_get_saved_front_page_template( $theme ) : null;

	if ( $template instanceof WP_Post ) {
		return (string) $template->post_content;
	}

	if ( MONOPAGE_CANVAS_THEME === $theme ) {
		return monopage_get_default_front_page_template_content();
	}

	return '';
}

/**
 * Validate that template links stay on-page and target existing anchors.
 *
 * @param string $content Template content.
 * @return array
 */
function monopage_validate_template_links( $content ) {
	return monopage_validate_content_links(
		array(
			'front-page template' => $content,
		)
	);
}

/**
 * Validate that links across content sources stay on-page and target known anchors.
 *
 * @param array $sources Content keyed by source label.
 * @return array
 */
function monopage_validate_content_links( $sources ) {
	$anchors = array();
	$links   = array();
	$errors  = array();

	foreach ( $sources as $content ) {
		foreach ( monopage_extract_template_anchors( $content ) as $anchor => $present ) {
			if ( $present ) {
				$anchors[ $anchor ] = true;
			}
		}
	}

	foreach ( $sources as $source => $content ) {
		foreach ( monopage_extract_template_links( $content ) as $link ) {
			$links[] = array(
				'source' => $source,
				'href'   => $link,
			);
		}
	}

	foreach ( $links as $link ) {
		$href = $link['href'];

		if ( 0 !== strpos( $href, '#' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Source label. 2: URL found in the content source. */
				__( '%1$s has an off-page link: %2$s.', 'monopage' ),
				$link['source'],
				$href
			);
			continue;
		}

		$target = trim( rawurldecode( substr( $href, 1 ) ) );
		if ( '' === $target || ! isset( $anchors[ $target ] ) ) {
			$errors[] = sprintf(
				/* translators: 1: Source label. 2: Hash link found in the content source. */
				__( '%1$s has a missing anchor target: %2$s.', 'monopage' ),
				$link['source'],
				$href
			);
		}
	}

	return array(
		'valid'        => empty( $errors ),
		'anchor_count' => count( $anchors ),
		'link_count'   => count( $links ),
		'source_count' => count( $sources ),
		'messages'     => $errors,
	);
}

/**
 * Extract HTML ids and block anchors from template content.
 *
 * @param string $content Template content.
 * @return array
 */
function monopage_extract_template_anchors( $content ) {
	$anchors = array();
	$matches = array();

	if ( preg_match_all( '/\bid=(["\'])(.*?)\1/', $content, $matches ) ) {
		foreach ( $matches[2] as $anchor ) {
			$anchor = trim( $anchor );
			if ( '' !== $anchor ) {
				$anchors[ $anchor ] = true;
			}
		}
	}

	if ( preg_match_all( '/"anchor"\s*:\s*"([^"]+)"/', $content, $matches ) ) {
		foreach ( $matches[1] as $anchor ) {
			$decoded = json_decode( '"' . $anchor . '"' );
			$anchor  = trim( is_string( $decoded ) ? $decoded : $anchor );
			if ( '' !== $anchor ) {
				$anchors[ $anchor ] = true;
			}
		}
	}

	return $anchors;
}

/**
 * Extract regular href links and Navigation block URLs from template content.
 *
 * @param string $content Template content.
 * @return string[]
 */
function monopage_extract_template_links( $content ) {
	$links   = array();
	$matches = array();

	if ( preg_match_all( '/href=(["\'])(.*?)\1/', $content, $matches ) ) {
		foreach ( $matches[2] as $link ) {
			$link = trim( $link );
			if ( '' !== $link ) {
				$links[] = $link;
			}
		}
	}

	if ( preg_match_all( '/<!--\s+wp:navigation-link\s+({.*?})\s+\/-->/s', $content, $matches ) ) {
		foreach ( $matches[1] as $attributes_json ) {
			$attributes = json_decode( $attributes_json, true );
			if ( is_array( $attributes ) && ! empty( $attributes['url'] ) && is_scalar( $attributes['url'] ) ) {
				$links[] = trim( (string) $attributes['url'] );
			}
		}
	}

	return array_values( array_unique( $links ) );
}
