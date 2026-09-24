<?php
/**
 * The optional settings contract.
 *
 * @package ErudaToolkit
 */

namespace ErudaToolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A module that carries its own options.
 *
 * Deliberately separate from Module rather than bolted onto it. Module is
 * implemented by every module in the registry, so adding a method there would
 * break all of them at once; a module opts into this one only when it has
 * something to configure.
 *
 * The settings screen renders whatever a module declares here, so a module
 * gains options without the screen learning anything about that module.
 */
interface Configurable {

	/**
	 * The module's option declarations.
	 *
	 * Each entry is an array describing one field:
	 *
	 *   id      string  Key within the module's own settings array. Stored, so
	 *                   it must never change once shipped.
	 *   label   string  Field label. Translated.
	 *   type    string  One of color, number, checkbox.
	 *   default mixed   Value used when nothing is stored.
	 *   help    string  Optional one-line explanation. Translated.
	 *   min     float   Numbers only. Lower clamp.
	 *   max     float   Numbers only. Upper clamp.
	 *   step    float   Numbers only. Input step.
	 *
	 * Called while rendering wp-admin and while sanitising a save, both of
	 * which are long after init, so translating here is safe.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function settings_fields();
}
