/**
 * Eruda Toolkit - Process Steps
 *
 * Writes one number per step and gets out of the way.
 *
 * --estp-p is nought when a step arrives at the bottom of the window and one
 * when it leaves the top. Every figure in the stylesheet reads that same
 * number and does something different with it, so the entire scroll behaviour
 * of five different illustrations is one property write per step per frame.
 * Nothing here knows what a figure looks like.
 *
 * The stylesheet opens and closes everything on hover by itself, and holds a
 * resting angle when --estp-p is never written. A blocked script therefore
 * costs the scroll camera and nothing else: the steps, the numbers, the spine
 * and the figures are all still there, drawn at the angle they were designed
 * at.
 */
( function () {
	'use strict';

	var ROOTS = '.estp';

	/**
	 * Is the browser actually going to project any of this?
	 *
	 * `overflow` or `filter` on the element carrying `transform-style:
	 * preserve-3d` forces it back to `flat`, and the failure is silent: the
	 * figure does not break, it quietly becomes a pile of rectangles with the
	 * depth calculated and then thrown away.
	 *
	 * It has to be that element -- an ancestor does not do it, which was
	 * measured rather than assumed. So the realistic cause is somebody's
	 * custom CSS reaching the scene, which is exactly the kind of thing that
	 * cannot be predicted from here and can be measured in one read.
	 *
	 * A probe is put into the first scene at a depth that would make it
	 * visibly larger if the projection were live, and its width is read. Same
	 * width means the depth went nowhere.
	 *
	 * @param {Element} root The widget root.
	 * @return {boolean} True when depth is being drawn.
	 */
	function depthWorks( root ) {
		var scene = root.querySelector( '.estp__scene' );

		if ( ! scene ) {
			return false;
		}

		var probe = document.createElement( 'span' );

		probe.style.cssText =
			'position:absolute;left:0;top:0;width:100px;height:10px;' +
			'pointer-events:none;visibility:hidden;transform:translateZ(120px)';

		scene.appendChild( probe );

		var drawn = probe.getBoundingClientRect().width;

		scene.removeChild( probe );

		// Flattened, the probe is exactly the 100px it was given. Projected,
		// the perspective on the stage makes it wider. A couple of pixels of
		// slack, because a zoomed page does not land on round numbers.
		return drawn > 103;
	}

	/**
	 * Set a widget going.
	 *
	 * @param {Element} root The widget root.
	 */
	function start( root ) {
		if ( root.hasAttribute( 'data-estp-ready' ) ) {
			return;
		}

		root.setAttribute( 'data-estp-ready', '' );

		var steps = Array.prototype.slice.call( root.querySelectorAll( '.estp__step' ) );

		if ( ! steps.length ) {
			return;
		}

		var reduced = (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);

		/*
		 * Measured after a frame, not now.
		 *
		 * The stylesheet may not have arrived when this runs, and a scene with
		 * no perspective on it yet measures as flat -- which would switch the
		 * flat fallback on permanently for a widget that was going to be fine.
		 */
		window.requestAnimationFrame( function () {
			if ( ! depthWorks( root ) ) {
				root.classList.add( 'is-flat' );
			}
		} );

		/* ------------------------------------------------- the scroll --- */

		var ticking = false;

		function paint() {
			ticking = false;

			var tall = window.innerHeight || document.documentElement.clientHeight;

			for ( var i = 0; i < steps.length; i++ ) {
				var box = steps[ i ].getBoundingClientRect();

				/*
				 * Nought at the bottom of the window, one at the top.
				 *
				 * Measured from the middle of the step rather than its edge,
				 * so a tall step and a short one travel through their arc at
				 * the same rate -- keyed to the top edge, a step twice the
				 * height of another turns twice as slowly for the same scroll.
				 */
				var middle = box.top + box.height / 2;
				var p = 1 - ( middle / tall );

				p = p < 0 ? 0 : ( p > 1 ? 1 : p );

				steps[ i ].style.setProperty( '--estp-p', p.toFixed( 4 ) );

				// The spine draws itself as the step arrives and stays drawn.
				if ( p > 0.12 ) {
					steps[ i ].style.setProperty( '--estp-draw', '1' );
				}
			}
		}

		function onScroll() {
			if ( ticking ) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame( paint );
		}

		if ( reduced ) {
			// The spine still draws; the camera does not follow the scroll.
			for ( var s = 0; s < steps.length; s++ ) {
				steps[ s ].style.setProperty( '--estp-draw', '1' );
			}
		} else {
			// Undrawn to start with, so the spine has somewhere to draw from.
			for ( var u = 0; u < steps.length; u++ ) {
				steps[ u ].style.setProperty( '--estp-draw', '0' );
			}

			paint();
			window.addEventListener( 'scroll', onScroll, { passive: true } );
			window.addEventListener( 'resize', onScroll, { passive: true } );
		}

		/* ------------------------------------------------ the pointer --- */

		/*
		 * Read on pointermove, written on the next frame.
		 *
		 * The handler fires far more often than the screen refreshes, and a
		 * style write per event is how a tilt ends up costing more than it is
		 * worth. One figure is being pointed at at a time, so one pending
		 * write is all there ever is.
		 */
		var pending = null;
		var queued = false;

		function tilt() {
			queued = false;

			if ( ! pending ) {
				return;
			}

			pending.el.style.setProperty( '--estp-hx', pending.x.toFixed( 3 ) );
			pending.el.style.setProperty( '--estp-hy', pending.y.toFixed( 3 ) );
		}

		var figures = root.querySelectorAll( '.estp__fig' );

		Array.prototype.forEach.call( figures, function ( fig ) {
			fig.addEventListener( 'pointermove', function ( event ) {
				// A finger is not a pointer you can hover with, and reading
				// one here fights the scroll it is in the middle of.
				if ( 'touch' === event.pointerType || reduced ) {
					return;
				}

				var box = fig.getBoundingClientRect();

				pending = {
					el: fig,
					x: ( event.clientX - ( box.left + box.width / 2 ) ) / ( box.width / 2 ),
					y: ( event.clientY - ( box.top + box.height / 2 ) ) / ( box.height / 2 ),
				};

				if ( ! queued ) {
					queued = true;
					window.requestAnimationFrame( tilt );
				}
			}, { passive: true } );

			fig.addEventListener( 'pointerleave', function () {
				pending = null;
				fig.style.setProperty( '--estp-hx', '0' );
				fig.style.setProperty( '--estp-hy', '0' );
			} );
		} );
	}

	/**
	 * Find every widget on the page and start it.
	 */
	function boot() {
		var roots = document.querySelectorAll( ROOTS );

		Array.prototype.forEach.call( roots, start );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}

	/*
	 * The editor rebuilds a widget's markup on every keystroke, so a widget
	 * that was set going once is gone by the time you have finished typing.
	 * Elementor says when it has finished putting one back.
	 */
	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/estp-process-steps.default',
				function ( $scope ) {
					var root = $scope && $scope[0] ? $scope[0].querySelector( ROOTS ) : null;

					if ( root ) {
						start( root );
					}
				}
			);
		} );
	}
}() );
