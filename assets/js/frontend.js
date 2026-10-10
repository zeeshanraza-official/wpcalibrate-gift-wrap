/**
 * Frontend JavaScript for WPCalibrate Gift Wrap.
 * Handles product wrap toggling, variable product reactivity, and checkout updates.
 */

/* global wpcalibrateGiftWrap, jQuery */
( function( $ ) {
	'use strict';

	$( function() {
		// 1. Single Product Page Wrap Selector.
		const $messageContainer = $( '#wpcalibrate-product-message-container' );
		const $styleRadios = $( 'input[name="wpcalibrate_gift_wrap_style"]' );

		function updateProductMessageVisibility() {
			const selectedVal = $( 'input[name="wpcalibrate_gift_wrap_style"]:checked' ).val();
			if ( selectedVal && selectedVal !== 'none' ) {
				$messageContainer.slideDown( 200 );
			} else {
				$messageContainer.slideUp( 200 );
			}
		}

		$styleRadios.on( 'change', updateProductMessageVisibility );
		updateProductMessageVisibility();

		// Variable product reactivity: refresh state on variation selection.
		$( '.variations_form' ).on( 'found_variation', function( event, variation ) {
			// If variation is virtual or not eligible, can adapt selector if needed.
		} );

		// 2. Checkout Whole-Order Selector.
		const $checkoutSelect = $( '#wpcalibrate_checkout_order_style' );
		const $checkoutMsgContainer = $( '#wpcalibrate_checkout_message_container' );

		$checkoutSelect.on( 'change', function() {
			const selectedVal = $( this ).val();
			if ( selectedVal && selectedVal !== 'none' ) {
				$checkoutMsgContainer.slideDown( 200 );
			} else {
				$checkoutMsgContainer.slideUp( 200 );
			}
			// Trigger WooCommerce checkout recalculation to update fees and totals.
			$( 'body' ).trigger( 'update_checkout' );
		} );

		// 3. Cart Page Whole-Order Wrap Selector AJAX handler (if AJAX submission used).
		$( document ).on( 'change', '.wpcalibrate-order-style-select-ajax', function() {
			const styleId = $( this ).val();
			const message = $( '#wpcalibrate_order_message' ).val() || '';

			$.ajax( {
				url: wpcalibrateGiftWrap.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wpcalibrate_set_whole_order_wrap',
					security: wpcalibrateGiftWrap.wholeOrderNonce,
					style_id: styleId,
					message: message,
				},
				success: function( response ) {
					if ( response.success ) {
						$( '[name="update_cart"]' ).prop( 'disabled', false ).trigger( 'click' );
					}
				},
			} );
		} );
	} );
} )( jQuery );
