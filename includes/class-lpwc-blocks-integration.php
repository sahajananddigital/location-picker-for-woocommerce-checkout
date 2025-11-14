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
		register_setting( 'lpwc_settings', 'lpwc_restricted_countries', array(
			'type' => 'array',
			'sanitize_callback' => array( $this, 'sanitize_countries' ),
		) );
	}

	/**
	 * Sanitize countries array.
	 *
	 * @param array $countries Countries array.
	 * @return array Sanitized countries.
	 */
	public function sanitize_countries( $countries ) {
		if ( ! is_array( $countries ) ) {
			return array();
		}

		// Get valid country codes.
		$valid_countries = $this->get_country_codes();

		// Filter to only include valid country codes.
		return array_intersect( $countries, $valid_countries );
	}

	/**
	 * Get list of country codes.
	 *
	 * @return array Country codes.
	 */
	private function get_country_codes() {
		// Use WooCommerce countries if available, otherwise use ISO codes.
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			$countries = WC()->countries->get_countries();
			return array_keys( $countries );
		}

		// Fallback to common ISO country codes.
		return array(
			'US', 'CA', 'GB', 'AU', 'DE', 'FR', 'IT', 'ES', 'NL', 'BE',
			'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'PL', 'IE', 'PT', 'GR',
			'CZ', 'HU', 'RO', 'BG', 'HR', 'SK', 'SI', 'LT', 'LV', 'EE',
			'JP', 'CN', 'IN', 'KR', 'SG', 'MY', 'TH', 'PH', 'ID', 'VN',
			'BR', 'MX', 'AR', 'CL', 'CO', 'PE', 'ZA', 'EG', 'AE', 'SA',
		);
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page() {
		$api_key = get_option( 'lpwc_google_maps_api_key', '' );
		$restricted_countries = get_option( 'lpwc_restricted_countries', array() );
		$countries = $this->get_countries_list();
		?>
		<div class="wrap lpwc-settings-page">
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
								value="<?php echo esc_attr( $api_key ); ?>"
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
					<tr>
						<th scope="row">
							<label for="lpwc_restricted_countries">
								<?php esc_html_e( 'Restrict to Countries', 'location-picker-woocommerce' ); ?>
							</label>
						</th>
						<td>
							<select
								id="lpwc_restricted_countries"
								name="lpwc_restricted_countries[]"
								multiple="multiple"
								size="10"
								class="lpwc-multi-select"
								style="width: 100%; max-width: 500px;"
							>
								<?php foreach ( $countries as $code => $name ) : ?>
									<option
										value="<?php echo esc_attr( $code ); ?>"
										<?php selected( in_array( $code, $restricted_countries, true ), true ); ?>
									>
										<?php echo esc_html( $name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description">
								<?php esc_html_e( 'Select countries to restrict address autocomplete results. Hold Ctrl (Windows) or Cmd (Mac) to select multiple countries. Leave empty to allow all countries.', 'location-picker-woocommerce' ); ?>
							</p>
							<p class="description">
								<button type="button" class="button lpwc-select-all-countries" style="margin-top: 5px;">
									<?php esc_html_e( 'Select All', 'location-picker-woocommerce' ); ?>
								</button>
								<button type="button" class="button lpwc-deselect-all-countries" style="margin-top: 5px;">
									<?php esc_html_e( 'Deselect All', 'location-picker-woocommerce' ); ?>
								</button>
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('.lpwc-select-all-countries').on('click', function() {
				$('#lpwc_restricted_countries option').prop('selected', true);
			});
			$('.lpwc-deselect-all-countries').on('click', function() {
				$('#lpwc_restricted_countries option').prop('selected', false);
			});
		});
		</script>
		<?php
	}

	/**
	 * Get countries list.
	 *
	 * @return array Countries array with code => name.
	 */
	private function get_countries_list() {
		// Use WooCommerce countries if available.
		if ( class_exists( 'WooCommerce' ) && function_exists( 'WC' ) ) {
			return WC()->countries->get_countries();
		}

		// Fallback to common countries.
		return array(
			'US' => 'United States',
			'CA' => 'Canada',
			'GB' => 'United Kingdom',
			'AU' => 'Australia',
			'DE' => 'Germany',
			'FR' => 'France',
			'IT' => 'Italy',
			'ES' => 'Spain',
			'NL' => 'Netherlands',
			'BE' => 'Belgium',
			'AT' => 'Austria',
			'CH' => 'Switzerland',
			'SE' => 'Sweden',
			'NO' => 'Norway',
			'DK' => 'Denmark',
			'FI' => 'Finland',
			'PL' => 'Poland',
			'IE' => 'Ireland',
			'PT' => 'Portugal',
			'GR' => 'Greece',
			'CZ' => 'Czech Republic',
			'HU' => 'Hungary',
			'RO' => 'Romania',
			'BG' => 'Bulgaria',
			'HR' => 'Croatia',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'LT' => 'Lithuania',
			'LV' => 'Latvia',
			'EE' => 'Estonia',
			'JP' => 'Japan',
			'CN' => 'China',
			'IN' => 'India',
			'KR' => 'South Korea',
			'SG' => 'Singapore',
			'MY' => 'Malaysia',
			'TH' => 'Thailand',
			'PH' => 'Philippines',
			'ID' => 'Indonesia',
			'VN' => 'Vietnam',
			'BR' => 'Brazil',
			'MX' => 'Mexico',
			'AR' => 'Argentina',
			'CL' => 'Chile',
			'CO' => 'Colombia',
			'PE' => 'Peru',
			'ZA' => 'South Africa',
			'EG' => 'Egypt',
			'AE' => 'United Arab Emirates',
			'SA' => 'Saudi Arabia',
		);
	}
}

