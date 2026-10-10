/**
 * Admin JavaScript for WPCalibrate Gift Wrap.
 * Handles WordPress Media Library integration for wrapping style images.
 */

/* global wp, wpcalibrateAdmin, jQuery */
( function( $ ) {
	'use strict';

	$( function() {
		const $uploadBtn = $( '#wpcalibrate_media_upload_btn' );
		const $removeBtn = $( '#wpcalibrate_media_remove_btn' );
		const $imageIdInput = $( '#style_image_id' );
		const $imageUrlInput = $( '#style_image_url' );
		const $preview = $( '#style_image_preview' );

		let mediaFrame;

		if ( $uploadBtn.length ) {
			$uploadBtn.on( 'click', function( e ) {
				e.preventDefault();

				if ( mediaFrame ) {
					mediaFrame.open();
					return;
				}

				mediaFrame = wp.media( {
					title: wpcalibrateAdmin.mediaTitle || 'Select Gift Wrap Preview Image',
					button: {
						text: wpcalibrateAdmin.mediaButton || 'Use This Image',
					},
					multiple: false,
					library: {
						type: 'image',
					},
				} );

				mediaFrame.on( 'select', function() {
					const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
					$imageIdInput.val( attachment.id );
					const imgUrl = ( attachment.sizes && attachment.sizes.medium ) ? attachment.sizes.medium.url : attachment.url;
					$imageUrlInput.val( imgUrl );

					$preview.html( '<img src="' + imgUrl + '" alt="" style="width:100%;height:100%;object-fit:cover;" />' );
					$removeBtn.show();
				} );

				mediaFrame.open();
			} );

			$removeBtn.on( 'click', function( e ) {
				e.preventDefault();
				$imageIdInput.val( '' );
				$imageUrlInput.val( '' );
				$preview.html( '<span class="dashicons dashicons-format-image" style="color:#94a3b8;"></span>' );
				$( this ).hide();
			} );
		}
	} );
} )( jQuery );
