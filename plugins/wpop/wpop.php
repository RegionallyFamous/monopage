<?php
/**
 * Plugin Name:       WPOP
 * Plugin URI:        https://github.com/RegionallyFamous/wpop
 * Description:       WordPress One Pager focuses WordPress around the Site Editor and a single homepage template.
 * Version:           0.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            WeirdPress
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpop
 *
 * @package WPOP
 */

defined( 'ABSPATH' ) || exit;

define( 'WPOP_VERSION', '0.1.0' );
define( 'WPOP_FILE', __FILE__ );
define( 'WPOP_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPOP_URL', plugin_dir_url( __FILE__ ) );
define( 'WPOP_FOCUS_OPTION', 'wpop_focus_enabled' );
define( 'WPOP_VERSION_OPTION', 'wpop_version' );
define( 'WPOP_FULL_DASHBOARD_META', 'wpop_full_dashboard' );
define( 'WPOP_CANVAS_THEME', 'wpop-canvas' );

register_activation_hook( __FILE__, 'wpop_activate' );

add_action( 'admin_menu', 'wpop_register_admin_menu', 5 );
add_action( 'admin_menu', 'wpop_prune_admin_menu', 999 );
add_action( 'admin_init', 'wpop_maybe_redirect_admin' );
add_action( 'admin_enqueue_scripts', 'wpop_enqueue_admin_assets' );
add_action( 'admin_notices', 'wpop_render_focus_notice' );
add_action( 'admin_bar_menu', 'wpop_prune_admin_bar', 999 );
add_action( 'admin_post_wpop_toggle_focus', 'wpop_handle_toggle_focus' );
add_action( 'admin_post_wpop_toggle_full_dashboard', 'wpop_handle_toggle_full_dashboard' );
add_action( 'admin_post_wpop_run_setup', 'wpop_handle_run_setup' );
add_filter( 'admin_body_class', 'wpop_admin_body_class' );
add_filter( 'login_redirect', 'wpop_login_redirect', 10, 3 );

/**
 * Register default plugin options.
 */
function wpop_activate() {
	add_option( WPOP_FOCUS_OPTION, '1' );
	update_option( WPOP_VERSION_OPTION, WPOP_VERSION );
}

/**
 * Register the WPOP control center.
 */
function wpop_register_admin_menu() {
	add_menu_page(
		__( 'WPOP', 'wpop' ),
		__( 'WPOP', 'wpop' ),
		'edit_theme_options',
		'wpop',
		'wpop_render_admin_page',
		'dashicons-welcome-widgets-menus',
		3
	);

	add_submenu_page(
		'wpop',
		__( 'Edit Homepage', 'wpop' ),
		__( 'Edit Homepage', 'wpop' ),
		'edit_theme_options',
		'wpop-edit-homepage',
		'wpop_render_edit_homepage_redirect'
	);
}

/**
 * Remove distracting admin menus when Focus Mode is active.
 *
 * This is interface cleanup only. WordPress capabilities still control access.
 */
function wpop_prune_admin_menu() {
	if ( ! wpop_is_focus_active_for_current_user() ) {
		return;
	}

	foreach ( wpop_get_hidden_menu_slugs() as $menu_slug ) {
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
function wpop_prune_admin_bar( $wp_admin_bar ) {
	if ( ! wpop_is_focus_active_for_current_user() ) {
		return;
	}

	foreach ( array( 'comments', 'new-content', 'customize', 'themes', 'widgets', 'menus', 'edit' ) as $node_id ) {
		$wp_admin_bar->remove_node( $node_id );
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'wpop-edit-homepage',
			'title' => __( 'Edit Homepage', 'wpop' ),
			'href'  => wpop_get_site_editor_url(),
			'meta'  => array(
				'class' => 'wpop-admin-bar-edit-homepage',
			),
		)
	);
}

/**
 * Redirect generic admin surfaces to the Site Editor in Focus Mode.
 */
function wpop_maybe_redirect_admin() {
	if ( ! is_admin() || ! wpop_is_focus_active_for_current_user() ) {
		return;
	}

	if ( ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) || ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) ) {
		return;
	}

	global $pagenow;

	if ( 'site-editor.php' === $pagenow ) {
		wpop_maybe_redirect_site_editor_to_canvas();
		return;
	}

	if ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && 0 === strpos( sanitize_key( wp_unslash( $_GET['page'] ) ), 'wpop' ) ) {
		return;
	}

	if ( in_array( $pagenow, wpop_get_allowed_focus_pages(), true ) ) {
		return;
	}

	if ( 'admin.php' === $pagenow || in_array( $pagenow, wpop_get_redirected_admin_pages(), true ) ) {
		wp_safe_redirect( wpop_get_site_editor_url() );
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
function wpop_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
	if ( ! $user instanceof WP_User || ! user_can( $user, 'edit_theme_options' ) ) {
		return $redirect_to;
	}

	if ( wpop_get_focus_enabled() && ! get_user_meta( $user->ID, WPOP_FULL_DASHBOARD_META, true ) ) {
		return wpop_get_site_editor_url();
	}

	return $redirect_to;
}

/**
 * Enqueue WPOP admin styles.
 *
 * @param string $hook_suffix Current admin page hook.
 */
function wpop_enqueue_admin_assets( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'wpop' ) && ! wpop_is_focus_active_for_current_user() ) {
		return;
	}

	wp_enqueue_style(
		'wpop-admin',
		WPOP_URL . 'assets/admin.css',
		array(),
		WPOP_VERSION
	);

	if ( wpop_is_site_editor_admin_screen( $hook_suffix ) && wpop_is_focus_active_for_current_user() ) {
		wp_enqueue_script(
			'wpop-site-editor',
			WPOP_URL . 'assets/site-editor.js',
			array( 'wp-data', 'wp-dom-ready', 'wp-preferences' ),
			WPOP_VERSION,
			true
		);
	}
}

/**
 * Add WPOP admin state classes.
 *
 * @param string $classes Space-separated admin body classes.
 * @return string
 */
function wpop_admin_body_class( $classes ) {
	if ( wpop_is_focus_active_for_current_user() ) {
		$classes .= ' wpop-focus-active wpop-sidebar-hidden';
	}

	return $classes;
}

/**
 * Check whether the current admin screen is the Site Editor.
 *
 * @param string $hook_suffix Current admin page hook.
 * @return bool
 */
function wpop_is_site_editor_admin_screen( $hook_suffix ) {
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
function wpop_render_focus_notice() {
	if ( ! wpop_is_focus_active_for_current_user() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && false !== strpos( $screen->id, 'wpop' ) ) {
		return;
	}

	?>
	<div class="notice notice-info wpop-focus-notice">
		<p>
			<strong><?php esc_html_e( 'WPOP Focus Mode is active.', 'wpop' ); ?></strong>
			<?php esc_html_e( 'WordPress is still here; WPOP is only narrowing the interface.', 'wpop' ); ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpop' ) ); ?>"><?php esc_html_e( 'Open WPOP controls', 'wpop' ); ?></a>
		</p>
	</div>
	<?php
}

/**
 * Render the WPOP control center.
 */
function wpop_render_admin_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to manage WPOP.', 'wpop' ) );
	}

	$status             = wpop_get_status();
	$focus_enabled      = wpop_get_focus_enabled();
	$full_dashboard     = wpop_current_user_has_full_dashboard();
	$can_manage_options = current_user_can( 'manage_options' );
	$setup_result       = isset( $_GET['wpop_setup'] ) ? sanitize_key( wp_unslash( $_GET['wpop_setup'] ) ) : '';
	$focus_result       = isset( $_GET['wpop_focus'] ) ? sanitize_key( wp_unslash( $_GET['wpop_focus'] ) ) : '';
	$dashboard_result   = isset( $_GET['wpop_dashboard'] ) ? sanitize_key( wp_unslash( $_GET['wpop_dashboard'] ) ) : '';
	$setup_error        = get_transient( 'wpop_setup_error_' . get_current_user_id() );

	if ( $setup_error ) {
		delete_transient( 'wpop_setup_error_' . get_current_user_id() );
	}

	?>
	<div class="wrap wpop-wrap">
		<h1><?php esc_html_e( 'WPOP', 'wpop' ); ?></h1>
		<p class="wpop-lede"><?php esc_html_e( 'WordPress One Pager keeps the authoring experience focused on the editable homepage template.', 'wpop' ); ?></p>

		<?php if ( 'error' === $setup_result ) : ?>
			<div class="notice notice-error inline"><p><?php echo esc_html( $setup_error ? $setup_error : __( 'WPOP setup failed.', 'wpop' ) ); ?></p></div>
		<?php elseif ( $setup_result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'WPOP setup finished.', 'wpop' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $focus_result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Focus Mode setting updated.', 'wpop' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $dashboard_result ) : ?>
			<div class="notice notice-success inline"><p><?php esc_html_e( 'Dashboard preference updated.', 'wpop' ); ?></p></div>
		<?php endif; ?>

		<div class="wpop-grid">
			<section class="wpop-panel">
				<h2><?php esc_html_e( 'Homepage', 'wpop' ); ?></h2>
				<dl class="wpop-status-list">
					<div>
						<dt><?php esc_html_e( 'Static front page', 'wpop' ); ?></dt>
						<dd><?php echo esc_html( $status['home_title'] ? $status['home_title'] : __( 'Not set', 'wpop' ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Active theme', 'wpop' ); ?></dt>
						<dd><?php echo esc_html( $status['active_theme'] ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Block theme', 'wpop' ); ?></dt>
						<dd><?php echo esc_html( wpop_bool_label( $status['block_theme'] ) ); ?></dd>
					</div>
				</dl>
				<p class="wpop-actions">
					<a class="button button-primary" href="<?php echo esc_url( wpop_get_site_editor_url() ); ?>"><?php esc_html_e( 'Edit Homepage', 'wpop' ); ?></a>
					<a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View Site', 'wpop' ); ?></a>
				</p>
			</section>

			<section class="wpop-panel">
				<h2><?php esc_html_e( 'Setup', 'wpop' ); ?></h2>
				<p><?php esc_html_e( 'Setup creates or reuses a Home page and points WordPress reading settings at it. Existing static homepages are preserved unless forced.', 'wpop' ); ?></p>
				<?php if ( $can_manage_options ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wpop_run_setup' ); ?>
						<input type="hidden" name="action" value="wpop_run_setup">
						<label class="wpop-checkbox">
							<input type="checkbox" name="force_home" value="1">
							<?php esc_html_e( 'Replace the current static front page assignment', 'wpop' ); ?>
						</label>
						<p><button class="button" type="submit"><?php esc_html_e( 'Run Setup', 'wpop' ); ?></button></p>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'Ask an administrator to run WPOP setup.', 'wpop' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="wpop-panel">
				<h2><?php esc_html_e( 'Focus Mode', 'wpop' ); ?></h2>
				<dl class="wpop-status-list">
					<div>
						<dt><?php esc_html_e( 'Global focus', 'wpop' ); ?></dt>
						<dd><?php echo esc_html( wpop_bool_label( $focus_enabled ) ); ?></dd>
					</div>
					<div>
						<dt><?php esc_html_e( 'Your full dashboard escape', 'wpop' ); ?></dt>
						<dd><?php echo esc_html( wpop_bool_label( $full_dashboard ) ); ?></dd>
					</div>
				</dl>

				<?php if ( $can_manage_options ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wpop_toggle_focus' ); ?>
						<input type="hidden" name="action" value="wpop_toggle_focus">
						<input type="hidden" name="enabled" value="<?php echo $focus_enabled ? '0' : '1'; ?>">
						<p><button class="button" type="submit"><?php echo esc_html( $focus_enabled ? __( 'Disable Focus Mode', 'wpop' ) : __( 'Enable Focus Mode', 'wpop' ) ); ?></button></p>
					</form>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'wpop_toggle_full_dashboard' ); ?>
						<input type="hidden" name="action" value="wpop_toggle_full_dashboard">
						<input type="hidden" name="enabled" value="<?php echo $full_dashboard ? '0' : '1'; ?>">
						<p><button class="button" type="submit"><?php echo esc_html( $full_dashboard ? __( 'Return To Focus Mode', 'wpop' ) : __( 'Use Full WordPress Dashboard', 'wpop' ) ); ?></button></p>
					</form>
				<?php endif; ?>
			</section>

			<section class="wpop-panel">
				<h2><?php esc_html_e( 'WP-CLI', 'wpop' ); ?></h2>
				<pre><code>wp wpop status
wp wpop setup
wp wpop setup --force-home
wp wpop focus enable
wp wpop focus disable</code></pre>
			</section>
		</div>
	</div>
	<?php
}

/**
 * Redirect the submenu page directly to the Site Editor.
 */
function wpop_render_edit_homepage_redirect() {
	wp_safe_redirect( wpop_get_site_editor_url() );
	exit;
}

/**
 * Handle Focus Mode global toggle.
 */
function wpop_handle_toggle_focus() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change WPOP settings.', 'wpop' ) );
	}

	check_admin_referer( 'wpop_toggle_focus' );

	$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
	update_option( WPOP_FOCUS_OPTION, $enabled ? '1' : '0' );

	wp_safe_redirect( add_query_arg( 'wpop_focus', $enabled ? 'enabled' : 'disabled', admin_url( 'admin.php?page=wpop' ) ) );
	exit;
}

/**
 * Handle per-admin full dashboard escape toggle.
 */
function wpop_handle_toggle_full_dashboard() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to change WPOP settings.', 'wpop' ) );
	}

	check_admin_referer( 'wpop_toggle_full_dashboard' );

	$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
	update_user_meta( get_current_user_id(), WPOP_FULL_DASHBOARD_META, $enabled ? '1' : '0' );

	wp_safe_redirect( add_query_arg( 'wpop_dashboard', $enabled ? 'full' : 'focus', admin_url( 'admin.php?page=wpop' ) ) );
	exit;
}

/**
 * Handle setup from the WPOP admin page.
 */
function wpop_handle_run_setup() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to run WPOP setup.', 'wpop' ) );
	}

	check_admin_referer( 'wpop_run_setup' );

	$result = wpop_setup_one_pager(
		array(
			'force_home' => isset( $_POST['force_home'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['force_home'] ) ),
		)
	);

	if ( is_wp_error( $result ) ) {
		set_transient( 'wpop_setup_error_' . get_current_user_id(), $result->get_error_message(), MINUTE_IN_SECONDS );
	}

	$query_args = array( 'wpop_setup' => is_wp_error( $result ) ? 'error' : 'done' );
	wp_safe_redirect( add_query_arg( $query_args, admin_url( 'admin.php?page=wpop' ) ) );
	exit;
}

/**
 * Run WPOP one-page setup.
 *
 * @param array $args Setup arguments.
 * @return array|WP_Error
 */
function wpop_setup_one_pager( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'force_home' => false,
			'home_title' => __( 'Home', 'wpop' ),
		)
	);

	$current_front_id     = absint( get_option( 'page_on_front' ) );
	$has_static_frontpage = 'page' === get_option( 'show_on_front' ) && $current_front_id > 0;

	if ( $has_static_frontpage && ! $args['force_home'] ) {
		return array(
			'changed' => false,
			'home_id' => $current_front_id,
			'message' => __( 'Existing static front page preserved.', 'wpop' ),
		);
	}

	$home_page = wpop_get_or_create_home_page( $args['home_title'] );
	if ( is_wp_error( $home_page ) ) {
		return $home_page;
	}

	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home_page->ID );
	update_option( 'page_for_posts', 0 );

	return array(
		'changed' => true,
		'home_id' => $home_page->ID,
		'message' => __( 'WPOP homepage setup complete.', 'wpop' ),
	);
}

/**
 * Get or create the routing Home page.
 *
 * @param string $title Desired page title.
 * @return WP_Post|WP_Error
 */
function wpop_get_or_create_home_page( $title ) {
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

		return $page;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'post_title'     => $title ? $title : __( 'Home', 'wpop' ),
			'post_name'      => $slug,
			'post_content'   => '',
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
 * Return the current WPOP status.
 *
 * @return array
 */
function wpop_get_status() {
	$front_id = absint( get_option( 'page_on_front' ) );
	$theme    = wp_get_theme();

	return array(
		'version'          => WPOP_VERSION,
		'focus_enabled'    => wpop_get_focus_enabled(),
		'show_on_front'    => get_option( 'show_on_front' ),
		'page_on_front'    => $front_id,
		'home_title'       => $front_id ? get_the_title( $front_id ) : '',
		'active_theme'     => $theme->get_stylesheet(),
		'block_theme'      => wpop_site_uses_block_theme(),
		'site_editor_url'  => wpop_get_site_editor_url(),
		'home_url'         => home_url( '/' ),
	);
}

/**
 * Check if the active theme is a block theme.
 *
 * @return bool
 */
function wpop_site_uses_block_theme() {
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
function wpop_get_site_editor_url() {
	return admin_url( 'site-editor.php?canvas=edit' );
}

/**
 * Keep Focus Mode in the Site Editor canvas instead of the navigation sidebar.
 */
function wpop_maybe_redirect_site_editor_to_canvas() {
	$canvas = isset( $_GET['canvas'] ) ? sanitize_key( wp_unslash( $_GET['canvas'] ) ) : '';

	if ( 'edit' === $canvas ) {
		return;
	}

	$query_args = array();

	foreach ( $_GET as $key => $value ) {
		if ( ! is_scalar( $value ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', (string) $key ) ) {
			continue;
		}

		$query_args[ (string) $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
	}

	$query_args['canvas'] = 'edit';

	wp_safe_redirect( add_query_arg( $query_args, admin_url( 'site-editor.php' ) ) );
	exit;
}

/**
 * Get global Focus Mode state.
 *
 * @return bool
 */
function wpop_get_focus_enabled() {
	return (bool) get_option( WPOP_FOCUS_OPTION, '1' );
}

/**
 * Check whether the current admin has enabled the full-dashboard escape hatch.
 *
 * @return bool
 */
function wpop_current_user_has_full_dashboard() {
	$user_id = get_current_user_id();

	if ( ! $user_id || ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	return (bool) get_user_meta( $user_id, WPOP_FULL_DASHBOARD_META, true );
}

/**
 * Check whether Focus Mode applies to the current request user.
 *
 * @return bool
 */
function wpop_is_focus_active_for_current_user() {
	if ( ! wpop_get_focus_enabled() ) {
		return false;
	}

	if ( current_user_can( 'manage_options' ) && wpop_current_user_has_full_dashboard() ) {
		return false;
	}

	return current_user_can( 'edit_theme_options' );
}

/**
 * Menu slugs hidden by Focus Mode.
 *
 * @return string[]
 */
function wpop_get_hidden_menu_slugs() {
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
function wpop_get_allowed_focus_pages() {
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
function wpop_get_redirected_admin_pages() {
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
function wpop_bool_label( $value ) {
	return $value ? __( 'Yes', 'wpop' ) : __( 'No', 'wpop' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * WPOP WP-CLI commands.
	 */
	class WPOP_CLI_Command {
		/**
		 * Show WPOP status.
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
			$status = wpop_get_status();

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
		 * [--home-title=<title>]
		 * : Home page title. Default: Home.
		 *
		 * [--activate-theme]
		 * : Activate WPOP Canvas before setup.
		 *
		 * @param array $args Positional args.
		 * @param array $assoc_args Associative args.
		 */
		public function setup( $args, $assoc_args ) {
			if ( \WP_CLI\Utils\get_flag_value( $assoc_args, 'activate-theme', false ) ) {
				$theme = wp_get_theme( WPOP_CANVAS_THEME );
				if ( ! $theme->exists() ) {
					\WP_CLI::error( 'WPOP Canvas theme is not installed.' );
				}

				switch_theme( WPOP_CANVAS_THEME );
				\WP_CLI::log( 'Activated WPOP Canvas theme.' );
			}

			$result = wpop_setup_one_pager(
				array(
					'force_home' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'force-home', false ),
					'home_title' => isset( $assoc_args['home-title'] ) ? $assoc_args['home-title'] : 'Home',
				)
			);

			if ( is_wp_error( $result ) ) {
				\WP_CLI::error( $result->get_error_message() );
			}

			\WP_CLI::success( $result['message'] . ' Home page ID: ' . $result['home_id'] );
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
				\WP_CLI::error( 'Usage: wp wpop focus <enable|disable>' );
			}

			update_option( WPOP_FOCUS_OPTION, 'enable' === $action ? '1' : '0' );
			\WP_CLI::success( 'Focus Mode ' . ( 'enable' === $action ? 'enabled.' : 'disabled.' ) );
		}
	}

	WP_CLI::add_command( 'wpop', 'WPOP_CLI_Command' );
}
