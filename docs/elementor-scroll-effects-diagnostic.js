/**
 * Why are Elementor's scrolling effects not working on this page?
 *
 * Paste the whole thing into the browser console on the page that is
 * misbehaving, on the machine that is misbehaving. It reports the known causes
 * in the order they are worth checking, and it changes nothing.
 *
 * The first check is the one that catches most "it works on my other laptop"
 * reports: Elementor switches motion effects off entirely for anyone whose
 * operating system is set to reduce motion, and says nothing about it.
 */
( function () {
	var lines = [];
	var say = function ( ok, label, detail ) {
		lines.push( ( ok ? '  OK   ' : '  <-- ' ) + label + ( detail ? '  ' + detail : '' ) );
	};

	// 1. The operating system. Elementor disables motion effects outright.
	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	say( ! reduced, 'OS reduce-motion is ' + ( reduced ? 'ON — Elementor disables motion effects entirely' : 'off' ),
		reduced ? '(macOS: Accessibility > Display > Reduce motion. Windows 11: Accessibility > Visual effects > Animation effects)' : '' );

	// 2. Is Elementor Pro's motion effects code even on the page?
	var hasPro = !! ( window.elementorFrontend && window.elementorFrontend.modules );
	var fx = document.querySelectorAll( '.elementor-motion-effects-element, [data-settings*="motion_fx"]' ).length;
	say( hasPro, 'elementorFrontend present', hasPro ? '' : '(Pro script missing or deferred by an optimiser)' );
	say( fx > 0, 'elements with motion effects on this page: ' + fx );

	// 3. Background effects need the layer Elementor injects.
	var layers = document.querySelectorAll( '.elementor-motion-effects-layer' ).length;
	var containers = document.querySelectorAll( '.elementor-motion-effects-container' ).length;
	say( ! ( fx > 0 && containers === 0 ), 'background effect containers: ' + containers + ', layers: ' + layers,
		fx > 0 && containers === 0 ? '(background effects need a background IMAGE on that section, not a colour)' : '' );

	// 4. An ancestor that breaks the measurement.
	var blocking = [];
	document.querySelectorAll( '.elementor-motion-effects-element' ).forEach( function ( el ) {
		var p = el.parentElement;
		while ( p && p !== document.documentElement ) {
			var cs = getComputedStyle( p );
			if ( cs.overflow !== 'visible' || cs.transform !== 'none' || cs.filter !== 'none' || cs.perspective !== 'none' ) {
				blocking.push( ( p.className || p.tagName ).toString().slice( 0, 40 ) +
					' {overflow:' + cs.overflow + '; transform:' + ( cs.transform === 'none' ? 'none' : 'set' ) + '}' );
				break;
			}
			p = p.parentElement;
		}
	} );
	say( ! blocking.length, 'ancestors that clip or create a containing block: ' + blocking.length,
		blocking.length ? '\n         ' + blocking.slice( 0, 4 ).join( '\n         ' ) : '' );

	// 5. Two things owning the same scroll.
	var behavior = getComputedStyle( document.documentElement ).scrollBehavior;
	var lenis = document.documentElement.classList.contains( 'lenis' );
	say( ! ( lenis && behavior === 'smooth' ), 'scroll-behavior: ' + behavior + ( lenis ? ', Lenis running' : ', no Lenis' ),
		lenis && behavior === 'smooth' ? '(both are animating the same scroll)' : '' );

	// 6. Scroll snap stops effects firing.
	var snap = getComputedStyle( document.documentElement ).scrollSnapType;
	var bodySnap = getComputedStyle( document.body ).scrollSnapType;
	say( snap === 'none' && bodySnap === 'none', 'scroll-snap-type: html ' + snap + ', body ' + bodySnap );

	// 7. The Elementor bug where effects do not start until a resize.
	var before = document.querySelectorAll( '.elementor-motion-effects-layer' ).length;
	window.dispatchEvent( new Event( 'resize' ) );
	window.setTimeout( function () {
		var after = document.querySelectorAll( '.elementor-motion-effects-layer' ).length;
		say( after === before, 'layers after a resize: ' + after + ' (was ' + before + ')',
			after !== before ? '(they only initialise on resize — Elementor issue 8529)' : '' );

		console.log( '\n=== Elementor scrolling effects ===\n' + lines.join( '\n' ) +
			'\n\nAnything marked <-- is worth acting on, top first.\n' );
	}, 400 );
}() );
