<?php
/**
 * Plugin Name: Location Picker for WooCommerce Checkout
 * Plugin URI: https://github.com/yourusername/location-picker-for-woocommerce-checkout
 * Description: Add Google Maps location picker to WooCommerce block checkout that automatically fills address fields.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: location-picker-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 *
 * @package LocationPickerWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin constants.
define( 'LPWC_VERSION', '1.0.0' );
define( 'LPWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LPWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'LPWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main plugin class.
 */
class Location_Picker_WooCommerce {
	/**
	 * Plugin instance.
	 *
	 * @var Location_Picker_WooCommerce
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @return Location_Picker_WooCommerce
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
		add_action( 'plugins_loaded', array( $this, 'init' ) );
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
	}

	/**
	 * Initialize plugin.
	 */
	public function init() {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		// Check if WooCommerce Blocks is available.
		if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Package' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_blocks_missing_notice' ) );
			return;
		}

		// Load plugin functionality.
		$this->load_dependencies();
		$this->register_assets();
	}

	/**
	 * Load plugin dependencies.
	 */
	private function load_dependencies() {
		require_once LPWC_PLUGIN_DIR . 'includes/class-lpwc-blocks-integration.php';
		LPWC_Blocks_Integration::get_instance();
	}

	/**
	 * Register and enqueue assets.
	 */
	private function register_assets() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
	}

	/**
	 * Enqueue frontend scripts and styles.
	 */
	public function enqueue_scripts() {
		// Only load on checkout page.
		if ( ! is_checkout() ) {
			return;
		}

		// Get Google Maps API key from settings.
		$api_key = get_option( 'lpwc_google_maps_api_key', '' );

		if ( empty( $api_key ) ) {
			return;
		}

		// Enqueue Google Maps API.
		wp_enqueue_script(
			'google-maps-api',
			'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $api_key ) . '&libraries=places',
			array(),
			LPWC_VERSION,
			true
		);

		// Enqueue plugin scripts.
		wp_enqueue_script(
			'lpwc-location-picker',
			LPWC_PLUGIN_URL . 'assets/js/location-picker.js',
			array( 'google-maps-api', 'wp-hooks' ),
			LPWC_VERSION,
			true
		);

		// Enqueue plugin styles.
		wp_enqueue_style(
			'lpwc-location-picker',
			LPWC_PLUGIN_URL . 'assets/css/location-picker.css',
			array(),
			LPWC_VERSION
		);

		// Localize script with data.
		wp_localize_script(
			'lpwc-location-picker',
			'lpwcData',
			array(
				'apiKey' => $api_key,
				'defaultZoom' => 15,
				'defaultCenter' => array(
					'lat' => 0,
					'lng' => 0,
				),
			)
		);
	}

	/**
	 * Enqueue admin scripts and styles.
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_lpwc-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'lpwc-admin',
			LPWC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			LPWC_VERSION
		);
	}

	/**
	 * WooCommerce missing notice.
	 */
	public function woocommerce_missing_notice() {
		?>
		<div class="error">
			<p>
				<strong><?php esc_html_e( 'Location Picker for WooCommerce Checkout', 'location-picker-woocommerce' ); ?></strong>
				<?php esc_html_e( 'requires WooCommerce to be installed and active.', 'location-picker-woocommerce' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * WooCommerce Blocks missing notice.
	 */
	public function woocommerce_blocks_missing_notice() {
		?>
		<div class="error">
			<p>
				<strong><?php esc_html_e( 'Location Picker for WooCommerce Checkout', 'location-picker-woocommerce' ); ?></strong>
				<?php esc_html_e( 'requires WooCommerce Blocks to be installed and active.', 'location-picker-woocommerce' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Set default options.
		if ( ! get_option( 'lpwc_google_maps_api_key' ) ) {
			add_option( 'lpwc_google_maps_api_key', '' );
		}
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Clean up if needed.
	}
}

/**
 * Initialize the plugin.
 */
function lpwc_init() {
	return Location_Picker_WooCommerce::get_instance();
}

// Start the plugin.
lpwc_init();

