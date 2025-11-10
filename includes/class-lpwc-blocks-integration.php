<?php
/**
 * WooCommerce Blocks Integration.
 *
 * @package LocationPickerWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Blocks Integration Class.
 */
class LPWC_Blocks_Integration {
	/**
	 * Instance.
	 *
	 * @var LPWC_Blocks_Integration
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return LPWC_Blocks_Integration
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Register checkout block integration.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_block_integration' ) );

		// Add admin settings page.
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register block integration.
	 */
	public function register_block_integration() {
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface' ) ) {
			return;
		}

		require_once LPWC_PLUGIN_DIR . 'includes/class-lpwc-checkout-block-integration.php';
		
		// Register the integration using the filter.
		add_filter(
			'woocommerce_blocks_checkout_block_registration',
			function( $integration_registry ) {
				$integration_registry->register( new LPWC_Checkout_Block_Integration() );
				return $integration_registry;
			}
		);
	}

	/**
	 * Add admin menu.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Location Picker Settings', 'location-picker-woocommerce' ),
			__( 'Location Picker', 'location-picker-woocommerce' ),
			'manage_options',
			'lpwc-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'lpwc_settings', 'lpwc_google_maps_api_key' );
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Location Picker Settings', 'location-picker-woocommerce' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'lpwc_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="lpwc_google_maps_api_key">
								<?php esc_html_e( 'Google Maps API Key', 'location-picker-woocommerce' ); ?>
							</label>
						</th>
						<td>
							<input
								type="text"
								id="lpwc_google_maps_api_key"
								name="lpwc_google_maps_api_key"
								value="<?php echo esc_attr( get_option( 'lpwc_google_maps_api_key', '' ) ); ?>"
								class="regular-text"
							/>
							<p class="description">
								<?php
								printf(
									/* translators: %s: Google Maps API documentation URL */
									esc_html__( 'Get your API key from %s', 'location-picker-woocommerce' ),
									'<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank">Google Maps Platform</a>'
								);
								?>
							</p>
							<p class="description">
								<?php esc_html_e( 'Make sure to enable the following APIs:', 'location-picker-woocommerce' ); ?>
							</p>
							<ul class="description">
								<li><?php esc_html_e( 'Maps JavaScript API', 'location-picker-woocommerce' ); ?></li>
								<li><?php esc_html_e( 'Places API', 'location-picker-woocommerce' ); ?></li>
							</ul>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

