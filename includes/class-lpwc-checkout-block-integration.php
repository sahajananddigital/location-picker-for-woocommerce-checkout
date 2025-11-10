<?php
/**
 * Checkout Block Integration.
 *
 * @package LocationPickerWooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Checkout Block Integration Class.
 */
class LPWC_Checkout_Block_Integration implements IntegrationInterface {
	/**
	 * The name of the integration.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'location-picker-woocommerce';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		// This is handled by the main plugin class via enqueue_scripts.
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'lpwc-location-picker' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array();
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return array(
			'apiKey' => get_option( 'lpwc_google_maps_api_key', '' ),
			'defaultCenter' => array(
				'lat' => 0,
				'lng' => 0,
			),
			'defaultZoom' => 15,
		);
	}
}

