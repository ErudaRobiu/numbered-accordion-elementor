/**
 * Eruda Toolkit - Split Explainer
 *
 * One job: bring the section in as it arrives. The heading fills in word by
 * word, the blocks under it rise, and each element is let go of once it has
 * played, so nothing is observed for the life of the page.
 *
 * The word spans are authored in the markup by PHP rather than split out of
 * innerHTML here. Splitting on the client eats the trademark element, and it
 * would also mean the heading arrived as one grey block for anyone whose
 * JavaScript is slow or off.
 *
 * Vanilla, no dependencies, safe to run twice. Without it the stylesheet
 * shows everything at full opacity, so the section never depends on it.
 */
( function () {
	'use strict';

	var ROOT = '.eexp';
	var READY = 'data-eexp-ready';
	var IN = 'eexp-in';

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function prefersReducedMotion() {
		return (
			typeof window.matchMedia === 'function' &&
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		);
	}

	/**
	 * Show everything at once, with no observer and no staggering.
	 *
	 * @param {Element} root Section root.
	 */
	function revealAll( root ) {
		toArray( root.querySelectorAll( '.eexp-rise, .eexp-anim' ) ).forEach( function ( el ) {
			el.style.transitionDelay = '';
			el.classList.add( IN );
		} );
	}

	/**
	 * Wire one section up.
	 *
	 * @param {Element} root Section root.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );
		root.classList.remove( 'eexp-no-js' );

		var targets = toArray( root.querySelectorAll( '.eexp-rise, .eexp-anim' ) );

		if ( ! targets.length ) {
			return;
		}

		// No observer, or motion is not wanted: show the section and stop.
		if ( typeof window.IntersectionObserver !== 'function' || prefersReducedMotion() ) {
			revealAll( root );
			return;
		}

		var observer = new window.IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					entry.target.classList.add( IN );
					observer.unobserve( entry.target );
				} );
			},
			{
				threshold: 0.2,
				rootMargin: '0px 0px -8% 0px',
			}
		);

		targets.forEach( function ( el, index ) {
			// A short stagger, capped, so a long section does not end up
			// waiting on a delay longer than the scroll that triggered it.
			el.style.transitionDelay = Math.min( index, 6 ) * 70 + 'ms';
			observer.observe( el );
		} );
	}

	/**
	 * Wire up every section inside a scope.
	 *
	 * @param {Element|Document} scope Where to look.
	 */
	function initAll( scope ) {
		var context = scope || document;

		if ( context.nodeType === 1 && context.matches && context.matches( ROOT ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( ROOT ) ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
				return;
			}

			window.elementorFrontend.hooks.addAction(
				'frontend/element_ready/eexp-split-explainer.default',
				function ( scope ) {
					var el = scope && scope[0] ? scope[0] : scope;

					if ( el && 1 === el.nodeType ) {
						// A re-render replaces the markup, so the guard on the
						// old node does not carry over.
						initAll( el );
					}
				}
			);
		} );
	}
}() );
