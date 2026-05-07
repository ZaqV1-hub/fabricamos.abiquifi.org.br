<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Abiquifi_Public_SSO {
	const COOKIE_NAME = 'abiquifi_public_sso';
	const COOKIE_DOMAIN = '.abiquifi.questione.ai';
	const SESSION_TTL = WEEK_IN_SECONDS;
	const OPTION_SECRET = 'abiquifi_public_sso_secret';
	const OPTION_MIGRATION = 'abiquifi_public_sso_migration_v1';
	const META_SOURCE = '_abiquifi_public_sso_source';
	const META_PHONE = '_abiquifi_public_phone';
	const META_JOB_TITLE = '_abiquifi_public_job_title';
	const INTERNAL_SECRET_FALLBACK = 'abiquifi-public-sso-2026';

	protected static $instance = null;
	protected $resolved_session = null;
	protected $resolved_user = null;
	protected $resolved_token = null;
	protected $did_bootstrap = false;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		$self = self::instance();
		$self->ensure_secret();
		$self->maybe_create_session_table();
	}

	protected function __construct() {
		add_action( 'init', array( $this, 'bootstrap' ), 1 );
		add_action( 'init', array( $this, 'maybe_run_migration' ), 20 );
		add_action( 'init', array( $this, 'register_shortcodes' ), 20 );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'template_redirect', array( $this, 'maybe_handle_frontend_actions' ), 1 );
		add_action( 'admin_post_nopriv_abiquifi_public_sso_login', array( $this, 'handle_dictionary_login_form' ) );
		add_action( 'admin_post_abiquifi_public_sso_login', array( $this, 'handle_dictionary_login_form' ) );
		add_action( 'admin_post_nopriv_abiquifi_public_sso_register', array( $this, 'handle_dictionary_register_form' ) );
		add_action( 'admin_post_abiquifi_public_sso_register', array( $this, 'handle_dictionary_register_form' ) );
		add_action( 'admin_post_nopriv_abiquifi_public_sso_logout', array( $this, 'handle_public_logout' ) );
		add_action( 'admin_post_abiquifi_public_sso_logout', array( $this, 'handle_public_logout' ) );
		add_filter( 'the_content', array( $this, 'filter_dictionary_pages' ), 50 );
		add_filter( 'elementor/frontend/the_content', array( $this, 'filter_elementor_content' ), 20 );
		add_filter( 'body_class', array( $this, 'filter_body_class' ) );
		add_filter( 'allowed_redirect_hosts', array( $this, 'filter_allowed_redirect_hosts' ) );
		add_filter( 'show_admin_bar', array( $this, 'filter_show_admin_bar' ) );
		add_action( 'wp_head', array( $this, 'print_public_session_script' ), 5 );
		add_action( 'wp_head', array( $this, 'print_public_ui_styles' ), 20 );
		add_action( 'wp_head', array( $this, 'print_access_gate_styles' ), 25 );
		add_action( 'wp_footer', array( $this, 'print_access_gate_modal' ), 5 );
	}

	public function register_shortcodes() {
		add_shortcode( 'dsf_user_menu', array( $this, 'render_dsf_user_menu_shortcode' ) );
	}

	public function bootstrap() {
		if ( $this->did_bootstrap ) {
			return;
		}

		$this->did_bootstrap = true;
		$this->ensure_secret();

		if ( $this->is_authority_site() ) {
			$this->maybe_create_session_table();
		}

		if ( $this->should_skip_bootstrap() ) {
			return;
		}

		if ( $this->is_fabricamos_exclusive_auth_request() ) {
			$this->maybe_logout_stale_local_public_session();
			return;
		}

		$this->resolved_token = $this->read_cookie_token();

		if ( '' === $this->resolved_token ) {
			if ( $this->bootstrap_existing_authority_public_session() ) {
				return;
			}

			$this->maybe_logout_stale_local_public_session();
			return;
		}

		if ( $this->is_authority_site() ) {
			$session = $this->resolve_authority_session( $this->resolved_token );
		} else {
			$session = $this->fetch_remote_session( $this->resolved_token );
		}

		if ( empty( $session['user'] ) || empty( $session['token'] ) ) {
			$this->clear_public_cookie();
			$this->maybe_logout_stale_local_public_session();
			return;
		}

		$this->resolved_session = $session;
		$this->resolved_user    = $session['user'];

		if ( $this->should_bootstrap_wordpress_user() ) {
			$user_id = $this->ensure_local_wordpress_user( $session['user'] );
			if ( $user_id ) {
				$this->login_local_user( $user_id );
			}
		}
	}

	protected function should_skip_bootstrap() {
		$is_ajax = function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : ( defined( 'DOING_AJAX' ) && DOING_AJAX );

		if ( is_admin() && ! $is_ajax ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! $this->is_authority_site() ) {
			return true;
		}

		return false;
	}

	protected function bootstrap_existing_authority_public_session() {
		if ( ! $this->is_authority_site() || ! is_user_logged_in() ) {
			return false;
		}

		$user = wp_get_current_user();
		if ( ! $user instanceof WP_User || ! $user->exists() ) {
			return false;
		}

		if ( user_can( $user, 'edit_posts' ) ) {
			return false;
		}

		$session = $this->create_public_session( (int) $user->ID, true );
		if ( empty( $session['token'] ) || empty( $session['expires_at'] ) ) {
			return false;
		}

		$this->set_public_cookie( $session['token'], (int) $session['expires_at'] );
		$this->resolved_token   = $session['token'];
		$this->resolved_user    = $this->normalize_user( $user );
		$this->resolved_session = array(
			'token'      => $session['token'],
			'expires_at' => (int) $session['expires_at'],
			'user'       => $this->resolved_user,
		);

		return true;
	}

	protected function should_bootstrap_wordpress_user() {
		if ( $this->is_main_site() ) {
			return false;
		}

		if ( $this->is_fabricamos_exclusive_auth_request() ) {
			return false;
		}

		return true;
	}

	protected function is_fabricamos_exclusive_auth_request() {
		if ( ! $this->is_fabricamos_site() ) {
			return false;
		}

		return $this->request_path_matches( 'fabricante' ) || $this->request_path_matches( 'painel' );
	}

	public function register_rest_routes() {
		register_rest_route(
			'abiquifi-sso/v1',
			'/login',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_login' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'abiquifi-sso/v1',
			'/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_register' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'abiquifi-sso/v1',
			'/logout',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_logout' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'abiquifi-sso/v1',
			'/session',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'rest_session' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'abiquifi-sso/v1',
			'/sync-user',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rest_sync_user' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function rest_login( WP_REST_Request $request ) {
		if ( ! $this->is_authority_site() ) {
			return new WP_Error( 'abiquifi_sso_not_authority', 'Este endpoint so pode ser executado no dicionario.', array( 'status' => 403 ) );
		}

		$login    = sanitize_text_field( (string) $request->get_param( 'login' ) );
		$password = (string) $request->get_param( 'password' );
		$remember = $this->to_bool( $request->get_param( 'remember' ) );

		$result = $this->authenticate_credentials( $login, $password, $remember );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	public function rest_register( WP_REST_Request $request ) {
		if ( ! $this->is_authority_site() ) {
			return new WP_Error( 'abiquifi_sso_not_authority', 'Este endpoint so pode ser executado no dicionario.', array( 'status' => 403 ) );
		}

		$name            = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$phone           = sanitize_text_field( (string) $request->get_param( 'phone' ) );
		$email           = sanitize_email( (string) $request->get_param( 'email' ) );
		$job_title       = sanitize_text_field( (string) $request->get_param( 'job_title' ) );
		$password        = (string) $request->get_param( 'password' );
		$password_repeat = (string) $request->get_param( 'password_repeat' );
		$remember        = true;

		$result = $this->register_public_user(
			$name,
			$email,
			$password,
			$password_repeat,
			$remember,
			array(
				'phone'     => $phone,
				'job_title' => $job_title,
			)
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	public function rest_logout( WP_REST_Request $request ) {
		if ( ! $this->is_authority_site() ) {
			return new WP_Error( 'abiquifi_sso_not_authority', 'Este endpoint so pode ser executado no dicionario.', array( 'status' => 403 ) );
		}

		$token = sanitize_text_field( (string) $request->get_param( 'token' ) );
		if ( '' === $token ) {
			$token = $this->read_cookie_token();
		}

		if ( '' !== $token ) {
			$this->revoke_session_token( $token );
		}

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	public function rest_session( WP_REST_Request $request ) {
		if ( ! $this->is_authority_site() ) {
			return new WP_Error( 'abiquifi_sso_not_authority', 'Este endpoint so pode ser executado no dicionario.', array( 'status' => 403 ) );
		}

		$token = sanitize_text_field( (string) $request->get_param( 'token' ) );
		if ( '' === $token ) {
			$token = $this->read_cookie_token();
		}

		$session = $this->resolve_authority_session( $token );
		if ( empty( $session['user'] ) ) {
			return new WP_Error( 'abiquifi_sso_invalid_session', 'Sessao invalida.', array( 'status' => 401 ) );
		}

		return rest_ensure_response( $session );
	}

	public function rest_sync_user( WP_REST_Request $request ) {
		if ( ! $this->is_authority_site() ) {
			return new WP_Error( 'abiquifi_sso_not_authority', 'Este endpoint so pode ser executado no dicionario.', array( 'status' => 403 ) );
		}

		$secret = sanitize_text_field( (string) $request->get_param( 'secret' ) );
		if ( ! $this->is_internal_secret_valid( $secret ) ) {
			return new WP_Error( 'abiquifi_sso_forbidden', 'Segredo invalido.', array( 'status' => 403 ) );
		}

		$mode = sanitize_text_field( (string) $request->get_param( 'mode' ) );

		if ( 'export' === $mode ) {
			return rest_ensure_response(
				array(
					'users' => $this->export_public_users(),
				)
			);
		}

		$user = $request->get_param( 'user' );
		if ( ! is_array( $user ) ) {
			return new WP_Error( 'abiquifi_sso_invalid_user', 'Usuario invalido.', array( 'status' => 400 ) );
		}

		$user_id = $this->ensure_local_wordpress_user( $user );
		if ( ! $user_id ) {
			return new WP_Error( 'abiquifi_sso_sync_failed', 'Nao foi possivel espelhar o usuario.', array( 'status' => 500 ) );
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'user_id' => (int) $user_id,
			)
		);
	}

	public function handle_dictionary_login_form() {
		$redirect = home_url( '/log-in/' );

		if ( ! isset( $_POST['abiquifi_public_sso_login_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abiquifi_public_sso_login_nonce'] ) ), 'abiquifi_public_sso_login' ) ) {
			wp_safe_redirect( add_query_arg( 'login_error', 'nonce', $redirect ) );
			exit;
		}

		$login      = sanitize_text_field( isset( $_POST['log'] ) ? wp_unslash( $_POST['log'] ) : '' );
		$password   = isset( $_POST['pwd'] ) ? (string) wp_unslash( $_POST['pwd'] ) : '';
		$remember   = ! empty( $_POST['rememberme'] );
		$redirect_to = esc_url_raw( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : home_url( '/' ) );
		if ( '' === $redirect_to ) {
			$redirect_to = home_url( '/' );
		}

		$result = $this->authenticate_credentials( $login, $password, $remember );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'login_error', 'invalid', $redirect ) );
			exit;
		}

		$this->set_public_cookie( $result['token'], $result['expires_at'] );
		$this->login_local_user( (int) $result['user']['ID'] );
		wp_safe_redirect( $redirect_to );
		exit;
	}

	public function handle_dictionary_register_form() {
		$redirect = home_url( '/cadastro/' );

		if ( ! isset( $_POST['abiquifi_public_sso_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['abiquifi_public_sso_register_nonce'] ) ), 'abiquifi_public_sso_register' ) ) {
			wp_safe_redirect( add_query_arg( 'register_error', 'nonce', $redirect ) );
			exit;
		}

		$name            = sanitize_text_field( isset( $_POST['register_name'] ) ? wp_unslash( $_POST['register_name'] ) : '' );
		$phone           = sanitize_text_field( isset( $_POST['register_phone'] ) ? wp_unslash( $_POST['register_phone'] ) : '' );
		$email           = sanitize_email( isset( $_POST['register_email'] ) ? wp_unslash( $_POST['register_email'] ) : '' );
		$job_title       = sanitize_text_field( isset( $_POST['register_job_title'] ) ? wp_unslash( $_POST['register_job_title'] ) : '' );
		$password        = isset( $_POST['register_password'] ) ? (string) wp_unslash( $_POST['register_password'] ) : '';
		$password_repeat = isset( $_POST['register_password_repeat'] ) ? (string) wp_unslash( $_POST['register_password_repeat'] ) : '';
		$redirect_to     = esc_url_raw( isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : home_url( '/' ) );
		$return_url      = esc_url_raw( isset( $_POST['return_url'] ) ? wp_unslash( $_POST['return_url'] ) : $redirect );
		if ( '' === $redirect_to ) {
			$redirect_to = home_url( '/' );
		}
		if ( '' === $return_url ) {
			$return_url = $redirect;
		}
		$redirect_to = remove_query_arg( array( 'register_error', 'registered', 'login_error' ), $redirect_to );
		$return_url  = remove_query_arg( array( 'register_error', 'registered', 'login_error' ), $return_url );

		$result = $this->register_public_user(
			$name,
			$email,
			$password,
			$password_repeat,
			true,
			array(
				'phone'     => $phone,
				'job_title' => $job_title,
			)
		);
		if ( is_wp_error( $result ) ) {
			$error_code = $result->get_error_code();
			$map        = array(
				'abiquifi_sso_required'          => 'required',
				'abiquifi_sso_invalid_phone'     => 'phone',
				'abiquifi_sso_invalid_email'     => 'email',
				'abiquifi_sso_password_mismatch' => 'password',
				'abiquifi_sso_email_exists'      => 'exists',
			);
			$state      = isset( $map[ $error_code ] ) ? $map[ $error_code ] : 'error';

			wp_safe_redirect( add_query_arg( 'register_error', $state, $return_url ) );
			exit;
		}

		$this->set_public_cookie( $result['token'], $result['expires_at'] );
		$this->login_local_user( (int) $result['user']['ID'] );
		wp_safe_redirect( add_query_arg( 'registered', '1', $redirect_to ) );
		exit;
	}

	public function handle_public_logout() {
		$redirect = esc_url_raw( isset( $_GET['redirect_to'] ) ? wp_unslash( $_GET['redirect_to'] ) : home_url( '/' ) );
		if ( '' === $redirect ) {
			$redirect = home_url( '/' );
		}
		$token    = $this->read_cookie_token();

		if ( $this->is_fabricamos_site() ) {
			if ( class_exists( 'Fabricamos_Native' ) ) {
				$fabricamos = Fabricamos_Native::instance();
				if ( $fabricamos && method_exists( $fabricamos, 'clear_public_access_state' ) ) {
					$fabricamos->clear_public_access_state();
				}
			}

			wp_logout();

			$authority_logout = trailingslashit( $this->authority_url() ) . 'sair/';
			$authority_logout = add_query_arg( 'redirect_to', $redirect, $authority_logout );

			wp_safe_redirect( $authority_logout );
			exit;
		}

		if ( $this->is_authority_site() ) {
			if ( '' !== $token ) {
				$this->revoke_session_token( $token );
			}
		} else {
			$this->remote_logout( $token );
		}

		$this->clear_public_cookie();
		wp_logout();
		wp_safe_redirect( $redirect );
		exit;
	}

	public function maybe_handle_frontend_actions() {
		if ( is_admin() ) {
			return;
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET';

		if ( $this->request_path_matches( 'sair' ) ) {
			$this->handle_public_logout();
		}

		if ( ! $this->is_authority_site() || 'POST' !== $method ) {
			return;
		}

		if ( $this->request_path_matches( 'log-in' ) ) {
			$this->handle_dictionary_login_form();
		}

		if ( $this->request_path_matches( 'cadastro' ) ) {
			$this->handle_dictionary_register_form();
		}
	}

	public function filter_dictionary_pages( $content ) {
		if ( ! $this->is_authority_site() || is_admin() || ! is_main_query() ) {
			return $content;
		}

		if ( is_page( 'log-in' ) ) {
			return $this->render_dictionary_login_page();
		}

		if ( is_page( 'cadastro' ) ) {
			return $this->render_dictionary_register_page();
		}

		if ( is_page( 'account' ) ) {
			return $this->render_public_account_page();
		}

		return $content;
	}

	public function filter_elementor_content( $content ) {
		if ( false !== strpos( $content, '[dsf_user_menu]' ) ) {
			$content = str_replace( '[dsf_user_menu]', $this->render_dsf_user_menu_shortcode(), $content );
		}

		return $content;
	}

	public function filter_body_class( $classes ) {
		if ( $this->is_fabricamos_exclusive_auth_request() ) {
			return $classes;
		}

		if ( $this->get_public_user() ) {
			$classes = array_values(
				array_filter(
					(array) $classes,
					static function ( $class ) {
						return ! in_array( $class, array( 'logged-in', 'admin-bar', 'user-subscriber', 'role-subscriber' ), true );
					}
				)
			);
			$classes[] = 'abiquifi-public-session';
		}

		if ( $this->is_authority_site() ) {
			$classes[] = 'abiquifi-site-dicionario';
		}

		if ( $this->is_fabricamos_site() ) {
			$classes[] = 'abiquifi-site-fabricamos';
		}

		if ( $this->is_main_site() && $this->get_public_user() ) {
			$classes[] = 'abiquifi-public-recognized';
		}

		if ( $this->is_authority_site() && is_page( 'log-in' ) ) {
			$classes[] = 'abiquifi-public-view-login';
		}

		if ( $this->is_authority_site() && is_page( 'cadastro' ) ) {
			$classes[] = 'abiquifi-public-view-register';
		}

		if ( $this->is_authority_site() && is_page( 'account' ) ) {
			$classes[] = 'abiquifi-public-view-account';
		}

		if ( $this->should_render_access_gate_modal() ) {
			$classes[] = 'abiquifi-public-gate-open';
		}

		return $classes;
	}

	public function filter_show_admin_bar( $show ) {
		if ( $this->is_fabricamos_exclusive_auth_request() ) {
			return false;
		}

		if ( ! is_admin() && $this->is_public_authenticated() ) {
			return false;
		}

		return $show;
	}

	public function filter_allowed_redirect_hosts( $hosts ) {
		$hosts   = (array) $hosts;
		$allowed = array(
			'abiquifi.questione.ai',
			'dicionario.abiquifi.questione.ai',
			'fabricamos.abiquifi.questione.ai',
		);

		foreach ( $allowed as $host ) {
			if ( ! in_array( $host, $hosts, true ) ) {
				$hosts[] = $host;
			}
		}

		return $hosts;
	}

	public function print_public_ui_styles() {
		if ( is_admin() ) {
			return;
		}

		?>
		<style id="abiquifi-public-sso-ui">
			:root {
				--abq-surface: #ffffff;
				--abq-bg: #eef1f6;
				--abq-border: #d4dbe5;
				--abq-shadow: 0 20px 44px rgba(23, 46, 84, 0.08);
				--abq-text: #203b67;
				--abq-text-strong: #1a3156;
				--abq-muted: #62728b;
				--abq-primary: #234785;
				--abq-primary-hover: #1a3568;
				--abq-radius-xl: 28px;
				--abq-radius-lg: 22px;
				--abq-radius-md: 16px;
				--abq-radius-sm: 12px;
			}

			body.abiquifi-public-session {
				overflow-x: hidden;
			}

			.jupiterx-header {
				position: relative;
			}

			.abiquifi-public-session.user-subscriber header,
			.abiquifi-public-session.user-subscriber .site-header,
			.abiquifi-public-session.user-subscriber .elementor-location-header {
				display: block !important;
			}

			.jupiterx-header .elementor-widget-shortcode,
			.jupiterx-header .elementor-widget-shortcode .elementor-widget-container,
			.jupiterx-header .elementor-widget-shortcode .elementor-shortcode {
				display: flex;
				justify-content: flex-start;
				width: auto;
				overflow: visible;
			}

			.jupiterx-header .dsf-user-menu-slot {
				position: static !important;
				display: inline-flex;
				max-width: 100%;
			}

			.jupiterx-header .dsf-user-menu {
				position: relative !important;
				top: auto !important;
				right: auto !important;
				z-index: 40 !important;
				display: inline-flex;
				align-items: center;
				max-width: min(100%, 220px);
				min-width: 0;
			}

			.jupiterx-header .dsf-user-menu__trigger {
				display: inline-flex;
				align-items: center;
				gap: 12px;
				padding: 6px 18px 6px 10px;
				background: #ffffff;
				border: 1px solid var(--abq-border);
				border-radius: 999px;
				box-shadow: 0 6px 14px rgba(22, 42, 77, 0.08);
				color: #2a4268;
				max-width: 100%;
				min-width: 0;
				white-space: nowrap;
				font: inherit;
				cursor: pointer;
			}

			.jupiterx-header .dsf-user-icon {
				position: relative;
				display: inline-flex;
				align-items: center;
				justify-content: center;
				width: 42px;
				height: 42px;
				flex: 0 0 42px;
				border-radius: 999px;
				background: #ffffff;
				border: 1px solid #cfd6e0;
			}

			.jupiterx-header .dsf-user-icon::before {
				content: "";
				position: absolute;
				top: 8px;
				width: 12px;
				height: 12px;
				border: 2px solid #7c8594;
				border-radius: 999px;
			}

			.jupiterx-header .dsf-user-icon::after {
				content: "";
				position: absolute;
				bottom: 8px;
				width: 22px;
				height: 12px;
				border: 2px solid #7c8594;
				border-top: 0;
				border-radius: 0 0 12px 12px;
			}

			.jupiterx-header .dsf-user-icon__ring,
			.jupiterx-header .dsf-user-icon__head {
				display: none;
			}

			.jupiterx-header .dsf-user-label {
				display: block;
				font-size: 15px;
				line-height: 1;
				letter-spacing: -0.02em;
				max-width: 120px;
				min-width: 0;
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			.jupiterx-header .dsf-user-label strong {
				font-weight: 700;
			}

			.jupiterx-header .dsf-user-dropdown {
				position: absolute;
				top: calc(100% + 10px);
				right: 0;
				display: grid;
				gap: 2px;
				min-width: 170px;
				padding: 8px;
				background: #ffffff;
				border: 1px solid var(--abq-border);
				border-radius: 14px;
				box-shadow: 0 14px 30px rgba(20, 38, 71, 0.14);
				opacity: 0;
				visibility: hidden;
				transform: translateY(-4px);
				transition: opacity 0.16s ease, transform 0.16s ease, visibility 0.16s ease;
			}

			.jupiterx-header .dsf-user-dropdown a {
				display: block;
				padding: 10px 12px;
				border-radius: 10px;
				color: #2a4268;
				font-size: 14px;
				font-weight: 500;
				line-height: 1.2;
				text-decoration: none;
			}

			.jupiterx-header .dsf-user-dropdown a:hover,
			.jupiterx-header .dsf-user-dropdown a:focus {
				background: #eef3fb;
			}

			.jupiterx-header .dsf-user-menu:hover .dsf-user-dropdown,
			.jupiterx-header .dsf-user-menu:focus-within .dsf-user-dropdown {
				opacity: 1;
				visibility: visible;
				transform: translateY(0);
			}

			@media (min-width: 1025px) {
				.elementor-33108 .elementor-element.elementor-element-0f8f97b > .elementor-container {
					position: relative;
					display: flex;
					justify-content: center;
					align-items: center;
					gap: 18px;
					row-gap: 12px;
					flex-wrap: wrap;
				}

				.elementor-33108 .elementor-element.elementor-element-5eba074,
				.elementor-33108 .elementor-element.elementor-element-f267c54 {
					width: auto !important;
					max-width: none !important;
					flex: 0 0 auto;
				}

				.elementor-33108 .elementor-element.elementor-element-5eba074 > .elementor-widget-wrap,
				.elementor-33108 .elementor-element.elementor-element-c22735b > .elementor-widget-wrap {
					justify-content: center;
					align-content: center;
					align-items: center;
				}

				.elementor-33108 .elementor-element.elementor-element-c22735b {
					position: absolute !important;
					left: 24px !important;
					top: 20px !important;
					width: auto !important;
					max-width: calc(100% - 48px) !important;
					z-index: 40 !important;
				}

				.elementor-33108 .elementor-element.elementor-element-c22735b > .elementor-widget-wrap {
					justify-content: flex-start !important;
				}
			}

			@media (max-width: 1200px) {
				.elementor-33108 .elementor-element.elementor-element-c22735b {
					left: 16px !important;
					top: 16px !important;
					max-width: calc(100vw - 32px) !important;
				}
			}

			@media (max-width: 767px) {
				.elementor-33108 .elementor-element.elementor-element-c22735b {
					left: 10px !important;
					top: 12px !important;
					max-width: calc(100vw - 20px) !important;
				}
			}

			body.abiquifi-public-view-login .jupiterx-main-header,
			body.abiquifi-public-view-register .jupiterx-main-header,
			body.abiquifi-public-view-account .jupiterx-main-header,
			body.abiquifi-public-view-login .jupiterx-post-header,
			body.abiquifi-public-view-register .jupiterx-post-header,
			body.abiquifi-public-view-account .jupiterx-post-header {
				display: none !important;
			}

			body.abiquifi-public-view-login .jupiterx-main-content,
			body.abiquifi-public-view-register .jupiterx-main-content,
			body.abiquifi-public-view-account .jupiterx-main-content {
				padding-top: 0 !important;
			}

			.abiquifi-public-auth {
				max-width: 1480px;
				margin: 0 auto;
				padding: 0 12px 28px;
				font-family: "Inter", "Segoe UI", "Helvetica Neue", Arial, sans-serif;
			}

			.abiquifi-public-auth--login,
			.abiquifi-public-auth--register,
			.abiquifi-public-auth--account {
				display: grid;
				place-items: center;
				min-height: calc(100vh - 220px);
			}

			.abiquifi-public-auth__shell {
				width: 100%;
				max-width: 520px;
				margin: 0 auto;
			}

			.abiquifi-public-auth__card {
				background: var(--abq-surface);
				border: 1px solid #dde4ef;
				border-radius: var(--abq-radius-xl);
				box-shadow: var(--abq-shadow);
				padding: 34px;
				color: var(--abq-text);
			}

			.abiquifi-public-auth .fab-login-wrap {
				width: 100%;
				max-width: 520px;
				margin: 0 auto;
			}

			.abiquifi-public-auth .fab-login-card {
				padding: 34px;
			}

			.abiquifi-public-auth .fab-page-intro {
				display: grid;
				gap: 14px;
				margin-bottom: 24px;
			}

			.abiquifi-public-auth .fab-page-kicker {
				display: inline-flex;
				align-items: center;
				width: fit-content;
				padding: 7px 12px;
				border-radius: 999px;
				background: rgba(35, 71, 133, 0.09);
				color: var(--abq-primary);
				font-size: 12px;
				font-weight: 700;
				letter-spacing: 0.05em;
				text-transform: uppercase;
			}

			.abiquifi-public-auth .fab-title-line-wrap {
				display: flex;
				align-items: center;
				gap: 16px;
				flex: 1;
				min-width: 0;
			}

			.abiquifi-public-auth .fab-screen-title {
				margin: 0;
				font-size: clamp(26px, 3.4vw, 36px);
				line-height: 1;
				letter-spacing: -0.04em;
				color: var(--abq-text-strong);
			}

			.abiquifi-public-auth .fab-line {
				flex: 1;
				height: 3px;
				border-radius: 999px;
				background: #3aa0df;
			}

			.abiquifi-public-auth .fab-page-copy {
				margin: 0;
				color: var(--abq-muted);
				font-size: 15px;
				line-height: 1.6;
			}

			.abiquifi-public-auth .fab-login-form {
				display: grid;
				gap: 12px;
			}

			.abiquifi-public-auth .fab-login-form label {
				display: block;
				font-size: 13px;
				font-weight: 500;
				color: #35547e;
				line-height: 1.25;
			}

			.abiquifi-public-auth .fab-input {
				width: 100%;
				box-sizing: border-box;
				border: 1px solid #c8d1df;
				border-radius: 4px;
				background: #ffffff;
				color: var(--abq-text);
				padding: 11px 14px;
				font-size: 13px;
				outline: none;
				transition: border-color 0.2s ease, box-shadow 0.2s ease;
			}

			.abiquifi-public-auth .fab-input--lg {
				padding: 12px 15px;
			}

			.abiquifi-public-auth .fab-input:focus {
				border-color: #77add8;
				box-shadow: 0 0 0 4px rgba(58, 160, 223, 0.12);
			}

			.abiquifi-public-auth .fab-remember {
				display: inline-flex;
				align-items: center;
				gap: 10px;
				font-size: 15px;
				color: var(--abq-muted);
			}

			.abiquifi-public-auth .fab-button {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				padding: 13px 18px;
				border: 0;
				border-radius: 4px;
				text-decoration: none;
				font-size: 14px;
				font-weight: 700;
				cursor: pointer;
				transition: background-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
				text-decoration: none;
			}

			.abiquifi-public-auth .fab-button:hover,
			.abiquifi-public-auth .fab-button:focus {
				transform: translateY(-1px);
			}

			.abiquifi-public-auth .fab-button--primary {
				background: var(--abq-primary);
				color: #ffffff;
				box-shadow: 0 10px 24px rgba(35, 71, 133, 0.12);
			}

			.abiquifi-public-auth .fab-button--primary:hover,
			.abiquifi-public-auth .fab-button--primary:focus {
				background: var(--abq-primary-hover);
				color: #ffffff;
			}

			.abiquifi-public-auth .fab-button--ghost {
				background: #ffffff;
				color: var(--abq-text);
				border: 1px solid #c8d1df;
			}

			.abiquifi-public-auth .fab-button--block {
				width: 100%;
			}

			.abiquifi-public-auth .fab-actions {
				display: grid;
				gap: 12px;
			}

			.abiquifi-public-auth .fab-actions--profile {
				margin-top: 22px;
			}

			.abiquifi-public-auth .fab-login-alt {
				margin: 4px 0 0;
				font-size: 14px;
				color: var(--abq-muted);
			}

			.abiquifi-public-auth .fab-login-alt a {
				color: var(--abq-primary);
				font-weight: 600;
				text-decoration: none;
			}

			.abiquifi-public-auth .fab-alert {
				padding: 14px 18px;
				margin-bottom: 18px;
				border-radius: 16px;
				font-weight: 600;
			}

			.abiquifi-public-auth .fab-alert strong {
				font-weight: 800;
			}

			.abiquifi-public-auth .fab-alert--error {
				background: rgba(180, 73, 67, 0.12);
				color: #b44943;
			}

			.abiquifi-public-auth .fab-alert--success {
				background: rgba(42, 122, 96, 0.12);
				color: #2a7a60;
			}

			.abiquifi-public-auth .fab-panel {
				padding: 24px;
				margin-bottom: 18px;
				background: #ffffff;
				border: 1px solid #dde4ef;
				border-radius: var(--abq-radius-xl);
				box-shadow: var(--abq-shadow);
			}

			.abiquifi-public-auth .fab-panel--soft {
				background: linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
			}

			.abiquifi-public-auth .fab-account-meta {
				display: grid;
				gap: 14px;
			}

			.abiquifi-public-auth .fab-account-meta__row {
				display: grid;
				gap: 4px;
			}

			.abiquifi-public-auth .fab-account-meta__row strong {
				color: var(--abq-text-strong);
				font-size: 13px;
			}

			.abiquifi-public-auth .fab-account-meta__row span {
				color: #38557f;
				font-size: 15px;
				line-height: 1.5;
			}

			@media (max-width: 1024px) {
				.jupiterx-header .dsf-user-label {
					display: none;
				}
			}

			@media (max-width: 767px) {
				.abiquifi-public-auth__card {
					padding: 20px;
				}

				.abiquifi-public-auth .fab-title-line-wrap {
					gap: 12px;
				}
			}
		</style>
		<?php
	}

	public function print_public_session_script() {
		if ( $this->is_fabricamos_exclusive_auth_request() ) {
			echo "<script>window.AbiquifiPublicSession={authenticated:false,exclusive:true};</script>\n";
			return;
		}

		$user = $this->get_public_user();
		if ( ! $user ) {
			echo "<script>window.AbiquifiPublicSession={authenticated:false};</script>\n";
			return;
		}

		$payload = array(
			'authenticated' => true,
			'user'          => array(
				'id'           => (int) $user['ID'],
				'email'        => $user['user_email'],
				'display_name' => $user['display_name'],
			),
			'source'        => $this->site_role(),
		);

		echo '<script>window.AbiquifiPublicSession=' . wp_json_encode( $payload ) . ';</script>' . "\n";
	}

	public function print_access_gate_styles() {
		if ( ! $this->should_render_access_gate_modal() ) {
			return;
		}
		?>
		<style id="abiquifi-public-gate-ui">
			body.abiquifi-public-gate-open {
				overflow: hidden;
			}

			.abiquifi-public-gate {
				position: fixed;
				inset: 0;
				z-index: 99999;
				display: flex;
				align-items: center;
				justify-content: center;
				padding: 24px;
				background: rgba(9, 14, 26, 0.72);
				backdrop-filter: blur(8px);
			}

			.abiquifi-public-gate__dialog {
				width: min(100%, 560px);
				background: #ffffff;
				border-radius: 28px;
				padding: 32px;
				box-shadow: 0 32px 80px rgba(15, 23, 42, 0.28);
			}

			.abiquifi-public-gate__kicker {
				display: inline-flex;
				margin-bottom: 12px;
				padding: 8px 14px;
				border-radius: 999px;
				background: #edf4ff;
				color: #1d4ed8;
				font-size: 12px;
				font-weight: 700;
				letter-spacing: 0.08em;
				text-transform: uppercase;
			}

			.abiquifi-public-gate__title {
				margin: 0 0 10px;
				color: #0f172a;
				font-size: 32px;
				font-weight: 800;
				line-height: 1.1;
			}

			.abiquifi-public-gate__copy {
				margin: 0 0 24px;
				color: #475569;
				font-size: 15px;
				line-height: 1.6;
			}

			.abiquifi-public-gate__alert {
				margin-bottom: 18px;
				padding: 14px 16px;
				border-radius: 16px;
				font-size: 14px;
				font-weight: 600;
			}

			.abiquifi-public-gate__alert--error {
				background: #fef2f2;
				color: #b91c1c;
			}

			.abiquifi-public-gate__form {
				display: grid;
				gap: 14px;
			}

			.abiquifi-public-gate__form label {
				display: grid;
				gap: 8px;
				color: #0f172a;
				font-size: 14px;
				font-weight: 700;
			}

			.abiquifi-public-gate__form input {
				width: 100%;
				min-height: 52px;
				padding: 0 16px;
				border: 1px solid #cbd5e1;
				border-radius: 14px;
				background: #fff;
				color: #0f172a;
				font-size: 15px;
			}

			.abiquifi-public-gate__form input:focus {
				outline: none;
				border-color: #2563eb;
				box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
			}

			.abiquifi-public-gate__submit {
				margin-top: 4px;
				min-height: 54px;
				border: 0;
				border-radius: 999px;
				background: linear-gradient(135deg, #1d4ed8, #2563eb);
				color: #fff;
				font-size: 15px;
				font-weight: 800;
				cursor: pointer;
			}

			.abiquifi-public-gate__login {
				margin: 18px 0 0;
				color: #475569;
				font-size: 14px;
				text-align: center;
			}

			.abiquifi-public-gate__login a {
				color: #1d4ed8;
				font-weight: 700;
				text-decoration: none;
			}

			.abiquifi-public-gate__login a:hover {
				text-decoration: underline;
			}

			@media (max-width: 640px) {
				.abiquifi-public-gate {
					padding: 16px;
					align-items: flex-end;
				}

				.abiquifi-public-gate__dialog {
					padding: 24px 20px;
					border-radius: 24px 24px 0 0;
				}

				.abiquifi-public-gate__title {
					font-size: 28px;
				}
			}
		</style>
		<?php
	}

	public function print_access_gate_modal() {
		$context = $this->get_public_access_modal_context();
		if ( empty( $context ) ) {
			return;
		}

		$register_error = isset( $_GET['register_error'] ) ? sanitize_text_field( wp_unslash( $_GET['register_error'] ) ) : '';
		?>
		<div class="abiquifi-public-gate" aria-modal="true" role="dialog" aria-labelledby="abiquifi-public-gate-title">
			<div class="abiquifi-public-gate__dialog">
				<span class="abiquifi-public-gate__kicker"><?php echo esc_html( $context['kicker'] ); ?></span>
				<h2 id="abiquifi-public-gate-title" class="abiquifi-public-gate__title"><?php echo esc_html( $context['title'] ); ?></h2>
				<p class="abiquifi-public-gate__copy"><?php echo esc_html( $context['copy'] ); ?></p>
				<?php if ( $register_error ) : ?>
					<div class="abiquifi-public-gate__alert abiquifi-public-gate__alert--error"><?php echo esc_html( $this->registration_error_message( $register_error ) ); ?></div>
				<?php endif; ?>
				<form class="abiquifi-public-gate__form" action="<?php echo esc_url( $context['form_action'] ); ?>" method="post">
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $context['redirect_to'] ); ?>" />
					<input type="hidden" name="return_url" value="<?php echo esc_url( $context['return_url'] ); ?>" />
					<?php wp_nonce_field( 'abiquifi_public_sso_register', 'abiquifi_public_sso_register_nonce' ); ?>
					<label for="abiquifi-public-gate-name">
						<span>Nome</span>
						<input id="abiquifi-public-gate-name" name="register_name" type="text" autocomplete="name" required />
					</label>
					<label for="abiquifi-public-gate-phone">
						<span>Número de telefone</span>
						<input id="abiquifi-public-gate-phone" name="register_phone" type="tel" inputmode="tel" autocomplete="tel" required />
					</label>
					<label for="abiquifi-public-gate-email">
						<span>E-mail</span>
						<input id="abiquifi-public-gate-email" name="register_email" type="email" autocomplete="email" required />
					</label>
					<label for="abiquifi-public-gate-job-title">
						<span>Cargo</span>
						<input id="abiquifi-public-gate-job-title" name="register_job_title" type="text" autocomplete="organization-title" required />
					</label>
					<button class="abiquifi-public-gate__submit" type="submit"><?php echo esc_html( $context['submit_label'] ); ?></button>
				</form>
				<p class="abiquifi-public-gate__login">Se você já tem login, <a href="<?php echo esc_url( $context['login_url'] ); ?>">clique aqui</a>.</p>
			</div>
		</div>
		<?php
	}

	public function render_dsf_user_menu_shortcode() {
		if ( $this->is_fabricamos_exclusive_auth_request() ) {
			return '';
		}

		$user         = $this->get_public_user();
		$is_logged_in = ! empty( $user );
		$name         = $is_logged_in && ! empty( $user['display_name'] ) ? $user['display_name'] : '[Nome]';
		$account_url  = $this->public_account_url();
		$auth_url     = $is_logged_in ? $this->public_logout_url( $this->public_logout_destination_url() ) : $this->public_login_url( home_url( '/' ) );
		$auth_label   = $is_logged_in ? 'Sair' : 'Entrar';

		ob_start();
		?>
		<div class="dsf-user-menu-slot">
			<div class="dsf-user-menu">
				<button class="dsf-user-menu__trigger" type="button" aria-haspopup="true" aria-expanded="false">
					<span class="dsf-user-icon" aria-hidden="true">
						<span class="dsf-user-icon__ring"></span>
						<span class="dsf-user-icon__head"></span>
					</span>
					<span class="dsf-user-label">
						<?php if ( $is_logged_in ) : ?>
							<span>Olá, <strong><?php echo esc_html( $name ); ?></strong></span>
						<?php else : ?>
							<span>Minha conta</span>
						<?php endif; ?>
					</span>
				</button>
				<div class="dsf-user-dropdown">
					<a href="<?php echo esc_url( $is_logged_in ? $account_url : $this->public_register_url( home_url( '/' ) ) ); ?>">
						<?php echo esc_html( $is_logged_in ? 'Minha conta' : 'Criar conta' ); ?>
					</a>
					<a href="<?php echo esc_url( $auth_url ); ?>"><?php echo esc_html( $auth_label ); ?></a>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	protected function render_public_account_page() {
		$user = $this->get_public_user();

		if ( ! $user ) {
			$this->require_public_authentication( $this->public_account_url() );
		}

		ob_start();
		?>
		<section class="abiquifi-public-auth abiquifi-public-auth--account">
			<div class="abiquifi-public-auth__shell fab-login-wrap">
				<div class="abiquifi-public-auth__card fab-login-card">
					<div class="fab-page-intro fab-page-intro--compact">
						<span class="fab-page-kicker">Conta pública</span>
						<div class="fab-title-line-wrap">
							<h1 class="fab-screen-title">Minha conta</h1>
							<span class="fab-line"></span>
						</div>
						<p class="fab-page-copy">Os links permanecem no dicionário e o encerramento da sessão leva você ao Fabricamos já deslogado.</p>
					</div>
					<div class="fab-panel fab-panel--soft fab-account-meta">
						<div class="fab-account-meta__row">
							<strong>Nome</strong>
							<span><?php echo esc_html( $user['display_name'] ); ?></span>
						</div>
						<?php if ( ! empty( $user['phone'] ) ) : ?>
						<div class="fab-account-meta__row">
							<strong>Telefone</strong>
							<span><?php echo esc_html( $user['phone'] ); ?></span>
						</div>
						<?php endif; ?>
						<div class="fab-account-meta__row">
							<strong>E-mail</strong>
							<span><?php echo esc_html( $user['user_email'] ); ?></span>
						</div>
						<?php if ( ! empty( $user['job_title'] ) ) : ?>
						<div class="fab-account-meta__row">
							<strong>Cargo</strong>
							<span><?php echo esc_html( $user['job_title'] ); ?></span>
						</div>
						<?php endif; ?>
					</div>
					<div class="fab-actions fab-actions--profile">
						<a class="fab-button fab-button--primary fab-button--block" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ir para o dicionário</a>
						<a class="fab-button fab-button--ghost fab-button--block" href="<?php echo esc_url( $this->public_logout_url( $this->public_logout_destination_url() ) ); ?>">Sair</a>
					</div>
				</div>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	protected function render_dictionary_login_page() {
		$user        = $this->get_public_user();
		$login_error = isset( $_GET['login_error'] ) ? sanitize_text_field( wp_unslash( $_GET['login_error'] ) ) : '';
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' );
		if ( '' === $redirect_to ) {
			$redirect_to = home_url( '/' );
		}

		ob_start();
		?>
		<div class="abiquifi-public-auth abiquifi-public-auth--login">
			<div class="abiquifi-public-auth__shell fab-login-wrap">
				<div class="abiquifi-public-auth__card fab-login-card">
					<div class="fab-page-intro fab-page-intro--compact">
						<span class="fab-page-kicker">Conta pública</span>
						<div class="fab-title-line-wrap">
							<h1 class="fab-screen-title">Entrar</h1>
							<span class="fab-line"></span>
						</div>
						<p class="fab-page-copy">Acesse sua conta pública do dicionário em um layout alinhado com o restante do ecossistema.</p>
					</div>
			<?php if ( $user ) : ?>
					<div class="fab-alert fab-alert--success">
						Você já está autenticado como <strong><?php echo esc_html( $user['display_name'] ); ?></strong>.
					</div>
					<div class="fab-actions fab-actions--profile">
						<a class="fab-button fab-button--primary fab-button--block" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ir para o dicionário</a>
						<a class="fab-button fab-button--ghost fab-button--block" href="<?php echo esc_url( $this->public_logout_url( $this->public_logout_destination_url() ) ); ?>">Sair</a>
					</div>
			<?php else : ?>
				<?php if ( $login_error ) : ?>
					<div class="fab-alert fab-alert--error">Não foi possível autenticar a solicitação.</div>
				<?php endif; ?>
				<form action="<?php echo esc_url( home_url( '/log-in/' ) ); ?>" method="post" class="fab-login-form">
					<input type="hidden" name="action" value="abiquifi_public_sso_login" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
					<?php wp_nonce_field( 'abiquifi_public_sso_login', 'abiquifi_public_sso_login_nonce' ); ?>
					<label for="abiquifi-sso-login-email">E-mail</label>
					<input id="abiquifi-sso-login-email" class="fab-input fab-input--lg" name="log" type="email" required />

					<label for="abiquifi-sso-login-password">Senha</label>
					<input id="abiquifi-sso-login-password" class="fab-input fab-input--lg" name="pwd" type="password" required />

					<label class="fab-remember">
						<input name="rememberme" type="checkbox" value="1" />
						<span>Lembrar de mim</span>
					</label>

					<button type="submit" class="fab-button fab-button--ghost">Entrar</button>
				</form>
				<p class="fab-login-alt">Ainda não tem conta? <a href="<?php echo esc_url( home_url( '/cadastro/' ) ); ?>">Criar cadastro</a></p>
			<?php endif; ?>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	protected function render_dictionary_register_page() {
		$user           = $this->get_public_user();
		$register_error = isset( $_GET['register_error'] ) ? sanitize_text_field( wp_unslash( $_GET['register_error'] ) ) : '';
		$redirect_to    = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' );
		$return_url     = $this->public_register_url( $redirect_to );
		if ( '' === $redirect_to ) {
			$redirect_to = home_url( '/' );
		}

		ob_start();
		?>
		<div class="abiquifi-public-auth abiquifi-public-auth--register">
			<div class="abiquifi-public-auth__shell fab-login-wrap">
				<div class="abiquifi-public-auth__card fab-login-card">
					<div class="fab-page-intro fab-page-intro--compact">
						<span class="fab-page-kicker">Conta pública</span>
						<div class="fab-title-line-wrap">
							<h1 class="fab-screen-title">Criar cadastro</h1>
							<span class="fab-line"></span>
						</div>
						<p class="fab-page-copy">Cadastre o acesso ao dicionário informando nome, telefone, e-mail e cargo. Se já tiver login, use a rota de entrada existente.</p>
					</div>
			<?php if ( $user ) : ?>
					<div class="fab-alert fab-alert--success">Você já possui uma conta ativa.</div>
					<div class="fab-actions fab-actions--profile">
						<a class="fab-button fab-button--primary fab-button--block" href="<?php echo esc_url( home_url( '/' ) ); ?>">Ir para o dicionário</a>
						<a class="fab-button fab-button--ghost fab-button--block" href="<?php echo esc_url( $this->public_logout_url( $this->public_logout_destination_url() ) ); ?>">Sair</a>
					</div>
			<?php else : ?>
				<?php if ( $register_error ) : ?>
					<div class="fab-alert fab-alert--error"><?php echo esc_html( $this->registration_error_message( $register_error ) ); ?></div>
				<?php endif; ?>
				<form action="<?php echo esc_url( home_url( '/cadastro/' ) ); ?>" method="post" class="fab-login-form">
					<input type="hidden" name="action" value="abiquifi_public_sso_register" />
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />
					<input type="hidden" name="return_url" value="<?php echo esc_url( $return_url ); ?>" />
					<?php wp_nonce_field( 'abiquifi_public_sso_register', 'abiquifi_public_sso_register_nonce' ); ?>
					<label for="abiquifi-sso-register-name">Nome</label>
					<input id="abiquifi-sso-register-name" class="fab-input fab-input--lg" name="register_name" type="text" required />

					<label for="abiquifi-sso-register-phone">Número de telefone</label>
					<input id="abiquifi-sso-register-phone" class="fab-input fab-input--lg" name="register_phone" type="tel" inputmode="tel" required />

					<label for="abiquifi-sso-register-email">E-mail</label>
					<input id="abiquifi-sso-register-email" class="fab-input fab-input--lg" name="register_email" type="email" required />

					<label for="abiquifi-sso-register-job-title">Cargo</label>
					<input id="abiquifi-sso-register-job-title" class="fab-input fab-input--lg" name="register_job_title" type="text" required />

					<button type="submit" class="fab-button fab-button--ghost">Acessar dicionário</button>
				</form>
				<p class="fab-login-alt">Se você já tem login, <a href="<?php echo esc_url( $this->public_login_url( $redirect_to ) ); ?>">clique aqui</a>.</p>
			<?php endif; ?>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	protected function registration_error_message( $state ) {
		if ( 'required' === $state ) {
			return 'Preencha todos os campos obrigatorios.';
		}

		if ( 'phone' === $state ) {
			return 'Informe um telefone valido com DDD.';
		}

		if ( 'email' === $state ) {
			return 'Informe um e-mail valido.';
		}

		if ( 'password' === $state ) {
			return 'As senhas informadas nao coincidem.';
		}

		if ( 'exists' === $state ) {
			return 'Ja existe uma conta com este e-mail.';
		}

		return 'Nao foi possivel criar a conta.';
	}

	protected function authenticate_credentials( $login, $password, $remember ) {
		$login = trim( (string) $login );

		if ( '' === $login || '' === $password ) {
			return new WP_Error( 'abiquifi_sso_required', 'Informe e-mail e senha.', array( 'status' => 400 ) );
		}

		$user = false;
		if ( is_email( $login ) ) {
			$user = get_user_by( 'email', $login );
		}

		if ( ! $user ) {
			$user = get_user_by( 'login', $login );
		}

		if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return new WP_Error( 'abiquifi_sso_invalid_credentials', 'Credenciais invalidas.', array( 'status' => 401 ) );
		}

		return $this->build_session_payload( $user, $remember );
	}

	protected function register_public_user( $name, $email, $password, $password_repeat, $remember, $extra = array() ) {
		$name  = trim( (string) $name );
		$email = trim( (string) $email );
		$phone = isset( $extra['phone'] ) ? sanitize_text_field( (string) $extra['phone'] ) : '';
		$job_title = isset( $extra['job_title'] ) ? sanitize_text_field( (string) $extra['job_title'] ) : '';
		$password = (string) $password;
		$password_repeat = (string) $password_repeat;

		if ( '' === $name || '' === $email || '' === $phone || '' === $job_title ) {
			if ( function_exists( 'abiquifi_mailer_log' ) ) {
				abiquifi_mailer_log(
					'Cadastro publico rejeitado por campos obrigatorios ausentes.',
					array(
						'name'      => '' !== $name,
						'email'     => '' !== $email,
						'phone'     => '' !== $phone,
						'job_title' => '' !== $job_title,
					)
				);
			}
			return new WP_Error( 'abiquifi_sso_required', 'Preencha os campos obrigatorios.', array( 'status' => 400 ) );
		}

		if ( ! $this->is_valid_phone( $phone ) ) {
			return new WP_Error( 'abiquifi_sso_invalid_phone', 'Telefone invalido.', array( 'status' => 400 ) );
		}

		if ( ! is_email( $email ) ) {
			return new WP_Error( 'abiquifi_sso_invalid_email', 'E-mail invalido.', array( 'status' => 400 ) );
		}

		if ( '' !== $password && strlen( $password ) < 8 ) {
			return new WP_Error( 'abiquifi_sso_password_short', 'A senha precisa ter pelo menos 8 caracteres.', array( 'status' => 400 ) );
		}

		if ( '' === $password && '' === $password_repeat ) {
			$password = wp_generate_password( 24, true, true );
			$password_repeat = $password;
		}

		if ( $password !== $password_repeat ) {
			return new WP_Error( 'abiquifi_sso_password_mismatch', 'As senhas nao coincidem.', array( 'status' => 400 ) );
		}

		$existing_user = get_user_by( 'email', $email );
		if ( $existing_user instanceof WP_User ) {
			return new WP_Error( 'abiquifi_sso_email_exists', 'Ja existe uma conta com este e-mail.', array( 'status' => 409 ) );
		}

		$user_id = $this->create_public_user_account(
			array(
				'name'     => $name,
				'email'    => $email,
				'password' => $password,
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		update_user_meta( $user_id, self::META_SOURCE, $this->site_role() );
		update_user_meta( $user_id, self::META_PHONE, $phone );
		update_user_meta( $user_id, self::META_JOB_TITLE, $job_title );

		$user = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			$this->send_registration_confirmation_email( $user );
		}

		return $this->build_session_payload( $user, $remember );
	}

	protected function create_public_user_account( $args ) {
		$name     = isset( $args['name'] ) ? trim( (string) $args['name'] ) : '';
		$email    = isset( $args['email'] ) ? sanitize_email( (string) $args['email'] ) : '';
		$password = isset( $args['password'] ) ? (string) $args['password'] : '';

		for ( $attempt = 0; $attempt < 5; $attempt++ ) {
			$username = 0 === $attempt ? $this->generate_username_from_email( $email ) : $this->generate_username_from_email( $email . '+' . $attempt );
			$user_id  = wp_insert_user(
				array(
					'user_login'   => $username,
					'user_email'   => $email,
					'user_pass'    => $password,
					'display_name' => $name,
					'first_name'   => $name,
					'role'         => 'subscriber',
				)
			);

			if ( ! is_wp_error( $user_id ) ) {
				return $user_id;
			}

			$error_codes = $user_id->get_error_codes();
			if ( ! in_array( 'existing_user_login', $error_codes, true ) && ! in_array( 'existing_user_email', $error_codes, true ) ) {
				return new WP_Error( 'abiquifi_sso_registration_failed', 'Nao foi possivel criar a conta.', array( 'status' => 500 ) );
			}

			if ( in_array( 'existing_user_email', $error_codes, true ) ) {
				return new WP_Error( 'abiquifi_sso_email_exists', 'Ja existe uma conta com este e-mail.', array( 'status' => 409 ) );
			}
		}

		return new WP_Error( 'abiquifi_sso_registration_failed', 'Nao foi possivel criar a conta.', array( 'status' => 500 ) );
	}

	protected function build_session_payload( $user, $remember ) {
		$session = $this->create_public_session( $user->ID, $remember );

		return array(
			'success'    => true,
			'token'      => $session['token'],
			'expires_at' => $session['expires_at'],
			'user'       => $this->normalize_user( $user ),
		);
	}

	protected function maybe_create_session_table() {
		global $wpdb;

		if ( ! $this->is_authority_site() ) {
			return;
		}

		$table_name      = $this->session_table_name();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_id varchar(64) NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			expires_at datetime NOT NULL,
			revoked_at datetime NULL DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY session_id (session_id),
			KEY user_id (user_id),
			KEY expires_at (expires_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	protected function session_table_name() {
		global $wpdb;

		return $wpdb->prefix . 'abiquifi_public_sessions';
	}

	protected function create_public_session( $user_id, $remember ) {
		global $wpdb;

		$token      = wp_generate_password( 48, false, false ) . wp_generate_password( 16, false, false );
		$expires_at = time() + self::SESSION_TTL;
		$now        = current_time( 'mysql', true );

		$wpdb->insert(
			$this->session_table_name(),
			array(
				'session_id' => wp_hash( $token ),
				'user_id'    => (int) $user_id,
				'expires_at' => gmdate( 'Y-m-d H:i:s', $expires_at ),
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%d', '%s', '%s', '%s' )
		);

		return array(
			'token'      => $token,
			'expires_at' => $expires_at,
		);
	}

	protected function resolve_authority_session( $token ) {
		global $wpdb;

		if ( '' === $token ) {
			return null;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->session_table_name()} WHERE session_id = %s LIMIT 1",
				wp_hash( $token )
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return null;
		}

		if ( ! empty( $row['revoked_at'] ) ) {
			return null;
		}

		if ( strtotime( $row['expires_at'] ) < time() ) {
			return null;
		}

		$user = get_user_by( 'id', (int) $row['user_id'] );
		if ( ! $user ) {
			return null;
		}

		return array(
			'token'      => $token,
			'expires_at' => strtotime( $row['expires_at'] ),
			'user'       => $this->normalize_user( $user ),
		);
	}

	protected function revoke_session_token( $token ) {
		global $wpdb;

		if ( '' === $token ) {
			return;
		}

		$wpdb->update(
			$this->session_table_name(),
			array(
				'revoked_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			),
			array(
				'session_id' => wp_hash( $token ),
			),
			array( '%s', '%s' ),
			array( '%s' )
		);
	}

	protected function normalize_user( $user ) {
		if ( is_array( $user ) ) {
			return $user;
		}

		return array(
			'ID'           => (int) $user->ID,
			'user_login'   => (string) $user->user_login,
			'user_email'   => (string) $user->user_email,
			'user_pass'    => (string) $user->user_pass,
			'display_name' => (string) $user->display_name,
			'first_name'   => (string) get_user_meta( $user->ID, 'first_name', true ),
			'last_name'    => (string) get_user_meta( $user->ID, 'last_name', true ),
			'phone'        => (string) get_user_meta( $user->ID, self::META_PHONE, true ),
			'job_title'    => (string) get_user_meta( $user->ID, self::META_JOB_TITLE, true ),
			'roles'        => array_values( (array) $user->roles ),
		);
	}

	public function get_public_user() {
		if ( ! empty( $this->resolved_user ) ) {
			return $this->resolved_user;
		}

		return null;
	}

	public function is_public_authenticated() {
		return (bool) $this->get_public_user();
	}

	protected function maybe_logout_stale_local_public_session() {
		if ( is_admin() || ! is_user_logged_in() ) {
			return;
		}

		$user = wp_get_current_user();
		if ( $user instanceof WP_User && $user->exists() && ! user_can( $user, 'edit_posts' ) ) {
			wp_logout();
		}
	}

	public function require_public_authentication( $redirect_url ) {
		if ( $this->is_public_authenticated() ) {
			return;
		}

		wp_safe_redirect( $this->public_login_url( $redirect_url ) );
		exit;
	}

	public function public_logout_url( $redirect_to ) {
		$url = home_url( '/sair/' );

		if ( '' !== $redirect_to ) {
			$url = add_query_arg( 'redirect_to', $redirect_to, $url );
		}

		return $url;
	}

	public function public_account_url() {
		return home_url( '/account/' );
	}

	public function public_logout_destination_url() {
		if ( $this->is_authority_site() ) {
			return $this->fabricamos_url( 'entrar' );
		}

		if ( $this->is_fabricamos_site() ) {
			return $this->fabricamos_url( 'catalogo' );
		}

		return home_url( '/' );
	}

	public function public_login_url( $redirect_to = '' ) {
		$path = $this->is_fabricamos_site() ? 'login' : 'log-in';

		return $this->public_frontend_url( $path, $redirect_to );
	}

	public function public_register_url( $redirect_to = '' ) {
		return $this->public_frontend_url( 'cadastro', $redirect_to );
	}

	public function remote_login( $login, $password, $remember ) {
		return $this->request_authority(
			'/login',
			array(
				'method' => 'POST',
				'body'   => array(
					'login'    => $login,
					'password' => $password,
					'remember' => $remember ? '1' : '0',
				),
			)
		);
	}

	public function remote_register( $name, $email, $password, $password_repeat, $profile = array() ) {
		return $this->request_authority(
			'/register',
			array(
				'method' => 'POST',
				'body'   => array_merge(
					array(
						'name'            => $name,
						'email'           => $email,
						'password'        => $password,
						'password_repeat' => $password_repeat,
					),
					(array) $profile
				),
			)
		);
	}

	public function remote_register_access( $name, $phone, $email, $job_title ) {
		return $this->request_authority(
			'/register',
			array(
				'method' => 'POST',
				'body'   => array(
					'name'      => $name,
					'phone'     => $phone,
					'email'     => $email,
					'job_title' => $job_title,
				),
			)
		);
	}

	public function remote_logout( $token ) {
		return $this->request_authority(
			'/logout',
			array(
				'method' => 'POST',
				'body'   => array(
					'token' => $token,
				),
			)
		);
	}

	protected function fetch_remote_session( $token ) {
		return $this->request_authority(
			'/session',
			array(
				'method' => 'GET',
				'query'  => array(
					'token' => $token,
				),
			)
		);
	}

	protected function request_authority( $path, $args ) {
		$url    = trailingslashit( $this->authority_url() ) . 'wp-json/abiquifi-sso/v1' . $path;
		$query  = isset( $args['query'] ) ? (array) $args['query'] : array();
		$method = isset( $args['method'] ) ? strtoupper( $args['method'] ) : 'GET';
		$body   = isset( $args['body'] ) ? (array) $args['body'] : array();

		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$response = wp_remote_request(
			$url,
			array(
				'method'      => $method,
				'timeout'     => 20,
				'redirection' => 2,
				'body'        => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'_transport_error' => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$data['_http_status'] = $code;
		return $data;
	}

	public function maybe_run_migration() {
		if ( ! $this->is_fabricamos_site() ) {
			return;
		}

		if ( get_option( self::OPTION_MIGRATION ) ) {
			return;
		}

		$this->push_local_public_users_to_authority();
		$this->pull_authority_public_users_to_local();
		update_option( self::OPTION_MIGRATION, current_time( 'mysql' ), false );
	}

	protected function push_local_public_users_to_authority() {
		$users = $this->export_public_users();
		foreach ( $users as $user ) {
			$this->request_authority(
				'/sync-user',
				array(
					'method' => 'POST',
					'body'   => array(
						'secret' => $this->internal_secret(),
						'user'   => $user,
					),
				)
			);
		}
	}

	protected function pull_authority_public_users_to_local() {
		$response = $this->request_authority(
			'/sync-user',
			array(
				'method' => 'POST',
				'body'   => array(
					'secret' => $this->internal_secret(),
					'mode'   => 'export',
				),
			)
		);

		if ( empty( $response['users'] ) || ! is_array( $response['users'] ) ) {
			return;
		}

		foreach ( $response['users'] as $user ) {
			$this->ensure_local_wordpress_user( $user );
		}
	}

	protected function export_public_users() {
		$users   = get_users(
			array(
				'number'  => 500,
				'orderby' => 'ID',
				'order'   => 'ASC',
			)
		);
		$payload = array();

		foreach ( $users as $user ) {
			if ( in_array( 'fabricante', (array) $user->roles, true ) ) {
				continue;
			}

			$payload[] = $this->normalize_user( $user );
		}

		return $payload;
	}

	public function ensure_local_wordpress_user( $user_data ) {
		global $wpdb;

		if ( empty( $user_data['user_email'] ) ) {
			return 0;
		}

		$email = sanitize_email( $user_data['user_email'] );
		$user  = get_user_by( 'email', $email );

		if ( $user ) {
			wp_update_user(
				array(
					'ID'           => (int) $user->ID,
					'user_login'   => empty( $user_data['user_login'] ) ? $user->user_login : $user_data['user_login'],
					'user_email'   => $email,
					'display_name' => empty( $user_data['display_name'] ) ? $user->display_name : $user_data['display_name'],
					'first_name'   => empty( $user_data['first_name'] ) ? '' : $user_data['first_name'],
					'last_name'    => empty( $user_data['last_name'] ) ? '' : $user_data['last_name'],
				)
			);

			if ( ! empty( $user_data['user_pass'] ) ) {
				$wpdb->update(
					$wpdb->users,
					array( 'user_pass' => $user_data['user_pass'] ),
					array( 'ID' => (int) $user->ID ),
					array( '%s' ),
					array( '%d' )
				);
				clean_user_cache( (int) $user->ID );
			}

			$this->sync_user_roles( (int) $user->ID, $user_data );
			update_user_meta( (int) $user->ID, self::META_SOURCE, $this->site_role() );

			return (int) $user->ID;
		}

		$desired_id = empty( $user_data['ID'] ) ? 0 : (int) $user_data['ID'];
		if ( $desired_id > 0 && ! get_user_by( 'id', $desired_id ) ) {
			$inserted = $wpdb->insert(
				$wpdb->users,
				array(
					'ID'               => $desired_id,
					'user_login'       => empty( $user_data['user_login'] ) ? $this->generate_username_from_email( $email ) : $user_data['user_login'],
					'user_pass'        => empty( $user_data['user_pass'] ) ? wp_hash_password( wp_generate_password( 24, true, true ) ) : $user_data['user_pass'],
					'user_nicename'    => sanitize_title( empty( $user_data['display_name'] ) ? $email : $user_data['display_name'] ),
					'user_email'       => $email,
					'user_url'         => '',
					'user_registered'  => current_time( 'mysql' ),
					'user_status'      => 0,
					'display_name'     => empty( $user_data['display_name'] ) ? $email : $user_data['display_name'],
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
			);

			if ( $inserted ) {
				$this->seed_user_meta( $desired_id, $user_data );
				$this->sync_user_roles( $desired_id, $user_data );
				clean_user_cache( $desired_id );
				return $desired_id;
			}
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => empty( $user_data['user_login'] ) ? $this->generate_username_from_email( $email ) : $user_data['user_login'],
				'user_email'   => $email,
				'user_pass'    => empty( $user_data['user_pass'] ) ? wp_generate_password( 24, true, true ) : $user_data['user_pass'],
				'display_name' => empty( $user_data['display_name'] ) ? $email : $user_data['display_name'],
				'first_name'   => empty( $user_data['first_name'] ) ? '' : $user_data['first_name'],
				'last_name'    => empty( $user_data['last_name'] ) ? '' : $user_data['last_name'],
				'role'         => ! empty( $user_data['roles'][0] ) ? $user_data['roles'][0] : 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			return 0;
		}

		if ( ! empty( $user_data['user_pass'] ) ) {
			$wpdb->update(
				$wpdb->users,
				array( 'user_pass' => $user_data['user_pass'] ),
				array( 'ID' => (int) $user_id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		$this->seed_user_meta( (int) $user_id, $user_data );
		$this->sync_user_roles( (int) $user_id, $user_data );
		clean_user_cache( (int) $user_id );

		return (int) $user_id;
	}

	protected function seed_user_meta( $user_id, $user_data ) {
		update_user_meta( $user_id, 'nickname', empty( $user_data['display_name'] ) ? $user_data['user_email'] : $user_data['display_name'] );
		update_user_meta( $user_id, 'first_name', empty( $user_data['first_name'] ) ? '' : $user_data['first_name'] );
		update_user_meta( $user_id, 'last_name', empty( $user_data['last_name'] ) ? '' : $user_data['last_name'] );
		update_user_meta( $user_id, self::META_PHONE, empty( $user_data['phone'] ) ? '' : $user_data['phone'] );
		update_user_meta( $user_id, self::META_JOB_TITLE, empty( $user_data['job_title'] ) ? '' : $user_data['job_title'] );
		update_user_meta( $user_id, self::META_SOURCE, $this->site_role() );
	}

	protected function sync_user_roles( $user_id, $user_data ) {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return;
		}

		$roles = empty( $user_data['roles'] ) ? array( 'subscriber' ) : array_values( array_unique( (array) $user_data['roles'] ) );

		if ( in_array( 'fabricante', $roles, true ) ) {
			return;
		}

		foreach ( $user->roles as $role ) {
			$user->remove_role( $role );
		}

		foreach ( $roles as $role ) {
			if ( get_role( $role ) ) {
				$user->add_role( $role );
			}
		}

		if ( empty( $user->roles ) ) {
			$user->set_role( 'subscriber' );
		}
	}

	protected function login_local_user( $user_id ) {
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true, is_ssl() );
		do_action( 'wp_login', get_userdata( $user_id )->user_login, get_userdata( $user_id ) );
	}

	public function set_public_session_from_response( $response ) {
		if ( empty( $response['token'] ) || empty( $response['user'] ) ) {
			return false;
		}

		$this->set_public_cookie( $response['token'], empty( $response['expires_at'] ) ? time() + self::SESSION_TTL : (int) $response['expires_at'] );
		$this->resolved_user    = $response['user'];
		$this->resolved_session = $response;
		$this->resolved_token   = $response['token'];

		if ( ! $this->should_bootstrap_wordpress_user() ) {
			return true;
		}

		$user_id = $this->ensure_local_wordpress_user( $response['user'] );

		if ( $user_id ) {
			$this->login_local_user( $user_id );
			return true;
		}

		return false;
	}

	public function set_public_cookie( $token, $expires_at ) {
		$params = array(
			'expires'  => (int) $expires_at,
			'path'     => '/',
			'domain'   => self::COOKIE_DOMAIN,
			'secure'   => true,
			'httponly' => true,
			'samesite' => 'Lax',
		);

		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie( self::COOKIE_NAME, $token, $params );
		} else {
			setcookie( self::COOKIE_NAME, $token, (int) $expires_at, '/; samesite=Lax', self::COOKIE_DOMAIN, true, true );
		}

		$_COOKIE[ self::COOKIE_NAME ] = $token;
	}

	public function clear_public_cookie() {
		if ( PHP_VERSION_ID >= 70300 ) {
			setcookie(
				self::COOKIE_NAME,
				'',
				array(
					'expires'  => time() - HOUR_IN_SECONDS,
					'path'     => '/',
					'domain'   => self::COOKIE_DOMAIN,
					'secure'   => true,
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
		} else {
			setcookie( self::COOKIE_NAME, '', time() - HOUR_IN_SECONDS, '/; samesite=Lax', self::COOKIE_DOMAIN, true, true );
		}

		unset( $_COOKIE[ self::COOKIE_NAME ] );
	}

	protected function read_cookie_token() {
		return isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : '';
	}

	protected function ensure_secret() {
		if ( ! get_option( self::OPTION_SECRET ) ) {
			update_option( self::OPTION_SECRET, $this->internal_secret(), false );
		}
	}

	protected function internal_secret() {
		$secret = get_option( self::OPTION_SECRET );
		if ( ! empty( $secret ) ) {
			return (string) $secret;
		}

		return self::INTERNAL_SECRET_FALLBACK;
	}

	protected function is_internal_secret_valid( $secret ) {
		return is_string( $secret ) && '' !== $secret && hash_equals( $this->internal_secret(), $secret );
	}

	public function authority_url() {
		$configured = esc_url_raw( (string) $this->read_env_value( 'ABIQUIFI_PUBLIC_SSO_AUTHORITY_URL', '' ) );
		if ( '' !== $configured ) {
			return trailingslashit( $configured );
		}

		if ( $this->is_authority_site() ) {
			return home_url( '/' );
		}

		return 'https://dicionario.abiquifi.questione.ai/';
	}

	public function fabricamos_url( $path = '' ) {
		$configured = esc_url_raw( (string) $this->read_env_value( 'ABIQUIFI_PUBLIC_SSO_FABRICAMOS_URL', '' ) );
		$base       = '' !== $configured
			? trailingslashit( $configured )
			: ( $this->is_fabricamos_site() ? home_url( '/' ) : 'https://fabricamos.abiquifi.questione.ai/' );

		if ( '' === $path ) {
			return $base;
		}

		return trailingslashit( $base ) . trim( $path, '/' ) . '/';
	}

	public function is_authority_site() {
		if ( 'authority' === $this->configured_site_role() ) {
			return true;
		}

		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return is_string( $host ) && false !== strpos( $host, 'dicionario.' );
	}

	public function is_fabricamos_site() {
		if ( 'fabricamos' === $this->configured_site_role() ) {
			return true;
		}

		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return is_string( $host ) && false !== strpos( $host, 'fabricamos.' );
	}

	public function is_main_site() {
		if ( 'main' === $this->configured_site_role() ) {
			return true;
		}

		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		return 'abiquifi.questione.ai' === $host;
	}

	protected function site_role() {
		if ( $this->is_authority_site() ) {
			return 'dictionary';
		}

		if ( $this->is_fabricamos_site() ) {
			return 'fabricamos';
		}

		return 'site';
	}

	protected function generate_username_from_email( $email ) {
		$base     = sanitize_user( current( explode( '@', $email ) ), true );
		$base     = '' === $base ? 'usuario' : $base;
		$username = $base;
		$index    = 1;

		while ( username_exists( $username ) ) {
			$username = $base . $index;
			$index++;
		}

		return $username;
	}

	protected function read_env_value( $key, $default = '' ) {
		$key = (string) $key;

		if ( '' === $key ) {
			return $default;
		}

		$value = getenv( $key );
		if ( false !== $value && '' !== trim( (string) $value ) ) {
			return trim( (string) $value );
		}

		if ( isset( $_ENV[ $key ] ) && '' !== trim( (string) $_ENV[ $key ] ) ) {
			return trim( (string) $_ENV[ $key ] );
		}

		if ( isset( $_SERVER[ $key ] ) && '' !== trim( (string) $_SERVER[ $key ] ) ) {
			return trim( (string) $_SERVER[ $key ] );
		}

		if ( defined( $key ) ) {
			$value = constant( $key );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return trim( (string) $value );
			}
		}

		return $default;
	}

	protected function configured_site_role() {
		$role = strtolower( (string) $this->read_env_value( 'ABIQUIFI_PUBLIC_SSO_SITE_ROLE', '' ) );

		return in_array( $role, array( 'authority', 'fabricamos', 'main' ), true ) ? $role : '';
	}

	protected function mail_from_email() {
		$sender = sanitize_email( $this->read_env_value( 'ABIQUIFI_MAIL_FROM_EMAIL', 'marketing@abiquifi.org.br' ) );

		if ( '' === $sender || ! is_email( $sender ) ) {
			$sender = sanitize_email( (string) get_option( 'admin_email', 'marketing@abiquifi.org.br' ) );
		}

		if ( '' === $sender || ! is_email( $sender ) ) {
			$sender = 'marketing@abiquifi.org.br';
		}

		return $sender;
	}

	protected function mail_from_name() {
		return $this->read_env_value( 'ABIQUIFI_MAIL_FROM_NAME', 'Fabricamos | Abiquifi' );
	}

	protected function send_registration_confirmation_email( $user ) {
		if ( ! $user instanceof WP_User || ! $user->exists() ) {
			return false;
		}

		$to = sanitize_email( (string) $user->user_email );
		if ( '' === $to || ! is_email( $to ) ) {
			return false;
		}

		$name          = $user->display_name ? $user->display_name : $user->user_login;
		$login_url     = $this->fabricamos_url( 'login' );
		$dictionary_url = trailingslashit( $this->authority_url() );
		$subject       = 'Cadastro confirmado | Fabricamos';
		$headers       = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . $this->mail_from_name() . ' <' . $this->mail_from_email() . '>',
		);
		$message       = sprintf(
			'<html><body style="margin:0;padding:0;background:#f4f7fb;font-family:Arial,sans-serif;color:#162b40;">' .
			'<div style="max-width:640px;margin:0 auto;padding:32px 20px;">' .
			'<div style="background:#ffffff;border-radius:16px;padding:32px;border:1px solid #d9e2ec;">' .
			'<p style="margin:0 0 16px;font-size:14px;letter-spacing:.08em;text-transform:uppercase;color:#6c8195;">Abiquifi</p>' .
			'<h1 style="margin:0 0 20px;font-size:28px;line-height:1.2;color:#0d2236;">Cadastro confirmado</h1>' .
			'<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Ola %1$s, seu cadastro foi concluido com sucesso.</p>' .
			'<p style="margin:0 0 16px;font-size:16px;line-height:1.6;">Ja e possivel acessar o Fabricamos e os ambientes conectados da Abiquifi usando o e-mail <strong>%2$s</strong>.</p>' .
			'<p style="margin:0 0 24px;font-size:16px;line-height:1.6;">Se voce nao reconhece este cadastro, basta ignorar este e-mail.</p>' .
			'<p style="margin:0 0 12px;"><a href="%3$s" style="display:inline-block;background:#0d2236;color:#ffffff;text-decoration:none;padding:14px 20px;border-radius:999px;font-weight:700;">Entrar no Fabricamos</a></p>' .
			'<p style="margin:0 0 24px;"><a href="%4$s" style="display:inline-block;color:#0d2236;text-decoration:underline;">Abrir o Dicionario</a></p>' .
			'<p style="margin:0;font-size:13px;line-height:1.6;color:#6c8195;">Mensagem automatica enviada por %5$s.</p>' .
			'</div></div></body></html>',
			esc_html( $name ),
			esc_html( $to ),
			esc_url( $login_url ),
			esc_url( $dictionary_url ),
			esc_html( $this->mail_from_name() )
		);

		return (bool) wp_mail( $to, $subject, $message, $headers );
	}

	protected function should_render_access_gate_modal() {
		return ! empty( $this->get_public_access_modal_context() );
	}

	protected function get_public_access_modal_context() {
		if ( is_admin() || $this->is_public_authenticated() ) {
			return array();
		}

		$current_url = remove_query_arg( array( 'register_error', 'registered', 'login_error' ), $this->current_request_url() );

		if ( $this->is_authority_site() ) {
			if ( ! $this->is_dictionary_access_request() || is_page( array( 'log-in', 'cadastro', 'account' ) ) ) {
				return array();
			}

			return array(
				'kicker'       => 'Acesso ao dicionário',
				'title'        => 'Preencha seus dados para continuar',
				'copy'         => 'Se você ainda não tem acesso, informe nome, telefone, e-mail e cargo. O acesso é liberado assim que o cadastro for concluído.',
				'submit_label' => 'Acessar dicionário',
				'redirect_to'  => $current_url,
				'return_url'   => $current_url,
				'login_url'    => $this->public_login_url( $current_url ),
				'form_action'  => $this->public_register_url(),
			);
		}

		if ( $this->is_fabricamos_site() && $this->is_fabricamos_public_access_request() ) {
			return array();
		}

		return array();
	}

	protected function is_dictionary_access_request() {
		if ( is_page( 'dicionario-dsf' ) ) {
			return true;
		}

		return isset( $_GET['public_access'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['public_access'] ) );
	}

	protected function is_fabricamos_public_access_request() {
		if ( is_singular( 'fabricante' ) ) {
			return true;
		}

		if ( is_front_page() ) {
			return true;
		}

		return is_page( array( 'fabricamos', 'catalogo' ) );
	}

	protected function is_valid_phone( $phone ) {
		$digits = preg_replace( '/\D+/', '', (string) $phone );
		$length = strlen( $digits );

		return $length >= 10 && $length <= 13;
	}

	protected function public_frontend_url( $path, $redirect_to = '' ) {
		$base_url = $this->is_fabricamos_site() ? home_url( '/' ) : $this->authority_url();
		$url      = trailingslashit( $base_url ) . trim( $path, '/' ) . '/';

		if ( '' !== $redirect_to ) {
			$url = add_query_arg( 'redirect_to', $redirect_to, $url );
		}

		return $url;
	}

	protected function current_request_url() {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	protected function request_path_matches( $path ) {
		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		$target_path  = wp_parse_url( home_url( '/' . trim( $path, '/' ) . '/' ), PHP_URL_PATH );

		return untrailingslashit( (string) $request_path ) === untrailingslashit( (string) $target_path );
	}

	protected function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		$value = strtolower( trim( (string) $value ) );
		return in_array( $value, array( '1', 'true', 'yes', 'on', 'forever' ), true );
	}
}
