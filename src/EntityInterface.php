<?php namespace Nymph;
/**
 * Entity interface.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

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
 * Tags are used to classify entities. Where an etype is used to separate data
 * by table, tags are used to separate entities within a table. You can define
 * specific tags to be protected, meaning they cannot be added/removed on the
 * frontend. It can be useful to allow user defined tags, such as for a blog
 * post.
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
interface EntityInterface extends DataObjectInterface, \JsonSerializable {
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
	 * @return array|\Nymph\Entity A Nymph Entity Reference array, or the entity if it is not saved yet.
	 */
	public function toReference();
}
