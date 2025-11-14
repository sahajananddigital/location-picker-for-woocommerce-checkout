/**
 * Location Picker for WooCommerce Checkout
 *
 * Integrates Google Maps location picker with WooCommerce block checkout.
 */

(function() {
	'use strict';

	let map;
	let marker;
	let autocomplete;
	let geocoder;
	let locationPickerInitialized = false;

	/**
	 * Initialize location picker when Google Maps API is loaded.
	 */
	function initLocationPicker() {
		if (locationPickerInitialized) {
			return;
		}

		// Check if Google Maps is loaded.
		if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
			setTimeout(initLocationPicker, 100);
			return;
		}

		// Wait for WooCommerce checkout block to be ready.
		if (typeof wp === 'undefined' || !wp.hooks) {
			setTimeout(initLocationPicker, 100);
			return;
		}

		// Wait for checkout block to be mounted.
		wp.hooks.addAction('woocommerce.checkout.block.update', 'lpwc', function() {
			setTimeout(initializeLocationPicker, 500);
		});

		// Also try to initialize immediately if checkout is already loaded.
		setTimeout(initializeLocationPicker, 1000);
	}

	// Make it available globally for callback if needed.
	window.initLocationPicker = initLocationPicker;

	/**
	 * Initialize the location picker functionality.
	 */
	function initializeLocationPicker() {
		if (locationPickerInitialized) {
			return;
		}

		// Check if we're on checkout page.
		const checkoutBlock = document.querySelector('.wp-block-woocommerce-checkout');
		if (!checkoutBlock) {
			return;
		}

		// Create location picker container.
		const locationPickerContainer = createLocationPickerContainer();
		if (!locationPickerContainer) {
			return;
		}

		// Initialize Google Maps components.
		geocoder = new google.maps.Geocoder();
		initializeMap(locationPickerContainer);
		initializeAutocomplete(locationPickerContainer);

		locationPickerInitialized = true;
	}

	/**
	 * Create location picker container element.
	 */
	function createLocationPickerContainer() {
		// Find the checkout form container.
		const checkoutForm = document.querySelector('.wc-block-checkout__form');
		if (!checkoutForm) {
			return null;
		}

		// Check if container already exists.
		let container = document.getElementById('lpwc-location-picker-container');
		if (container) {
			return container;
		}

		// Create container.
		container = document.createElement('div');
		container.id = 'lpwc-location-picker-container';
		container.className = 'lpwc-location-picker-container';

		// Create search input.
		const searchWrapper = document.createElement('div');
		searchWrapper.className = 'lpwc-search-wrapper';
		
		const searchInput = document.createElement('input');
		searchInput.type = 'text';
		searchInput.id = 'lpwc-address-search';
		searchInput.className = 'lpwc-address-search';
		searchInput.placeholder = 'Search for an address or click on the map...';
		
		searchWrapper.appendChild(searchInput);

		// Create map container.
		const mapContainer = document.createElement('div');
		mapContainer.id = 'lpwc-map';
		mapContainer.className = 'lpwc-map';

		// Create button to toggle map visibility.
		const toggleButton = document.createElement('button');
		toggleButton.type = 'button';
		toggleButton.className = 'lpwc-toggle-map';
		toggleButton.textContent = 'Show Map';
		toggleButton.addEventListener('click', function() {
			const isVisible = mapContainer.style.display !== 'none';
					mapContainer.style.display = isVisible ? 'none' : 'block';
					toggleButton.textContent = isVisible ? 'Show Map' : 'Hide Map';
		});

		// Initially hide map.
		mapContainer.style.display = 'none';

		container.appendChild(searchWrapper);
		container.appendChild(toggleButton);
		container.appendChild(mapContainer);

		// Insert before billing address fields.
		const billingFields = checkoutForm.querySelector('.wc-block-components-address-form');
		if (billingFields) {
			billingFields.parentNode.insertBefore(container, billingFields);
		} else {
			checkoutForm.insertBefore(container, checkoutForm.firstChild);
		}

		return container;
	}

	/**
	 * Initialize Google Maps.
	 */
	function initializeMap(container) {
		const mapElement = container.querySelector('#lpwc-map');
		if (!mapElement) {
			return;
		}

		// Default center (can be customized via settings).
		// Get data from integration or fallback to defaults.
		// WooCommerce Blocks makes integration data available via window.wc[integrationName]
		const integrationData = window.wc?.['location-picker-woocommerce'] || {};
		const defaultCenter = integrationData.defaultCenter || lpwcData?.defaultCenter || { lat: 0, lng: 0 };
		const defaultZoom = integrationData.defaultZoom || lpwcData?.defaultZoom || 2;

		// Try to get user's current location.
		if (navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(
				function(position) {
					defaultCenter.lat = position.coords.latitude;
					defaultCenter.lng = position.coords.longitude;
					createMap(defaultCenter, 15);
				},
				function() {
					createMap(defaultCenter, defaultZoom);
				}
			);
		} else {
			createMap(defaultCenter, defaultZoom);
		}

		function createMap(center, zoom) {
			map = new google.maps.Map(mapElement, {
				center: center,
				zoom: zoom,
				mapTypeControl: true,
				streetViewControl: true,
				fullscreenControl: true,
			});

			// Add click listener to map.
			map.addListener('click', function(event) {
				placeMarkerAndGetAddress(event.latLng);
			});

			// Create initial marker.
			marker = new google.maps.Marker({
				map: map,
				draggable: true,
				position: center,
			});

			// Add drag listener to marker.
			marker.addListener('dragend', function(event) {
				getAddressFromLatLng(event.latLng);
			});
		}
	}

	/**
	 * Initialize autocomplete for address search.
	 */
	function initializeAutocomplete(container) {
		const searchInput = container.querySelector('#lpwc-address-search');
		if (!searchInput) {
			return;
		}

		// Get restricted countries from integration data or fallback.
		const integrationData = window.wc?.['location-picker-woocommerce'] || {};
		const restrictedCountries = integrationData.restrictedCountries || lpwcData?.restrictedCountries || [];

		// Configure autocomplete options.
		const autocompleteOptions = {
			types: ['address'],
		};

		// Apply country restrictions if any are set.
		if (restrictedCountries && restrictedCountries.length > 0) {
			autocompleteOptions.componentRestrictions = {
				country: restrictedCountries,
			};
		}

		autocomplete = new google.maps.places.Autocomplete(searchInput, autocompleteOptions);

		// When place is selected, update map and fill form.
		autocomplete.addListener('place_changed', function() {
			const place = autocomplete.getPlace();
			if (!place.geometry) {
				return;
			}

			// Update map and marker.
			if (place.geometry.viewport) {
				map.fitBounds(place.geometry.viewport);
			} else {
				map.setCenter(place.geometry.location);
				map.setZoom(17);
			}

			if (marker) {
				marker.setPosition(place.geometry.location);
			} else {
				marker = new google.maps.Marker({
					map: map,
					draggable: true,
					position: place.geometry.location,
				});
				marker.addListener('dragend', function(event) {
					getAddressFromLatLng(event.latLng);
				});
			}

			// Fill checkout form with address.
			fillCheckoutForm(place);
		});
	}

	/**
	 * Place marker on map and get address.
	 */
	function placeMarkerAndGetAddress(latLng) {
		if (marker) {
			marker.setPosition(latLng);
		} else {
			marker = new google.maps.Marker({
				map: map,
				draggable: true,
				position: latLng,
			});
			marker.addListener('dragend', function(event) {
				getAddressFromLatLng(event.latLng);
			});
		}

		map.setCenter(latLng);
		getAddressFromLatLng(latLng);
	}

	/**
	 * Get address from latitude/longitude.
	 */
	function getAddressFromLatLng(latLng) {
		geocoder.geocode({ location: latLng }, function(results, status) {
			if (status === 'OK' && results[0]) {
				// Update search input.
				const searchInput = document.getElementById('lpwc-address-search');
				if (searchInput) {
					searchInput.value = results[0].formatted_address;
				}

				// Convert to Place object format and fill form.
				const place = {
					address_components: results[0].address_components,
					formatted_address: results[0].formatted_address,
					geometry: {
						location: latLng,
					},
				};

				fillCheckoutForm(place);
			}
		});
	}

	/**
	 * Fill WooCommerce checkout form with address data.
	 */
	function fillCheckoutForm(place) {
		if (!place.address_components) {
			return;
		}

		// Parse address components.
		const addressData = parseAddressComponents(place.address_components);

		// Fill billing address fields.
		fillAddressFields('billing', addressData);

		// If shipping is different, don't auto-fill shipping.
		// User can manually copy if needed or we can add a "Copy to shipping" button.

		// Trigger change events to ensure WooCommerce processes the data.
		triggerFieldUpdates('billing', addressData);
	}

	/**
	 * Parse Google Maps address components.
	 */
	function parseAddressComponents(components) {
		const addressData = {
			address_1: '',
			address_2: '',
			city: '',
			state: '',
			postcode: '',
			country: '',
		};

		components.forEach(function(component) {
			const type = component.types[0];

			switch (type) {
				case 'street_number':
					addressData.address_1 = component.long_name + ' ';
					break;
				case 'route':
					addressData.address_1 += component.long_name;
					break;
				case 'subpremise':
					addressData.address_2 = component.long_name;
					break;
				case 'locality':
					addressData.city = component.long_name;
					break;
				case 'administrative_area_level_1':
					addressData.state = component.short_name;
					break;
				case 'postal_code':
					addressData.postcode = component.long_name;
					break;
				case 'country':
					addressData.country = component.short_name;
					break;
			}
		});

		return addressData;
	}

	/**
	 * Fill address fields in checkout form.
	 */
	function fillAddressFields(type, addressData) {
		// WooCommerce block checkout uses specific field names.
		const fieldMappings = {
			address_1: `#${type}_address_1`,
			address_2: `#${type}_address_2`,
			city: `#${type}_city`,
			state: `#${type}_state`,
			postcode: `#${type}_postcode`,
			country: `#${type}_country`,
		};

		Object.keys(fieldMappings).forEach(function(key) {
			const selector = fieldMappings[key];
			const field = document.querySelector(selector);

			if (field && addressData[key]) {
				const value = addressData[key];

				// Handle select elements (country, state dropdowns).
				if (field.tagName === 'SELECT') {
					// Try to find option by value.
					const option = Array.from(field.options).find(function(opt) {
						return opt.value === value || opt.value.toLowerCase() === value.toLowerCase();
					});

					if (option) {
						field.value = option.value;
					} else {
						// Try to set directly.
						field.value = value;
					}

					// Trigger change event for select.
					const changeEvent = new Event('change', { bubbles: true });
					field.dispatchEvent(changeEvent);
				} else {
					// Handle input/text fields.
					// Set value.
					field.value = value;

					// Trigger input event for React components.
					const inputEvent = new Event('input', { bubbles: true });
					field.dispatchEvent(inputEvent);

					// Trigger change event.
					const changeEvent = new Event('change', { bubbles: true });
					field.dispatchEvent(changeEvent);

					// For React controlled components, also set the value property.
					const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
						window.HTMLInputElement.prototype,
						'value'
					)?.set;
					if (nativeInputValueSetter) {
						nativeInputValueSetter.call(field, value);
						field.dispatchEvent(new Event('input', { bubbles: true }));
					}
				}
			}
		});
	}

	/**
	 * Trigger field updates using WooCommerce Blocks data store if available.
	 */
	function triggerFieldUpdates(type, addressData) {
		// Try to use WooCommerce Blocks checkout data store.
		if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
			try {
				// WooCommerce Blocks checkout uses 'wc/store/checkout' store.
				const dispatch = wp.data.dispatch('wc/store/checkout');

				// Update billing address.
				if (type === 'billing' && dispatch.setBillingAddress) {
					dispatch.setBillingAddress({
						address_1: addressData.address_1 || '',
						address_2: addressData.address_2 || '',
						city: addressData.city || '',
						state: addressData.state || '',
						postcode: addressData.postcode || '',
						country: addressData.country || '',
					});
				}

				// Also try the legacy cart store method as fallback.
				if (type === 'billing') {
					const cartDispatch = wp.data.dispatch('wc/store/cart');
					if (cartDispatch && cartDispatch.setBillingAddress) {
						cartDispatch.setBillingAddress({
							address_1: addressData.address_1 || '',
							address_2: addressData.address_2 || '',
							city: addressData.city || '',
							state: addressData.state || '',
							postcode: addressData.postcode || '',
							country: addressData.country || '',
						});
					}
				}
			} catch (e) {
				// Fallback to DOM manipulation if data store is not available.
				console.log('WooCommerce Blocks data store not available, using DOM manipulation', e);
			}
		}
	}

	// Initialize when DOM is ready and Google Maps is loaded.
	function startInitialization() {
		if (typeof google !== 'undefined' && google.maps && google.maps.places) {
			initLocationPicker();
		} else {
			// Wait for Google Maps to load.
			setTimeout(startInitialization, 100);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', startInitialization);
	} else {
		startInitialization();
	}

})();

