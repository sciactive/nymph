<?php
/**
 * Define Nymph interfaces.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/agpl-3.0.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * Nymph database abstraction layer or object relational mapper.
 * @package Nymph
 */
interface NymphDriverInterface {
	/**
	 * Connect to the database.
	 *
	 * @return bool Whether the instance is connected to the database.
	 */
	public function connect();
	/**
	 * Delete an entity from the database.
	 *
	 * @param Entity &$entity The entity to delete.
	 * @return bool True on success, false on failure.
	 */
	public function deleteEntity(&$entity);
	/**
	 * Delete an entity by its GUID.
	 *
	 * @param int $guid The GUID of the entity.
	 * @return bool True on success, false on failure.
	 */
	public function deleteEntityByID($guid);
	/**
	 * Delete a unique ID.
	 *
	 * @param string $name The UID's name.
	 * @return bool True on success, false on failure.
	 */
	public function deleteUID($name);
	/**
	 * Disconnect from the database.
	 *
	 * @return bool Whether the instance is connected to the database.
	 */
	public function disconnect();
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
	public function export($filename);
	/**
	 * Export entities to the client as a downloadable file.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function exportPrint();
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
	 *   "mdate". Defaults to "guid".
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
	 * $selector['name'] = array(value1, value2,...)):
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
	 *   named variable matches. Uses preg_match().
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
	 * $entities = $require('Nymph')->getEntities(
	 *	array('reverse' => true, 'limit' => 2),
	 *	array(
	 *		'&', // all must be true
	 *		'tag' => 'person',
	 *		'isset' => 'spouse',
	 *		'data' => array(
	 *			array('gender', 'male'),
	 *			array('lname', 'Smith')
	 *		),
	 *		'!strict' => array('warnings', 0)
	 *	),
	 *	array(
	 *		'|', // at least one must be true
	 *		'tag' => array('employee', 'manager')
	 *	),
	 *	array(
	 *		'|',
	 *		'data' => array(
	 *			array('name', 'Clark'),
	 *			array('name', 'James')
	 *		),
	 *		'match' => array(
	 *			array('name', '/Chris(topher)?/'),
	 *			array('name', '/Ja(ke|cob)/')
	 *		)
	 *	),
	 *	array(
	 *		'!|', // at least one must be false
	 *		'gte' => array('age', 22),
	 *		'gt' => array('pay', 8)
	 *	)
	 * );
	 * </pre>
	 *
	 * @param array $options The options.
	 * @param array $selectors,... The optional selectors to search for. If none are given, all entities are retrieved.
	 * @return array|null An array of entities, or null on failure.
	 * @todo An option to place a total count in a var.
	 * @todo Use an asterisk to specify any variable.
	 */
	public function getEntities();
	/**
	 * Get the first entity to match all options/selectors.
	 *
	 * $options and $selectors are the same as in getEntities().
	 *
	 * This function is equivalent to setting $options['limit'] to 1 for
	 * getEntities(), except that it will return null if no entity is found.
	 * getEntities() would return an empty array.
	 *
	 * @param mixed $options The options to search for, or just a GUID.
	 * @param mixed $selectors,... The optional selectors to search for, or nothing if $options is a GUID.
	 * @return Entity|null An entity, or null on failure and nothing found.
	 */
	public function getEntity();
	/**
	 * Get the current value of a unique ID.
	 *
	 * @param string $name The UID's name.
	 * @return int|null The UID's value, or null on failure and if it doesn't exist.
	 */
	public function getUID($name);
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
	public function hsort(&$array, $property = null, $parent_property = null, $case_sensitive = false, $reverse = false);
	/**
	 * Import entities from a file.
	 *
	 * @param string $filename The file to import from.
	 * @return bool True on success, false on failure.
	 */
	public function import($filename);
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
	public function newUID($name);
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
	public function psort(&$array, $property = null, $parent_property = null, $case_sensitive = false, $reverse = false);
	/**
	 * Rename a unique ID.
	 *
	 * @param string $old_name The old name.
	 * @param string $new_name The new name.
	 * @return bool True on success, false on failure.
	 */
	public function renameUID($old_name, $new_name);
	/**
	 * Save an entity to the database.
	 *
	 * If the entity has never been saved (has no GUID), a variable "p_cdate"
	 * is set on it with the current Unix timestamp using microtime(true).
	 *
	 * The variable "p_mdate" is set to the current Unix timestamp using
	 * microtime(true).
	 *
	 * @param mixed &$entity The entity.
	 * @return bool True on success, false on failure.
	 */
	public function saveEntity(&$entity);
	/**
	 * Set the value of a UID.
	 *
	 * @param string $name The UID's name.
	 * @param int $value The value.
	 * @return bool True on success, false on failure.
	 */
	public function setUID($name, $value);
	/**
	 * Sort an array of entities by a specified property's value.
	 *
	 * @param array &$array The array of entities.
	 * @param string|null $property The name of the property to sort entities by.
	 * @param bool $case_sensitive Sort case sensitively.
	 * @param bool $reverse Reverse the sort order.
	 */
	public function sort(&$array, $property = null, $case_sensitive = false, $reverse = false);
}

/**
 * Objects which hold data from some type of storage.
 * @package Nymph
 */
interface DataObjectInterface {
	/**
	 * Search the array for this object and return the corresponding key.
	 *
	 * If $strict is false, is() is used to compare. If $strict is true,
	 * equals() is used.
	 *
	 * @param array $array The array to search.
	 * @param bool $strict Whether to use stronger comparison.
	 * @return mixed The key if the object is in the array, false if it isn't or if $array is not an array.
	 */
	public function arraySearch($array, $strict = false);
	/**
	 * Delete the object from storage.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function delete();
	/**
	 * Perform a more strict comparison of this object to another.
	 *
	 * @param mixed &$object The object to compare.
	 * @return bool True or false.
	 */
	public function equals(&$object);
	/**
	 * Check whether this object is in an array.
	 *
	 * If $strict is false, is() is used to compare. If $strict is true,
	 * equals() is used.
	 *
	 * @param array $array The array to search.
	 * @param bool $strict Whether to use stronger comparison.
	 * @return bool True if the object is in the array, false if it isn't or if $array is not an array.
	 */
	public function inArray($array, $strict = false);
	/**
	 * Get info about an object.
	 *
	 * This function is meant to provide a way to represent an object even when
	 * nothing is known about it.
	 *
	 * There are a few common types that most entities/objects should provide.
	 * - name - The name of the object.
	 * - type - The type of data this object represents. (E.g., "user",
	 *   "customer", "page".) This can be localized.
	 * - types - The same as above, but pluralized. (E.g., "users".)
	 * - url_view - The URL where this object can be viewed. If the currently
	 *   logged in user doesn't have the ability to view it, or there is no URL
	 *   to view it, this should return null.
	 * - url_edit - The same as above, but for editing.
	 * - url_list - The URL where this object, and others like it, can be found.
	 *   (E.g., to a list of users.)
	 * - icon - The class to apply for an icon representing this object.
	 * - image - The URL to an image representing this object.
	 *
	 * @param string $type The type of information being requested.
	 * @return mixed The information, or null if the information doesn't exist or can't be returned.
	 */
	public function info($type);
	/**
	 * Perform a less strict comparison of this object to another.
	 *
	 * @param mixed &$object The object to compare.
	 * @return bool True or false.
	 */
	public function is(&$object);
	/**
	 * Refresh the object from storage.
	 *
	 * If the object has been deleted from storage, the database cannot be
	 * reached, or a database error occurs, refresh() will return 0.
	 *
	 * @return bool|int False if the data has not been saved, 0 if it can't be refreshed, true on success.
	 */
	public function refresh();
	/**
	 * Save the object to storage.
	 *
	 * @return bool True on success, false on failure.
	 */
	public function save();
}

/**
 * Database abstraction object.
 *
 * Used to provide a standard, abstract way to access, manipulate, and store
 * data.
 *
 * The GUID is not set until the entity is saved. GUIDs must be unique forever,
 * even after deletion. It's the job of the entity manager to make sure no two
 * entities ever have the same GUID.
 *
 * Tags are used to classify entities. Though not strictly necessary, it is
 * *HIGHLY RECOMMENDED* to give every entity your component creates a tag
 * indentical to your component's name, such as 'com_xmlparser'. You don't want
 * to accidentally get another component's entities.
 *
 * Simply calling delete() will not unset the entity. It will still take up
 * memory. Likewise, simply calling unset will not delete the entity from
 * storage.
 *
 * Some notes about equals() and is():
 *
 * equals() performs a more strict comparison of the entity to another. Use
 * equals() instead of the == operator, because the cached entity data causes ==
 * to return false when it should return true. In order to return true, the
 * entity and $object must meet the following criteria:
 *
 * - They must be entities.
 * - They must have equal GUIDs. (Or both can have no GUID.)
 * - They must be instances of the same class.
 * - Their data must be equal.
 *
 * is() performs a less strict comparison of the entity to another. Use is()
 * instead of the == operator when the entity's data may have been changed, but
 * you only care if it is the same entity. In order to return true, the entity
 * and $object must meet the following criteria:
 *
 * - They must be entities.
 * - They must have equal GUIDs. (Or both can have no GUID.)
 * - If they have no GUIDs, their data must be equal.
 *
 * Some notes about saving entities in other entity's variables:
 *
 * The entity class often uses references to store an entity in another entity's
 * variable or array. The reference is stored as an array with the values:
 *
 * - 0 => The string 'nymph_entity_reference'
 * - 1 => The reference entity's GUID.
 * - 2 => The reference entity's class name.
 *
 * Since the reference entity's class name is stored in the reference on the
 * entity's first save and used to retrieve the reference entity using the same
 * class, if you change the class name in an update, you need to reassign the
 * reference entity and save to storage.
 *
 * When an entity is loaded, it does not request its referenced entities from
 * the entity manager. This is done the first time the variable/array is
 * accessed. The referenced entity is then stored in a cache, so if it is
 * altered elsewhere, then accessed again through the variable, the changes will
 * *not* be there. Therefore, you should take great care when accessing entities
 * from multiple variables. If you might be using a referenced entity again
 * later in the code execution (after some other processing occurs), it's
 * recommended to call clearCache().
 *
 * @package Nymph
 * @property int $guid The GUID of the entity.
 * @property array $tags Array of the entity's tags.
 * @property bool $_nUseSkipAC Whether to use the skip_ac option when retrieving referenced entities.
 */
interface EntityInterface extends DataObjectInterface, JsonSerializable {
	/**
	 * Load an entity.
	 * @param int $id The ID of the entity to load, 0 for a new entity.
	 */
	public function __construct($id = 0);
	/**
	 * Create a new instance.
	 * @return Entity An entity instance.
	 */
	public static function factory();
	/**
	 * Add one or more tags.
	 *
	 * @param mixed $tag,... List or array of tags.
	 */
	public function addTag();
	/**
	 * Clear the cache of referenced entities.
	 *
	 * Calling this function ensures that the next time a referenced entity is
	 * accessed, it will be retrieved from the entity manager.
	 */
	public function clearCache();
	/**
	 * Used to retrieve the data array.
	 *
	 * This should only be used by the entity manager to save the data array
	 * into storage.
	 *
	 * @return array The entity's data array.
	 * @access protected
	 */
	public function getData();
	/**
	 * Used to retrieve the serialized data array.
	 *
	 * This should only be used by the entity manager to save the data array
	 * into storage.
	 *
	 * This method can be used by entity managers to avoid unserializing data
	 * that hasn't been requested yet.
	 *
	 * It should always be called after getData().
	 *
	 * @return array The entity's serialized data array.
	 * @access protected
	 */
	public function getSData();
	/**
	 * Check that the entity has all of the given tags.
	 *
	 * @param mixed $tag,... List or array of tags.
	 * @return bool
	 */
	public function hasTag();
	/**
	 * Used to set the data array.
	 *
	 * This should only be used by the entity manager to push the data array
	 * from storage.
	 *
	 * $sdata be used by entity managers to avoid unserializing data that hasn't
	 * been requested yet.
	 *
	 * @param array $data The data array.
	 * @param array $sdata The serialized data array.
	 */
	public function putData($data, $sdata = array());
	/**
	 * Remove one or more tags.
	 *
	 * @param mixed $tag,... List or array of tags.
	 */
	public function removeTag();
	/**
	 * Return a Nymph Entity Reference for this entity.
	 *
	 * @return array A Nymph Entity Reference array.
	 */
	public function toReference();
}