/**
 * Measure the industry showcase in a real browser.
 *
 * What only a browser can answer: whether the panel actually pins, whether
 * scrolling lands on the items in order and on the right one at each end,
 * whether clicking a name lands on that item rather than its neighbour, and
 * whether the phone layout really does stop pinning rather than merely looking
 * different.
 *
 * Usage, from the repository root:
 *
 *   php tests/browser/industry-fixture.php > tests/browser/industry.html
 *   python3 -m http.server 8732 &
 *   node tests/browser/industry-probe.js
 *
 * If a run hangs with no output, a Chrome from an interrupted run is still
 * about: pkill -f chrome-headless-shell.
 */

const puppeteer = require( 'puppeteer' );

const URL = process.env.EIND_URL || 'http://localhost:8732/tests/browser/industry.html';

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

/** Which item is currently showing, by every signal at once. */
const STATE = () => {
	const root = document.querySelector( '.eind' );
	const names = [ ...root.querySelectorAll( '.eind__name' ) ];
	const shots = [ ...root.querySelectorAll( '.eind__shot' ) ];
	const panels = [ ...root.querySelectorAll( '.eind__pin .eind__panel' ) ];
	const ticks = [ ...root.querySelectorAll( '.eind__tick' ) ];
	const count = root.querySelector( '.eind__count' );
	const pin = root.querySelector( '.eind__pin' );
	const at = ( list, test ) => list.findIndex( test );
	return {
		name: at( names, ( n ) => n.getAttribute( 'aria-current' ) === 'true' ),
		shot: at( shots, ( s ) => s.classList.contains( 'eind__shot--on' ) ),
		panel: at( panels, ( p ) => p.classList.contains( 'eind__panel--on' ) ),
		tick: at( ticks, ( t ) => t.classList.contains( 'eind__tick--on' ) ),
		counter: count ? count.textContent.trim() : null,
		pinTop: pin ? Math.round( pin.getBoundingClientRect().top ) : null,
		pinned: pin ? getComputedStyle( pin ).position : null,
		p: getComputedStyle( root ).getPropertyValue( '--eind-p' ).trim(),
	};
};

( async () => {
	const browser = await puppeteer.launch( {
		headless: process.env.EIND_HEADFUL ? false : 'shell',
		executablePath: process.env.EIND_CHROME || undefined,
	} );

	/* ------------------------------------------------------ desktop --- */
	{
		const page = await browser.newPage();
		await page.setViewport( { width: 1440, height: 900 } );
		await page.goto( URL, { waitUntil: 'load' } );

		const geometry = await page.evaluate( () => {
			const root = document.querySelector( '.eind' );
			return {
				h: root.offsetHeight,
				vh: window.innerHeight,
				top: root.getBoundingClientRect().top + window.pageYOffset,
				items: root.querySelectorAll( '.eind__name' ).length,
			};
		} );

		// 6 items at 85vh each, plus one viewport for the panel.
		check( 'six industries render', 6, geometry.items );
		near( 'the section is as tall as the arithmetic says', 6.1 * geometry.vh, geometry.h, 4 );

		const at = async ( progress ) => {
			await page.evaluate(
				( t, s, p ) => window.scrollTo( 0, t + s * p ),
				geometry.top,
				geometry.h - geometry.vh,
				progress
			);
			await page.evaluate( () => new Promise( ( r ) => requestAnimationFrame( () => requestAnimationFrame( r ) ) ) );
			return page.evaluate( STATE );
		};

		const start = await at( 0 );
		check( 'it starts on the first industry', 0, start.name );
		check( 'the panel is pinned', 'sticky', start.pinned );
		near( 'and sits at the top of the screen', 0, start.pinTop, 2 );
		check( 'the counter agrees', '01 / 06', start.counter );

		// Every signal has to move together. A picture showing one industry
		// beside words describing another is the failure that matters here.
		const middle = await at( 0.5 );
		check( 'the name, picture, words and tick agree', true,
			middle.name === middle.shot && middle.shot === middle.panel && middle.panel === middle.tick );

		const end = await at( 1 );
		check( 'the far end is the last industry, not past it', 5, end.name );
		check( 'and the counter says so', '06 / 06', end.counter );

		// Walking the whole section must produce every index in order, with
		// nothing skipped and nothing going backwards.
		const seen = [];
		for ( let i = 0; i <= 40; i++ ) {
			const s = await at( i / 40 );
			if ( seen[ seen.length - 1 ] !== s.name ) {
				seen.push( s.name );
			}
		}
		check( 'every industry is reached, in order, once each', [ 0, 1, 2, 3, 4, 5 ], seen );

		// Clicking a name must land on that industry, not on the boundary
		// where the next one takes over.
		const clicked = [];
		for ( let i = 0; i < 6; i++ ) {
			await page.evaluate( ( n ) => {
				document.querySelectorAll( '.eind__name' )[ n ].click();
			}, i );
			await page.evaluate( () => new Promise( ( r ) => setTimeout( r, 700 ) ) );
			clicked.push( ( await page.evaluate( STATE ) ).name );
		}
		check( 'clicking each name lands on it', [ 0, 1, 2, 3, 4, 5 ], clicked );

		// The frame's proportions are a design decision; the photograph's are
		// an accident. Fitting rather than filling would leave bars.
		// Measured from layout, not from the painted rectangle: the incoming
		// picture is still easing out of a 1.06 overscale, which inflates its
		// bounding box by a few tenths of a percent and makes a comparison
		// against the frame fail for reasons that have nothing to do with fit.
		const fill = await page.evaluate( () => {
			const frame = document.querySelector( '.eind__frame' );
			const shot = document.querySelector( '.eind__shot--on' );
			return {
				fit: getComputedStyle( shot ).objectFit,
				coversWidth: shot.offsetWidth === frame.offsetWidth,
				coversHeight: shot.offsetHeight === frame.offsetHeight,
				ratio: ( frame.offsetWidth / frame.offsetHeight ).toFixed( 2 ),
			};
		} );

		check( 'the picture fills rather than fits', 'cover', fill.fit );
		check( 'it covers the frame across', true, fill.coversWidth );
		check( 'and down', true, fill.coversHeight );
		check( 'the frame keeps its shape', '0.75', fill.ratio );

		// The panel spans the section it is dropped into rather than sitting
		// in a column of its own.
		const spans = await page.evaluate( () => {
			const root = document.querySelector( '.eind' );
			const pin = root.querySelector( '.eind__pin' );
			return {
				rootWidth: Math.round( root.getBoundingClientRect().width ),
				pinWidth: Math.round( pin.getBoundingClientRect().width ),
				viewport: window.innerWidth,
			};
		} );
		check( 'the panel is as wide as the widget', true, spans.pinWidth === spans.rootWidth );
		check( 'and the widget fills the window', true, spans.rootWidth >= spans.viewport - 1 );

		// Past the end the panel must let go rather than stay stuck.
		await page.evaluate(
			( t, h ) => window.scrollTo( 0, t + h + 400 ),
			geometry.top,
			geometry.h
		);
		await page.evaluate( () => new Promise( ( r ) => requestAnimationFrame( r ) ) );
		const past = await page.evaluate( STATE );
		check( 'the panel releases past the section', true, past.pinTop < -100 );

		await page.close();
	}

	/* -------------------------------------------------------- phone --- */
	{
		const page = await browser.newPage();
		await page.setViewport( { width: 390, height: 844, isMobile: true, hasTouch: true } );
		await page.goto( URL, { waitUntil: 'load' } );

		const mobile = await page.evaluate( () => {
			const root = document.querySelector( '.eind' );
			const pin = root.querySelector( '.eind__pin' );
			const stack = root.querySelector( '.eind__stack' );
			const cards = root.querySelectorAll( '.eind__card' );
			return {
				pinDisplay: getComputedStyle( pin ).display,
				stackDisplay: getComputedStyle( stack ).display,
				cards: cards.length,
				height: root.offsetHeight,
				vh: window.innerHeight,
				overflowX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
				firstCardWidth: cards.length ? Math.round( cards[ 0 ].getBoundingClientRect().width ) : 0,
				headings: [ ...root.querySelectorAll( '.eind__stack .eind__heading' ) ].map( ( h ) => h.textContent.trim().length ),
			};
		} );

		check( 'the pinned panel is gone on a phone', 'none', mobile.pinDisplay );
		check( 'the stack is shown instead', 'flex', mobile.stackDisplay );
		check( 'every industry is in the stack', 6, mobile.cards );
		check( 'each one has its words', true, mobile.headings.length === 6 && mobile.headings.every( ( n ) => n > 0 ) );

		// The desktop section height is in a style attribute, so the phone
		// rule has to beat it or the page ends up six screens of nothing.
		check( 'the six-viewport height is dropped', true, mobile.height < mobile.vh * 6 );

		// The thing people actually notice on a phone.
		check( 'nothing overflows sideways', 0, mobile.overflowX );
		check( 'the cards fit the screen', true, mobile.firstCardWidth <= 390 );

		// Text hard against the edge of a phone is the thing everybody sees
		// and nobody writes a test for.
		const gutters = await page.evaluate( () => {
			const edges = [];
			document.querySelectorAll( '.eind__stack .eind__heading, .eind__stack .eind__body, .eind__stack .eind__link' )
				.forEach( ( el ) => {
					const r = el.getBoundingClientRect();
					edges.push( Math.round( r.left ) );
					edges.push( Math.round( window.innerWidth - r.right ) );
				} );
			return { min: Math.min.apply( null, edges ), count: edges.length };
		} );

		check( 'there is text to measure', true, gutters.count > 0 );
		check( 'nothing sits against the edge of the screen', true, gutters.min >= 12 );

		// Scrolling must not drive anything here: there is no panel to drive.
		await page.evaluate( () => window.scrollTo( 0, 1200 ) );
		await page.evaluate( () => new Promise( ( r ) => requestAnimationFrame( r ) ) );
		const after = await page.evaluate( () => ( {
			overflowX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
		} ) );
		check( 'and still nothing overflows after scrolling', 0, after.overflowX );

		await page.close();
	}

	/* ----------------------------------------------- a narrow laptop --- */
	{
		// The awkward width: too wide for the stack, too short for a
		// comfortable panel. The panel must not clip its own text.
		const page = await browser.newPage();
		await page.setViewport( { width: 1024, height: 620 } );
		await page.goto( URL, { waitUntil: 'load' } );

		const fits = await page.evaluate( () => {
			const pin = document.querySelector( '.eind__pin' );
			const panel = document.querySelector( '.eind__panel--on' );
			const list = document.querySelector( '.eind__list' );
			const pinRect = pin.getBoundingClientRect();
			const panelRect = panel.getBoundingClientRect();
			const listRect = list.getBoundingClientRect();
			return {
				panelInside: panelRect.top >= pinRect.top - 1 && panelRect.bottom <= pinRect.bottom + 1,
				listInside: listRect.top >= pinRect.top - 1 && listRect.bottom <= pinRect.bottom + 1,
				overflowX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
			};
		} );

		check( 'the words fit the panel on a short window', true, fits.panelInside );
		check( 'so does the list', true, fits.listInside );
		check( 'and nothing overflows sideways', 0, fits.overflowX );

		await page.close();
	}

	/* ------------------------------------------------ reduced motion --- */
	{
		const page = await browser.newPage();
		await page.setViewport( { width: 1440, height: 900 } );
		await page.emulateMediaFeatures( [ { name: 'prefers-reduced-motion', value: 'reduce' } ] );
		await page.goto( URL, { waitUntil: 'load' } );

		const still = await page.evaluate( () => {
			const shot = document.querySelector( '.eind__shot' );
			const panel = document.querySelector( '.eind__panel' );
			return {
				shotTransition: getComputedStyle( shot ).transitionDuration,
				shotTransform: getComputedStyle( shot ).transform,
				panelTransition: getComputedStyle( panel ).transitionDuration,
			};
		} );

		check( 'the picture does not animate', '0s', still.shotTransition );
		check( 'nor is it held overscaled', 'none', still.shotTransform );
		check( 'the words do not animate', '0s', still.panelTransition );

		// It still has to work: scrolling still changes which one is showing.
		const geometry = await page.evaluate( () => {
			const root = document.querySelector( '.eind' );
			return { h: root.offsetHeight, vh: window.innerHeight, top: root.getBoundingClientRect().top + window.pageYOffset };
		} );
		await page.evaluate( ( t, s ) => window.scrollTo( 0, t + s * 0.9 ), geometry.top, geometry.h - geometry.vh );
		await page.evaluate( () => new Promise( ( r ) => requestAnimationFrame( () => requestAnimationFrame( r ) ) ) );
		const state = await page.evaluate( STATE );
		check( 'and scrolling still changes the industry', 5, state.name );

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
