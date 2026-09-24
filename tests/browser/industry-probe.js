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

		// The names are <button>s inside a theme that styles buttons loudly.
		// They have to read as text in a list, not as controls.
		const asText = await page.evaluate( () => {
			const btn = document.querySelector( '.eind__name' );
			const li = btn.closest( 'li' );
			const cs = getComputedStyle( btn );
			const ls = getComputedStyle( li );
			return {
				bgColor: cs.backgroundColor,
				bgImage: cs.backgroundImage,
				borderWidth: cs.borderTopWidth,
				radius: cs.borderTopLeftRadius,
				shadow: cs.boxShadow,
				transform: cs.textTransform,
				padLeft: cs.paddingLeft,
				minHeight: cs.minHeight,
				family: cs.fontFamily,
				listStyle: getComputedStyle( btn.closest( 'ul' ) ).listStyleType,
				liMargin: ls.marginBottom,
				ulPadLeft: getComputedStyle( btn.closest( 'ul' ) ).paddingLeft,
			};
		} );

		check( 'no theme fill survives', 'rgba(0, 0, 0, 0)', asText.bgColor );
		check( 'nor a gradient', 'none', asText.bgImage );
		check( 'nor a border', '0px', asText.borderWidth );
		check( 'nor a radius', '0px', asText.radius );
		check( 'nor a shadow', 'none', asText.shadow );
		check( 'nor uppercasing', 'none', asText.transform );
		check( 'nor button padding', '0px', asText.padLeft );
		check( 'nor a minimum height', '0px', asText.minHeight );
		check( 'the list has no markers', 'none', asText.listStyle );
		check( 'and no list indent', '0px', asText.ulPadLeft );

		const start = await at( 0 );
		check( 'it starts on the first industry', 0, start.name );
		check( 'the panel is pinned', 'sticky', start.pinned );
		near( 'and sits at the top of the screen', 0, start.pinTop, 2 );
		check( 'the counter agrees', '01 / 06', start.counter );

		// Every signal has to move together, at every step rather than at one.
		// A picture showing one industry beside words describing another is
		// the failure that matters here, and it only appears once an industry
		// without a picture has been passed.
		const agreement = [];
		for ( let i = 0; i < 6; i++ ) {
			const s = await at( ( i + 0.5 ) / 6 );
			agreement.push( [ s.name, s.shot, s.panel, s.tick ] );
		}

		check( 'the name, picture, words and tick agree at every step', true,
			agreement.every( ( row, i ) => row.every( ( v ) => v === i ) ) );

		// One industry in the fixture has no picture on purpose. It must show
		// the empty frame rather than its neighbour's photograph.
		const blank = await page.evaluate( () => {
			const shots = [ ...document.querySelectorAll( '.eind__shot' ) ];
			return {
				count: shots.length,
				tags: shots.map( ( s ) => s.tagName.toLowerCase() ),
				lit: shots.findIndex( ( s ) => s.classList.contains( 'eind__shot--on' ) ),
			};
		} );

		check( 'there is one picture slot per industry', 6, blank.count );
		check( 'the one without a picture is a placeholder, not a missing slot',
			[ 'img', 'img', 'img', 'img', 'span', 'img' ], blank.tags );

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

		// The bed is inside the sticky panel, so it must hold still while the
		// section scrolls past. A background on the section would not.
		const bedTravel = [];
		for ( let i = 0; i <= 4; i++ ) {
			await at( i / 4 );
			bedTravel.push( await page.evaluate( () => {
				const bed = document.querySelector( '.eind__bed' );
				const r = bed.getBoundingClientRect();
				return { top: Math.round( r.top ), h: Math.round( r.height ) };
			} ) );
		}

		check( 'the bed exists', true, bedTravel.every( ( b ) => b.h > 0 ) );
		check( 'and never moves while the section scrolls', true,
			bedTravel.every( ( b ) => Math.abs( b.top - bedTravel[ 0 ].top ) <= 2 ) );
		check( 'it covers the panel', true,
			bedTravel.every( ( b ) => Math.abs( b.h - 900 ) <= 2 ) );

		// It must sit behind the content, not over it.
		const bedBehind = await page.evaluate( () => {
			const bed = document.querySelector( '.eind__bed' );
			const heading = document.querySelector( '.eind__panel--on .eind__heading' );
			const r = heading.getBoundingClientRect();
			return document.elementFromPoint( r.left + 4, r.top + r.height / 2 ) === bed;
		} );
		check( 'the bed does not cover the words', false, bedBehind );

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

		// The picture change. Not a crossfade: a hard edge sweeps the frame with
		// a ragged band of blocks on it.
		const grid = await page.evaluate( () => {
			const g = document.querySelector( '.eind__grid' );
			const cells = [ ...g.querySelectorAll( '.eind__cell' ) ];
			const ds = cells.map( ( c ) => parseFloat( c.style.getPropertyValue( '--d' ) ) );
			return {
				cells: cells.length,
				spec: g.getAttribute( 'data-eind-grid' ),
				minD: Math.min.apply( null, ds ),
				maxD: Math.max.apply( null, ds ),
				allNumbers: ds.every( ( d ) => isFinite( d ) ),
				distinct: new Set( ds.map( ( d ) => d.toFixed( 4 ) ) ).size,
			};
		} );

		check( 'the grid is built', true, grid.cells > 40 );
		check( 'every block has a position', true, grid.allNumbers );
		check( 'they run across the sweep', true, grid.minD < 0.1 && grid.maxD > 0.8 );

		// If every cell in a row shared a number the front would be a straight
		// line and the blocks would be decoration rather than the effect.
		check( 'and they are scattered, not in ranks', true, grid.distinct > grid.cells / 3 );

		// Watch an actual change: blocks must light up, and the outgoing
		// picture must stay visible underneath while the front crosses it.
		const sweep = await page.evaluate( ( ms ) => new Promise( ( resolve ) => {
			const frame = document.querySelector( '.eind__frame' );
			const names = [ ...document.querySelectorAll( '.eind__name' ) ];
			const out = { litPeak: 0, wipeSeen: false, outSeen: false, frames: 0 };
			const t0 = performance.now();
			const tick = () => {
				out.frames++;
				if ( frame.classList.contains( 'eind__frame--wipe' ) ) { out.wipeSeen = true; }
				if ( document.querySelector( '.eind__shot--out' ) ) { out.outSeen = true; }
				let lit = 0;
				document.querySelectorAll( '.eind__cell' ).forEach( ( c ) => {
					if ( +getComputedStyle( c ).opacity > 0.5 ) { lit++; }
				} );
				out.litPeak = Math.max( out.litPeak, lit );
				if ( performance.now() - t0 < ms ) { requestAnimationFrame( tick ); } else { resolve( out ); }
			};
			names[ 2 ].click();
			requestAnimationFrame( tick );
		} ), 1400 );

		check( 'the wipe runs', true, sweep.wipeSeen );
		check( 'the outgoing picture is held underneath', true, sweep.outSeen );
		check( 'blocks light up during it', true, sweep.litPeak > 10 );

		// A band, not the whole frame. The first attempt lit every block at the
		// same moment, because each stayed visible for longer than the stagger
		// spread them over -- which is a flash, not a front crossing.
		check( 'but never more than a band of it at once', true, sweep.litPeak < grid.cells * 0.55 );

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

	/* ------------------------------------------------ tablet and phone --- */
	for ( const device of [
		{ name: 'phone', width: 390, height: 844 },
		{ name: 'tablet', width: 834, height: 1112 },
	] ) {
		const page = await browser.newPage();
		if ( process.env.EIND_TRACE ) { console.log( '-> ' + device.name ); }
		await page.setViewport( {
			width: device.width,
			height: device.height,
			isMobile: true,
			hasTouch: true,
		} );
		await page.goto( URL, { waitUntil: 'load' } );

		const geometry = await page.evaluate( () => {
			const root = document.querySelector( '.eind' );
			return { h: root.offsetHeight, vh: window.innerHeight, top: root.getBoundingClientRect().top + window.pageYOffset };
		} );

		const state = await page.evaluate( () => {
			const root = document.querySelector( '.eind' );
			const pin = root.querySelector( '.eind__pin' );
			const stack = root.querySelector( '.eind__stack' );
			const list = root.querySelector( '.eind__list' );
			const frame = root.querySelector( '.eind__frame' );
			const ticks = root.querySelector( '.eind__ticks' );
			const panel = root.querySelector( '.eind__panel--on' );
			const box = ( el ) => {
				const r = el.getBoundingClientRect();
				return { top: Math.round( r.top ), bottom: Math.round( r.bottom ), left: Math.round( r.left ), right: Math.round( r.right ), w: Math.round( r.width ), h: Math.round( r.height ) };
			};
			return {
				pinned: getComputedStyle( pin ).position,
				pinShown: getComputedStyle( pin ).display !== 'none',
				stackShown: getComputedStyle( stack ).display !== 'none',
				listShown: getComputedStyle( list ).display !== 'none',
				frame: box( frame ),
				ticks: box( ticks ),
				panel: box( panel ),
				pin: box( pin ),
				overflowX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
			};
		} );

		// The point of the whole change: it stays one industry at a time,
		// driven by scroll. It does not become a list.
		check( device.name + ': the panel is still there', true, state.pinShown );
		check( device.name + ': and still pinned', 'sticky', state.pinned );
		check( device.name + ': it has not become a stack', false, state.stackShown );
		check( device.name + ': the names are gone', false, state.listShown );

		// Picture above, words below.
		check( device.name + ': the words sit under the picture', true, state.panel.top >= state.frame.bottom - 2 );
		check( device.name + ': and both are inside the panel', true,
			state.frame.top >= state.pin.top - 2 && state.panel.bottom <= state.pin.bottom + 2 );

		// Ticks against the edge of the screen rather than beside the picture.
		check( device.name + ': the ticks are at the screen edge', true,
			device.width - state.ticks.right < 40 );

		check( device.name + ': nothing overflows sideways', 0, state.overflowX );

		// And scrolling still walks the list.
		const at = async ( progress ) => {
			await page.evaluate(
				( t, sp, p ) => window.scrollTo( 0, t + sp * p ),
				geometry.top,
				geometry.h - geometry.vh,
				progress
			);
			await page.evaluate( () => new Promise( ( r ) => requestAnimationFrame( () => requestAnimationFrame( r ) ) ) );
			return page.evaluate( () => {
				const names = [ ...document.querySelectorAll( '.eind__name' ) ];
				return names.findIndex( ( n ) => n.getAttribute( 'aria-current' ) === 'true' );
			} );
		};

		check( device.name + ': it starts on the first', 0, await at( 0 ) );
		check( device.name + ': and scrolling reaches the last', 5, await at( 1 ) );

		// Measured against the window, not against the panel. The panel was
		// sized in vh, which on a phone is the viewport with the toolbars
		// hidden and therefore taller than what is on screen -- so everything
		// fitted the panel while the last line of it sat under the address
		// bar, and a test against the panel said it was fine.
		const fits = await page.evaluate( () => {
			const pin = document.querySelector( '.eind__pin' ).getBoundingClientRect();
			const link = document.querySelector( '.eind__panel--on .eind__link' );
			const r = link.getBoundingClientRect();
			return {
				panelFitsScreen: Math.round( pin.height ) <= window.innerHeight + 1,
				linkOnScreen: r.bottom <= window.innerHeight + 1 && r.top >= 0,
			};
		} );
		check( device.name + ': the panel fits the screen', true, fits.panelFitsScreen );
		check( device.name + ': the link is on screen, not under the toolbar', true, fits.linkOnScreen );

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
			const media = document.querySelector( '.eind__media' ).getBoundingClientRect();
			const tops = [ listRect.top, media.top, document.querySelector( '.eind__text' ).getBoundingClientRect().top ];

			return {
				panelInside: panelRect.top >= pinRect.top - 1 && panelRect.bottom <= pinRect.bottom + 1,
				listInside: listRect.top >= pinRect.top - 1 && listRect.bottom <= pinRect.bottom + 1,
				overflowX: document.documentElement.scrollWidth - document.documentElement.clientWidth,
				oneRow: Math.max.apply( null, tops ) - Math.min.apply( null, tops ) < pinRect.height,
			};
		} );

		check( 'the words fit the panel on a short window', true, fits.panelInside );

		// The failure that produced the above was five children in a
		// three-column grid, which wraps to a second row. Checking the three
		// columns share a row says so directly.
		check( 'the three columns are on one row', true, fits.oneRow );
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
				gridDisplay: getComputedStyle( document.querySelector( '.eind__grid' ) ).display,
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
