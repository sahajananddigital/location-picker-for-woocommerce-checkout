# Location Picker for WooCommerce Checkout

A WooCommerce addon that adds a Google Maps location picker to the WooCommerce block checkout, allowing customers to select their address on a map or search for it, and automatically fill the checkout form fields.

## Features

- 🗺️ Interactive Google Maps location picker
- 🔍 Address autocomplete search
- 📍 Click on map or drag marker to select location
- ✨ Automatically fills WooCommerce checkout address fields
- 🎨 Clean, modern UI that integrates seamlessly with WooCommerce block checkout
- 📱 Responsive design for mobile devices
- 🔒 Secure API key management via WordPress settings

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- WooCommerce 6.0 or higher
- WooCommerce Blocks plugin (usually included with WooCommerce)
- Google Maps API key with the following APIs enabled:
  - Maps JavaScript API
  - Places API

## Installation

1. Download or clone this repository into your WordPress plugins directory:
   ```bash
   cd wp-content/plugins
   git clone https://github.com/sahajananddigital/location-picker-for-woocommerce-checkout.git
   ```

2. Activate the plugin through the 'Plugins' menu in WordPress.

3. Go to **Settings > Location Picker** in your WordPress admin.

4. Enter your Google Maps API key and save.

## Getting a Google Maps API Key

1. Go to the [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Enable the following APIs:
   - Maps JavaScript API
   - Places API
4. Go to "Credentials" and create an API key
5. (Optional but recommended) Restrict the API key to your domain
6. Copy the API key and paste it into the plugin settings

## Usage

Once installed and configured:

1. The location picker will automatically appear on your WooCommerce block checkout page
2. Customers can:
   - **Search for an address** using the search box (with autocomplete)
   - **Click on the map** to select a location
   - **Drag the marker** to adjust the location
3. When a location is selected, the checkout form fields will be automatically filled with:
   - Street address
   - City
   - State/Province
   - Postal/ZIP code
   - Country

## How It Works

The plugin integrates with WooCommerce's block checkout system using the `IntegrationInterface`. It:

1. Adds a location picker component above the billing address fields
2. Uses Google Maps JavaScript API and Places API for geocoding
3. Automatically fills the checkout form using WooCommerce Blocks data store
4. Works seamlessly with the React-based block checkout

## File Structure

```
location-picker-for-woocommerce-checkout/
├── assets/
│   ├── css/
│   │   ├── location-picker.css    # Frontend styles
│   │   └── admin.css              # Admin styles
│   └── js/
│       └── location-picker.js     # Main JavaScript functionality
├── includes/
│   ├── class-lpwc-blocks-integration.php      # Blocks integration handler
│   └── class-lpwc-checkout-block-integration.php  # Checkout block integration
├── location-picker-woocommerce.php            # Main plugin file
└── README.md                                   # This file
```

## Development

### Prerequisites

- Node.js (for any future build processes)
- WordPress development environment
- WooCommerce with block checkout enabled

### Testing

1. Ensure you have a test WooCommerce store with products
2. Use the block checkout (not classic checkout)
3. Test the location picker on the checkout page
4. Verify that address fields are filled correctly

## Troubleshooting

### Location picker doesn't appear

- Make sure you're using WooCommerce block checkout (not classic checkout)
- Verify that WooCommerce Blocks is installed and active
- Check that your Google Maps API key is valid and has the required APIs enabled
- Check browser console for JavaScript errors

### Address fields not filling

- Ensure you're using the block checkout (the plugin only works with block checkout)
- Check browser console for errors
- Verify that the Google Maps API key has Places API enabled

### Map not loading

- Verify your Google Maps API key is correct
- Check that Maps JavaScript API is enabled in Google Cloud Console
- Ensure your API key isn't restricted in a way that blocks your domain
- Check browser console for API errors

## Support

For issues, feature requests, or contributions, please visit the [GitHub repository](https://github.com/sahajananddigital/location-picker-for-woocommerce-checkout).

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Built for WooCommerce block checkout
- Uses Google Maps JavaScript API
- Integrates with WooCommerce Blocks integration system

