/**
 * Eruda Toolkit - Assessment Form
 *
 * Steps through the form, keeps slider readouts live, checks each step
 * before moving on, posts the request, and shows the estimate the server
 * returns. As a pop-up it is a native <dialog>: any link matching the
 * widget's trigger opens it, and without this script those links simply
 * go where they point.
 *
 * Vanilla, no dependencies, safe to run twice.
 */
( function () {
	'use strict';

	var READY = 'data-eas-ready';
	var nf = typeof Intl !== 'undefined' ? new Intl.NumberFormat( 'en-US' ) : { format: String };

	function toArray( list ) {
		return Array.prototype.slice.call( list || [] );
	}

	function range( pair, unit, pre ) {
		return ( pre || '' ) + nf.format( pair[0] ) + '–' + ( pre || '' ) + nf.format( pair[1] ) + ( unit || '' );
	}

	function init( root ) {
		if ( ! root || root.getAttribute( READY ) ) {
			return;
		}

		root.setAttribute( READY, '1' );

		var form = root.querySelector( '.eas__form' );
		var steps = toArray( root.querySelectorAll( '.eas__step' ) );
		var dots = toArray( root.querySelectorAll( '[data-step-dot]' ) );
		var back = root.querySelector( '[data-eas-back]' );
		var next = root.querySelector( '[data-eas-next]' );
		var send = root.querySelector( '[data-eas-send]' );
		var status = root.querySelector( '.eas__status' );
		var result = root.querySelector( '.eas__result' );
		var dialog = root.closest( 'dialog' );
		var current = 1;
		var started = Date.now();

		if ( ! form ) {
			return;
		}

		// A pop-up lives at the end of <body>, outside every Elementor section:
		// section styles (lazy-loaded backgrounds, transforms, overflow) must
		// not reach it just because the widget was dropped into one.
		// The corner booking button follows the form in the markup; it moves
		// out for the same reason, or a transformed section would pin it to
		// itself instead of the viewport.
		var badge = ( dialog || root ).nextElementSibling;
		if ( badge && badge.classList.contains( 'eas-badge' ) ) {
			if ( document.body.querySelector( ':scope > .eas-badge' ) ) {
				badge.parentNode.removeChild( badge ); // A second form on the page: one corner button is enough.
			} else {
				document.body.appendChild( badge );
				// Held back until the hero is mostly scrolled past: heroes carry
				// their own call to action in that corner. Without script it
				// simply shows from the start.
				badge.setAttribute( 'data-wait', '' );
				var reveal = function () {
					badge.classList.toggle( 'is-shown', window.scrollY > window.innerHeight * 0.6 );
				};
				window.addEventListener( 'scroll', reveal, { passive: true } );
				reveal();
			}
		}

		if ( dialog && dialog.parentNode !== document.body ) {
			document.body.appendChild( dialog );
		}

		/* Sliders show their value and fill up to the thumb. */
		toArray( root.querySelectorAll( '.eas__range' ) ).forEach( function ( input ) {
			var out = root.querySelector( 'output[for="' + input.id + '"]' );
			var paint = function () {
				var p = ( input.value - input.min ) / ( input.max - input.min ) * 100;
				input.style.setProperty( '--p', p + '%' );
				if ( out ) {
					out.textContent = nf.format( +input.value ) + ( out.getAttribute( 'data-unit' ) || '' );
				}
			};
			input.addEventListener( 'input', paint );
			paint();
		} );

		function field( name ) {
			return form.elements[ name ];
		}

		function setError( name, message ) {
			var el = field( name );
			var err = el && document.getElementById( el.id + '-err' );

			if ( ! el || ! err ) {
				return;
			}

			el.setAttribute( 'aria-invalid', message ? 'true' : 'false' );
			err.textContent = message || '';
			err.hidden = ! message;
		}

		function show( n, focus ) {
			current = n;
			steps.forEach( function ( s ) {
				s.hidden = +s.getAttribute( 'data-step' ) !== n;
			} );
			dots.forEach( function ( d ) {
				var i = +d.getAttribute( 'data-step-dot' );
				d.classList.toggle( 'is-on', i === n );
				d.classList.toggle( 'is-done', i < n );
				if ( i === n ) {
					d.setAttribute( 'aria-current', 'step' );
				} else {
					d.removeAttribute( 'aria-current' );
				}
			} );
			back.hidden = n === 1;
			next.hidden = n === steps.length;
			send.hidden = n !== steps.length;
			status.hidden = true;

			if ( focus ) {
				var title = steps[ n - 1 ].querySelector( '.eas__step-title' );
				if ( title ) {
					title.focus( { preventScroll: false } );
				}
			}
		}

		var checks = {
			industry: function () { return field( 'industry' ).value ? '' : 'Choose your industry.'; },
			first: function () { return field( 'first' ).value.trim() ? '' : 'Enter your first name.'; },
			last: function () { return field( 'last' ).value.trim() ? '' : 'Enter your last name.'; },
			email: function () { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( field( 'email' ).value.trim() ) ? '' : 'Enter an email address like name@company.com.'; },
			company: function () { return field( 'company' ).value.trim() ? '' : 'Enter your company name.'; },
			location: function () { return field( 'location' ).value.trim() ? '' : 'Enter the facility\'s city and state or province, or choose Several sites.'; },
		};
		var byStep = { 1: [], 2: [ 'industry' ], 3: [ 'first', 'last', 'email', 'company', 'location' ] };

		/* "Several sites" answers the location in one tap, and a second tap
		   clears it again. Typing a place lets go of the button. */
		var several = root.querySelector( '[data-eas-several]' );
		if ( several && field( 'location' ) ) {
			var place = field( 'location' );
			var label = several.getAttribute( 'data-eas-several' );
			var sync = function () {
				several.setAttribute( 'aria-pressed', place.value.trim() === label ? 'true' : 'false' );
			};
			several.addEventListener( 'click', function () {
				place.value = place.value.trim() === label ? '' : label;
				place.dispatchEvent( new Event( 'input' ) );
				sync();
				if ( '' === place.value ) {
					place.focus();
				}
			} );
			place.addEventListener( 'input', sync );
		}

		function valid( n ) {
			var first = null;

			byStep[ n ].forEach( function ( name ) {
				var message = checks[ name ]();
				setError( name, message );
				if ( message && ! first ) {
					first = field( name );
				}
			} );

			if ( first ) {
				first.focus();
				return false;
			}

			return true;
		}

		// Clear a field's error as soon as it is fixed.
		Object.keys( checks ).forEach( function ( name ) {
			var el = field( name );
			if ( el ) {
				el.addEventListener( 'input', function () {
					if ( 'true' === el.getAttribute( 'aria-invalid' ) && ! checks[ name ]() ) {
						setError( name, '' );
					}
				} );
				el.addEventListener( 'change', function () {
					if ( 'true' === el.getAttribute( 'aria-invalid' ) && ! checks[ name ]() ) {
						setError( name, '' );
					}
				} );
			}
		} );

		next.addEventListener( 'click', function () {
			if ( valid( current ) ) {
				show( current + 1, true );
			}
		} );

		back.addEventListener( 'click', function () {
			show( current - 1, true );
		} );

		function payload() {
			var get = function ( name ) { return field( name ) ? field( name ).value : ''; };
			var checked = function ( name ) {
				return toArray( form.querySelectorAll( 'input[name="' + name + '"]:checked' ) ).map( function ( i ) { return i.value; } );
			};
			var days = form.querySelector( 'input[name="days"]:checked' );

			return {
				doc: +root.getAttribute( 'data-doc' ),
				el: root.getAttribute( 'data-el' ),
				temp: +get( 'temp' ),
				cfm: +get( 'cfm' ),
				hours: +get( 'hours' ),
				days: days ? +days.value : 5,
				industry: get( 'industry' ),
				contaminants: checked( 'contaminants' ),
				uses: checked( 'uses' ),
				first: get( 'first' ),
				last: get( 'last' ),
				email: get( 'email' ),
				company: get( 'company' ),
				role: get( 'role' ),
				phone: get( 'phone' ),
				location: get( 'location' ),
				notes: get( 'notes' ),
				website: get( 'website' ),
				source: window.location.href,
				elapsed: Date.now() - started,
			};
		}

		function fail( message ) {
			status.textContent = message;
			status.hidden = false;
		}

		function showResult( e, rows ) {
			var price = e ? e.price : +root.getAttribute( 'data-price' );
			var set = function ( key, text ) {
				var el = result.querySelector( '[data-r="' + key + '"]' );
				if ( el ) {
					el.textContent = text;
				}
			};
			var table = result.querySelector( '[data-r="rows"]' );

			set( 'name', field( 'first' ).value.trim() );

			if ( e ) {
				var share = e.share && 2 === e.share.length ? e.share : [ 30, 50 ];
				set( 'therms', nf.format( e.therms[0] ) + ' to ' + nf.format( e.therms[1] ) );
				set( 'price', '$' + Number( price ).toFixed( 2 ) );
				set( 'dollars', '$' + nf.format( e.dollars[0] ) + ' to $' + nf.format( e.dollars[1] ) );
				set( 'basis', 'Indicative. Heat = 1.08 × airflow × (exhaust temperature − 60 °F), with ' + share[0] + '% to ' + share[1] + '% of it recovered for the uses you chose, displacing fuel burned at 80% efficiency. The free screen replaces these assumptions with your real data.' );
			} else {
				set( 'therms', 'Thank you' );
			}

			// The answers as the server cleaned them, so the table matches the email.
			if ( table ) {
				table.textContent = '';
				( rows || [] ).forEach( function ( row ) {
					var tr = document.createElement( 'tr' );
					var th = document.createElement( 'th' );
					var td = document.createElement( 'td' );
					th.scope = 'row';
					th.textContent = row[0];
					td.textContent = row[1] || '—';
					tr.appendChild( th );
					tr.appendChild( td );
					table.appendChild( tr );
				} );
				table.closest( 'details' ).hidden = ! ( rows && rows.length );
			}

			form.hidden = true;
			result.hidden = false;
			dots.forEach( function ( d ) { d.classList.remove( 'is-on' ); d.classList.add( 'is-done' ); } );
			result.focus();
		}

		// Another estimate: back to the start with the last answers kept, so
		// the visitor changes only what they want to.
		var again = root.querySelector( '[data-eas-again]' );
		if ( again ) {
			again.addEventListener( 'click', function () {
				result.hidden = true;
				form.hidden = false;
				started = Date.now();
				show( 1, true );
			} );
		}

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();

			if ( ! valid( 3 ) || 'true' === send.getAttribute( 'aria-busy' ) ) {
				return;
			}

			send.setAttribute( 'aria-busy', 'true' );
			send.textContent = 'Sending…';

			fetch( root.getAttribute( 'data-endpoint' ), {
				method: 'POST',
				headers: root.getAttribute( 'data-nonce' ) ? { 'Content-Type': 'application/json', 'X-WP-Nonce': root.getAttribute( 'data-nonce' ) } : { 'Content-Type': 'application/json' },
				body: JSON.stringify( payload() ),
				credentials: 'same-origin',
			} )
				.then( function ( response ) {
					return response.json().catch( function () { return {}; } ).then( function ( body ) {
						return { status: response.status, body: body };
					} );
				} )
				.then( function ( r ) {
					if ( r.body && r.body.ok ) {
						showResult( r.body.estimate, r.body.rows );
						return;
					}

					if ( 422 === r.status && r.body.errors ) {
						var keys = Object.keys( r.body.errors );
						keys.forEach( function ( k ) { setError( k, r.body.errors[ k ] ); } );
						if ( keys.indexOf( 'industry' ) > -1 ) {
							show( 2, false );
							field( 'industry' ).focus();
						}
						return;
					}

					fail( ( r.body && r.body.message ) || 'Something went wrong sending your request. Please email solutions@thermstar.com or call 833 667 7359.' );
				} )
				.catch( function () {
					fail( 'Your request could not be sent. Check your connection, or email solutions@thermstar.com or call 833 667 7359.' );
				} )
				.then( function () {
					send.removeAttribute( 'aria-busy' );
					send.textContent = 'Get my estimate';
				} );
		} );

		function reset() {
			form.reset();
			toArray( root.querySelectorAll( '.eas__range' ) ).forEach( function ( i ) { i.dispatchEvent( new Event( 'input' ) ); } );
			Object.keys( checks ).forEach( function ( k ) { setError( k, '' ); } );
			if ( several ) {
				several.setAttribute( 'aria-pressed', 'false' );
			}
			form.hidden = false;
			result.hidden = true;
			show( 1, false );
		}

		/* Pop-up behaviour. */
		if ( dialog ) {
			var trigger = root.getAttribute( 'data-trigger' );

			// Overflow alone does not hold a page that smooth scrolling drives:
			// Lenis moves it from script, so it is paused while the pop-up is
			// open and told to leave the pop-up's own scrolling alone.
			dialog.setAttribute( 'data-lenis-prevent', '' );
			var lockPage = function ( on ) {
				var smooth = window.erudaSmoothScroll && window.erudaSmoothScroll.lenis;

				document.documentElement.classList.toggle( 'eas-locked', !! on );

				if ( smooth && typeof smooth.stop === 'function' ) {
					if ( on ) {
						smooth.stop();
					} else {
						smooth.start();
					}
				}
			};

			root.eas = {
				open: function () {
					if ( ! result.hidden ) {
						reset();
					}
					started = Date.now();
					if ( typeof dialog.showModal === 'function' ) {
						dialog.showModal();
					} else {
						dialog.setAttribute( 'open', '' );
					}
					lockPage( true );
				},
			};

			toArray( root.querySelectorAll( '[data-eas-close]' ) ).forEach( function ( b ) {
				b.addEventListener( 'click', function () { dialog.close(); } );
			} );

			dialog.addEventListener( 'close', function () {
				lockPage( false );
			} );

			// A click on the backdrop lands on the dialog itself.
			dialog.addEventListener( 'click', function ( event ) {
				if ( event.target === dialog ) {
					dialog.close();
				}
			} );

			if ( trigger && ! document.documentElement.getAttribute( 'data-eas-trigger' ) ) {
				document.documentElement.setAttribute( 'data-eas-trigger', '1' );
				document.addEventListener( 'click', function ( event ) {
					var hit = null;
					try {
						hit = event.target.closest && event.target.closest( trigger );
					} catch ( e ) {
						return;
					}
					if ( ! hit || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.button > 0 ) {
						return;
					}
					event.preventDefault();
					root.eas.open();
				}, true ); // Capture: run before anything else that takes link clicks (page transitions do), which then sees the click is handled.

				if ( '#assessment' === window.location.hash ) {
					root.eas.open();
				}
			}
		}

		show( 1, false );
	}

	function initAll( scope ) {
		var context = scope || document;
		toArray( context.querySelectorAll( '.eas' ) ).forEach( init );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () { initAll( document ); } );
	} else {
		initAll( document );
	}

	if ( window.jQuery ) {
		window.jQuery( window ).on( 'elementor/frontend/init', function () {
			if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
				window.elementorFrontend.hooks.addAction( 'frontend/element_ready/eas-assessment-form.default', function ( scope ) {
					var el = scope && scope[0] ? scope[0] : scope;
					if ( el && 1 === el.nodeType ) {
						initAll( el );
					}
				} );
			}
		} );
	}
}() );
