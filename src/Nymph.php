<?php namespace Nymph;
/**
 * Nymph
 *
 * An object relational mapper with PHP and JavaScript interfaces. Written by
 * Hunter Perrin for SciActive.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
use SciActive\R as R;

R::_('Nymph', ['NymphConfig'], function($NymphConfig){
	$class = '\\Nymph\\Drivers\\'.$NymphConfig->driver['value'].'Driver';

	$Nymph = new $class($NymphConfig);
	return $Nymph;
});

class Nymph {
	public static function __callStatic($name, $args) {
		return call_user_func_array(array(R::_('Nymph'), $name), $args);
	}

	// Any method with an argument passed by reference must be passed directly.
	/**
	 * Delete an entity from the database.
	 *
	 * @param Entity &$entity The entity to delete.
	 * @return bool True on success, false on failure.
	 */
	public static function deleteEntity(&$entity) {
		return R::_('Nymph')->deleteEntity($entity);
	}

	/**
	 * Save an entity to the database.
	 *
	 * If the entity has never been saved (has no GUID), a variable "cdate"
	 * is set on it with the current Unix timestamp using microtime(true).
	 *
	 * The variable "mdate" is set to the current Unix timestamp using
	 * microtime(true).
	 *
	 * @param mixed &$entity The entity.
	 * @return bool True on success, false on failure.
	 */
	public static function saveEntity(&$entity) {
		return R::_('Nymph')->saveEntity($entity);
	}

	/**
	 * Sort an array of entities hierarchically by a specified property's value.
	 *
	 * Entities will be placed immediately after their parents. The
	 * $parent_property property must hold either null, or the entity's parent.
	 *
	 * @param array &$array The array of entities.
	 * @param string|null $property The name of the property to sort entities by.
	 * @param string|null $parent_property The name of the property which holds the parent of the entity.
	 * @param bool $case_sensitive Sort case sensitively.
	 * @param bool $reverse Reverse the sort order.
	 */
	public static function hsort(&$array, $property = null, $parent_property = null, $case_sensitive = false, $reverse = false) {
		return R::_('Nymph')->hsort($array, $property, $parent_property, $case_sensitive, $reverse);
	}

	/**
	 * Sort an array of entities by parent and a specified property's value.
	 *
	 * Entities' will be sorted by their parents' properties, then the entities'
	 * properties.
	 *
	 * @param array &$array The array of entities.
	 * @param string|null $property The name of the property to sort entities by.
	 * @param string|null $parent_property The name of the property which holds the parent of the entity.
	 * @param bool $case_sensitive Sort case sensitively.
	 * @param bool $reverse Reverse the sort order.
	 */
	public static function psort(&$array, $property = null, $parent_property = null, $case_sensitive = false, $reverse = false) {
		return R::_('Nymph')->psort($array, $property, $parent_property, $case_sensitive, $reverse);
	}

	/**
	 * Sort an array of entities by a specified property's value.
	 *
	 * @param array &$array The array of entities.
	 * @param string|null $property The name of the property to sort entities by.
	 * @param bool $case_sensitive Sort case sensitively.
	 * @param bool $reverse Reverse the sort order.
	 */
	public static function sort(&$array, $property = null, $case_sensitive = false, $reverse = false) {
		return R::_('Nymph')->sort($array, $property, $case_sensitive, $reverse);
	}

	// The rest of the methods are handled by __callStatic. Simple versions go
	// here for code completion.
	/**
	 * Connect to the database.
	 *
	 * @return bool Whether the instance is connected to the database.
	 */
	public static function connect() {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Delete an entity by its GUID.
	 *
	 * @param int $guid The GUID of the entity.
	 * @return bool True on success, false on failure.
	 */
	public static function deleteEntityByID($guid) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Delete a unique ID.
	 *
	 * @param string $name The UID's name.
	 * @return bool True on success, false on failure.
	 */
	public static function deleteUID($name) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Disconnect from the database.
	 *
	 * @return bool Whether the instance is connected to the database.
	 */
	public static function disconnect() {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Export entities to a local file.
	 *
	 * This is the file format:
	 *
	 * <pre>
	 * # Comments begin with #
	 *    # And can have white space before them.
	 * # This defines a UID.
	 * &lt;name/of/uid&gt;[5]
	 * &lt;another uid&gt;[8000]
	 * # For UIDs, the name is in angle brackets (&lt;&gt;) and the value follows in
	 * #  square brackets ([]).
	 * # This starts a new entity.
	 * {1}[tag,list,with,commas]
	 * # For entities, the GUID is in curly brackets ({}) and the comma
	 * #  separated tag list follows in square brackets ([]).
	 * # Variables are stored like this:
	 * # varname=json_encode(serialize(value))
	 *     abilities="a:1:{i:0;s:10:\"system\/all\";}"
	 *     groups="a:0:{}"
	 *     inherit_abilities="b:0;"
	 *     name="s:5:\"admin\";"
	 * # White space before/after "=" and at beginning/end of line is ignored.
	 *         username  =     "s:5:\"admin\";"
	 * {2}[tag,list]
	 *     another="s:23:\"This is another entity.\";"
	 *     newline="s:1:\"\n\";"
	 * </pre>
	 *
	 * @param string $filename The file to export to.
	 * @return bool True on success, false on failure.
	 */
	public static function export($filename) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Export entities to the client as a downloadable file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function exportPrint() {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Get an array of entities.
	 *
	 * $options is an associative array, which contains any of the following
	 * settings (in the form $options['name'] = value):
	 *
	 * - class - (string) The class to create each entity with.
	 * - limit - (int) The limit of entities to be returned.
	 * - offset - (int) The offset from the oldest matching entity to start
	 *   retrieving.
	 * - reverse - (bool) If true, entities will be retrieved from newest to
	 *   oldest. Therefore, offset will be from the newest entity.
	 * - sort - (string) How to sort the entities. Accepts "guid", "cdate", and
	 *   "mdate". Defaults to "cdate".
	 * - skip_ac - (bool) If true, the user manager will not filter returned
	 *   entities according to access controls.
	 *
	 * If a class is specified, it must have a factory() static method that
	 * returns a new instance.
	 *
	 * Selectors are also associative arrays. Any amount of selectors can be
	 * provided. Empty selectors will be ignored. The first member of a selector
	 * must be a "type" string. The type string can be:
	 *
	 * - & - (and) All values in the selector must be true.
	 * - | - (or) At least one value in the selector must be true.
	 * - !& - (not and) All values in the selector must be false.
	 * - !| - (not or) At least one value in the selector must be false.
	 *
	 * The rest of the entries in the selector are associative entries called
	 * selector clauses, which can be any of the following (in the form
	 * $selector['name'] = value, or
	 * $selector['name'] = [value1, value2,...]:
	 *
	 * - guid - A GUID. True if the entity's GUID is equal.
	 * - tag - A tag. True if the entity has the tag.
	 * - isset - A name. True if the named variable exists and is not null.
	 * - data - An array with a name, then value. True if the named variable is
	 *   defined and equal.
	 * - strict - An array with a name, then value. True if the named variable
	 *   is defined and identical.
	 * - array - An array with a name, then value. True if the named variable is
	 *   an array containing the value. Uses in_array().
	 * - match - An array with a name, then regular expression. True if the
	 *   named variable matches. Uses preg_match(). More powerful than "pmatch"
	 *   but slower. Must be surrounded by "/" delimiters.
	 * - pmatch - An array with a name, then regular expression. True if the
	 *   named variable matches. Uses POSIX RegExp. Case sensitive. Faster than
	 *   "match". Must *not* be surrounded by any delimiters.
	 * - like - An array with a name, then pattern. True if the named variable
	 *   matches. Uses % for variable length wildcard and _ for single character
	 *   wildcard.
	 * - gt - An array with a name, then value. True if the named variable is
	 *   greater than the value.
	 * - gte - An array with a name, then value. True if the named variable is
	 *   greater than or equal to the value.
	 * - lt - An array with a name, then value. True if the named variable is
	 *   less than the value.
	 * - lte - An array with a name, then value. True if the named variable is
	 *   less than or equal to the value.
	 * - ref - An array with a name, then either a entity, or a GUID. True if
	 *   the named variable is the entity or an array containing the entity.
	 *
	 * These clauses can all be negated, by prefixing them with an exclamation
	 * point, such as "!isset".
	 *
	 * This example will retrieve the last two entities where:
	 *
	 * - It has 'person' tag.
	 * - spouse exists and is not null.
	 * - gender is male and lname is Smith.
	 * - warnings is not an integer 0.
	 * - It has either 'employee' or 'manager' tag.
	 * - name is either Clark, James, Chris, Christopher, Jake, or Jacob.
	 * - If age is 22 or more, then pay is not greater than 8.
	 *
	 * <pre>
	 * $entities = \Nymph\Nymph::getEntities(
	 *	['reverse' => true, 'limit' => 2],
	 *	[
	 *		'&', // all must be true
	 *		'tag' => 'person',
	 *		'isset' => 'spouse',
	 *		'data' => [
	 *			['gender', 'male'],
	 *			['lname', 'Smith']
	 *		],
	 *		'!strict' => ['warnings', 0]
	 *	],
	 *	[
	 *		'|', // at least one must be true
	 *		'tag' => ['employee', 'manager']
	 *	],
	 *	[
	 *		'|',
	 *		'data' => [
	 *			['name', 'Clark'],
	 *			['name', 'James']
	 *		],
	 *		'pmatch' => [
	 *			['name', 'Chris(topher)?'],
	 *			['name', 'Ja(ke|cob)']
	 *		]
	 *	],
	 *	[
	 *		'!|', // at least one must be false
	 *		'gte' => ['age', 22],
	 *		'gt' => ['pay', 8]
	 *	]
	 * );
	 * </pre>
	 *
	 * @param array $options The options.
	 * @param array $selectors,... The optional selectors to search for. If none are given, all entities are retrieved for the given options.
	 * @return array|null An array of entities, or null on failure.
	 * @todo An option to place a total count in a var.
	 * @todo Use an asterisk to specify any variable.
	 */
	public static function getEntities() {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Get the first entity to match all options/selectors.
	 *
	 * $options and $selectors are the same as in getEntities().
	 *
	 * This function is equivalent to setting $options['limit'] to 1 for
	 * getEntities(), except that it will return null if no entity is found.
	 * getEntities() would return an empty array.
	 *
	 * @param array $options The options to search for.
	 * @param mixed $selectors,... The selectors to search for, or just a GUID.
	 * @return Entity|null An entity, or null on failure and nothing found.
	 */
	public static function getEntity() {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Get the current value of a unique ID.
	 *
	 * @param string $name The UID's name.
	 * @return int|null The UID's value, or null on failure and if it doesn't exist.
	 */
	public static function getUID($name) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Import entities from a file.
	 *
	 * @param string $filename The file to import from.
	 * @return bool True on success, false on failure.
	 */
	public static function import($filename) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Increment or create a unique ID and return the new value.
	 *
	 * Unique IDs, or UIDs, are ID numbers, similar to GUIDs, but without any
	 * constraints on how they are used. UIDs can be named anything. A good
	 * naming convention, in order to avoid conflicts, is to use your
	 * component's name, a slash, then a descriptive name of the sequence being
	 * identified for non-entity sequences, and the name of the entity's class
	 * for entity sequences. E.g. "com_example/widget_hits" or
	 * "com_hrm_employee".
	 *
	 * A UID can be used to identify an object when the GUID doesn't suffice. On
	 * a system where a new entity is created many times per second, referring
	 * to something by its GUID may be unintuitive. However, the component
	 * designer is responsible for assigning UIDs to the component's entities.
	 * Beware that if a UID is incremented for an entity, and the entity cannot
	 * be saved, there is no safe, and therefore, no recommended way to
	 * decrement the UID back to its previous value.
	 *
	 * If newUID() is passed the name of a UID which does not exist yet, one
	 * will be created with that name, and assigned the value 1. If the UID
	 * already exists, its value will be incremented. The new value will be
	 * returned.
	 *
	 * @param string $name The UID's name.
	 * @return int|null The UID's new value, or null on failure.
	 */
	public static function newUID($name) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Rename a unique ID.
	 *
	 * @param string $old_name The old name.
	 * @param string $new_name The new name.
	 * @return bool True on success, false on failure.
	 */
	public static function renameUID($old_name, $new_name) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
	/**
	 * Set the value of a UID.
	 *
	 * @param string $name The UID's name.
	 * @param int $value The value.
	 * @return bool True on success, false on failure.
	 */
	public static function setUID($name, $value) {
        return self::__callStatic(__FUNCTION__, func_get_args());
	}
}
