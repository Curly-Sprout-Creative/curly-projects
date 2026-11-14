/**
 * Scroll preview — pans the full-page screenshots inside both device frames as
 * the card crosses the viewport.
 *
 * progress = (viewportHeight + lead - frameBottom) / (viewportHeight + lead)
 * where lead lets the reveal start before the frame is fully in view. The eased
 * progress is applied with translate3d on each pan wrapper (compositor only),
 * and eased toward the scroll position so motion trails smoothly.
 *
 * One card drives both frames: the desktop frame supplies the progress, and the
 * phone's own travel distance is scaled by the same amount so the two screens
 * move together.
 *
 * Short pages: a card near the bottom of the document can never have its frame
 * bottom reach the top of the viewport, so the pan used to stop part-way. The
 * reachable range is measured per card at the document's maximum scroll and the
 * progress is normalised against it, so the last row still lands at the end.
 */
( function () {
	var CFG = {
		easing: 0.14, // 0..1 — how hard the screens chase the scroll
		startVisible: 0.5, // how much of the frame must be on screen first
		hold: 0.05, // extra dead zone, as a fraction of the range
		curve: 2, // >1 = slow start, soft landing
		minRange: 0.08 // never divide by less than this (very short pages)
	};

	var cards = Array.prototype.slice.call(
		document.querySelectorAll( '.csc-preview:not(.csc-preview--fallback)' )
	);
	if ( ! cards.length ) {
		return;
	}

	var groups = [];
	var raf = null;

	function ease( p ) {
		var t = ( p - CFG.hold ) / ( 1 - CFG.hold );
		t = t < 0 ? 0 : ( t > 1 ? 1 : t );
		var a = Math.pow( t, CFG.curve );
		var b = Math.pow( 1 - t, CFG.curve );
		return a + b === 0 ? 1 : a / ( a + b );
	}

	function buildGroup( card ) {
		var anchor = card.querySelector( '.csc-preview__shot--desktop' );
		var items = [];

		Array.prototype.forEach.call(
			card.querySelectorAll( '.csc-preview__shot' ),
			function ( shot ) {
				var pan = shot.querySelector( '.csc-preview__pan' );
				var img = pan && pan.querySelector( 'img' );
				if ( ! pan || ! img ) {
					return;
				}
				items.push( {
					pan: pan,
					shot: shot,
					img: img,
					travel: 0,
					cur: 0,
					target: 0
				} );
			}
		);

		if ( ! anchor ) {
			anchor = items.length ? items[ 0 ].shot : null;
		}

		return { card: card, anchor: anchor, items: items, range: 1 };
	}

	function measureTravel( item ) {
		// The image's rendered height is the reliable measure; the pan wrapper is
		// absolutely positioned, so scrollHeight can under-report the overflow.
		var height = item.img.getBoundingClientRect().height;
		item.travel = Math.max( 0, height - item.shot.clientHeight );
	}

	function paint( group ) {
		group.items.forEach( function ( item ) {
			item.pan.style.transform = 'translate3d(0,' + item.cur.toFixed( 2 ) + 'px,0)';
		} );
	}

	function updateTargets() {
		var vh = window.innerHeight || document.documentElement.clientHeight;
		var maxScroll = Math.max( 0, document.documentElement.scrollHeight - vh );
		var scrollY = window.scrollY || window.pageYOffset || 0;

		groups.forEach( function ( group ) {
			if ( ! group.anchor ) {
				return;
			}

			var rect = group.anchor.getBoundingClientRect();
			var lead = ( 1 - CFG.startVisible ) * rect.height;
			var span = vh + lead;
			if ( span <= 0 ) {
				return;
			}

			var raw = ( vh + lead - rect.bottom ) / span;

			// How far this card can actually progress before the document runs out
			// of scroll. Cards in the last row otherwise never reach the end.
			var docBottom = rect.bottom + scrollY;
			var rawAtMax = ( vh + lead - ( docBottom - maxScroll ) ) / span;
			group.range = Math.min( 1, Math.max( rawAtMax, CFG.minRange ) );

			var p = raw / group.range;
			p = p < 0 ? 0 : ( p > 1 ? 1 : p );

			var offset = -ease( p );

			group.items.forEach( function ( item ) {
				item.target = offset * item.travel;
				if ( ! item.init ) {
					item.cur = item.target;
					item.init = true;
					paint( group );
				}
			} );
		} );
	}

	function tick() {
		var busy = false;

		groups.forEach( function ( group ) {
			group.items.forEach( function ( item ) {
				var delta = item.target - item.cur;
				if ( Math.abs( delta ) > 0.25 ) {
					item.cur += delta * CFG.easing;
					busy = true;
				} else if ( item.cur !== item.target ) {
					item.cur = item.target;
				}
			} );
			paint( group );
		} );

		raf = busy ? window.requestAnimationFrame( tick ) : null;
	}

	function kick() {
		if ( raf === null ) {
			raf = window.requestAnimationFrame( tick );
		}
	}

	function measure() {
		if ( ! groups.length ) {
			groups = cards.map( buildGroup );
		}

		groups.forEach( function ( group ) {
			group.items.forEach( function ( item ) {
				measureTravel( item );

				if ( ! item.bound ) {
					item.bound = true;
					item.img.addEventListener( 'load', function () {
						measureTravel( item );
						item.init = false;
						updateTargets();
						kick();
					} );
				}
			} );
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
