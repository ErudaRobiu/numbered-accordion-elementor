/**
 * Measure the page transitions module in a real browser.
 *
 * The things this checks cannot be checked any other way: whether the columns
 * actually stagger by the configured amount, whether a visitor arriving on a
 * covered page ever sees it uncovered for a frame, and whether every one of
 * the timeout fallbacks really does end with the page revealed. The PHP suite
 * can only prove the numbers going in are sane.
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/transitions-fixture.php
 *   python3 -m http.server 8732 &
 *   node tests/browser/transitions-probe.js
 *
 * Needs puppeteer available (npx puppeteer works). tests/ is excluded from the
 * release zip, so none of this ships.
 */

const puppeteer = require( 'puppeteer' );

const BASE = process.env.ETRN_BASE || 'http://localhost:8732/tests/browser/transitions';

let passed = 0;
const failed = [];

function check( name, expected, actual ) {
	if ( JSON.stringify( expected ) === JSON.stringify( actual ) ) {
		passed++;
		return;
	}
	failed.push( `${ name }\n    expected: ${ JSON.stringify( expected ) }\n    actual:   ${ JSON.stringify( actual ) }` );
}

function near( name, expected, actual, tolerance ) {
	if ( Math.abs( expected - actual ) <= tolerance ) {
		passed++;
		return;
	}
	failed.push( `${ name }\n    expected: ${ expected } ±${ tolerance }\n    actual:   ${ actual }` );
}

/** Column translateY in px, per column. */
const READ_COLUMNS = () =>
	[ ...document.querySelectorAll( '.etrn__col' ) ].map( ( c ) => {
		const m = new DOMMatrixReadOnly( getComputedStyle( c ).transform );
		return Math.round( m.f );
	} );

async function newPage( browser, options = {} ) {
	const page = await browser.newPage();
	await page.setViewport( { width: 1280, height: 800 } );

	if ( options.reducedMotion ) {
		await page.emulateMediaFeatures( [
			{ name: 'prefers-reduced-motion', value: 'reduce' },
		] );
	}

	return page;
}

( async () => {
	const browser = await puppeteer.launch( {
		headless: process.env.ETRN_HEADFUL ? false : 'shell',
		executablePath: process.env.ETRN_CHROME || undefined,
	} );

	/* ------------------------------------------------ the preloader --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> preloader' ); }
		const page = await newPage( browser );

		// Record every frame from the very first, so "was the page ever
		// visible before the curtain covered it" is answerable.
		await page.evaluateOnNewDocument( () => {
			window.__frames = [];
			const tick = () => {
				const curtain = document.querySelector( '.etrn' );
				window.__frames.push( {
					t: Math.round( performance.now() ),
					html: document.documentElement.className,
					display: curtain ? getComputedStyle( curtain ).display : null,
					covered: curtain
						? [ ...curtain.querySelectorAll( '.etrn__col' ) ].every( ( c ) => {
								const m = new DOMMatrixReadOnly( getComputedStyle( c ).transform );
								return Math.abs( m.f ) < 1 && getComputedStyle( c ).display !== 'none';
						  } )
						: false,
				} );
				requestAnimationFrame( tick );
			};
			requestAnimationFrame( tick );
		} );

		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );

		const early = await page.evaluate( () => document.documentElement.className );
		check( 'a first visit is marked as preloading', true, early.includes( 'etrn-preloading' ) );

		const busy = await page.evaluate( () => document.body.getAttribute( 'aria-busy' ) );
		check( 'the body is marked busy while loading', 'true', busy );

		// The preloader must end on its own, without anybody clicking.
		await page.waitForFunction(
			() => ! document.documentElement.className.includes( 'etrn-preloading' ) &&
				! document.documentElement.className.includes( 'etrn-leaving' ),
			{ timeout: 15000 }
		);

		const done = await page.evaluate( () => ( {
			html: document.documentElement.className,
			display: getComputedStyle( document.querySelector( '.etrn' ) ).display,
			busy: document.body.getAttribute( 'aria-busy' ),
			visited: sessionStorage.getItem( 'etrn:visited' ),
			covering: sessionStorage.getItem( 'etrn:covering' ),
		} ) );

		check( 'the curtain is hidden afterwards', 'none', done.display );
		check( 'no state class survives', false, /etrn-(preloading|covering|entering|leaving)/.test( done.html ) );
		check( 'the busy flag is cleared', null, done.busy );
		check( 'the visit is remembered', '1', done.visited );
		check( 'no covering flag is left behind', null, done.covering );

		// The bar has to have actually moved, not jumped from 0 to 100.
		const frames = await page.evaluate( () => window.__frames );
		const coveredEarly = frames.slice( 0, 3 ).every( ( f ) => f.covered || f.display === 'none' );
		check( 'the page is never shown uncovered on a first visit', true, coveredEarly );

		// The curtain must not come back. Two paths race to open the
		// preloader, and the loser used to fire its own reveal a full
		// maximum-plus-1200ms later, sweeping a second curtain across a page
		// the visitor was already reading. Waited out past that moment.
		const settledAt = Date.now();
		await page.evaluate( () => {
			window.__returned = false;
			const watch = () => {
				const curtain = document.querySelector( '.etrn' );
				if ( curtain && getComputedStyle( curtain ).display !== 'none' ) {
					window.__returned = true;
				}
				requestAnimationFrame( watch );
			};
			requestAnimationFrame( watch );
		} );

		await page.evaluate(
			( ms ) => new Promise( ( r ) => setTimeout( r, ms ) ),
			6500 - ( Date.now() - settledAt )
		);

		const returned = await page.evaluate( () => ( {
			seen: window.__returned,
			html: document.documentElement.className,
			display: getComputedStyle( document.querySelector( '.etrn' ) ).display,
		} ) );

		check( 'the curtain never reappears after the preloader', false, returned.seen );
		check( 'and no leaving class is re-applied', false, returned.html.includes( 'etrn-leaving' ) );
		check( 'and it is still hidden well past the fallback', 'none', returned.display );

		await page.close();
	}

	/* ------------------------------------------- a logo from settings --- */
	{
		const page = await newPage( browser );
		if ( process.env.ETRN_TRACE ) { console.log( '-> chosen logo' ); }

		await page.goto( `${ BASE }/chosen.html`, { waitUntil: 'domcontentloaded' } );

		const logo = await page.evaluate( () => {
			const img = document.querySelector( '.etrn__logo img' );
			return img
				? { src: img.getAttribute( 'src' ), cls: img.className, loading: img.getAttribute( 'loading' ) }
				: null;
		} );

		check( 'a logo chosen in the settings is the one used', true, !! logo && logo.src === 'chosen.svg' );
		check( 'and it is not lazily loaded', 'eager', logo && logo.loading );

		// It has to animate the same way the fallback does.
		await page.waitForFunction(
			() => {
				const l = document.querySelector( '.etrn__logo' );
				return l && +getComputedStyle( l ).opacity > 0.99;
			},
			{ timeout: 15000 }
		);
		check( 'and it animates in like any other', true, true );

		await page.waitForFunction(
			() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
			{ timeout: 15000 }
		);

		await page.close();
	}

	// The site logo is still what a site without a chosen one gets.
	{
		const page = await newPage( browser );
		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );
		const fallback = await page.evaluate( () => {
			const img = document.querySelector( '.etrn__logo img' );
			return img ? img.getAttribute( 'src' ) : null;
		} );
		check( 'without one chosen, the site logo is used', 'logo.svg', fallback );
		await page.close();
	}

	/* ------------------------------------------- a slow-arriving logo --- */
	{
		const page = await newPage( browser );
		if ( process.env.ETRN_TRACE ) { console.log( '-> slow logo' ); }

		// A media library logo is an HTTP request, and the whole reason this
		// test exists is that it usually has not arrived when the curtain goes
		// up. Held back deliberately: with the animation keyed to the
		// preloading class it finished on an empty box and the logo appeared
		// afterwards with no animation at all.
		await page.setRequestInterception( true );
		page.on( 'request', async ( request ) => {
			if ( request.url().endsWith( 'logo.svg' ) ) {
				await new Promise( ( r ) => setTimeout( r, 900 ) );
			}
			request.continue();
		} );

		await page.evaluateOnNewDocument( () => {
			window.__logo = [];
			const tick = () => {
				const logo = document.querySelector( '.etrn__logo' );
				const img = logo ? logo.querySelector( 'img' ) : null;
				if ( logo ) {
					window.__logo.push( {
						t: Math.round( performance.now() ),
						o: +getComputedStyle( logo ).opacity,
						clip: getComputedStyle( logo ).clipPath,
						loaded: img ? img.complete && img.naturalWidth > 0 : null,
						curtain: getComputedStyle( document.querySelector( '.etrn' ) ).display,
					} );
				}
				requestAnimationFrame( tick );
			};
			requestAnimationFrame( tick );
		} );

		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );
		await page.waitForFunction(
			() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
			{ timeout: 20000 }
		);

		const samples = await page.evaluate( () => window.__logo );
		const arrived = samples.find( ( s ) => s.loaded );
		const visible = samples.find( ( s ) => s.o > 0.99 );
		const midFade = samples.filter( ( s ) => s.o > 0.01 && s.o < 0.99 );
		const midWipe = samples.filter(
			( s ) => /inset/.test( s.clip ) && ! /inset\(0px 0px 0px 0px\)|inset\(0%\)|none/.test( s.clip )
				&& ! /100%/.test( s.clip )
		);

		check( 'the logo image really did arrive late', true, !! arrived && arrived.t > 400 );

		// Before it arrived the logo must still be hidden. This is the failure
		// that shipped: the animation had already run to completion on an
		// empty box.
		const beforeArrival = samples.filter( ( s ) => ! s.loaded );
		check(
			'the logo stays hidden until its image is there',
			true,
			beforeArrival.every( ( s ) => s.o < 0.05 )
		);

		check( 'it becomes fully visible afterwards', true, !! visible );
		check( 'and it got there by animating, not by appearing', true, midFade.length > 2 );
		check( 'the wipe runs alongside the fade', true, midWipe.length > 2 );

		if ( arrived && visible ) {
			check( 'the fade starts only once the image has landed', true, visible.t > arrived.t );
		}

		// And the curtain must not leave mid-animation.
		const lastShown = samples.filter( ( s ) => s.curtain !== 'none' ).pop();
		if ( lastShown && visible ) {
			check( 'the curtain waits for the logo to finish', true, lastShown.t >= visible.t );
		}

		await page.close();
	}

	/* ------------------------------------------------- covering up --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> covering up' ); }
		const page = await newPage( browser );
		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );

		// Skip past the preloader so the click is the only thing measured.
		await page.evaluate( () => sessionStorage.setItem( 'etrn:visited', '1' ) );
		await page.reload( { waitUntil: 'domcontentloaded' } );
		await page.waitForFunction(
			() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
			{ timeout: 10000 }
		);

		// The samples have to outlive the document that takes them, so they
		// go into sessionStorage on the way out rather than being read back
		// across a navigation that has already destroyed the context.
		await page.evaluate( () => {
			const samples = [];
			const tick = () => {
				samples.push( {
					t: Math.round( performance.now() ),
					y: [ ...document.querySelectorAll( '.etrn__col' ) ].map( ( c ) => {
						const m = new DOMMatrixReadOnly( getComputedStyle( c ).transform );
						return Math.round( m.f );
					} ),
				} );
				requestAnimationFrame( tick );
			};

			window.addEventListener( 'pagehide', () => {
				sessionStorage.setItem( 'probe:cover', JSON.stringify( samples ) );
			} );

			requestAnimationFrame( tick );
			document.querySelector( 'nav a[href="two.html"]' ).click();
		} );

		await page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 20000 } );
		check( 'the click navigated', true, page.url().endsWith( 'two.html' ) );

		const cover = await page.evaluate( () => JSON.parse( sessionStorage.getItem( 'probe:cover' ) || '[]' ) );

		const starts = [];
		for ( let col = 0; col < 6; col++ ) {
			const moved = cover.find( ( f ) => f.y[ col ] !== undefined && f.y[ col ] < 790 );
			if ( moved ) {
				starts.push( moved.t );
			}
		}

		check( 'every column moves', 6, starts.length );

		if ( starts.length === 6 ) {
			check( 'they start in order', true, starts.every( ( t, i ) => i === 0 || t >= starts[ i - 1 ] ) );

			const gaps = starts.slice( 1 ).map( ( t, i ) => t - starts[ i ] );
			const mean = gaps.reduce( ( a, b ) => a + b, 0 ) / gaps.length;
			near( 'the stagger matches the setting', 50, mean, 22 );
		}

		// The screen is covered by the time the page is left, and no column
		// has carried on past zero: an overshoot would show the old page again.
		if ( cover.length ) {
			const last = cover[ cover.length - 1 ];
			check( 'the screen is covered before leaving', true, last.y.every( ( y ) => y <= 2 && y > -20 ) );
		}

		await page.evaluate( () => sessionStorage.removeItem( 'probe:cover' ) );
		await page.close();
	}

	/* --------------------------------------------------- arriving --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> arriving' ); }
		const page = await newPage( browser );

		await page.evaluateOnNewDocument( () => {
			window.__first = null;
			const grab = () => {
				const curtain = document.querySelector( '.etrn' );
				if ( ! curtain ) {
					requestAnimationFrame( grab );
					return;
				}
				if ( window.__first === null ) {
					window.__first = {
						html: document.documentElement.className,
						display: getComputedStyle( curtain ).display,
						y: [ ...curtain.querySelectorAll( '.etrn__col' ) ].map( ( c ) => {
							const m = new DOMMatrixReadOnly( getComputedStyle( c ).transform );
							return Math.round( m.f );
						} ),
					};
				}
			};
			requestAnimationFrame( grab );
		} );

		// Arrive the way a real click leaves things: the flag set, the page
		// never having been seen.
		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );
		await page.evaluate( () => {
			sessionStorage.setItem( 'etrn:visited', '1' );
			sessionStorage.setItem( 'etrn:covering', '1' );
		} );

		await page.goto( `${ BASE }/two.html`, { waitUntil: 'domcontentloaded' } );

		const first = await page.evaluate( () => window.__first );

		check( 'the arriving page is covered on its first frame', true, !! first && first.display !== 'none' );

		if ( first ) {
			check( 'and every column is in place, not below', true, first.y.every( ( y ) => Math.abs( y ) < 2 ) );
		}

		await page.waitForFunction(
			() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
			{ timeout: 10000 }
		);

		const after = await page.evaluate( () => ( {
			covering: sessionStorage.getItem( 'etrn:covering' ),
			html: document.documentElement.className,
		} ) );

		check( 'the covering flag is cleared on arrival', null, after.covering );
		check( 'and no state class is left behind', false, /etrn-(preloading|covering|entering|leaving)/.test( after.html ) );

		await page.close();
	}

	/* ------------------------------------------- links left alone --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> links left alone' ); }
		const page = await newPage( browser );
		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );
		await page.evaluate( () => sessionStorage.setItem( 'etrn:visited', '1' ) );
		await page.reload( { waitUntil: 'domcontentloaded' } );
		await page.waitForFunction(
			() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
			{ timeout: 10000 }
		);

		// Dispatching a real click on a real anchor navigates, which would take
		// the execution context with it. A listener registered after the
		// module's sees whatever the module did or did not do, and then stops
		// the navigation itself so the page survives to be asked about.
		await page.evaluate( () => {
			window.__seen = null;
			document.addEventListener( 'click', ( e ) => {
				window.__seen = e.defaultPrevented;
				e.preventDefault();
			} );
		} );

		const clickOn = ( selector, init = {} ) =>
			page.evaluate(
				( sel, extra ) => {
					window.__seen = null;
					const link = document.querySelector( sel );
					link.dispatchEvent(
						new MouseEvent( 'click', Object.assign( { bubbles: true, cancelable: true, button: 0 }, extra ) )
					);
					return {
						intercepted: window.__seen,
						display: getComputedStyle( document.querySelector( '.etrn' ) ).display,
					};
				},
				selector,
				init
			);

		const noTransition = await clickOn( 'a.no-transition' );
		check( 'a no-transition link is not intercepted', false, noTransition.intercepted );
		check( 'and no curtain appears for it', 'none', noTransition.display );

		check( 'an external link is not intercepted', false, ( await clickOn( 'a.out' ) ).intercepted );
		check( 'a command-click is not intercepted', false, ( await clickOn( 'nav a[href="three.html"]', { metaKey: true } ) ).intercepted );
		check( 'a link to the current page is not intercepted', false, ( await clickOn( 'nav a[aria-current]' ) ).intercepted );

		// The control: an ordinary internal link must still be taken.
		const ordinary = await clickOn( 'nav a[href="three.html"]' );
		check( 'an ordinary internal link is intercepted', true, ordinary.intercepted );

		await page.close();
	}

	/* ------------------------------------ the animationend fallback --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> animationend fallback' ); }
		const page = await newPage( browser );

		// Swallow every animationend before the module can hear it. Without a
		// timeout this is exactly the state that kills a link for good.
		await page.evaluateOnNewDocument( () => {
			window.addEventListener(
				'animationend',
				( e ) => {
					e.stopImmediatePropagation();
				},
				true
			);
		} );

		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );
		await page.evaluate( () => sessionStorage.setItem( 'etrn:visited', '1' ) );
		await page.reload( { waitUntil: 'domcontentloaded' } );

		let revealed = true;
		await page
			.waitForFunction(
				() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
				{ timeout: 8000 }
			)
			.catch( () => {
				revealed = false;
			} );
		check( 'the reveal still finishes with no animationend', true, revealed );

		const navigation = page.waitForNavigation( { waitUntil: 'domcontentloaded', timeout: 8000 } ).catch( () => null );
		await page
			.evaluate( () => document.querySelector( 'nav a[href="two.html"]' ).click() )
			.catch( () => {} );
		const arrived = await navigation;
		check( 'and a click still navigates', true, !! arrived );

		await page.close();
	}

	/* ------------------------------------------ storage unavailable --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> storage unavailable' ); }
		const page = await newPage( browser );

		// Private browsing and blocked site data make these throw rather than
		// return null. A throw must not leave the curtain up.
		await page.evaluateOnNewDocument( () => {
			Object.defineProperty( window, 'sessionStorage', {
				get() {
					throw new Error( 'blocked' );
				},
			} );
		} );

		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );

		let survived = true;
		await page
			.waitForFunction(
				() => getComputedStyle( document.querySelector( '.etrn' ) ).display === 'none',
				{ timeout: 12000 }
			)
			.catch( () => {
				survived = false;
			} );

		check( 'a blocked sessionStorage still reveals the page', true, survived );
		await page.close();
	}

	/* --------------------------------------------- reduced motion --- */
	{
		if ( process.env.ETRN_TRACE ) { console.log( '-> reduced motion' ); }
		const page = await newPage( browser, { reducedMotion: true } );
		await page.goto( `${ BASE }/one.html`, { waitUntil: 'domcontentloaded' } );

		const state = await page.evaluate( () => ( {
			html: document.documentElement.className,
			display: getComputedStyle( document.querySelector( '.etrn' ) ).display,
			overflow: getComputedStyle( document.documentElement ).overflow,
		} ) );

		check( 'no preloading class is set', false, state.html.includes( 'etrn-preloading' ) );
		check( 'the curtain is not displayed', 'none', state.display );
		check( 'the page is not locked', true, state.overflow !== 'hidden' );

		const prevented = await page.evaluate( () => {
			const link = document.querySelector( 'nav a[href="two.html"]' );
			const event = new MouseEvent( 'click', { bubbles: true, cancelable: true, button: 0 } );
			link.dispatchEvent( event );
			return event.defaultPrevented;
		} );
		check( 'clicks are left entirely alone', false, prevented );

		await page.close();
	}

	await browser.close();

	console.log( '' );

	if ( ! failed.length ) {
		console.log( `OK — ${ passed } browser assertions passed\n` );
		process.exit( 0 );
	}

	console.log( `FAILED — ${ passed } passed, ${ failed.length } failed\n` );
	failed.forEach( ( f ) => console.log( '  ' + f + '\n' ) );
	process.exit( 1 );
} )();
