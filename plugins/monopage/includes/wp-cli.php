<?php
/**
 * WP-CLI commands for Monopage.
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Monopage WP-CLI commands.
 */
class Monopage_CLI_Command {
	/**
	 * Show Monopage status.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format. table, json, csv, yaml, or count.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function status( $args, $assoc_args ) {
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$status = monopage_get_status();

		\WP_CLI\Utils\format_items( $format, array( $status ), array_keys( $status ) );
	}

	/**
	 * Validate the current Monopage setup.
	 *
	 * ## OPTIONS
	 *
	 * [--allow-custom-theme]
	 * : Treat a non-Canvas block theme as a warning instead of a failure.
	 *
	 * [--require-focus]
	 * : Treat disabled Focus Mode as a failure instead of a warning.
	 *
	 * [--check-http]
	 * : Request the homepage and fail on non-2xx/3xx responses.
	 *
	 * [--format=<format>]
	 * : Output format. table, json, csv, yaml, or count.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function validate( $args, $assoc_args ) {
		$format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';
		$checks = monopage_get_validation_checks(
			array(
				'allow_custom_theme' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'allow-custom-theme', false ),
				'require_focus'      => \WP_CLI\Utils\get_flag_value( $assoc_args, 'require-focus', false ),
				'check_http'         => \WP_CLI\Utils\get_flag_value( $assoc_args, 'check-http', false ),
			)
		);

		\WP_CLI\Utils\format_items( $format, $checks, array( 'check', 'status', 'severity', 'message' ) );

		if ( monopage_validation_has_failures( $checks ) ) {
			if ( 'json' !== $format ) {
				\WP_CLI::warning( 'Monopage validation failed.' );
			}

			\WP_CLI::halt( 1 );
		}

		if ( 'table' === $format ) {
			\WP_CLI::success( 'Monopage validation passed.' );
		}
	}

	/**
	 * Set up the one-page homepage routing.
	 *
	 * ## OPTIONS
	 *
	 * [--force-home]
	 * : Replace the current static front page assignment.
	 *
	 * [--force-template]
	 * : Replace the saved Site Editor front-page template with the current Monopage Canvas default.
	 *
	 * [--home-title=<title>]
	 * : Home page title. Default: Home.
	 *
	 * [--activate-theme]
	 * : Activate Monopage Canvas before setup.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function setup( $args, $assoc_args ) {
		if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'activate-theme', false ) ) {
			$theme = wp_get_theme( MONOPAGE_CANVAS_THEME );
			if ( ! $theme->exists() ) {
				\WP_CLI::error( 'Monopage Canvas theme is not installed.' );
			}

			switch_theme( MONOPAGE_CANVAS_THEME );
			\WP_CLI::log( 'Activated Monopage Canvas theme.' );
		}

		$result = monopage_setup_one_pager(
			array(
				'force_home'     => \WP_CLI\Utils\get_flag_value( $assoc_args, 'force-home', false ),
				'force_template' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'force-template', false ),
				'home_title'     => isset( $assoc_args['home-title'] ) ? $assoc_args['home-title'] : 'Home',
			)
		);

		if ( is_wp_error( $result ) ) {
			\WP_CLI::error( $result->get_error_message() );
		}

		$message = $result['message'] . ' Home page ID: ' . $result['home_id'];
		if ( ! empty( $result['template_id'] ) ) {
			$message .= ' Front template ID: ' . $result['template_id'];
		}

		\WP_CLI::success( $message );
	}

	/**
	 * Enable or disable Focus Mode.
	 *
	 * ## OPTIONS
	 *
	 * <enable|disable>
	 * : Desired Focus Mode state.
	 *
	 * @param array $args Positional args.
	 * @param array $assoc_args Associative args.
	 */
	public function focus( $args, $assoc_args ) {
		$action = isset( $args[0] ) ? $args[0] : '';

		if ( ! in_array( $action, array( 'enable', 'disable' ), true ) ) {
			\WP_CLI::error( 'Usage: wp monopage focus <enable|disable>' );
		}

		update_option( MONOPAGE_FOCUS_OPTION, 'enable' === $action ? '1' : '0' );
		\WP_CLI::success( 'Focus Mode ' . ( 'enable' === $action ? 'enabled.' : 'disabled.' ) );
	}
}

WP_CLI::add_command( 'monopage', 'Monopage_CLI_Command' );
