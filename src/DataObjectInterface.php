<?php namespace Nymph;
/**
 * Data Object interface.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

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
	 *   (Generally a Font Awesome or Bootstrap class.)
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
