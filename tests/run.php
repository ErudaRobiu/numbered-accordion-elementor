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
use ErudaToolkit\Modules\Explainer\Explainer_Content;
use ErudaToolkit\Modules\Compare\Compare_Content;
use ErudaToolkit\Modules\Schematic\Schematic_Content;
use ErudaToolkit\Modules\Schematic\Schematic_Svg;
use ErudaToolkit\Modules\Motion\Motion_Presets;
use ErudaToolkit\Modules\Badge\Spin_Controls;
use ErudaToolkit\Modules\Motion\Motion_Controls;
use ErudaToolkit\Modules\SmoothScroll\SmoothScroll_Module;
use ErudaToolkit\Fields;
use ErudaToolkit\Modules\Transitions\Transitions_Fields;

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

check( 'twelve presets including none', 12, count( $presets ) );
check( 'none is present', true, isset( $presets['none'] ) );

foreach ( array( 'fade-in', 'words-up', 'words-fade', 'words-build', 'scroll-highlight', 'chars-cascade', 'chars-flip', 'lines-mask', 'blur-in', 'scale-pop', 'slide-left' ) as $value ) {
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
check( 'words-build splits by word', Motion_Presets::SPLIT_WORDS, Motion_Presets::split_mode( 'words-build' ) );
check( 'scroll-highlight splits by word', Motion_Presets::SPLIT_WORDS, Motion_Presets::split_mode( 'scroll-highlight' ) );

// A scrubbed preset takes its timing from the scrollbar, so the controls that
// would do nothing are hidden rather than left sitting there.
check( 'the scrubbed presets', array( 'scroll-highlight' ), Motion_Presets::scrubbed() );

foreach ( Motion_Presets::scrubbed() as $value ) {
	check( "the scrubbed preset {$value} is a real preset", true, Motion_Presets::is_valid( $value ) );
}
check( 'fade-in does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( 'fade-in' ) );

// Direction only means something for a preset that moves as one block.
check( 'the directional presets', array( 'fade-in', 'words-fade' ), Motion_Presets::directional() );
check( 'five directions', 5, count( Motion_Presets::directions() ) );
check( 'the default direction exists', true, isset( Motion_Presets::directions()['up'] ) );
check( 'a direction for no movement exists', true, isset( Motion_Presets::directions()['none'] ) );

foreach ( Motion_Presets::directional() as $value ) {
	check( "the directional preset {$value} is a real preset", true, Motion_Presets::is_valid( $value ) );
}

// An unknown value must degrade to "do nothing", never to a split mode.
check( 'an unknown preset does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( 'nonsense' ) );
check( 'null does not split', Motion_Presets::SPLIT_NONE, Motion_Presets::split_mode( null ) );

check( 'a known preset is valid', true, Motion_Presets::is_valid( 'scale-pop' ) );
check( 'an unknown preset is not valid', false, Motion_Presets::is_valid( 'nonsense' ) );
check( 'an array is not a valid preset', false, Motion_Presets::is_valid( array() ) );
check( 'null is not a valid preset', false, Motion_Presets::is_valid( null ) );

$easings = Motion_Presets::easings();

check( 'six easings', 6, count( $easings ) );
check( 'the default easing exists', true, isset( $easings['natural'] ) );
check( 'the earlier default is still offered', true, isset( $easings['out-expo'] ) );

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
	array( 'eanm_preset', 'eanm_trigger', 'eanm_replay_preview', 'eanm_dim', 'eanm_direction', 'eanm_distance', 'eanm_duration', 'eanm_stagger', 'eanm_delay', 'eanm_ease', 'eanm_threshold', 'eanm_replay' ),
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

	// The directional pair names the presets it serves outright, which is a
	// stricter condition than "anything but none".
	if ( isset( $definition['condition']['eanm_preset'] ) ) {
		check(
			"{$id} names the presets it applies to",
			true,
			is_array( $definition['condition']['eanm_preset'] ) && ! in_array( 'none', $definition['condition']['eanm_preset'], true )
		);
		continue;
	}

	$hidden_for = isset( $definition['condition']['eanm_preset!'] ) ? $definition['condition']['eanm_preset!'] : array();

	check( "{$id} is hidden while the preset is none", true, in_array( 'none', (array) $hidden_for, true ) );
	check( "{$id} is hidden for a scrubbed preset", true, in_array( 'scroll-highlight', (array) $hidden_for, true ) );
}

check( 'the dim control is shown only for scrubbed presets', Motion_Presets::scrubbed(), $controls['eanm_dim']['condition']['eanm_preset'] );
check( 'dim writes --eanm-dim as a fraction', array( '{{WRAPPER}}' => '--eanm-dim: calc({{SIZE}} / 100);' ), $controls['eanm_dim']['selectors'] );
check( 'dim defaults to 15 per cent', 15, $controls['eanm_dim']['default']['size'] );

// Neither of these means anything for an animation that fires on load.
foreach ( array( 'eanm_threshold', 'eanm_replay' ) as $id ) {
	check(
		"{$id} is hidden unless the trigger is scroll",
		'scroll',
		isset( $controls[ $id ]['condition']['eanm_trigger'] ) ? $controls[ $id ]['condition']['eanm_trigger'] : null
	);
}

// Every value has to reach the DOM somehow: a class, or a custom property.
// A control with neither is a control that does nothing -- except the Replay
// button, which stores no value at all and fires an editor event instead.
foreach ( $controls as $id => $definition ) {
	if ( 'button' === $definition['type'] ) {
		check( 'the replay button fires an editor event', 'eanm:replay', $definition['event'] );
		check( 'the replay button stores no value', false, isset( $definition['default'] ) );
		continue;
	}

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

// The reference recipe this is modelled on: 500ms, 60ms apart, power2.out.
check( 'duration defaults to 500ms', 500, $controls['eanm_duration']['default']['size'] );
check( 'easing defaults to power2.out', 'natural', $controls['eanm_ease']['default'] );
check( 'stagger defaults to 60ms', 60, $controls['eanm_stagger']['default']['size'] );
check( 'delay defaults to none', 0, $controls['eanm_delay']['default']['size'] );
check( 'threshold defaults to 20 per cent', 20, $controls['eanm_threshold']['default']['size'] );
check( 'replay is off by default', '', $controls['eanm_replay']['default'] );

check( 'direction offers every direction', array_keys( Motion_Presets::directions() ), array_keys( $controls['eanm_direction']['options'] ) );
check( 'direction writes the wrapper class', 'eanm-dir-', $controls['eanm_direction']['prefix_class'] );
check( 'direction defaults to coming from below', 'up', $controls['eanm_direction']['default'] );
check( 'travel writes --eanm-distance', array( '{{WRAPPER}}' => '--eanm-distance: {{SIZE}}px;' ), $controls['eanm_distance']['selectors'] );
check( 'travel defaults to 24px', 24, $controls['eanm_distance']['default']['size'] );

// Both are shown only for the presets that can use them, and travel is
// pointless once the direction is "no movement".
check( 'direction is shown only for the directional presets', Motion_Presets::directional(), $controls['eanm_direction']['condition']['eanm_preset'] );
check( 'travel is shown only for the directional presets', Motion_Presets::directional(), $controls['eanm_distance']['condition']['eanm_preset'] );
check( 'travel hides when nothing moves', 'none', $controls['eanm_distance']['condition']['eanm_direction!'] );

/* --------------------------------------------------- Spin_Controls --- */

check( 'the image widget can be turned', true, Spin_Controls::is_supported( 'image' ) );
check( 'so can a button', true, Spin_Controls::is_supported( 'button' ) );
check( 'a heading cannot', false, Spin_Controls::is_supported( 'heading' ) );
check( 'nor can a nonsense name', false, Spin_Controls::is_supported( '' ) );

$spin = Spin_Controls::control_definitions();

check( 'the switch that turns it on', true, isset( $spin['espin_on'] ) );

/*
 * Every value reaches the DOM through prefix_class or selectors, never through
 * markup. A control that did neither would be one the front end cannot see --
 * which is how a whole feature shipped doing nothing in 2.3.0.
 */
foreach ( $spin as $id => $definition ) {
	check(
		"{$id} reaches the page",
		true,
		isset( $definition['prefix_class'] ) || isset( $definition['selectors'] )
	);
}

// The class the stylesheet and the script both look for.
check( 'the prefix that makes the widget spin', 'espin-', $spin['espin_on']['prefix_class'] );
check( 'and the value it is given', 'yes', $spin['espin_on']['return_value'] );
check( 'off by default, so no widget starts turning on its own', '', $spin['espin_on']['default'] );

// Changing any of these changes which element is animated or how, so the
// widget has to be rebuilt rather than restyled.
foreach ( array( 'espin_on', 'espin_target', 'espin_direction', 'espin_hover' ) as $id ) {
	check( "{$id} re-renders rather than restyles", 'template', $spin[ $id ]['render_type'] );
}

// Everything but the switch is hidden until it is on.
foreach ( $spin as $id => $definition ) {
	if ( 'espin_on' === $id ) {
		continue;
	}

	check( "{$id} is hidden until the switch is on", 'yes', $definition['condition']['espin_on'] );
}

check(
	'the stopping time is hidden when it is set to keep turning',
	'stop',
	$spin['espin_ramp']['condition']['espin_hover']
);

/* ------------------------------------------------- Toolkit registry --- */

check( 'the motion module is registered', true, in_array( 'motion', Toolkit::instance()->ids(), true ) );
check( 'the smooth scroll module is registered', true, in_array( 'smoothscroll', Toolkit::instance()->ids(), true ) );
check( 'the scroll story module is registered', true, in_array( 'story', Toolkit::instance()->ids(), true ) );
check( 'the scroll rail module is registered', true, in_array( 'rail', Toolkit::instance()->ids(), true ) );
check( 'the spin module is registered', true, in_array( 'badge', Toolkit::instance()->ids(), true ) );
check( 'the mega header module is registered', true, in_array( 'header', Toolkit::instance()->ids(), true ) );
check( 'the split explainer module is registered', true, in_array( 'explainer', Toolkit::instance()->ids(), true ) );
check( 'the image compare module is registered', true, in_array( 'compare', Toolkit::instance()->ids(), true ) );
check( 'the flow schematic module is registered', true, in_array( 'schematic', Toolkit::instance()->ids(), true ) );
check( 'the data table module is registered', true, in_array( 'table', Toolkit::instance()->ids(), true ) );
check( 'the process steps module is registered', true, in_array( 'steps', Toolkit::instance()->ids(), true ) );
check( 'the page transitions module is registered', true, in_array( 'transitions', Toolkit::instance()->ids(), true ) );
check( 'fifteen modules ship', 15, count( Toolkit::instance()->ids() ) );
check( 'motion is on by default', true, Toolkit::is_enabled( 'motion', array() ) );
check( 'motion can be switched off', false, Toolkit::is_enabled( 'motion', array( 'motion' => false ) ) );

/* ----------------------------------------------------- auto update --- */

// A missing key means yes, which is what turns it on for sites installed
// before the setting existed.
check( 'auto update is on by default', true, Toolkit::auto_update_enabled( array() ) );
check( 'a new install inherits it', true, Toolkit::auto_update_enabled( array( 'accordion' => true ) ) );
check( 'it can be switched off', false, Toolkit::auto_update_enabled( array( Toolkit::AUTO_UPDATE => false ) ) );
check( 'stored "0" is off', false, Toolkit::auto_update_enabled( array( Toolkit::AUTO_UPDATE => '0' ) ) );
check( 'stored "1" is on', true, Toolkit::auto_update_enabled( array( Toolkit::AUTO_UPDATE => '1' ) ) );
check( 'a corrupt option falls back to on', true, Toolkit::auto_update_enabled( 'not an array' ) );

// It lives in the module option but must never be mistaken for a module.
check( 'the key is reserved', '_auto_update', Toolkit::AUTO_UPDATE );
check( 'it is not a module id', false, in_array( Toolkit::AUTO_UPDATE, Toolkit::instance()->ids(), true ) );

foreach ( Toolkit::instance()->ids() as $id ) {
	check( "module id {$id} cannot collide with the reserved key", true, 0 !== strpos( $id, '_' ) );
}

/* --------------------------------------------------- panel category --- */

// Every widget belongs in the plugin's own section of the panel, not
// scattered through Elementor's General.
foreach ( array(
	'modules/accordion/widgets/class-numbered-accordion-widget.php',
	'modules/impact/widgets/class-impact-grid-widget.php',
	'modules/story/widgets/class-scroll-story-widget.php',
	'modules/rail/widgets/class-scroll-rail-widget.php',
) as $relative ) {
	$source = (string) file_get_contents( dirname( __DIR__ ) . '/' . $relative );

	check( "{$relative} uses the plugin's panel category", true, false !== strpos( $source, 'Panel_Category::SLUG' ) );
	check( "{$relative} is no longer in General", false, false !== strpos( $source, "array( 'general' )" ) );
}

check( 'the category slug', 'eruda-toolkit', \ErudaToolkit\Panel_Category::SLUG );

/* ------------------------------------------- module / widget wiring --- */

/*
 * Every widget class a module registers must actually exist.
 *
 * Both 2.3.0 and 2.6.0 shipped a feature that was simply absent from the
 * Elementor panel, because register_widgets() is guarded by class_exists() and
 * a name that matches nothing fails silently. The widget files cannot be
 * loaded here -- they extend Elementor's Widget_Base -- so this reads the
 * names out of the source instead, which is enough to catch a typo or a
 * rename.
 */
$module_files = array(
	'modules/accordion/class-accordion-module.php',
	'modules/impact/class-impact-module.php',
	'modules/story/class-story-module.php',
	'modules/rail/class-rail-module.php',
	'modules/header/class-header-module.php',
);

foreach ( $module_files as $relative ) {
	$source = (string) file_get_contents( dirname( __DIR__ ) . '/' . $relative );

	// The fully-qualified names this module expects to instantiate.
	preg_match_all( '/class_exists\(\s*\x27(\\\\ErudaToolkit\\\\Modules\\\\[A-Za-z_\\\\]+)\x27/', $source, $matches );

	$wanted = isset( $matches[1] ) ? $matches[1] : array();

	check( "{$relative} names a widget class", true, count( $wanted ) > 0 );

	foreach ( $wanted as $fqn ) {
		$short = substr( $fqn, strrpos( $fqn, '\\' ) + 1 );

		// It must also be the one new'd up a line later.
		check(
			"{$relative} instantiates {$short}",
			true,
			false !== strpos( $source, 'new Widgets\\' . $short . '(' )
		);

		// And a file in that module must declare it.
		$module_dir = dirname( __DIR__ ) . '/' . dirname( $relative ) . '/widgets';
		$declared   = false;

		foreach ( (array) glob( $module_dir . '/*.php' ) as $widget_file ) {
			if ( preg_match( '/class\s+' . preg_quote( $short, '/' ) . '\s+extends/', (string) file_get_contents( $widget_file ) ) ) {
				$declared = true;
				break;
			}
		}

		check( "{$short} is declared by a file in {$module_dir}", true, $declared );
	}
}

/* ------------------------------------------------ Mega_Header_Widget --- */

/*
 * The panel-links textarea is the only place in the header where someone's
 * typing becomes markup, so it is the only place a stray character can empty
 * a menu. Elementor has no nested repeater, which is why this is a textarea
 * at all -- see the note on the control.
 */
require_once __DIR__ . '/stubs/elementor.php';
require_once dirname( __DIR__ ) . '/modules/header/widgets/class-mega-header-widget.php';

$header = new \ErudaToolkit\Modules\Header\Widgets\Mega_Header_Widget();
// No setAccessible(): it has done nothing since PHP 8.1 and is deprecated as
// of 8.5, which turns a clean run into a wall of notices.
$parse = new ReflectionMethod( $header, 'parse_links' );

$links = function ( $raw ) use ( $parse, $header ) {
	return $parse->invoke( $header, $raw );
};

check( 'no text is no links', array(), $links( '' ) );
check( 'whitespace is no links', array(), $links( "  \n \t \n " ) );
check( 'a non-string is no links', array(), $links( null ) );

check(
	'label, url and note',
	array( array( 'label' => 'Feasibility', 'url' => '/f', 'note' => 'What it costs' ) ),
	$links( 'Feasibility | /f | What it costs' )
);

// A label on its own is a valid row: a heading inside a list of links is a
// reasonable thing to want, and dropping it would silently eat a line.
check(
	'a label alone still counts',
	array( array( 'label' => 'Services', 'url' => '', 'note' => '' ) ),
	$links( 'Services' )
);

check(
	'blank lines between rows are skipped',
	2,
	count( $links( "One | /one\n\n\nTwo | /two" ) )
);

// Windows and old Mac line endings both reach a textarea through a browser.
check( 'CRLF splits', 2, count( $links( "One | /one\r\nTwo | /two" ) ) );
check( 'CR splits', 2, count( $links( "One | /one\rTwo | /two" ) ) );

// A row with no label is not a row, however many pipes it has.
check( 'a leading pipe drops the row', array(), $links( ' | /nowhere | orphaned' ) );

// Trailing pipes are what you get from deleting a description but not the
// separator, and they must not become an empty note in the markup.
check( 'a trailing pipe leaves an empty note', '', $links( 'One | /one |' )[0]['note'] );

// Extra pipes past the third field are ignored rather than shifting the row.
check( 'a fourth field is ignored', 'note', $links( 'One | /one | note | extra' )[0]['note'] );

check( 'surrounding spaces are trimmed', 'One', $links( '   One   |   /one   ' )[0]['label'] );

/*
 * Every slider offers its units, and writes whichever one was picked.
 *
 * A control with several `size_units` but a selector that hardcodes `px` looks
 * like it works -- the unit buttons appear, you click one, and nothing happens,
 * because the value still reaches the page as pixels. The two have to be kept
 * in step, and this is the check that keeps them there.
 *
 * Four controls are deliberately single-unit: their value is also read in PHP
 * into a data attribute that the script parses as a bare number, so "0.3"
 * meaning seconds would be treated as 0.3 milliseconds.
 */
$header_src = (string) file_get_contents( dirname( __DIR__ ) . '/modules/header/widgets/class-mega-header-widget.php' );
$frozen     = array( 'stick_at', 'grab', 'intent', 'scramble_step' );

preg_match_all(
	"/\t\t\t'([a-z_]+)',\n\t\t\tarray\(\n(.*?)\n\t\t\t\)\n\t\t\);/s",
	$header_src,
	$controls,
	PREG_SET_ORDER
);

check( 'the header widget has controls to read', true, count( $controls ) > 30 );

$multi = 0;

foreach ( $controls as $control ) {
	list( , $id, $body ) = $control;

	if ( false === strpos( $body, 'size_units' ) || false === strpos( $body, 'selectors' ) ) {
		continue;
	}

	preg_match( "/'size_units'\s*=> array\(([^)]*)\)/", $body, $um );
	$units = isset( $um[1] ) ? preg_split( '/\s*,\s*/', trim( $um[1] ), -1, PREG_SPLIT_NO_EMPTY ) : array();

	if ( count( $units ) < 2 ) {
		continue;
	}

	$multi++;

	// The selector must not pin a unit of its own.
	$pinned = (bool) preg_match( '/\{\{SIZE\}\}(px|%|ms|em|rem|vw|vh)/', $body );

	check( "{$id} writes the unit that was picked", false, $pinned );

	// And every unit offered needs a range, or the slider falls back to 0-100
	// and a pixel width becomes unreachable.
	preg_match( "/'range'\s*=> array\((.*?)\),\n/s", $body, $rm );
	$ranged = isset( $rm[1] ) ? $rm[1] : '';

	foreach ( $units as $unit ) {
		$bare = trim( $unit, "' " );

		check(
			"{$id} gives {$bare} a range of its own",
			true,
			false !== strpos( $ranged, "'{$bare}'" )
		);
	}
}

check( 'most of the header\'s sliders take more than one unit', true, $multi >= 30 );

foreach ( $frozen as $id ) {
	$found = '';

	foreach ( $controls as $control ) {
		if ( $control[1] === $id ) {
			$found = $control[2];
		}
	}

	check( "{$id} is still declared", true, '' !== $found );

	preg_match( "/'size_units'\s*=> array\(([^)]*)\)/", $found, $um );
	$units = isset( $um[1] ) ? preg_split( '/\s*,\s*/', trim( $um[1] ), -1, PREG_SPLIT_NO_EMPTY ) : array();

	// Single-unit on purpose. See the note above.
	check( "{$id} stays on one unit, because the script reads it as a number", 1, count( $units ) );
}

/* ------------------------------------------------- SmoothScroll_Module --- */

// Needs no Elementor: a classic theme gets the same benefit.
check( 'smooth scrolling is always available', true, SmoothScroll_Module::is_available() );
check( 'smooth scrolling reports no unmet requirements', array(), SmoothScroll_Module::requirement_messages() );
check( 'smooth scrolling is on by default', true, Toolkit::is_enabled( 'smoothscroll', array() ) );

check( 'the default duration', 1.1, SmoothScroll_Module::options()['duration'] );

// The filter is the tuning surface, and a bad value from it must not reach
// the library: zero would divide by nothing inside Lenis.
$cases = array(
	'0 falls back'          => array( 0, 1.1 ),
	'negative falls back'   => array( -3, 1.1 ),
	'absurd falls back'     => array( 99, 1.1 ),
	'a sane value is kept'  => array( 1.6, 1.6 ),
	'a string is coerced'   => array( '0.9', 0.9 ),
);

foreach ( $cases as $name => $case ) {
	$GLOBALS['eruda_test_filters']['eruda_smooth_scroll_options'] = function () use ( $case ) {
		return array( 'duration' => $case[0] );
	};

	check( "duration: {$name}", $case[1], SmoothScroll_Module::options()['duration'] );
}

$GLOBALS['eruda_test_filters']['eruda_smooth_scroll_options'] = function () {
	return 'not an array';
};

check( 'a corrupt filter return falls back', 1.1, SmoothScroll_Module::options()['duration'] );

unset( $GLOBALS['eruda_test_filters']['eruda_smooth_scroll_options'] );

/* ------------------------------------------------- split explainer --- */

/* ------------------------------------- Explainer_Content::has_step --- */

// The correction that started this widget: the numbers are a control, and an
// empty one takes the gap with it rather than leaving a hole behind.
check( 'an empty number is no number', false, Explainer_Content::has_step( '' ) );
check( 'whitespace is no number either', false, Explainer_Content::has_step( "  \t " ) );
check( 'a number is a number', true, Explainer_Content::has_step( '01' ) );
check( 'so is a word', true, Explainer_Content::has_step( 'Step one' ) );
check( 'null is no number', false, Explainer_Content::has_step( null ) );
check( 'an array is no number', false, Explainer_Content::has_step( array( '01' ) ) );
check( 'the number is trimmed', '01', Explainer_Content::step_text( '  01 ' ) );
check( 'zero counts', true, Explainer_Content::has_step( '0' ) );

check(
	'an empty number closes its own gap',
	'eexp-step eexp-step--bare',
	Explainer_Content::step_classes( '' )
);
check(
	'a number keeps the gap',
	'eexp-step',
	Explainer_Content::step_classes( '02' )
);

/* ------------------------------ Explainer_Content::text and ::rows --- */

check( 'a setting is trimmed', 'Flow', Explainer_Content::text( array( 'k' => '  Flow ' ), 'k' ) );
check( 'a missing setting is empty', '', Explainer_Content::text( array(), 'k' ) );
check( 'a non-array is empty', '', Explainer_Content::text( 'nope', 'k' ) );
check( 'an array value is empty', '', Explainer_Content::text( array( 'k' => array( 'x' ) ), 'k' ) );
check( 'a number comes back as a string', '5', Explainer_Content::text( array( 'k' => 5 ), 'k' ) );

check( 'rows come back whole', array( array( 'a' => 1 ) ), Explainer_Content::rows( array( 'r' => array( array( 'a' => 1 ) ) ), 'r' ) );
check( 'a missing repeater is no rows', array(), Explainer_Content::rows( array(), 'r' ) );
check( 'a scalar repeater is no rows', array(), Explainer_Content::rows( array( 'r' => 'x' ), 'r' ) );

/* ------------------------------ Explainer_Content::title_classes --- */

check( 'solid is the default and adds nothing', 'eexp-heading eexp-panel__title', Explainer_Content::title_classes( 'solid' ) );
check( 'an unsaved widget is solid too', 'eexp-heading eexp-panel__title', Explainer_Content::title_classes( '' ) );
check( 'gradient', 'eexp-heading eexp-panel__title eexp-panel__title--gradient', Explainer_Content::title_classes( 'gradient' ) );
check(
	'reveal also takes the class the script watches for',
	'eexp-heading eexp-panel__title eexp-panel__title--reveal eexp-anim',
	Explainer_Content::title_classes( 'reveal' )
);

/* ------------------------------- Explainer_Content::heading_words --- */

$words = Explainer_Content::heading_words( 'How Lepido™ works' );

check( 'one span per word', 3, count( $words ) );
check( 'the first word', '<span>How</span>', $words[0] );
check( 'the trademark keeps its own element', '<span>Lepido<i class="eexp-tm">™</i></span>', $words[1] );
check( 'a registered sign too', array( '<span>Norrel<i class="eexp-tm">®</i></span>' ), Explainer_Content::heading_words( 'Norrel®' ) );
check( 'an empty heading makes no spans', array(), Explainer_Content::heading_words( '   ' ) );
check( 'runs of whitespace make no empty spans', 2, count( Explainer_Content::heading_words( "How    works" ) ) );
check(
	'markup in a heading is escaped, not run',
	array( '<span>&lt;script&gt;alert(1)&lt;/script&gt;</span>' ),
	Explainer_Content::heading_words( '<script>alert(1)</script>' )
);

/* ------------------------------- Explainer_Content::pill_classes --- */

check( 'the lit dot is the default and carries no modifier', 'eexp-loads', Explainer_Content::pill_classes( 'dot' ) );
check( 'filled', 'eexp-loads eexp-loads--solid', Explainer_Content::pill_classes( 'solid' ) );
check( 'glass', 'eexp-loads eexp-loads--glass', Explainer_Content::pill_classes( 'glass' ) );
check( 'plain', 'eexp-loads eexp-loads--plain', Explainer_Content::pill_classes( 'plain' ) );
check( 'an unsaved widget falls back to the default', 'eexp-loads', Explainer_Content::pill_classes( '' ) );
check( 'so does a style that no longer exists', 'eexp-loads', Explainer_Content::pill_classes( 'neon' ) );
check( 'and a non-string', 'eexp-loads', Explainer_Content::pill_classes( array( 'solid' ) ) );

/* ----------------------------------- Explainer_Content::mote_style --- */

check( 'no motes asked for, none drawn', 0, Explainer_Content::mote_count( 0 ) );
check( 'the default five', 5, Explainer_Content::mote_count( 5 ) );
check( 'a negative count is none', 0, Explainer_Content::mote_count( -3 ) );
check( 'the count is capped', 7, Explainer_Content::mote_count( 99 ) );
check( 'a non-number is none', 0, Explainer_Content::mote_count( array() ) );

check(
	'the first mote carries its whole character, not just a position',
	'top:26%;--eexp-mote-size:8px;--eexp-mote-dur:7.5s;--eexp-mote-peak:0.92;--eexp-mote-wander:14px;animation-delay:0s',
	Explainer_Content::mote_style( 0, 5 )
);

// Depth: no two neighbours share a size, a speed or a brightness, or the row
// reads as a conveyor belt rather than as air.
$sizes  = array();
$speeds = array();
$peaks  = array();

for ( $i = 0; $i < 7; $i++ ) {
	preg_match( '/--eexp-mote-size:([0-9.]+)px/', Explainer_Content::mote_style( $i, 7 ), $m );
	$sizes[] = $m[1];
	preg_match( '/--eexp-mote-dur:([0-9.]+)s/', Explainer_Content::mote_style( $i, 7 ), $m );
	$speeds[] = $m[1];
	preg_match( '/--eexp-mote-peak:([0-9.]+)/', Explainer_Content::mote_style( $i, 7 ), $m );
	$peaks[] = $m[1];
}

check( 'seven motes, seven sizes', 7, count( array_unique( $sizes ) ) );
check( 'seven speeds', 7, count( array_unique( $speeds ) ) );
check( 'seven brightnesses', 7, count( array_unique( $peaks ) ) );

// The delays are negative, which starts each mote partway across rather than
// leaving the plate empty until the first one arrives.
check(
	'the first starts at the edge',
	true,
	(bool) preg_match( '/animation-delay:0s$/', Explainer_Content::mote_style( 0, 5 ) )
);
check(
	'the rest start already in flight',
	true,
	(bool) preg_match( '/animation-delay:-[0-9.]+s$/', Explainer_Content::mote_style( 2, 5 ) )
);

// Deterministic: the same page must look the same on every load.
check(
	'the same index gives the same style twice',
	Explainer_Content::mote_style( 3, 5 ),
	Explainer_Content::mote_style( 3, 5 )
);

$styles = array();

for ( $i = 0; $i < 7; $i++ ) {
	$styles[] = Explainer_Content::mote_style( $i, 7 );
}

check( 'seven motes, seven different styles', 7, count( array_unique( $styles ) ) );

check(
	'an index past the end wraps its layer',
	'top:26%',
	substr( Explainer_Content::mote_style( 7, 7 ), 0, 7 )
);

/* ---------------------------- Explainer_Content::panel_sizes_attr --- */

check(
	'the sizes hint follows the slab',
	'(max-width: 767px) 92vw, (max-width: 1200px) 88vw, 44vw',
	Explainer_Content::panel_sizes_attr()
);

/* ------------------------------- Compare_Content::clamp_position --- */

check( 'the middle by default', 50.0, Compare_Content::clamp_position( null ) );
check( 'a slider array is read', 30.0, Compare_Content::clamp_position( array( 'size' => 30 ) ) );
check( 'an empty slider array falls back', 50.0, Compare_Content::clamp_position( array() ) );
check( 'a number passes through', 12.5, Compare_Content::clamp_position( 12.5 ) );
check( 'a numeric string passes through', 80.0, Compare_Content::clamp_position( '80' ) );
check( 'below zero is zero', 0.0, Compare_Content::clamp_position( -20 ) );
check( 'above a hundred is a hundred', 100.0, Compare_Content::clamp_position( 240 ) );
check( 'nonsense falls back to the middle', 50.0, Compare_Content::clamp_position( 'halfway' ) );

/* ----------------------------------- Compare_Content::clip_inset --- */

// The second picture is cut back to the divider: from the left when the
// divider runs up and down, from the top when it runs across.
check( 'side to side', 'inset(0 0 0 50%)', Compare_Content::clip_inset( 50, 'horizontal' ) );
check( 'up and down', 'inset(50% 0 0 0)', Compare_Content::clip_inset( 50, 'vertical' ) );
check( 'a fraction keeps its decimals', 'inset(0 0 0 33.33%)', Compare_Content::clip_inset( 33.333, 'horizontal' ) );
check( 'a whole number loses its zeros', 'inset(0 0 0 40%)', Compare_Content::clip_inset( 40.0, 'horizontal' ) );
check( 'fully closed', 'inset(0 0 0 0%)', Compare_Content::clip_inset( 0, 'horizontal' ) );
check( 'fully open', 'inset(0 0 0 100%)', Compare_Content::clip_inset( 100, 'horizontal' ) );

check(
	'the frame carries both the position and the clip',
	'--ecmp-pos:25%;--ecmp-n:25;--ecmp-clip:inset(0 0 0 25%)',
	Compare_Content::frame_style( 25, 'horizontal' )
);

/* --------------------------------- Compare_Content::root_classes --- */

check( 'plain', 'ecmp', Compare_Content::root_classes( 'horizontal', 'drag' ) );
check( 'vertical', 'ecmp ecmp--vertical', Compare_Content::root_classes( 'vertical', 'drag' ) );
check( 'hover', 'ecmp ecmp--hover', Compare_Content::root_classes( 'horizontal', 'hover' ) );
check( 'both', 'ecmp ecmp--vertical ecmp--hover', Compare_Content::root_classes( 'vertical', 'hover' ) );
check( 'an unknown direction is the usual one', 'ecmp', Compare_Content::root_classes( 'sideways', 'drag' ) );

/* -------------------------------- Compare_Content::slider_label --- */

check(
	'the control says what it swaps between',
	'Reveal Before or After',
	Compare_Content::slider_label( 'Before', 'After' )
);
check(
	'with a label missing it still says something',
	'Compare the two pictures',
	Compare_Content::slider_label( 'Before', '' )
);

/* ------------------------------------ Schematic_Content::columns --- */

/*
 * The column model is the whole diagram. Everything else -- the return path,
 * the monitoring bar, the arrow out to the targets -- is placed by naming
 * columns, so if this is wrong the drawing is wrong in a way no amount of CSS
 * can rescue.
 */

check(
	'two stages between a source and its targets take seven columns',
	7,
	Schematic_Content::total_columns( 2, true, true )
);
check(
	'without either end they take three',
	3,
	Schematic_Content::total_columns( 2, false, false )
);
check(
	'a source pushes the first stage to column three',
	3,
	Schematic_Content::stage_column( 1, true )
);
check(
	'and without one it starts at column one',
	1,
	Schematic_Content::stage_column( 1, false )
);
check(
	'stages sit two columns apart, with the connector between',
	5,
	Schematic_Content::stage_column( 2, true )
);
check(
	'the connector after a stage is the column following it',
	4,
	Schematic_Content::link_column( 1, true )
);
check(
	'the targets sit two past the last stage',
	7,
	Schematic_Content::outputs_column( 2, true )
);
check(
	'the track list names a floor for every node and lets the runs stretch',
	'minmax(110px, 0.85fr) 0.9fr minmax(150px, 1.05fr) 1.4fr minmax(150px, 1.05fr) 0.8fr minmax(130px, 0.9fr)',
	Schematic_Content::columns( 2, true, true )
);
check(
	'a single stage has no run between anything',
	'minmax(150px, 1.05fr)',
	Schematic_Content::columns( 1, false, false )
);
check(
	'more stages than the row can carry are dropped, not squeezed',
	Schematic_Content::MAX_STAGES,
	Schematic_Content::stage_count( array_fill( 0, 9, array() ) )
);

/* --------------------------------------- Schematic_Content::span --- */

check(
	'a band over two stages spans from the first to past the second',
	array( 3, 6 ),
	Schematic_Content::span( 1, 2, 2, true )
);
check(
	'the numbers typed in backwards still describe the same band',
	array( 3, 6 ),
	Schematic_Content::span( 2, 1, 2, true )
);
check(
	'a stage that is not there is pulled back to one that is',
	array( 3, 6 ),
	Schematic_Content::span( 1, 7, 2, true )
);
check(
	'a band over one stage still has width',
	array( 3, 4 ),
	Schematic_Content::span( 1, 1, 2, true )
);

/* ------------------------------ Schematic_Content::clamp_spread --- */

check( 'the run between stages has a default', 1.4, Schematic_Content::clamp_spread( null ) );
check( 'a slider value is read out of its array', 2.5, Schematic_Content::clamp_spread( array( 'size' => 2.5 ) ) );
check( 'a run too short to draw is opened up', 0.4, Schematic_Content::clamp_spread( 0.05 ) );
check( 'and one long enough to break the row is reined in', 4.0, Schematic_Content::clamp_spread( 99 ) );
check( 'nonsense lands on the default', 1.4, Schematic_Content::clamp_spread( 'wide' ) );

/* ----------------------------- Schematic_Content::clamp_percent --- */

check( 'the boundary defaults to the middle', 50.0, Schematic_Content::clamp_percent( 'x' ) );
check( 'and cannot be pushed off either edge', 100.0, Schematic_Content::clamp_percent( 140 ) );

/* --------------------------------------- Schematic_Content::number --- */

check( 'a width is written the way CSS reads it', '1.4', Schematic_Content::number( 1.4 ) );
check( 'with no trailing zeroes', '2', Schematic_Content::number( 2.0 ) );

/* ----------------------------------------- Schematic_Svg::sanitise --- */

/*
 * Printing a file into the page gives up the isolation an <img> provides, so
 * this is the thing replacing it. Every assertion here is a way that isolation
 * used to be doing the work.
 */

$dirty = '<?xml version="1.0"?><!-- a comment --><svg xmlns="http://www.w3.org/2000/svg" '
	. 'viewBox="0 0 10 10" width="10" height="10">'
	. '<script>alert(1)</script>'
	. '<foreignObject><body onload="alert(2)">hi</body></foreignObject>'
	. '<title>A drawing</title>'
	. '<path d="M0 0 L10 10" fill="#fff" stroke-width="2" font-family="Inter" onclick="alert(3)"/>'
	. '<a href="https://example.com/evil"><rect x="1" y="1" width="2" height="2"/></a>'
	. '<use href="#thing"/><use href="https://elsewhere/thing"/>'
	. '<rect style="fill:url(https://elsewhere/x)" x="0" y="0" width="1" height="1"/>'
	. '</svg>';

$clean = Schematic_Svg::sanitise( $dirty, 'efs__art' );

check( 'a script element does not survive', false, false !== strpos( $clean, '<script' ) );
check( 'nor does foreignObject, which can carry a whole document', false, false !== strpos( $clean, 'foreignObject' ) );
check( 'nor a handler attribute', false, false !== strpos( $clean, 'onclick' ) );
check( 'nor the one inside the element that was removed', false, false !== strpos( $clean, 'onload' ) );
check( 'nor a comment', false, false !== strpos( $clean, 'a comment' ) );
check( 'an element with no business drawing is dropped with its subtree', false, false !== strpos( $clean, '<a ' ) );
check( 'a reference inside the file is kept', true, false !== strpos( $clean, 'href="#thing"' ) );
check( 'a reference out of it is not', false, false !== strpos( $clean, 'elsewhere/thing' ) );
check( 'a style that fetches something is dropped', false, false !== strpos( $clean, 'url(' ) );

check( 'the drawing itself survives', true, false !== strpos( $clean, 'd="M0 0 L10 10"' ) );
check( 'so do its presentation attributes', true, false !== strpos( $clean, 'stroke-width="2"' ) );
check( 'and the typeface, which is the entire point of inlining', true, false !== strpos( $clean, 'font-family="Inter"' ) );
check( 'the title survives, because it is the accessible name', true, false !== strpos( $clean, '<title>A drawing</title>' ) );
check( 'the viewBox survives', true, false !== strpos( $clean, 'viewBox="0 0 10 10"' ) );
check( 'the class asked for is added', true, false !== strpos( $clean, 'efs__art' ) );

// width and height give an <img> its ratio; inlined they only fight the
// stylesheet for the size.
check( 'a fixed width is dropped', false, false !== strpos( $clean, 'width="10"' ) );
check( 'and a fixed height with it', false, false !== strpos( $clean, 'height="10"' ) );

check(
	'something that is not an SVG at all yields nothing',
	'',
	Schematic_Svg::sanitise( '<html><body>no</body></html>' )
);
check( 'and neither does a file that will not parse', '', Schematic_Svg::sanitise( '<svg><unclosed>' ) );
check( 'an empty string yields nothing', '', Schematic_Svg::sanitise( '' ) );
check( 'a path that is not a file yields nothing', '', Schematic_Svg::inline( '/no/such/file.svg' ) );

check( 'letter-spacing counts as a presentation attribute', true, Schematic_Svg::allowed_attribute( 'letter-spacing' ) );
check( 'text-anchor does too', true, Schematic_Svg::allowed_attribute( 'text-anchor' ) );
check( 'and formaction does not', false, Schematic_Svg::allowed_attribute( 'formaction' ) );

/* ----------------------------------------- Mega_Header_Widget markup --- */

/*
 * The shape of a panel, proven from the widget's own render().
 *
 * A panel is two sides: the list of links, and the picture with whatever the
 * panel says about itself underneath it. Which of the two it actually has
 * decides the layout, and the layout is a class the stylesheet keys off -- so
 * the class being right is the whole of the contract between the PHP and the
 * CSS, and it is not something reading either one on its own can confirm.
 */
foreach ( array( 'esc_html', 'esc_attr', 'esc_url' ) as $fn ) {
	if ( ! function_exists( $fn ) ) {
		eval( 'function ' . $fn . '( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, "UTF-8" ); }' ); // phpcs:ignore
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	/**
	 * @param string $text   Text.
	 * @param string $domain Domain.
	 * @return string
	 */
	function esc_attr__( $text, $domain = 'default' ) { // phpcs:ignore
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * The widget with a settings array pushed into it.
 *
 * render() is protected and reads its settings through Elementor; a subclass
 * is the whole of what it takes to call it without either.
 */
class Header_Render_Probe extends \ErudaToolkit\Modules\Header\Widgets\Mega_Header_Widget {

	/**
	 * @var array
	 */
	public $feed = array();

	/**
	 * @return array
	 */
	public function get_settings_for_display() {
		return $this->feed;
	}

	/**
	 * @param array $settings Settings to render with.
	 * @return string
	 */
	public function markup( $settings ) {
		$this->feed = $settings;

		ob_start();
		( new ReflectionMethod( $this, 'render' ) )->invoke( $this );

		return (string) ob_get_clean();
	}
}

/**
 * One menu item, with whatever panel parts are asked for.
 *
 * @param array $parts Panel parts to include.
 * @return string
 */
function header_panel_markup( $parts ) {
	$probe = new Header_Render_Probe();

	$item = array(
		'label'     => 'Systems',
		'has_panel' => 'yes',
	);

	if ( ! empty( $parts['links'] ) ) {
		$item['panel_links'] = "How it works | /how\nSpecifications | /spec";
	}

	if ( ! empty( $parts['image'] ) ) {
		$item['panel_image'] = array( 'url' => 'https://example.test/p.jpg' );
	}

	if ( ! empty( $parts['blurb'] ) ) {
		$item['panel_blurb'] = 'What it does.';
	}

	return $probe->markup( array( 'items' => array( $item ) ) );
}

$both  = header_panel_markup( array( 'links' => true, 'image' => true, 'blurb' => true ) );
$only  = header_panel_markup( array( 'links' => true ) );
$noimg = header_panel_markup( array( 'links' => true, 'blurb' => true ) );

check( 'a panel with both sides is not marked solo', false, (bool) strpos( $both, 'ehdr__panel-inner--solo' ) );
check( 'and not marked as a short dropdown either', false, (bool) strpos( $both, 'ehdr__item--drop' ) );

// The picture and the caption are one column, so they have to be one element.
check( 'the picture and the blurb are in the same side', 1, preg_match(
	'/<div class="ehdr__panel-aside">.*?<figure class="ehdr__figure">.*?<\/figure>.*?<p class="ehdr__blurb">.*?<\/p>.*?<\/div>/s',
	$both
) );

check( 'the picture comes before the words under it', true,
	strpos( $both, 'ehdr__figure' ) < strpos( $both, 'ehdr__blurb' ) );

check( 'a links-only panel is one column', true, (bool) strpos( $only, 'ehdr__panel-inner--solo' ) );
check( 'and its item is a short dropdown', true, (bool) strpos( $only, 'ehdr__item--drop' ) );
check( 'with no empty second side left behind', false, (bool) strpos( $only, 'ehdr__panel-aside' ) );

// A blurb with no picture is still a second side; it is only the drop-down
// form that needs the item to have nothing but links.
check( 'a blurb without a picture still makes two sides', false, (bool) strpos( $noimg, 'ehdr__panel-inner--solo' ) );
check( 'and that is not a short dropdown', false, (bool) strpos( $noimg, 'ehdr__item--drop' ) );

/*
 * The drawer's chevron, which is why the label above it can be a link.
 *
 * On a phone the trigger used to be a toggle both ways -- one tap opened the
 * panel, the next closed it -- so the page the word names could not be reached
 * from the menu at all. The button carries a second copy of the caret because
 * the one in the link belongs to the desktop, where it is part of the label
 * and takes the underline with it; exactly one of the two is ever displayed.
 */
check( 'an item with a panel gets a chevron button', 1, substr_count( $both, 'ehdr__toggle' ) );
check( 'it is a button rather than a link', 1, preg_match( '/<button class="ehdr__toggle" type="button"/', $both ) );
check( 'it starts closed', 1, preg_match( '/class="ehdr__toggle"[^>]*aria-expanded="false"/', $both ) );
check( 'it says which menu it opens', 1, preg_match( '/aria-label="Open the Systems menu"/', $both ) );
check( 'and it sits outside the link, not inside it', 1, preg_match( '/<\/a>\s*(?:<\?php.*?\?>\s*)?.*?<button class="ehdr__toggle"/s', $both ) );

// Two carets in the markup, one per breakpoint -- never two on one row.
check( 'the caret is in both the link and the button', 2, substr_count( $both, 'ehdr__caret' ) );

// An item with no panel has nothing to open, so it gets no chevron.
$plain = ( new Header_Render_Probe() )->markup( array(
	'items' => array( array( 'label' => 'News' ) ),
) );
check( 'an item without a panel gets no chevron', false, (bool) strpos( $plain, 'ehdr__toggle' ) );
check( 'and no caret either', false, (bool) strpos( $plain, 'ehdr__caret' ) );

// An eyebrow on its own used to be dropped: the emptiness test named the
// blurb and the picture and forgot it, so a panel carrying only a heading
// rendered a caret over nothing.
$eyebrow = ( new Header_Render_Probe() )->markup( array(
	'items' => array( array( 'label' => 'Systems', 'has_panel' => 'yes', 'panel_eyebrow' => 'What we do' ) ),
) );
check( 'a panel with only an eyebrow still opens', true, (bool) strpos( $eyebrow, 'ehdr__panel' ) );

/* ------------------------------------------------- Table_Content --- */

/*
 * How wide the table is, and how a row is fitted to it.
 *
 * Elementor never forgets a repeater field. A row saved while the table was
 * three columns wide still carries its third cell after the third heading has
 * been cleared, so "how many columns" and "which cells" have to be decided
 * from the headings rather than from whatever the rows happen to hold -- or
 * the ghost cell comes back as an extra column in the markup.
 */
require_once dirname( __DIR__ ) . '/modules/table/class-table-content.php';

use ErudaToolkit\Modules\Table\Table_Content;

$three = array( 'head_1' => 'Measured', 'head_2' => 'What it tells you', 'head_3' => 'Source of the value' );
$two   = array( 'head_1' => 'Measured', 'head_2' => 'What it tells you', 'head_3' => '' );

check( 'three headings is a three-column table', 3, Table_Content::column_count( $three ) );
check( 'clearing the last heading makes it two', 2, Table_Content::column_count( $two ) );
check( 'whitespace in a heading is not a heading', 2, Table_Content::column_count(
	array( 'head_1' => 'A', 'head_2' => 'B', 'head_3' => "  \n\t " )
) );

// A gap in the middle is somebody's unfinished work, not a two-column table.
// Closing it would shift their third column under the second heading.
check( 'an empty middle heading still leaves three columns', 3, Table_Content::column_count(
	array( 'head_1' => 'A', 'head_2' => '', 'head_3' => 'C' )
) );
check( 'and the empty one is kept in place', array( 'A', '', 'C' ), Table_Content::headings(
	array( 'head_1' => 'A', 'head_2' => '', 'head_3' => 'C' )
) );

// With the header row off there are no headings to count, so the widest row
// decides instead. A table is never narrower than one column.
check( 'with no headings the widest row sets the width', 3, Table_Content::column_count(
	array( 'rows' => array(
		array( 'cell_1' => 'one' ),
		array( 'cell_1' => 'a', 'cell_2' => 'b', 'cell_3' => 'c' ),
	) )
) );
check( 'an empty table is still one column wide', 1, Table_Content::column_count( array() ) );

// The ghost cell.
check( 'a row is cut to the table it is in', array( 'a', 'b' ), Table_Content::cells(
	array( 'cell_1' => 'a', 'cell_2' => 'b', 'cell_3' => 'left over' ), 2
) );
check( 'and padded out when the table is wider than the row', array( 'a', '', '' ), Table_Content::cells(
	array( 'cell_1' => 'a' ), 3
) );
check( 'a row asked for no width trims its own blanks', array( 'a' ), Table_Content::cells(
	array( 'cell_1' => 'a', 'cell_2' => '', 'cell_3' => '' )
) );
check( 'a count past the maximum is held at it', 3, count( Table_Content::cells( array(), 9 ) ) );

// A row added and then left alone should not draw a blank stripe.
check( 'an empty row is not a row', false, Table_Content::row_has_content( array( 'cell_1' => '', 'cell_2' => '  ' ) ) );
check( 'a row with anything in it is', true, Table_Content::row_has_content( array( 'cell_1' => '', 'cell_3' => 'x' ) ) );
check( 'and the empty ones are dropped before rendering', 1, count( Table_Content::visible_rows(
	array( 'rows' => array( array( 'cell_1' => 'keep' ), array( 'cell_1' => '', 'cell_2' => '' ) ) )
) ) );

// The label a folded cell carries. Out of range is empty rather than a notice.
check( 'a cell takes its label from its column', 'Source of the value', Table_Content::cell_label( array( 'A', 'B', 'Source of the value' ), 2 ) );
check( 'a column with no heading has no label', '', Table_Content::cell_label( array( 'A' ), 2 ) );
check( 'and neither does a heading that is not a string', '', Table_Content::cell_label( array( array() ), 0 ) );

// The template: typed wins, otherwise the shape follows the column count.
check( 'a typed column setting is used as written', '2fr 1fr', Table_Content::template( array( 'columns' => '2fr 1fr' ), 3 ) );
check( 'two columns get an even default', 'minmax(0, 1fr) minmax(0, 1fr)', Table_Content::template( array(), 2 ) );
check( 'three do not', true, false !== strpos( Table_Content::template( array(), 3 ), '0.8fr' ) );

/* --------------------------------------------- Data_Table_Widget markup --- */

/*
 * A real table, and the attributes that make it one.
 *
 * The layout is a grid, which is exactly the arrangement that tempts you to
 * build it out of divs. What a <table> buys is the association: a screen
 * reader says "Source of the value, Field sensors" only because the th and
 * the td are joined by the markup, and someone copying it into a spreadsheet
 * gets columns rather than one run of text. None of that survives divs, and
 * none of it is visible in a screenshot -- so it is checked here.
 */
require_once dirname( __DIR__ ) . '/modules/table/widgets/class-data-table-widget.php';

/**
 * The widget with a settings array pushed into it.
 */
class Table_Render_Probe extends \ErudaToolkit\Modules\Table\Widgets\Data_Table_Widget {

	/**
	 * @var array
	 */
	public $feed = array();

	/**
	 * @return array
	 */
	public function get_settings_for_display() {
		return $this->feed;
	}

	/**
	 * @param array $settings Settings to render with.
	 * @return string
	 */
	public function markup( $settings ) {
		$this->feed = $settings;

		ob_start();
		( new ReflectionMethod( $this, 'render' ) )->invoke( $this );

		return (string) ob_get_clean();
	}
}

/**
 * Repeater rows from a plain list.
 *
 * @param array $rows Rows.
 * @return array
 */
function table_rows( $rows ) {
	$out = array();

	foreach ( $rows as $row ) {
		$out[] = array(
			'cell_1' => isset( $row[0] ) ? $row[0] : '',
			'cell_2' => isset( $row[1] ) ? $row[1] : '',
			'cell_3' => isset( $row[2] ) ? $row[2] : '',
		);
	}

	return $out;
}

$tbl   = new Table_Render_Probe();
$three = $tbl->markup( array(
	'head_1'    => 'Measured',
	'head_2'    => 'What it tells you',
	'head_3'    => 'Source of the value',
	'show_head' => 'yes',
	'band'      => 'yes',
	'rows'      => table_rows( array( array( 'Fluid flow', 'Circulation against the design case', 'Field sensors' ) ) ),
) );

check( 'the table is a table', 1, preg_match( '/<table class="etbl__table">/', $three ) );
check( 'with a real header row', 1, preg_match( '/<thead>.*<tr class="etbl__tr etbl__tr--head">/s', $three ) );
check( 'and column headers that say they are columns', 3, substr_count( $three, 'scope="col"' ) );
check( 'the rows are in a tbody', 1, preg_match( '/<tbody>.*<tr class="etbl__tr">/s', $three ) );
check( 'three headings give three cells', 3, substr_count( $three, '<td class="etbl__td"' ) );
check( 'the column count is on the wrapper', 1, preg_match( '/class="etbl etbl--cols-3/', $three ) );
check( 'banding is a class, not a style', 1, preg_match( '/class="[^"]*etbl--banded/', $three ) );

// Each cell carries its heading, for the phone layout to draw. Written once
// into the markup rather than repeated as visible text, so a desktop screen
// reader is not read every heading twice.
check( 'every cell carries its own heading', 1, preg_match(
	'/<td class="etbl__td" data-etbl-label="Source of the value">Field sensors<\/td>/', $three ) );

// The ghost cell: a row saved three columns wide, in a table now two wide.
$ghost = $tbl->markup( array(
	'head_1'    => 'Measured',
	'head_2'    => 'What it tells you',
	'head_3'    => '',
	'show_head' => 'yes',
	'rows'      => table_rows( array( array( 'Fluid flow', 'Circulation', 'Field sensors' ) ) ),
) );

check( 'clearing the last heading gives a two-column table', 1, preg_match( '/etbl--cols-2/', $ghost ) );
check( 'and the orphaned third cell is not rendered', 2, substr_count( $ghost, '<td class="etbl__td"' ) );
check( 'nor is its content', false, (bool) strpos( $ghost, 'Field sensors' ) );

// No header row: the thead goes, and with it the labels, because there is
// nothing left to label the folded cells with.
$bare = $tbl->markup( array(
	'head_1'    => 'Measured',
	'head_2'    => 'What it tells you',
	'show_head' => '',
	'rows'      => table_rows( array( array( 'Fluid flow', 'Circulation' ) ) ),
) );

check( 'the header row can be switched off', false, (bool) strpos( $bare, '<thead>' ) );
check( 'but the cells keep their labels for the phone', true, (bool) strpos( $bare, 'data-etbl-label="Measured"' ) );

// A caption is the one piece of text that tells a screen reader what it is
// about to read before it starts reading cells.
$cap = $tbl->markup( array(
	'head_1'  => 'Stage',
	'caption' => 'Commissioning runs in four stages.',
	'rows'    => table_rows( array( array( 'Survey' ) ) ),
) );
check( 'a caption is a real caption element', 1, preg_match( '/<caption class="etbl__caption">Commissioning runs in four stages\.<\/caption>/', $cap ) );
check( 'and there is none when nobody wrote one', false, (bool) strpos( $three, '<caption' ) );

// Nothing at all should render nothing at all, not an empty shell.
check( 'a table with no rows renders nothing', '', $tbl->markup( array( 'head_1' => 'Measured', 'rows' => array() ) ) );
check( 'and neither do rows with nothing in them', '', $tbl->markup( array(
	'head_1' => 'Measured',
	'rows'   => table_rows( array( array( '', '', '' ), array( '  ', '' ) ) ),
) ) );

// The column template is written inline because it depends on a count made at
// render time, which is after Elementor's selectors have already run.
check( 'the column template is written onto the wrapper', 1, preg_match( '/--etbl-cols-default: minmax\(0, 1\.05fr\)/', $three ) );
check( 'and a two-column table gets an even one', 1, preg_match( '/--etbl-cols-default: minmax\(0, 1fr\) minmax\(0, 1fr\);/', $ghost ) );

// Escaping. A table is the one widget people paste supplier data into.
$nasty = $tbl->markup( array(
	'head_1'    => '<script>alert(1)</script>',
	'head_2'    => 'Reading',
	'show_head' => 'yes',
	'rows'      => table_rows( array( array( 'Fluid " flow', 'A & B' ) ) ),
) );
check( 'a heading cannot carry markup', false, (bool) strpos( $nasty, '<script>' ) );
check( 'a quote in a cell cannot break out of its label', false, (bool) strpos( $nasty, 'label="Fluid " flow"' ) );
check( 'and an ampersand survives as an ampersand', true, (bool) strpos( $nasty, 'A &amp; B' ) );

/* ------------------------------------------------- Steps_Content --- */

/*
 * The figure table, and the two things about it that are easy to get wrong.
 *
 * Every figure spends the accent exactly once. An accent on three elements out
 * of five is not an accent, it is a second body colour -- and the first draft
 * of the converge figure filled a 104px plane with it and stopped being about
 * the choice it was drawn to show.
 *
 * And every part's coordinates are numbers in the table rather than something
 * worked out from an index in CSS, because calc() has no modulo operator: the
 * first version's `var(--i) % 2` was invalid, every declaration carrying it
 * was dropped, and five sensors rendered stacked on one spot looking like one.
 */
require_once dirname( __DIR__ ) . '/modules/steps/class-steps-content.php';

use ErudaToolkit\Modules\Steps\Steps_Content;

$figures = Steps_Content::figures();

check( 'five illustrations ship', 5, count( $figures ) );

foreach ( $figures as $id => $figure ) {
	$keys = 0;

	foreach ( $figure['parts'] as $part ) {
		if ( ! empty( $part['key'] ) ) {
			$keys++;
		}
	}

	check( 'the ' . $id . ' figure spends the accent exactly once', 1, $keys );
	check( 'and it is made of something', true, count( $figure['parts'] ) > 0 );
}

// A preset id that no longer exists should leave a drawing in place rather
// than a hole in the page.
check( 'an unknown figure falls back rather than vanishing', true, is_array( Steps_Content::figure( 'nope' ) ) );
check( 'and a step asking for one gets a real id', true,
	in_array( Steps_Content::figure_id( array( 'figure' => 'nope' ) ), Steps_Content::figure_ids(), true ) );
check( 'a step asking for a real one keeps it', 'datum', Steps_Content::figure_id( array( 'figure' => 'datum' ) ) );

// Numbering: typed wins, otherwise it counts, padded to two digits so 01 and
// 10 are the same width in a column.
check( 'steps count from one', '01', Steps_Content::number( array(), 0 ) );
check( 'and pad to two digits', '09', Steps_Content::number( array(), 8 ) );
check( 'but stop padding past nine', '10', Steps_Content::number( array(), 9 ) );
check( 'a typed number wins', 'A', Steps_Content::number( array( 'number' => 'A' ), 3 ) );
check( 'and whitespace is not a typed number', '04', Steps_Content::number( array( 'number' => '  ' ), 3 ) );

// A step added and then left empty should not draw a row with a figure in it.
check( 'an empty step is not a step', false, Steps_Content::step_has_content( array( 'title' => '', 'body' => '  ' ) ) );
check( 'a step with only a heading is', true, Steps_Content::step_has_content( array( 'title' => 'Agree the method' ) ) );
check( 'and the empty ones are dropped before rendering', 1, count( Steps_Content::visible_steps(
	array( 'steps' => array( array( 'title' => 'Keep' ), array( 'title' => '', 'body' => '' ) ) )
) ) );

// Custom properties reach CSS through a style attribute, which is one of the
// few places a settings string could otherwise arrive unexamined.
check( 'a part writes its coordinates as custom properties', '--i:0;--x:-64;--y:-30;--z:14',
	Steps_Content::part_style( array( 'vars' => array( 'i' => 0, 'x' => -64, 'y' => -30, 'z' => 14 ) ) ) );
check( 'a part with no coordinates writes no style', '', Steps_Content::part_style( array( 'class' => 'estp__plane' ) ) );
check( 'anything that is not a number is dropped', '--i:1',
	Steps_Content::part_style( array( 'vars' => array( 'i' => 1, 'x' => '12px; color:red' ) ) ) );
// The name is stripped of everything but letters rather than truncated at the
// first bad character, so `x:red;--y` comes out as the harmless `--xredy`.
// What matters is that no punctuation survives: without a colon or a semicolon
// there is nothing to close the property with and nothing to open another.
$hostile = Steps_Content::part_style( array( 'vars' => array( 'x:red;--y' => 5 ) ) );
check( 'a property name cannot carry punctuation out', '--xredy:5', $hostile );
check( 'so it cannot close its own declaration', 1, substr_count( $hostile, ':' ) );
check( 'nor start another', 0, substr_count( $hostile, ';' ) );

check( 'a key part says so in its class', 'estp__part estp__node estp__part--key',
	Steps_Content::part_class( array( 'class' => 'estp__node', 'key' => true ) ) );
check( 'and an ordinary one does not', 'estp__part',
	Steps_Content::part_class( array() ) );

/* ------------------------------------------------------------ Fields --- */

$decl = array(
	array( 'id' => 'color',  'type' => 'color',    'default' => '#111111' ),
	array( 'id' => 'count',  'type' => 'number',   'default' => 6, 'min' => 1, 'max' => 12, 'step' => 1 ),
	array( 'id' => 'speed',  'type' => 'number',   'default' => 0.18, 'min' => 0.05, 'max' => 1.5, 'step' => 0.01 ),
	array( 'id' => 'on',     'type' => 'checkbox', 'default' => true ),
);

check( 'a six-digit colour survives', '#0042ff', Fields::color( '#0042FF' ) );
check( 'shorthand is expanded', '#00ff00', Fields::color( '#0f0' ) );
check( 'surrounding space is ignored', '#112233', Fields::color( '  #112233 ' ) );
check( 'a named colour is refused', null, Fields::color( 'red' ) );
check( 'a function is refused', null, Fields::color( 'rgb(0,0,0)' ) );
check( 'a four-digit value is refused', null, Fields::color( '#0042' ) );
check( 'a non-string is refused', null, Fields::color( array( '#fff' ) ) );

// The colour is printed into a style attribute, so anything that could close
// it has to be turned away rather than escaped and hoped about.
check( 'a value that would close the attribute is refused', null, Fields::color( '#fff;x:y' ) );
check( 'so is one carrying a quote', null, Fields::color( '#fff"' ) );

check( 'an unusable colour falls back to the default',
	'#111111', Fields::values( $decl, array( 'color' => 'nonsense' ) )['color'] );
check( 'a usable one is kept',
	'#0042ff', Fields::values( $decl, array( 'color' => '#0042ff' ) )['color'] );
check( 'a missing value falls back',
	'#111111', Fields::values( $decl, array() )['color'] );
check( 'a corrupt option falls back throughout',
	array( 'color' => '#111111', 'count' => 6, 'speed' => 0.18, 'on' => true ),
	Fields::values( $decl, 'not an array' ) );

check( 'a number above its range is clamped', 12, Fields::values( $decl, array( 'count' => 99 ) )['count'] );
check( 'a number below its range is clamped', 1, Fields::values( $decl, array( 'count' => -4 ) )['count'] );
check( 'a whole-stepped number comes back an int', 7, Fields::values( $decl, array( 'count' => '7' ) )['count'] );
check( 'and rounds rather than truncating', 8, Fields::values( $decl, array( 'count' => 7.6 ) )['count'] );
check( 'a fractional-stepped number stays a float', 0.42, Fields::values( $decl, array( 'speed' => '0.42' ) )['speed'] );
check( 'a non-numeric number falls back', 6, Fields::values( $decl, array( 'count' => 'six' ) )['count'] );
check( 'a boolean is not a number', 6, Fields::values( $decl, array( 'count' => true ) )['count'] );

check( 'a stored falsey checkbox is false', false, Fields::values( $decl, array( 'on' => '0' ) )['on'] );
check( 'a stored truthy checkbox is true', true, Fields::values( $decl, array( 'on' => '1' ) )['on'] );

// Unchecked boxes never reach the POST body, so sanitising has to rebuild the
// set from the declarations the way the module switches already do.
check( 'an absent checkbox sanitises to false', false, Fields::sanitize( $decl, array() )['on'] );
check( 'a present checkbox sanitises to true', true, Fields::sanitize( $decl, array( 'on' => '1' ) )['on'] );
check( 'an absent number sanitises to its default', 6, Fields::sanitize( $decl, array() )['count'] );
check( 'an undeclared key is dropped', false,
	array_key_exists( 'sneaky', Fields::sanitize( $decl, array( 'sneaky' => 'x' ) ) ) );
check( 'a non-array submission yields defaults',
	array( 'color' => '#111111', 'count' => 6, 'speed' => 0.18, 'on' => false ),
	Fields::sanitize( $decl, 'nonsense' ) );

check( 'a declaration with no id is dropped', array(), Fields::valid( array( array( 'type' => 'color', 'default' => '#fff' ) ) ) );
check( 'a declaration with no default is dropped', array(), Fields::valid( array( array( 'id' => 'a', 'type' => 'color' ) ) ) );
check( 'a declaration with an unknown type is dropped', array(), Fields::valid( array( array( 'id' => 'a', 'type' => 'wysiwyg', 'default' => '' ) ) ) );
check( 'a non-array declaration is dropped', array(), Fields::valid( array( 'nope' ) ) );
check( 'non-array declarations yield nothing', array(), Fields::valid( 'nope' ) );

// One malformed field must not cost a module its other fields.
check( 'a good declaration beside a bad one survives', 1,
	count( Fields::valid( array( array( 'id' => 'a', 'type' => 'color', 'default' => '#fff' ), array( 'id' => 'b' ) ) ) ) );

/* --------------------------------------------- Transitions_Fields --- */

$opts = Transitions_Fields::options( array() );

check( 'the curtain defaults to near-black', '#111111', $opts['color'] );
check( 'six columns by default', 6, $opts['columns'] );
check( 'a phone gets four', 4, $opts['mobileColumns'] );
check( 'the preloader is on by default', true, $opts['preloader'] );
check( 'the percentage is on by default', true, $opts['percentage'] );

// The sweep is what the last column finishes at, and it is what the fallback
// timer is sized from. Six columns, 0.18s each, 0.05s apart.
check( 'the sweep spans every column', 0.43, round( $opts['sweep'], 5 ) );

$kit = Transitions_Fields::options( array(), '#c46a2d' );
check( 'a kit colour is adopted when nothing is stored', '#c46a2d', $kit['color'] );
check( 'a stored colour beats the kit', '#0042ff', Transitions_Fields::options( array( 'color' => '#0042ff' ), '#c46a2d' )['color'] );
check( 'an unusable kit colour falls back', '#111111', Transitions_Fields::options( array(), 'chartreuse' )['color'] );

check( 'fewer columns than the phone cap means the phone shows fewer', 2,
	Transitions_Fields::options( array( 'columns' => 2 ) )['mobileColumns'] );

// A maximum under the minimum is contradictory. The maximum is the promise
// that matters to a visitor, so it wins.
$inverted = Transitions_Fields::options( array( 'minimum' => 4000, 'maximum' => 1000 ) );
check( 'an inverted pair collapses onto the maximum', 1000, $inverted['minimum'] );
check( 'and the maximum is untouched', 1000, $inverted['maximum'] );

check( 'a stored zero stagger is honoured', 0.0, Transitions_Fields::options( array( 'stagger' => 0 ) )['stagger'] );
check( 'and makes every column move together', 0.18, round( Transitions_Fields::options( array( 'stagger' => 0 ) )['sweep'], 5 ) );

check( 'every declared field has a value', true,
	count( array_diff(
		array( 'color', 'columns', 'travel', 'stagger', 'preloader', 'minimum', 'maximum', 'percentage' ),
		array_keys( $opts )
	) ) === 0 );

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
