<?php
/**
 * Plugin Name:       Monopage
 * Plugin URI:        https://github.com/RegionallyFamous/monopage
 * Description:       Monopage focuses WordPress around the Site Editor and a single homepage template.
 * Version:           0.2.43
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            WeirdPress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       monopage
 *
 * @package Monopage
 */

defined( 'ABSPATH' ) || exit;

define( 'MONOPAGE_VERSION', '0.2.43' );
define( 'MONOPAGE_FILE', __FILE__ );
define( 'MONOPAGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MONOPAGE_URL', plugin_dir_url( __FILE__ ) );
define( 'MONOPAGE_FOCUS_OPTION', 'monopage_focus_enabled' );
define( 'MONOPAGE_VERSION_OPTION', 'monopage_version' );
define( 'MONOPAGE_FULL_DASHBOARD_META', 'monopage_full_dashboard' );
define( 'MONOPAGE_CANVAS_THEME', 'monopage-canvas' );
define( 'MONOPAGE_LEGACY_FOCUS_OPTION', 'wpop_focus_enabled' );
define( 'MONOPAGE_LEGACY_VERSION_OPTION', 'wpop_version' );
define( 'MONOPAGE_LEGACY_FULL_DASHBOARD_META', 'wpop_full_dashboard' );

register_activation_hook( __FILE__, 'monopage_activate' );

add_action( 'admin_menu', 'monopage_register_admin_menu', 5 );
add_action( 'admin_menu', 'monopage_prune_admin_menu', 999 );
add_action( 'admin_init', 'monopage_maybe_redirect_home_page_editor', 1 );
add_action( 'admin_init', 'monopage_maybe_redirect_admin' );
add_action( 'admin_enqueue_scripts', 'monopage_enqueue_admin_assets' );
add_action( 'admin_notices', 'monopage_render_focus_notice' );
add_action( 'admin_bar_menu', 'monopage_prune_admin_bar', 999 );
add_action( 'admin_post_monopage_toggle_focus', 'monopage_handle_toggle_focus' );
add_action( 'admin_post_monopage_toggle_full_dashboard', 'monopage_handle_toggle_full_dashboard' );
add_action( 'admin_post_monopage_run_setup', 'monopage_handle_run_setup' );
add_action( 'after_switch_theme', 'monopage_maybe_setup_canvas_defaults' );
add_filter( 'admin_body_class', 'monopage_admin_body_class' );
add_filter( 'login_redirect', 'monopage_login_redirect', 10, 3 );

/**
 * Register default plugin options.
 */
function monopage_activate() {
	monopage_migrate_legacy_settings();

	add_option( MONOPAGE_FOCUS_OPTION, '1' );
	update_option( MONOPAGE_VERSION_OPTION, MONOPAGE_VERSION );

	monopage_setup_one_pager();
}

/**
 * Copy early WPOP settings into the new Monopage option names.
 */
function monopage_migrate_legacy_settings() {
	$legacy_focus = get_option( MONOPAGE_LEGACY_FOCUS_OPTION, null );

	if ( null !== $legacy_focus && null === get_option( MONOPAGE_FOCUS_OPTION, null ) ) {
		update_option( MONOPAGE_FOCUS_OPTION, $legacy_focus );
	}

	$legacy_version = get_option( MONOPAGE_LEGACY_VERSION_OPTION, null );

	if ( null !== $legacy_version && null === get_option( MONOPAGE_VERSION_OPTION, null ) ) {
		update_option( MONOPAGE_VERSION_OPTION, $legacy_version );
	}
}

/**
 * Register the Monopage control center.
 */
function monopage_register_admin_menu() {
	add_menu_page(
		__( 'Monopage', 'monopage' ),
		__( 'Monopage', 'monopage' ),
		'edit_theme_options',
		'monopage',
		'monopage_render_admin_page',
		'dashicons-welcome-widgets-menus',
		3
	);

	add_submenu_page(
		'monopage',
		__( 'Edit Homepage', 'monopage' ),
		__( 'Edit Homepage', 'monopage' ),
		'edit_theme_options',
		'monopage-edit-homepage',
		'monopage_render_edit_homepage_redirect'
	);
}

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

	if ( ! current_user_can( 'manage_options' ) ) {
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'options-general.php' );
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

	foreach ( array( 'comments', 'new-content', 'customize', 'themes', 'widgets', 'menus', 'edit' ) as $node_id ) {
		$wp_admin_bar->remove_node( $node_id );
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'monopage-edit-homepage',
			'title' => __( 'Edit Homepage', 'monopage' ),
			'href'  => monopage_get_site_editor_url(),
			'meta'  => array(
				'class' => 'monopage-admin-bar-edit-homepage',
			),
		)
	);
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

	if ( 'admin.php' === $pagenow && 0 === strpos( monopage_get_query_key( 'page' ), 'monopage' ) ) {
		return;
	}

	if ( in_array( $pagenow, monopage_get_allowed_focus_pages(), true ) ) {
		return;
	}

	if ( 'admin.php' === $pagenow || in_array( $pagenow, monopage_get_redirected_admin_pages(), true ) ) {
		wp_safe_redirect( monopage_get_site_editor_url() );
		exit;
	}
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

	$full_dashboard = get_user_meta( $user->ID, MONOPAGE_FULL_DASHBOARD_META, true );

	if ( '' === $full_dashboard ) {
		$full_dashboard = get_user_meta( $user->ID, MONOPAGE_LEGACY_FULL_DASHBOARD_META, true );
	}

	if ( monopage_get_focus_enabled() && ! $full_dashboard ) {
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
	if ( false === strpos( $hook_suffix, 'monopage' ) && ! monopage_is_focus_active_for_current_user() ) {
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
 * Read a checkbox-style POST flag.
 *
 * @param string $key POST field name.
 * @return bool
 */
function monopage_get_post_flag( $key ) {
	$value = monopage_get_request_value( $_POST, $key ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

	return '1' === sanitize_text_field( $value );
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
 * Render a small Focus Mode notice for admins.
 */
function monopage_render_focus_notice() {
	if ( ! monopage_is_focus_active_for_current_user() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && false !== strpos( $screen->id, 'monopage' ) ) {
		return;
	}

	?>
	<div class="notice notice-info monopage-focus-notice">
		<p>
			<strong><?php esc_html_e( 'Monopage Focus Mode is active.', 'monopage' ); ?></strong>
			<?php esc_html_e( 'WordPress is still here; Monopage is only narrowing the interface.', 'monopage' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=monopage' ) ); ?>"><?php esc_html_e( 'Open Monopage controls', 'monopage' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Render the Monopage control center.
 */
function monopage_render_admin_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage Monopage.', 'monopage' ) );
	}

	$status             = monopage_get_status();
	$focus_enabled      = monopage_get_focus_enabled();
	$full_dashboard     = monopage_current_user_has_full_dashboard();
	$can_manage_options = current_user_can( 'manage_options' );
	$setup_result       = monopage_get_query_key( 'monopage_setup' );
	$focus_result       = monopage_get_query_key( 'monopage_focus' );
	$dashboard_result   = monopage_get_query_key( 'monopage_dashboard' );
	$setup_error        = get_transient( 'monopage_setup_error_' . get_current_user_id() );

	if ( $setup_error ) {
		delete_transient( 'monopage_setup_error_' . get_current_user_id() );
	}

	?>
	<div class="wrap monopage-wrap">
		<h1><?php esc_html_e( 'Monopage', 'monopage' ); ?></h1>
		<p class="monopage-lede"><?php esc_html_e( 'Monopage keeps the authoring experience focused on the editable homepage template.', 'monopage' ); ?></p>

		<?php if ( 'error' === $setup_result ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $setup_error ? $setup_error : __( 'Monopage setup failed.', 'monopage' ) ); ?></p></div>
		<?php elseif ( $setup_result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Monopage setup finished.', 'monopage' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $focus_result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Focus Mode setting updated.', 'monopage' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $dashboard_result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Dashboard preference updated.', 'monopage' ); ?></p></div>
		<?php endif; ?>

		<div class="monopage-grid">
			<section class="monopage-panel">
				<h2><?php esc_html_e( 'Homepage', 'monopage' ); ?></h2>
				<dl class="monopage-status-list">
					<div>
						<dt><?php esc_html_e( 'Static front page', 'monopage' ); ?></dt>
						<dd><?php echo esc_html( $status['home_title'] ? $status['home_title'] : __( 'Not set', 'monopage' ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Active theme', 'monopage' ); ?></dt>
						<dd><?php echo esc_html( $status['active_theme'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Block theme', 'monopage' ); ?></dt>
						<dd><?php echo esc_html( monopage_bool_label( $status['block_theme'] ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Editable template', 'monopage' ); ?></dt>
						<dd><?php echo esc_html( $status['front_template_saved'] ? sprintf(
							/* translators: %d: Saved front-page template post ID. */
							__( 'Front Page #%d', 'monopage' ),
							$status['front_template_id']
						) : __( 'Theme file fallback', 'monopage' ) ); ?></dd>
					</div>
				</dl>
				<p class="description"><?php esc_html_e( 'The visible homepage is edited in the Site Editor front-page template. The Home page is only the WordPress routing page.', 'monopage' ); ?></p>
				<p class="monopage-actions">
					<a class="button button-primary" href="<?php echo esc_url( monopage_get_site_editor_url() ); ?>"><?php esc_html_e( 'Edit Homepage', 'monopage' ); ?></a>
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View Site', 'monopage' ); ?></a>
				</p>
			</section>

			<section class="monopage-panel">
				<h2><?php esc_html_e( 'Setup', 'monopage' ); ?></h2>
				<p><?php esc_html_e( 'Setup creates or reuses a Home page and points WordPress reading settings at it. Existing static homepages are preserved unless forced.', 'monopage' ); ?></p>
				<?php if ( $can_manage_options ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'monopage_run_setup' ); ?>
						<input type="hidden" name="action" value="monopage_run_setup">
						<label class="monopage-checkbox">
							<input type="checkbox" name="force_home" value="1">
							<?php esc_html_e( 'Replace the current static front page assignment', 'monopage' ); ?>
						</label>
						<label class="monopage-checkbox">
							<input type="checkbox" name="force_template" value="1">
							<?php esc_html_e( 'Refresh the editable front-page template from Monopage Canvas', 'monopage' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Refreshing the template replaces saved Site Editor changes for the front-page template.', 'monopage' ); ?></p>
						<p><button class="button" type="submit"><?php esc_html_e( 'Run Setup', 'monopage' ); ?></button></p>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'Ask an administrator to run Monopage setup.', 'monopage' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="monopage-panel">
				<h2><?php esc_html_e( 'Focus Mode', 'monopage' ); ?></h2>
				<dl class="monopage-status-list">
					<div>
						<dt><?php esc_html_e( 'Global focus', 'monopage' ); ?></dt>
						<dd><?php echo esc_html( monopage_bool_label( $focus_enabled ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Your full dashboard escape', 'monopage' ); ?></dt>
						<dd><?php echo esc_html( monopage_bool_label( $full_dashboard ) ); ?></dd>
					</div>
				</dl>

				<?php if ( $can_manage_options ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'monopage_toggle_focus' ); ?>
						<input type="hidden" name="action" value="monopage_toggle_focus">
						<input type="hidden" name="enabled" value="<?php echo esc_attr( $focus_enabled ? '0' : '1' ); ?>">
						<p><button class="button" type="submit"><?php echo esc_html( $focus_enabled ? __( 'Disable Focus Mode', 'monopage' ) : __( 'Enable Focus Mode', 'monopage' ) ); ?></button></p>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'monopage_toggle_full_dashboard' ); ?>
						<input type="hidden" name="action" value="monopage_toggle_full_dashboard">
						<input type="hidden" name="enabled" value="<?php echo esc_attr( $full_dashboard ? '0' : '1' ); ?>">
						<p><button class="button" type="submit"><?php echo esc_html( $full_dashboard ? __( 'Return To Focus Mode', 'monopage' ) : __( 'Use Full WordPress Dashboard', 'monopage' ) ); ?></button></p>
					</form>
				<?php endif; ?>
			</section>

			<section class="monopage-panel">
				<h2><?php esc_html_e( 'WP-CLI', 'monopage' ); ?></h2>
				<pre><code>wp monopage status
wp monopage setup
wp monopage setup --force-home
wp monopage setup --force-template
wp monopage focus enable
wp monopage focus disable</code></pre>
			</section>
		</div>
	</div>
	<?php
}

/**
 * Redirect the submenu page directly to the Site Editor.
 */
function monopage_render_edit_homepage_redirect() {
	wp_safe_redirect( monopage_get_site_editor_url() );
	exit;
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
 * Handle Focus Mode global toggle.
 */
function monopage_handle_toggle_focus() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change Monopage settings.', 'monopage' ) );
	}

	check_admin_referer( 'monopage_toggle_focus' );

	$enabled = monopage_get_post_flag( 'enabled' );
	update_option( MONOPAGE_FOCUS_OPTION, $enabled ? '1' : '0' );

	wp_safe_redirect( add_query_arg( 'monopage_focus', $enabled ? 'enabled' : 'disabled', admin_url( 'admin.php?page=monopage' ) ) );
	exit;
}

/**
 * Handle per-admin full dashboard escape toggle.
 */
function monopage_handle_toggle_full_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change Monopage settings.', 'monopage' ) );
	}

	check_admin_referer( 'monopage_toggle_full_dashboard' );

	$enabled = monopage_get_post_flag( 'enabled' );
	update_user_meta( get_current_user_id(), MONOPAGE_FULL_DASHBOARD_META, $enabled ? '1' : '0' );

	wp_safe_redirect( add_query_arg( 'monopage_dashboard', $enabled ? 'full' : 'focus', admin_url( 'admin.php?page=monopage' ) ) );
	exit;
}

/**
 * Handle setup from the Monopage admin page.
 */
function monopage_handle_run_setup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run Monopage setup.', 'monopage' ) );
	}

	check_admin_referer( 'monopage_run_setup' );

	$result = monopage_setup_one_pager(
		array(
			'force_home'     => monopage_get_post_flag( 'force_home' ),
			'force_template' => monopage_get_post_flag( 'force_template' ),
		)
	);

	if ( is_wp_error( $result ) ) {
		set_transient( 'monopage_setup_error_' . get_current_user_id(), $result->get_error_message(), MINUTE_IN_SECONDS );
	}

	$query_args = array( 'monopage_setup' => is_wp_error( $result ) ? 'error' : 'done' );
	wp_safe_redirect( add_query_arg( $query_args, admin_url( 'admin.php?page=monopage' ) ) );
	exit;
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

	if ( $existing_template instanceof WP_Post ) {
		$updated_template_id = wp_update_post(
			array(
				'ID'           => $existing_template->ID,
				'post_status'  => 'publish',
				'post_title'   => __( 'Front Page', 'monopage' ),
				'post_excerpt' => __( 'Default Monopage one-page marketing homepage.', 'monopage' ),
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
			'post_excerpt' => __( 'Default Monopage one-page marketing homepage.', 'monopage' ),
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
	return '<!-- wp:paragraph --><p>' . esc_html__( 'Monopage uses the Site Editor front-page template for visible homepage content. Edit the homepage from Monopage > Edit Homepage.', 'monopage' ) . '</p><!-- /wp:paragraph -->';
}

/**
 * Return the current Monopage status.
 *
 * @return array
 */
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

	if ( $canvas_active && class_exists( 'WP_Block_Patterns_Registry' ) ) {
		$pattern_count = monopage_get_registered_canvas_pattern_count();
		monopage_add_validation_check(
			$checks,
			'canvas_patterns',
			$pattern_count > 0 ? 'pass' : 'fail',
			'error',
			sprintf(
				/* translators: %d: Number of registered Monopage Canvas patterns. */
				__( '%d Monopage Canvas patterns are registered.', 'monopage' ),
				$pattern_count
			)
		);

		if ( $pattern_count > 0 ) {
			$pattern_link_result = monopage_validate_canvas_pattern_links( $template_content );
			monopage_add_validation_check(
				$checks,
				'canvas_pattern_links',
				$pattern_link_result['valid'] ? 'pass' : 'fail',
				'error',
				$pattern_link_result['valid'] ? sprintf(
					/* translators: 1: Number of Monopage Canvas patterns. 2: Number of anchors available across the template and patterns. */
					__( '%1$d Monopage Canvas patterns keep links on-page across %2$d known anchors.', 'monopage' ),
					$pattern_count,
					$pattern_link_result['anchor_count']
				) : implode( ' ', $pattern_link_result['messages'] )
			);
		}
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
 * Validate that Canvas pattern links stay on-page and target known anchors.
 *
 * @param string $template_content Saved or fallback front-page template content.
 * @return array
 */
function monopage_validate_canvas_pattern_links( $template_content = '' ) {
	$sources = array();

	if ( '' !== trim( $template_content ) ) {
		$sources['front-page template'] = $template_content;
	}

	foreach ( monopage_get_registered_canvas_patterns() as $pattern ) {
		if ( ! empty( $pattern['content'] ) && is_scalar( $pattern['content'] ) ) {
			$sources[ $pattern['name'] ] = (string) $pattern['content'];
		}
	}

	return monopage_validate_content_links( $sources );
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

/**
 * Count registered Monopage Canvas patterns.
 *
 * @return int
 */
function monopage_get_registered_canvas_pattern_count() {
	return count( monopage_get_registered_canvas_patterns() );
}

/**
 * Get registered Monopage Canvas patterns.
 *
 * @return array[]
 */
function monopage_get_registered_canvas_patterns() {
	if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
		return array();
	}

	$patterns = WP_Block_Patterns_Registry::get_instance()->get_all_registered();
	$canvas_patterns = array();

	foreach ( $patterns as $pattern ) {
		if ( isset( $pattern['name'] ) && 0 === strpos( $pattern['name'], 'monopage-canvas/' ) ) {
			$canvas_patterns[] = $pattern;
		}
	}

	return $canvas_patterns;
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

/**
 * Get global Focus Mode state.
 *
 * @return bool
 */
function monopage_get_focus_enabled() {
	$value = get_option( MONOPAGE_FOCUS_OPTION, null );

	if ( null === $value ) {
		$value = get_option( MONOPAGE_LEGACY_FOCUS_OPTION, '1' );
	}

	return (bool) $value;
}

/**
 * Check whether the current admin has enabled the full-dashboard escape hatch.
 *
 * @return bool
 */
function monopage_current_user_has_full_dashboard() {
	$user_id = get_current_user_id();

	if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	$value = get_user_meta( $user_id, MONOPAGE_FULL_DASHBOARD_META, true );

	if ( '' === $value ) {
		$value = get_user_meta( $user_id, MONOPAGE_LEGACY_FULL_DASHBOARD_META, true );
	}

	return (bool) $value;
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

	if ( current_user_can( 'manage_options' ) && monopage_current_user_has_full_dashboard() ) {
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
		'upload.php',
		'media-new.php',
		'async-upload.php',
		'admin-ajax.php',
		'admin-post.php',
		'plugins.php',
		'plugin-install.php',
		'users.php',
		'user-new.php',
		'profile.php',
		'options-general.php',
		'options-reading.php',
		'options-permalink.php',
		'update-core.php',
		'update.php',
	);
}

/**
 * Admin pages redirected to the Site Editor during Focus Mode.
 *
 * @return string[]
 */
function monopage_get_redirected_admin_pages() {
	return array(
		'index.php',
		'edit.php',
		'post.php',
		'post-new.php',
		'edit-comments.php',
		'themes.php',
		'customize.php',
		'widgets.php',
		'nav-menus.php',
		'tools.php',
	);
}

/**
 * Human-readable boolean label.
 *
 * @param bool $value Boolean value.
 * @return string
 */
function monopage_bool_label( $value ) {
	return $value ? __( 'Yes', 'monopage' ) : __( 'No', 'monopage' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
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
}
