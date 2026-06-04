<?php
/**
 * Plugin Name:       Monopage
 * Plugin URI:        https://github.com/RegionallyFamous/monopage
 * Description:       Monopage focuses WordPress around the Site Editor and a single homepage template.
 * Version:           0.2.7
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

define( 'MONOPAGE_VERSION', '0.2.7' );
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

	if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 0 === strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'monopage' ) ) {
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
	$setup_result       = isset( $_GET['monopage_setup'] ) ? sanitize_key( wp_unslash( $_GET['monopage_setup'] ) ) : '';
	$focus_result       = isset( $_GET['monopage_focus'] ) ? sanitize_key( wp_unslash( $_GET['monopage_focus'] ) ) : '';
	$dashboard_result   = isset( $_GET['monopage_dashboard'] ) ? sanitize_key( wp_unslash( $_GET['monopage_dashboard'] ) ) : '';
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
						<dd><?php echo esc_html( $status['front_template_saved'] ? sprintf( __( 'Front Page #%d', 'monopage' ), $status['front_template_id'] ) : __( 'Theme file fallback', 'monopage' ) ); ?></dd>
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
						<input type="hidden" name="enabled" value="<?php echo $focus_enabled ? '0' : '1'; ?>">
						<p><button class="button" type="submit"><?php echo esc_html( $focus_enabled ? __( 'Disable Focus Mode', 'monopage' ) : __( 'Enable Focus Mode', 'monopage' ) ); ?></button></p>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'monopage_toggle_full_dashboard' ); ?>
						<input type="hidden" name="action" value="monopage_toggle_full_dashboard">
						<input type="hidden" name="enabled" value="<?php echo $full_dashboard ? '0' : '1'; ?>">
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

	$post_id = isset( $_GET['post'] ) && is_scalar( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0;
	$action  = isset( $_GET['action'] ) && is_scalar( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';

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

	$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
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

	$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
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
			'force_home'     => isset( $_POST['force_home'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['force_home'] ) ),
			'force_template' => isset( $_POST['force_template'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['force_template'] ) ),
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
	$query = new WP_Query(
		array(
			'post_type'              => 'wp_template',
			'post_status'            => 'any',
			'post_name__in'          => array( 'front-page' ),
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => $theme,
				),
			),
		)
	);

	return isset( $query->posts[0] ) && $query->posts[0] instanceof WP_Post ? $query->posts[0] : null;
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
		$actual = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		if ( $expected !== $actual ) {
			$is_target = false;
			break;
		}
	}

	if ( $is_target ) {
		return;
	}

	$reserved_keys = array_unique( array_merge( array_keys( $query_args ), array( 'canvas', 'postType', 'postId', 'p' ) ) );

	foreach ( $_GET as $key => $value ) {
		if ( ! is_scalar( $value ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', (string) $key ) ) {
			continue;
		}

		if ( in_array( (string) $key, $reserved_keys, true ) ) {
			continue;
		}

		$query_args[ (string) $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
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
	WP_CLI::add_command( 'wpop', 'Monopage_CLI_Command' );
}

if ( ! function_exists( 'wpop_setup_one_pager' ) ) {
	/**
	 * Legacy WPOP setup alias.
	 *
	 * @param array $args Setup arguments.
	 * @return array|WP_Error
	 */
	function wpop_setup_one_pager( $args = array() ) {
		return monopage_setup_one_pager( $args );
	}
}

if ( ! function_exists( 'wpop_get_status' ) ) {
	/**
	 * Legacy WPOP status alias.
	 *
	 * @return array
	 */
	function wpop_get_status() {
		return monopage_get_status();
	}
}

if ( ! function_exists( 'wpop_get_site_editor_url' ) ) {
	/**
	 * Legacy WPOP Site Editor URL alias.
	 *
	 * @return string
	 */
	function wpop_get_site_editor_url() {
		return monopage_get_site_editor_url();
	}
}
