/**
 * Scroll preview — pans the full-page screenshot inside each device frame as
 * the card crosses the viewport.
 *
 * progress = (viewportHeight + lead - frameBottom) / (viewportHeight + lead)
 * where lead lets the reveal start before the frame is fully in view. The eased
 * progress is applied with translate3d on the pan wrapper (compositor only), and
 * eased toward the scroll position so motion trails smoothly.
 */
( function () {
	var CFG = {
		easing: 0.14, // 0..1 — how hard the screens chase the scroll
		startVisible: 0.5, // how much of the frame must be on screen first
		hold: 0.05, // extra dead zone, as a fraction of the range
		curve: 2 // >1 = slow start, soft landing
	};

	var cards = Array.prototype.slice.call( document.querySelectorAll( '.csc-preview' ) );
	if ( ! cards.length ) {
		return;
	}

	var state = [];
	var raf = null;

	function ease( p ) {
		var t = ( p - CFG.hold ) / ( 1 - CFG.hold );
		t = t < 0 ? 0 : ( t > 1 ? 1 : t );
		var a = Math.pow( t, CFG.curve );
		var b = Math.pow( 1 - t, CFG.curve );
		return a + b === 0 ? 1 : a / ( a + b );
	}

	function travelOf( card ) {
		var pan = card.querySelector( '.csc-preview__pan' );
		var shot = card.querySelector( '.csc-preview__shot--desktop' );
		if ( ! pan || ! shot ) {
			return null;
		}
		return { pan: pan, shot: shot, travel: Math.max( 0, pan.scrollHeight - shot.clientHeight ) };
	}

	function paint( item ) {
		item.pan.style.transform = 'translate3d(0,' + item.cur.toFixed( 2 ) + 'px,0)';
	}

	function updateTargets() {
		var vh = window.innerHeight || document.documentElement.clientHeight;

		state.forEach( function ( item ) {
			var rect = item.shot.getBoundingClientRect();
			var lead = ( 1 - CFG.startVisible ) * rect.height;
			var span = vh + lead;
			var raw = ( vh + lead - rect.bottom ) / span;
			raw = raw < 0 ? 0 : ( raw > 1 ? 1 : raw );

			item.target = -ease( raw ) * item.travel;
			if ( ! item.init ) {
				item.cur = item.target;
				item.init = true;
				paint( item );
			}
		} );
	}

	function tick() {
		var busy = false;

		state.forEach( function ( item ) {
			var delta = item.target - item.cur;
			if ( Math.abs( delta ) > 0.25 ) {
				item.cur += delta * CFG.easing;
				busy = true;
			} else if ( item.cur !== item.target ) {
				item.cur = item.target;
			}
			paint( item );
		} );

		raf = busy ? window.requestAnimationFrame( tick ) : null;
	}

	function kick() {
		if ( raf === null ) {
			raf = window.requestAnimationFrame( tick );
		}
	}

	function measure() {
		state = [];

		cards.forEach( function ( card ) {
			var parts = travelOf( card );
			if ( ! parts ) {
				return;
			}

			state.push( {
				pan: parts.pan,
				shot: parts.shot,
				travel: parts.travel,
				cur: 0,
				target: 0,
				init: false
			} );

			var img = parts.pan.querySelector( 'img' );
			if ( img && ! img.dataset.cscPreviewBound ) {
				img.dataset.cscPreviewBound = '1';
				if ( ! img.complete ) {
					img.addEventListener( 'load', function () {
						var fresh = travelOf( card );
						if ( fresh ) {
							state.forEach( function ( item ) {
								if ( item.pan === fresh.pan ) {
									item.travel = fresh.travel;
									item.init = false;
								}
							} );
						}
						updateTargets();
						kick();
					} );
				}
			}
		} );

		updateTargets();
	}

	var pending = false;
	window.addEventListener(
		'scroll',
		function () {
			if ( pending ) {
				return;
			}
			pending = true;
			window.requestAnimationFrame( function () {
				pending = false;
				updateTargets();
				kick();
			} );
		},
		{ passive: true }
	);

	window.addEventListener( 'resize', function () {
		measure();
		kick();
	} );

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', measure );
	} else {
		measure();
	}

	window.addEventListener( 'load', measure );
} )();
