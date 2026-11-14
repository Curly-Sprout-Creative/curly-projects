/**
 * Scroll Preview meta box — media pickers and colour inputs.
 */
( function () {
	document.addEventListener( 'DOMContentLoaded', function () {
		// --- media pickers -------------------------------------------------
		Array.prototype.forEach.call(
			document.querySelectorAll( '[data-curly-preview-image]' ),
			function ( wrap ) {
				var input = wrap.querySelector( '[data-curly-preview-input]' );
				var thumb = wrap.querySelector( '.curly-preview-image__thumb img' );
				var name = wrap.querySelector( '.curly-preview-image__name' );
				var select = wrap.querySelector( '[data-curly-preview-select]' );
				var clear = wrap.querySelector( '[data-curly-preview-clear]' );
				var frame = null;

				if ( ! input || ! select || ! window.wp || ! wp.media ) {
					return;
				}

				select.addEventListener( 'click', function ( event ) {
					event.preventDefault();

					if ( ! frame ) {
						frame = wp.media( {
							title: 'Select screenshot',
							button: { text: 'Use this image' },
							library: { type: 'image' },
							multiple: false
						} );

						frame.on( 'select', function () {
							var attachment = frame.state().get( 'selection' ).first().toJSON();
							input.value = attachment.id;
							thumb.src = attachment.url;
							thumb.hidden = false;
							if ( name ) {
								name.textContent = attachment.title || attachment.filename || '';
							}
							select.textContent = 'Replace image';
							clear.hidden = false;
						} );
					}

					frame.open();
				} );

				clear.addEventListener( 'click', function ( event ) {
					event.preventDefault();
					input.value = '';
					thumb.src = '';
					thumb.hidden = true;
					if ( name ) {
						name.textContent = '';
					}
					select.textContent = 'Select image';
					clear.hidden = true;
				} );
			}
		);

		// --- colour inputs -------------------------------------------------
		Array.prototype.forEach.call(
			document.querySelectorAll( '.curly-preview-colour' ),
			function ( row ) {
				var swatch = row.querySelector( '[data-curly-preview-swatch]' );
				var hex = row.querySelector( '[data-curly-preview-hex]' );
				var reset = row.querySelector( '[data-curly-preview-reset]' );

				if ( ! swatch || ! hex ) {
					return;
				}

				swatch.addEventListener( 'input', function () {
					hex.value = swatch.value;
				} );

				hex.addEventListener( 'input', function () {
					if ( /^#[0-9a-f]{6}$/i.test( hex.value ) ) {
						swatch.value = hex.value;
					}
				} );

				if ( reset ) {
					reset.addEventListener( 'click', function ( event ) {
						event.preventDefault();
						hex.value = '';
					} );
				}
			}
		);
	} );
} )();
