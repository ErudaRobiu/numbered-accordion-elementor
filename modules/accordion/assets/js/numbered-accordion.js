/**
 * Numbered Accordion for Elementor
 *
 * Vanilla JS, no dependencies. Safe to load more than once, safe to run
 * against the same DOM twice, and it never throws if the markup is missing.
 */
( function () {
	'use strict';

	var ROOT_SELECTOR = '.nacc';

	/**
	 * The animated collapse relies on transitioning grid-template-rows between
	 * 0fr and 1fr. Where that is not supported we leave the plain show/hide
	 * baseline in the stylesheet alone.
	 */
	var SUPPORTS_FR = (
		typeof window.CSS !== 'undefined' &&
		typeof window.CSS.supports === 'function' &&
		window.CSS.supports( 'grid-template-rows', '0fr' )
	);

	var SUPPORTS_INERT = ( typeof HTMLElement !== 'undefined' && 'inert' in HTMLElement.prototype );

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	/**
	 * Open or close a single item and sync its ARIA state.
	 *
	 * @param {Element} item Item element.
	 * @param {boolean} open Desired state.
	 */
	function setState( item, open ) {
		var trigger = item.querySelector( '.nacc-item__trigger' );

		item.classList.toggle( 'is-open', open );

		if ( trigger ) {
			trigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}

		/*
		 * Every collapsible region in the item, not just the panel. The eyebrow
		 * sits inside the trigger button in its own .nacc-collapse, and a
		 * closed one is clipped by overflow rather than hidden by display:none.
		 * Clipped content stays in the accessibility tree, so without this a
		 * screen reader reads a closed row's eyebrow as part of the button's
		 * name: "Recover, Lepido Heat Recovery Unit".
		 */
		if ( SUPPORTS_INERT ) {
			toArray( item.querySelectorAll( '.nacc-collapse' ) ).forEach( function ( region ) {
				region.inert = ! open;
			} );
		}
	}

	/**
	 * Handle a click on a trigger.
	 *
	 * @param {Element} root Accordion root.
	 * @param {Element} item Clicked item.
	 */
	function toggle( root, item ) {
		var multiple = 'yes' === root.getAttribute( 'data-multiple' );
		var collapseAll = 'yes' === root.getAttribute( 'data-collapse-all' );
		var isOpen = item.classList.contains( 'is-open' );

		/*
		 * "Allow closing every item" off means one row must stay open -- not
		 * that an open row can never be closed. Refusing every close is right
		 * in single-open mode, where the open row is always the last one, but
		 * in multi-open mode it used to lock the accordion: open three rows and
		 * none of them could be closed again. Bail only on the last one.
		 */
		if ( isOpen && ! collapseAll ) {
			if ( root.querySelectorAll( '.nacc-item.is-open' ).length < 2 ) {
				return;
			}
		}

		if ( ! isOpen && ! multiple ) {
			toArray( root.querySelectorAll( '.nacc-item.is-open' ) ).forEach( function ( other ) {
				if ( other !== item ) {
					setState( other, false );
				}
			} );
		}

		setState( item, ! isOpen );
	}

	/**
	 * Roving keyboard navigation between headers.
	 *
	 * @param {KeyboardEvent} event    Key event.
	 * @param {Array}         triggers All triggers in this accordion.
	 */
	function onKeydown( event, triggers ) {
		var current = triggers.indexOf( event.currentTarget );
		var next = -1;

		if ( current < 0 ) {
			return;
		}

		switch ( event.key ) {
			case 'ArrowDown':
				next = ( current + 1 ) % triggers.length;
				break;
			case 'ArrowUp':
				next = ( current - 1 + triggers.length ) % triggers.length;
				break;
			case 'Home':
				next = 0;
				break;
			case 'End':
				next = triggers.length - 1;
				break;
			default:
				return;
		}

		event.preventDefault();
		triggers[ next ].focus();
	}

	/**
	 * Wire up one accordion. Idempotent.
	 *
	 * @param {Element} root Accordion root element.
	 */
	function init( root ) {
		if ( ! root || 1 !== root.nodeType || '1' === root.getAttribute( 'data-nacc-ready' ) ) {
			return;
		}

		root.setAttribute( 'data-nacc-ready', '1' );

		if ( SUPPORTS_FR ) {
			root.classList.add( 'nacc--anim' );
		}

		var triggers = toArray( root.querySelectorAll( '.nacc-item__trigger' ) );

		// Sync inert state with whatever the server rendered.
		toArray( root.querySelectorAll( '.nacc-item' ) ).forEach( function ( item ) {
			setState( item, item.classList.contains( 'is-open' ) );
		} );

		triggers.forEach( function ( trigger ) {
			trigger.addEventListener( 'click', function () {
				var item = trigger.closest ? trigger.closest( '.nacc-item' ) : null;
				if ( item ) {
					toggle( root, item );
				}
			} );

			trigger.addEventListener( 'keydown', function ( event ) {
				onKeydown( event, triggers );
			} );
		} );
	}

	/**
	 * Initialise every accordion inside a scope.
	 *
	 * @param {Element|Document} scope Container to search.
	 */
	function initAll( scope ) {
		var context = scope || document;

		if ( ! context || ! context.querySelectorAll ) {
			return;
		}

		if ( context.matches && context.matches( ROOT_SELECTOR ) ) {
			init( context );
		}

		toArray( context.querySelectorAll( ROOT_SELECTOR ) ).forEach( init );
	}

	/* ------------------------------------------------------- front end --- */

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			initAll( document );
		} );
	} else {
		initAll( document );
	}

	/* ---------------------------------------------- Elementor editor --- */

	function registerElementorHook() {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}

		window.elementorFrontend.hooks.addAction(
			'frontend/element_ready/nacc-numbered-accordion.default',
			function ( $scope ) {
				var el = ( $scope && $scope[ 0 ] ) ? $scope[ 0 ] : $scope;
				initAll( el );
			}
		);
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', registerElementorHook );
	}

	// In case Elementor already booted before this script ran.
	registerElementorHook();
}() );
