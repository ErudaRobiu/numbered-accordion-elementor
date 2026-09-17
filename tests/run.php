<?php
/**
 * Tests for the plugin's pure logic.
 *
 * Run with: php tests/run.php
 *
 * Deliberately dependency-free. This repository has no Composer setup and
 * vendors its one library by hand; a test suite that needs an install step
 * before it can run is a test suite that stops being run.
 *
 * @package ErudaToolkit
 */

require_once __DIR__ . '/bootstrap.php';

use ErudaToolkit\Toolkit;
use ErudaToolkit\Modules\Duplicator\Duplicator;
use ErudaToolkit\Modules\Impact\Impact_Content;
use ErudaToolkit\Modules\Motion\Motion_Presets;
use ErudaToolkit\Modules\Motion\Motion_Controls;

/**
 * Undo wp_slash(), so a round trip can be asserted.
 *
 * @param mixed $value Value.
 * @return mixed
 */
function wp_unslash_stub( $value ) {
	if ( is_array( $value ) ) {
		return array_map( 'wp_unslash_stub', $value );
	}

	if ( is_string( $value ) ) {
		return stripslashes( $value );
	}

	return $value;
}

$passed = 0;
$failed = array();

/**
 * Assert two values match.
 *
 * @param string $name     Test name.
 * @param mixed  $expected Expected.
 * @param mixed  $actual   Actual.
 */
function check( $name, $expected, $actual ) {
	global $passed, $failed;

	if ( $expected === $actual ) {
		++$passed;
		return;
	}

	$failed[] = sprintf(
		"%s\n    expected: %s\n    actual:   %s",
		$name,
		var_export( $expected, true ),
		var_export( $actual, true )
	);
}

/* ------------------------------------------------ Toolkit::is_enabled --- */

check( 'missing key counts as enabled', true, Toolkit::is_enabled( 'duplicator', array() ) );
check( 'a new module is on by default', true, Toolkit::is_enabled( 'future', array( 'accordion' => true ) ) );
check( 'explicit true is enabled', true, Toolkit::is_enabled( 'accordion', array( 'accordion' => true ) ) );
check( 'explicit false is disabled', false, Toolkit::is_enabled( 'accordion', array( 'accordion' => false ) ) );
check( 'stored "0" is disabled', false, Toolkit::is_enabled( 'accordion', array( 'accordion' => '0' ) ) );
check( 'a corrupt option counts as enabled', true, Toolkit::is_enabled( 'accordion', 'not an array' ) );

/* ------------------------------------------------- Duplicator::filter_meta --- */

$denied = Duplicator::denied_meta_keys();

foreach ( array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date' ) as $key ) {
	check( "denylist contains {$key}", true, in_array( $key, $denied, true ) );
}

// The three Elementor caches keyed to the source post id. Copying any of them
// makes the duplicate serve the original's stale CSS.
foreach ( array( '_elementor_css', '_elementor_page_assets', '_elementor_element_cache' ) as $key ) {
	check( "denylist contains {$key}", true, in_array( $key, $denied, true ) );
}

// The layout itself, and everything needed to render it, must survive.
foreach ( array( '_elementor_data', '_elementor_page_settings', '_elementor_edit_mode', '_elementor_template_type', '_elementor_version', '_thumbnail_id', '_wp_page_template' ) as $key ) {
	check( "denylist does not contain {$key}", false, in_array( $key, $denied, true ) );
}

$meta = Duplicator::filter_meta(
	array(
		'_elementor_data' => array( '[{"id":"a1"}]' ),
		'_elementor_css'  => array( 'stale' ),
		'_edit_lock'      => array( '1234:1' ),
		'_thumbnail_id'   => array( '42' ),
	)
);

check( 'denied keys are dropped', array( '_elementor_data', '_thumbnail_id' ), array_keys( $meta ) );

check(
	'a bare scalar is normalised to a list',
	array( 'x' => array( 'y' ) ),
	Duplicator::filter_meta( array( 'x' => 'y' ) )
);

check( 'a non-array argument yields no meta', array(), Duplicator::filter_meta( 'nonsense' ) );
check( 'null yields no meta', array(), Duplicator::filter_meta( null ) );

/*
 * The one that matters. _elementor_data is slashed JSON in the database.
 * get_post_meta() unslashes on read and add_post_meta() unslashes again on
 * write, so without a re-slash every escaped quote in the layout loses its
 * backslash and the JSON stops parsing. Assert the round trip is lossless.
 */
$layout = '[{"id":"a1","settings":{"title":"He said \"hello\"","path":"C:\\temp"}}]';

$round_tripped = wp_unslash_stub( Duplicator::filter_meta( array( '_elementor_data' => array( $layout ) ) ) );

check( 'slashing survives the meta round trip', array( '_elementor_data' => array( $layout ) ), $round_tripped );
check( 'the round-tripped layout is still valid JSON', true, null !== json_decode( $round_tripped['_elementor_data'][0], true ) );

$nested = array( 'a' => array( 'b' => 'quote " here' ) );
check(
	'nested array values are slashed too',
	array( 'k' => array( $nested ) ),
	wp_unslash_stub( Duplicator::filter_meta( array( 'k' => array( $nested ) ) ) )
);

check(
	'non-string values pass through untouched',
	array( 'n' => array( 7 ) ),
	Duplicator::filter_meta( array( 'n' => array( 7 ) ) )
);

/* --------------------------------------------------- Duplicator::copy_title --- */

check( 'title gains a suffix', 'About us (Copy)', Duplicator::copy_title( 'About us' ) );
check( 'an empty title still gets a suffix', ' (Copy)', Duplicator::copy_title( '' ) );

/* --------------------------------------------------- Duplicator::build_args --- */

$source = array(
	'ID'                    => 12,
	'post_title'            => 'Pricing',
	'post_content'          => '<p>hi</p>',
	'post_content_filtered' => 'filtered',
	'post_excerpt'          => 'short',
	'post_type'             => 'page',
	'post_parent'           => '3',
	'menu_order'            => '5',
	'comment_status'        => 'open',
	'ping_status'           => 'open',
	'post_password'         => 'secret',
	'post_status'           => 'publish',
	'post_author'           => '9',
	'post_name'             => 'pricing',
	'post_date'             => '2020-01-01 00:00:00',
);

$args = Duplicator::build_args( $source, 4 );

check( 'the copy is always a draft', 'draft', $args['post_status'] );
check( 'the copy belongs to whoever clicked', 4, $args['post_author'] );
check( 'the title is suffixed', 'Pricing (Copy)', $args['post_title'] );
check( 'content carries over', '<p>hi</p>', $args['post_content'] );
check( 'filtered content carries over', 'filtered', $args['post_content_filtered'] );
check( 'the excerpt carries over', 'short', $args['post_excerpt'] );
check( 'the post type carries over', 'page', $args['post_type'] );
check( 'the parent carries over as an int', 3, $args['post_parent'] );
check( 'menu order carries over as an int', 5, $args['menu_order'] );
check( 'comment status carries over', 'open', $args['comment_status'] );
check( 'the password carries over', 'secret', $args['post_password'] );

// Left out so WordPress generates them; copying the slug would collide with
// the source.
check( 'the slug is not copied', false, array_key_exists( 'post_name', $args ) );
check( 'the date is not copied', false, array_key_exists( 'post_date', $args ) );
check( 'the source id is not copied', false, array_key_exists( 'ID', $args ) );

$sparse = Duplicator::build_args( array( 'post_title' => 'Bare' ), 1 );

check( 'a sparse source still yields a draft', 'draft', $sparse['post_status'] );
check( 'post type defaults to post', 'post', $sparse['post_type'] );
check( 'parent defaults to zero', 0, $sparse['post_parent'] );
check( 'comment status defaults to closed', 'closed', $sparse['comment_status'] );


/* ------------------------------------------------ Impact_Content::parse_list --- */

check( 'an empty list yields nothing', array(), Impact_Content::parse_list( '' ) );
check( 'whitespace only yields nothing', array(), Impact_Content::parse_list( "  \n\t\n " ) );
check( 'a non-string yields nothing', array(), Impact_Content::parse_list( null ) );

check(
	'a single line becomes one item with no sub-bullets',
	array( array( 'text' => 'Minimal permitting complexity', 'sub' => array() ) ),
	Impact_Content::parse_list( 'Minimal permitting complexity' )
);

check(
	'two lines become two items',
	array(
		array( 'text' => 'Minimal permitting complexity', 'sub' => array() ),
		array( 'text' => 'Designed for retrofit and new-build applications', 'sub' => array() ),
	),
	Impact_Content::parse_list( "Minimal permitting complexity\nDesigned for retrofit and new-build applications" )
);

// Card 6 of the source artwork: one check item carrying two nested bullets.
check(
	'a dash-prefixed line nests under the item above it',
	array(
		array(
			'text' => 'Cloud-connected ThermStar Power Intelligence',
			'sub'  => array( 'Real-time performance monitoring', 'Decarbonization reporting' ),
		),
	),
	Impact_Content::parse_list( "Cloud-connected ThermStar Power Intelligence\n- Real-time performance monitoring\n- Decarbonization reporting" )
);

check(
	'nesting resets at the next top-level line',
	array(
		array( 'text' => 'First', 'sub' => array( 'One' ) ),
		array( 'text' => 'Second', 'sub' => array() ),
	),
	Impact_Content::parse_list( "First\n- One\nSecond" )
);

check(
	'bullet, en dash and asterisk all mark a sub-bullet',
	array( array( 'text' => 'Top', 'sub' => array( 'a', 'b', 'c' ) ) ),
	Impact_Content::parse_list( "Top\n* a\n\xe2\x80\xa2 b\n\xe2\x80\x93 c" )
);

/*
 * Content must never vanish silently. A list whose very first line is already
 * a sub-bullet has nothing to nest under, so it is promoted rather than
 * dropped -- an editor who indents everything still sees all of their text.
 */
check(
	'a leading sub-bullet is promoted rather than dropped',
	array( array( 'text' => 'Orphan', 'sub' => array() ) ),
	Impact_Content::parse_list( '- Orphan' )
);

check(
	'blank lines between items are ignored',
	array(
		array( 'text' => 'One', 'sub' => array() ),
		array( 'text' => 'Two', 'sub' => array() ),
	),
	Impact_Content::parse_list( "One\n\n\nTwo\n" )
);

check(
	'carriage returns are handled',
	array(
		array( 'text' => 'One', 'sub' => array( 'a' ) ),
		array( 'text' => 'Two', 'sub' => array() ),
	),
	Impact_Content::parse_list( "One\r\n-\ta\r\nTwo\r\n" )
);

// A dash inside the text is only a marker at the start of the line.
check(
	'an internal dash is left alone',
	array( array( 'text' => 'More exhaust heat = more recoverable energy', 'sub' => array() ) ),
	Impact_Content::parse_list( 'More exhaust heat = more recoverable energy' )
);

check(
	'a line of nothing but a marker is dropped',
	array( array( 'text' => 'Top', 'sub' => array() ) ),
	Impact_Content::parse_list( "Top\n-\n-   " )
);

/* --------------------------------------------- Impact_Content::is_countable --- */

// Every figure printed on the source artwork must animate.
foreach ( array( '165,000,000', '26,015', '30,400', '57,352,423', '5,631,400' ) as $figure ) {
	check( "{$figure} counts up", true, Impact_Content::is_countable( $figure ) );
}

check( 'a bare integer counts up', true, Impact_Content::is_countable( '400' ) );
check( 'a decimal counts up', true, Impact_Content::is_countable( '26.5' ) );
check( 'a grouped decimal counts up', true, Impact_Content::is_countable( '1,234.56' ) );
check( 'surrounding spaces are tolerated', true, Impact_Content::is_countable( '  30,400 ' ) );

check( 'an approximation is left static', false, Impact_Content::is_countable( '~5' ) );
check( 'a range is left static', false, Impact_Content::is_countable( '10-20' ) );
check( 'a figure with a suffix is left static', false, Impact_Content::is_countable( '30,400+' ) );
check( 'a percentage is left static', false, Impact_Content::is_countable( '45%' ) );
check( 'text is left static', false, Impact_Content::is_countable( 'Varies' ) );
check( 'an empty value is left static', false, Impact_Content::is_countable( '' ) );
check( 'a non-string is left static', false, Impact_Content::is_countable( array() ) );
check( 'mis-grouped digits are left static', false, Impact_Content::is_countable( '1,23,456' ) );

/* -------------------------------------------- Impact_Content::format_number --- */

check( 'plain numbering starts at one', '1', Impact_Content::format_number( 0, 'plain', 1 ) );
check( 'plain numbering follows position', '6', Impact_Content::format_number( 5, 'plain', 1 ) );
check( 'padded numbering pads to two digits', '01', Impact_Content::format_number( 0, 'pad', 1 ) );
check( 'padded numbering stops padding past nine', '10', Impact_Content::format_number( 9, 'pad', 1 ) );
check( 'numbering can start elsewhere', '7', Impact_Content::format_number( 0, 'plain', 7 ) );
check( 'numbering can be switched off', '', Impact_Content::format_number( 0, 'none', 1 ) );


/* --------------------------------------- Impact_Content::icon_sizes_attr --- */

/*
 * The icon is requested at its full size so the width control is never capped
 * by the intrinsic width of a scaled-down file. That would leave the browser
 * free to pull a 4000px original for a 150px slot, so the display width is
 * handed over as an explicit sizes hint.
 */
check( 'a pixel width becomes a sizes hint', '150px', Impact_Content::icon_sizes_attr( array( 'size' => 150, 'unit' => 'px' ) ) );
check( 'a numeric string works too', '220px', Impact_Content::icon_sizes_attr( array( 'size' => '220', 'unit' => 'px' ) ) );
check( 'a fractional width is kept', '150.5px', Impact_Content::icon_sizes_attr( array( 'size' => 150.5, 'unit' => 'px' ) ) );

// A percentage is of the card, whose width is not known here. Saying nothing
// lets WordPress fall back to its own default rather than assert a wrong one.
check( 'a percentage yields no hint', '', Impact_Content::icon_sizes_attr( array( 'size' => 60, 'unit' => '%' ) ) );
check( 'a missing unit yields no hint', '', Impact_Content::icon_sizes_attr( array( 'size' => 150 ) ) );
check( 'a missing size yields no hint', '', Impact_Content::icon_sizes_attr( array( 'unit' => 'px' ) ) );
check( 'an empty size yields no hint', '', Impact_Content::icon_sizes_attr( array( 'size' => '', 'unit' => 'px' ) ) );
check( 'a zero width yields no hint', '', Impact_Content::icon_sizes_attr( array( 'size' => 0, 'unit' => 'px' ) ) );
check( 'a negative width yields no hint', '', Impact_Content::icon_sizes_attr( array( 'size' => -10, 'unit' => 'px' ) ) );
check( 'a non-numeric size yields no hint', '', Impact_Content::icon_sizes_attr( array( 'size' => 'big', 'unit' => 'px' ) ) );
check( 'an empty array yields no hint', '', Impact_Content::icon_sizes_attr( array() ) );
check( 'a non-array yields no hint', '', Impact_Content::icon_sizes_attr( null ) );

/* --------------------------------------------------- Motion_Presets --- */

$presets = Motion_Presets::all();

check( 'nine presets including none', 9, count( $presets ) );
check( 'none is present', true, isset( $presets['none'] ) );

foreach ( array( 'words-up', 'words-fade', 'chars-cascade', 'chars-flip', 'lines-mask', 'blur-in', 'scale-pop', 'slide-left' ) as $value ) {
	check( "preset {$value} exists", true, isset( $presets[ $value ] ) );
}

foreach ( $presets as $value => $preset ) {
	check( "preset {$value} has a label", true, isset( $preset['label'] ) && '' !== $preset['label'] );
	check(
		"preset {$value} has a valid split mode",
		true,
		in_array(
			isset( $preset['split'] ) ? $preset['split'] : null,
			array( Motion_Presets::SPLIT_NONE, Motion_Presets::SPLIT_WORDS, Motion_Presets::SPLIT_CHARS, Motion_Presets::SPLIT_LINES ),
			true
		)
	);
}

check( 'options() maps every value to its label', array_keys( $presets ), array_keys( Motion_Presets::options() ) );
check( 'words-up splits by word', Motion_Presets::SPLIT_WORDS, Motion_Presets::split_mode( 'words-up' ) );
check( 'chars-flip splits by character', Motion_Presets::SPLIT_CHARS, Motion_Presets::split_mode( 'chars-flip' ) );
check( 'lines-mask splits by line', Motion_Presets::SPLIT_LINES, Motion_Presets::split_mode( 'lines-mask' ) );
check( 'blur-in does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( 'blur-in' ) );

// An unknown value must degrade to "do nothing", never to a split mode.
check( 'an unknown preset does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( 'nonsense' ) );
check( 'null does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( null ) );

check( 'a known preset is valid', true, Motion_Presets::is_valid( 'scale-pop' ) );
check( 'an unknown preset is not valid', false, Motion_Presets::is_valid( 'nonsense' ) );
check( 'an array is not a valid preset', false, Motion_Presets::is_valid( array() ) );
check( 'null is not a valid preset', false, Motion_Presets::is_valid( null ) );

$easings = Motion_Presets::easings();

check( 'five easings', 5, count( $easings ) );
check( 'the default easing exists', true, isset( $easings['out-expo'] ) );

/* -------------------------------------------------- Motion_Controls --- */

check(
	'only the two plain-text widgets are supported',
	array( 'heading', 'text-editor' ),
	Motion_Controls::supported_widgets()
);

check( 'heading is supported', true, Motion_Controls::is_supported( 'heading' ) );
check( 'text-editor is supported', true, Motion_Controls::is_supported( 'text-editor' ) );
check( 'a button is not supported', false, Motion_Controls::is_supported( 'button' ) );
check( 'an icon box is not supported', false, Motion_Controls::is_supported( 'icon-box' ) );
check( "this plugin's own accordion is not supported", false, Motion_Controls::is_supported( 'nacc-numbered-accordion' ) );
check( 'an empty name is not supported', false, Motion_Controls::is_supported( '' ) );
check( 'null is not supported', false, Motion_Controls::is_supported( null ) );

// The filter is the documented extension point. A site adding a widget must
// work, and a site returning nonsense must not produce a PHP warning.
$GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] = function ( $list ) {
	$list[] = 'button';
	return $list;
};

check( 'the filter can add a widget', true, Motion_Controls::is_supported( 'button' ) );
check( 'the filter keeps the defaults', true, Motion_Controls::is_supported( 'heading' ) );

$GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] = function () {
	return array();
};

check( 'a filter can switch the section off entirely', false, Motion_Controls::is_supported( 'heading' ) );

$GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] = function () {
	return 'not an array';
};

check( 'a corrupt filter return supports nothing', false, Motion_Controls::is_supported( 'heading' ) );

unset( $GLOBALS['eruda_test_filters']['eruda_motion_supported_widgets'] );

check( 'the defaults come back once the filter is gone', true, Motion_Controls::is_supported( 'heading' ) );

// The 2.3.0 regression. The common controls stack is not a widget: Elementor
// registers it once, named 'common', and merges it into everything. A guard
// that sees that name must not conclude it is looking at a Heading -- and the
// hook it arrived on must be one that fires on the real widget.
foreach ( array( 'common', 'common-base', 'common-optimized' ) as $stack ) {
	check( "the {$stack} stack is not mistaken for a widget", false, Motion_Controls::is_supported( $stack ) );
}

check( 'a supported widget is injected once', true, Motion_Controls::should_inject( 'heading', false ) );
check( 'the same widget is never injected twice', false, Motion_Controls::should_inject( 'heading', true ) );
check( 'an unsupported widget is never injected', false, Motion_Controls::should_inject( 'button', false ) );
check( 'the common stack is never injected', false, Motion_Controls::should_inject( 'common', false ) );

$controls = Motion_Controls::control_definitions();

check(
	'the section carries exactly the frozen control ids',
	array( 'eanm_preset', 'eanm_trigger', 'eanm_duration', 'eanm_stagger', 'eanm_delay', 'eanm_ease', 'eanm_threshold', 'eanm_replay' ),
	array_keys( $controls )
);

check( 'the preset control offers every preset', array_keys( Motion_Presets::all() ), array_keys( $controls['eanm_preset']['options'] ) );
check( 'the preset control defaults to none', 'none', $controls['eanm_preset']['default'] );
check( 'the preset control writes the wrapper class', 'eanm-preset-', $controls['eanm_preset']['prefix_class'] );
check( 'the easing control offers every easing', array_keys( Motion_Presets::easings() ), array_keys( $controls['eanm_ease']['options'] ) );

// Everything but the preset dropdown stays hidden until a preset is chosen,
// so the section reads as a single control when it is not in use.
foreach ( $controls as $id => $definition ) {
	if ( 'eanm_preset' === $id ) {
		check( 'the preset control is never conditional', false, isset( $definition['condition'] ) );
		continue;
	}

	check(
		"{$id} is hidden while the preset is none",
		'none',
		isset( $definition['condition']['eanm_preset!'] ) ? $definition['condition']['eanm_preset!'] : null
	);
}

// Neither of these means anything for an animation that fires on load.
foreach ( array( 'eanm_threshold', 'eanm_replay' ) as $id ) {
	check(
		"{$id} is hidden unless the trigger is scroll",
		'scroll',
		isset( $controls[ $id ]['condition']['eanm_trigger'] ) ? $controls[ $id ]['condition']['eanm_trigger'] : null
	);
}

// Every value has to reach the DOM somehow: a class, or a custom property.
// A control with neither is a control that does nothing.
foreach ( $controls as $id => $definition ) {
	check(
		"{$id} reaches the DOM",
		true,
		isset( $definition['prefix_class'] ) || isset( $definition['selectors'] )
	);
}

// The custom properties the stylesheet and the script read by name.
check(
	'duration writes --eanm-duration',
	array( '{{WRAPPER}}' => '--eanm-duration: {{SIZE}}ms;' ),
	$controls['eanm_duration']['selectors']
);
check(
	'stagger writes --eanm-stagger',
	array( '{{WRAPPER}}' => '--eanm-stagger: {{SIZE}}ms;' ),
	$controls['eanm_stagger']['selectors']
);
check(
	'delay writes --eanm-delay',
	array( '{{WRAPPER}}' => '--eanm-delay: {{SIZE}}ms;' ),
	$controls['eanm_delay']['selectors']
);
check(
	'threshold writes --eanm-threshold',
	array( '{{WRAPPER}}' => '--eanm-threshold: {{SIZE}};' ),
	$controls['eanm_threshold']['selectors']
);

check( 'duration defaults to 800ms', 800, $controls['eanm_duration']['default']['size'] );
check( 'stagger defaults to 60ms', 60, $controls['eanm_stagger']['default']['size'] );
check( 'delay defaults to none', 0, $controls['eanm_delay']['default']['size'] );
check( 'threshold defaults to 20 per cent', 20, $controls['eanm_threshold']['default']['size'] );
check( 'replay is off by default', '', $controls['eanm_replay']['default'] );

/* ------------------------------------------------- Toolkit registry --- */

check( 'the motion module is registered', true, in_array( 'motion', Toolkit::instance()->ids(), true ) );
check( 'four modules ship', 4, count( Toolkit::instance()->ids() ) );
check( 'motion is on by default', true, Toolkit::is_enabled( 'motion', array() ) );
check( 'motion can be switched off', false, Toolkit::is_enabled( 'motion', array( 'motion' => false ) ) );

/* ------------------------------------------------------------- report --- */


echo "\n";

if ( empty( $failed ) ) {
	printf( "OK — %d assertions passed\n\n", $passed );
	exit( 0 );
}

printf( "FAILED — %d passed, %d failed\n\n", $passed, count( $failed ) );

foreach ( $failed as $failure ) {
	echo '  ' . $failure . "\n\n";
}

exit( 1 );
