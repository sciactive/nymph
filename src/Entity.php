<?php
/**
 * Entity class.
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
 * @package Nymph
 */
class Entity implements EntityInterface {
	const etype = 'entity';

	/**
	 * The GUID of the entity.
	 *
	 * @var int
	 * @access private
	 */
	private $guid = null;
	/**
	 * Array of the entity's tags.
	 *
	 * @var array
	 * @access protected
	 */
	protected $tags = array();
	/**
	 * The array used to store each variable assigned to an entity.
	 *
	 * @var array
	 * @access protected
	 */
	protected $data = array();
	/**
	 * Same as $data, but hasn't been unserialized.
	 *
	 * @var array
	 * @access protected
	 */
	protected $sdata = array();
	/**
	 * The array used to store referenced entities.
	 *
	 * This technique allows your code to see another entity as a variable,
	 * while storing only a reference.
	 *
	 * @var array
	 * @access protected
	 */
	protected $entityCache = array();
	/**
	 * Whether this instance is a sleeping reference.
	 *
	 * @var bool
	 * @access private
	 */
	private $isASleepingReference = false;
	/**
	 * The reference to use to wake.
	 *
	 * @var array
	 * @access private
	 */
	private $sleepingReference = false;
	/**
	 * The entries listed here correspond to variables that should be converted
	 * to standard objects instead of arrays when unserializing from JSON.
	 *
	 * @var array
	 * @access public
	 */
	public $objectData = array('ac');
	/**
	 * The entries listed here correspond to properties that will not be
	 * serialized into JSON with json_encode(). This can also be considered a
	 * blacklist, because these properties will not be set with incoming JSON.
	 *
	 * @var array
	 * @access protected
	 */
	protected $privateData = array();
	/**
	 * The entries listed here correspond to properties that can only be
	 * modified by server side code. They will still be visible on the frontend,
	 * unlike privateData, but any changes to them that come from the frontend
	 * will be ignored. This can also be considered a blacklist.
	 *
	 * @var array
	 * @access protected
	 */
	protected $protectedData = array();
	/**
	 * If this is an array, then entries listed here correspond to the only
	 * properties that will be accepted from incoming JSON. Any other properties
	 * will be ignored.
	 *
	 * If you use a whitelist, you don't need to use protectedData, since you
	 * can simply leave those entries out of whitelistData.
	 *
	 * @var array
	 * @access protected
	 */
	protected $whitelistData = false;
	/**
	 * The entries listed here correspond to tags that can only be added/removed
	 * by server side code. They will still be visible on the frontend, but any
	 * changes to them that come from the frontend will be ignored. This can
	 * also be considered a blacklist.
	 *
	 * @var array
	 * @access protected
	 */
	protected $protectedTags = array();
	/**
	 * If this is an array, then tags listed here are the only tags that will be
	 * accepted from incoming JSON. Any other tags will be ignored.
	 *
	 * @var array
	 * @access protected
	 */
	protected $whitelistTags = false;
	/**
	 * The names of the methods allowed to be called by client side JavaScript
	 * with serverCall.
	 *
	 * @var array
	 * @access protected
	 */
	protected $clientEnabledMethods = array();
	/**
	 * Whether to use "skip_ac" when accessing entity references.
	 *
	 * @var bool
	 * @access public
	 */
	public $_nUseSkipAC = false;

	/**
	 * Load an entity.
	 * @param int $id The ID of the entity to load, 0 for a new entity.
	 */
	public function __construct($id = 0) {
		if ($id > 0) {
			$entity = RPHP::_('Nymph')->getEntity(array('class' => get_class($this)), array('&', 'guid' => $id));
			if (isset($entity)) {
				$this->guid = $entity->guid;
				$this->tags = $entity->tags;
				$this->putData($entity->getData(), $entity->getSData());
				return $this;
			}
		}
		return null;
	}

	/**
	 * Create a new instance.
	 * @return Entity The new instance.
	 */
	public static function factory() {
		global $_;
		$class = get_called_class();
		$args = func_get_args();
		$reflector = new ReflectionClass($class);
		$entity = $reflector->newInstanceArgs($args);
		// Use hook functionality when in 2be.
		if (isset($_) && isset($_->hook))
			$_->hook->hook_object($entity, $class.'->', false);
		return $entity;
	}

	/**
	 * Create a new sleeping reference instance.
	 *
	 * Sleeping references won't retrieve their data from the database until it
	 * is actually used.
	 *
	 * @param array $reference The Nymph Entity Reference to use to wake.
	 * @return Entity The new instance.
	 */
	public static function factoryReference($reference) {
		$class = $reference[2];
		$entity = call_user_func(array($class, 'factory'));
		$entity->referenceSleep($reference);
		return $entity;
	}

	/**
	 * Retrieve a variable.
	 *
	 * You do not need to explicitly call this method. It is called by PHP when
	 * you access the variable normally.
	 *
	 * @param string $name The name of the variable.
	 * @return mixed The value of the variable or nothing if it doesn't exist.
	 */
	public function &__get($name) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ($name === 'guid' || $name === 'tags')
			return $this->$name;
		// Unserialize.
		if (isset($this->sdata[$name])) {
			$this->data[$name] = unserialize($this->sdata[$name]);
			unset($this->sdata[$name]);
		}
		// Check for peditor sources.
		if (substr($name, -9) === "_pesource" && !isset($this->sdata[$name]) && isset($this->sdata[substr($name, 0, -9)])) {
			$this->data[substr($name, 0, -9)] = unserialize($this->sdata[substr($name, 0, -9)]);
			unset($this->sdata[substr($name, 0, -9)]);
		}
		// Check for an entity first.
		if (isset($this->entityCache[$name])) {
			if ($this->data[$name][0] == 'nymph_entity_reference') {
				if ($this->entityCache[$name] === 0) {
					// The entity hasn't been loaded yet, so load it now.
					$class = $this->data[$name][2];
					$this->entityCache[$name] = $class::factoryReference($this->data[$name]);
					$this->entityCache[$name]->_nUseSkipAC = (bool) $this->_nUseSkipAC;
				}
				return $this->entityCache[$name];
			} else {
				throw new EntityCorruptedException("Corrupted entity data found on entity with GUID {$this->guid}.");
			}
		}
		// If it's not an entity, return the regular value.
		if ((array) $this->data[$name] === $this->data[$name]) {
			// But, if it's an array, check all the values for entity references, and change them.
			array_walk($this->data[$name], array($this, 'referenceToEntity'));
		} elseif ((object) $this->data[$name] === $this->data[$name] && !(((is_a($this->data[$name], 'Entity') || is_a($this->data[$name], 'hook_override'))) && is_callable(array($this->data[$name], 'toReference')))) {
			// Only do this for non-entity objects.
			foreach ($this->data[$name] as &$cur_property)
				$this->referenceToEntity($cur_property, null);
			unset($cur_property);
		}
		// Check for peditor sources.
		if (substr($name, -9) === "_pesource" && !isset($this->data[$name])) {
			return $this->data[substr($name, 0, -9)];
		}
		return $this->data[$name];
	}

	/**
	 * Checks whether a variable is set.
	 *
	 * You do not need to explicitly call this method. It is called by PHP when
	 * you access the variable normally.
	 *
	 * @param string $name The name of the variable.
	 * @return bool
	 * @todo Check that a referenced entity has not been deleted.
	 */
	public function __isset($name) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ($name === 'guid' || $name === 'tags')
			return isset($this->$name);
		// Unserialize.
		if (isset($this->sdata[$name])) {
			$this->data[$name] = unserialize($this->sdata[$name]);
			unset($this->sdata[$name]);
		}
		return isset($this->data[$name]);
	}

	/**
	 * Sets a variable.
	 *
	 * You do not need to explicitly call this method. It is called by PHP when
	 * you access the variable normally.
	 *
	 * @param string $name The name of the variable.
	 * @param string $value The value of the variable.
	 * @return mixed The value of the variable.
	 */
	public function __set($name, $value) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ($name === 'guid' || $name === 'tags')
			return ($this->$name = $value);
		// Delete any serialized value.
		if (isset($this->sdata[$name]))
			unset($this->sdata[$name]);
		if ((is_a($value, 'Entity') || is_a($value, 'hook_override')) && is_callable(array($value, 'toReference'))) {
			// Store a reference to the entity (its GUID and the class it was loaded as).
			// We don't want to manipulate $value itself, because it could be a variable that the program is still using.
			$save_value = $value->toReference();
			// If toReference returns an array, the GUID of the entity is set
			// or it's a sleeping reference, so this is an entity and we don't
			// store it in the data array.
			if ((array) $save_value === $save_value)
				$this->entityCache[$name] = $value;
			elseif (isset($this->entityCache[$name]))
				unset($this->entityCache[$name]);
			$this->data[$name] = $save_value;
			return $value;
		} else {
			// This is not an entity, so if it was one, delete the cached entity.
			if (isset($this->entityCache[$name]))
				unset($this->entityCache[$name]);
			// Store the actual value passed.
			$save_value = $value;
			// If the variable is an array, look through it and change entities to references.
			if ((array) $save_value === $save_value)
				array_walk_recursive($save_value, array($this, 'entityToReference'));
			return ($this->data[$name] = $save_value);
		}
	}

	/**
	 * Unsets a variable.
	 *
	 * You do not need to explicitly call this method. It is called by PHP when
	 * you access the variable normally.
	 *
	 * @param string $name The name of the variable.
	 */
	public function __unset($name) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ($name === 'guid') {
			unset($this->$name);
			return;
		}
		if ($name === 'tags') {
			$this->$name = array();
			return;
		}
		if (isset($this->entityCache[$name]))
			unset($this->entityCache[$name]);
		unset($this->data[$name]);
		unset($this->sdata[$name]);
	}

	public function addTag() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		$tag_array = func_get_args();
		if ((array) $tag_array[0] === $tag_array[0])
			$tag_array = $tag_array[0];
		if (empty($tag_array))
			return;
		foreach ($tag_array as $tag) {
			$this->tags[] = $tag;
		}
		$this->tags = array_keys(array_flip($this->tags));
	}

	public function arraySearch($array, $strict = false) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((array) $array !== $array)
			return false;
		foreach ($array as $key => $cur_entity) {
			if ($strict ? $this->equals($cur_entity) : $this->is($cur_entity))
				return $key;
		}
		return false;
	}

	public function clearCache() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		// Convert entities in arrays.
		foreach ($this->data as &$value) {
			if ((array) $value === $value)
				array_walk_recursive($value, array($this, 'entityToReference'));
		}
		unset($value);

		// Handle individual entities.
		foreach ($this->entityCache as $key => &$value) {
			if (strpos($key, 'reference_guid: ') === 0) {
				// If it's from an array, remove it.
				unset($this->entityCache[$key]);
			} else {
				// If it's from a property, set it back to 0.
				$value = 0;
			}
		}
		unset($value);
	}

	public function clientEnabledMethods() {
		return $this->clientEnabledMethods;
	}

	public function delete() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		return RPHP::_('Nymph')->deleteEntity($this);
	}

	/**
	 * Check if an item is an entity, and if it is, convert it to a reference.
	 *
	 * @param mixed &$item The item to check.
	 * @param mixed $key Unused.
	 * @access private
	 */
	private function entityToReference(&$item, $key) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((is_a($item, 'Entity') || is_a($item, 'hook_override')) && isset($item->guid) && is_callable(array($item, 'toReference'))) {
			// This is an entity, so we should put it in the entity cache.
			if (!isset($this->entityCache["reference_guid: {$item->guid}"]))
				$this->entityCache["reference_guid: {$item->guid}"] = clone $item;
			// Make a reference to the entity (its GUID) and the class the entity was loaded as.
			$item = $item->toReference();
		}
	}

	public function equals(&$object) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if (!(is_a($object, 'Entity') || is_a($object, 'hook_override')))
			return false;
		if (isset($this->guid) || isset($object->guid)) {
			if ($this->guid != $object->guid)
				return false;
		}
		if (get_class($object) != get_class($this))
			return false;
		$ob_data = $object->getData();
		$my_data = $this->getData();
		return ($ob_data == $my_data);
	}

	public function getData() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		// Convert any entities to references.
		return array_map(array($this, 'getDataReference'), $this->data);
	}

	/**
	 * Convert entities to references and return the result.
	 *
	 * @param mixed $item The item to convert.
	 * @return mixed The resulting item.
	 */
	private function getDataReference($item) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((is_a($item, 'Entity') || is_a($item, 'hook_override')) && is_callable(array($item, 'toReference'))) {
			// Convert entities to references.
			return $item->toReference();
		} elseif ((array) $item === $item) {
			// Recurse into lower arrays.
			return array_map(array($this, 'getDataReference'), $item);
		} elseif ((object) $item === $item) {
			foreach ($item as &$cur_property)
				$cur_property = $this->getDataReference($cur_property);
			unset($cur_property);
		}
		// Not an entity or array, just return it.
		return $item;
	}

	public function getSData() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		return $this->sdata;
	}

	public function getTags() {
		return $this->tags;
	}

	public function hasTag() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((array) $this->tags !== $this->tags)
			return false;
		$tag_array = func_get_args();
		if ((array) $tag_array[0] === $tag_array[0])
			$tag_array = $tag_array[0];
		foreach ($tag_array as $tag) {
			if ( !in_array($tag, $this->tags) )
				return false;
		}
		return true;
	}

	public function inArray($array, $strict = false) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((array) $array !== $array)
			return false;
		foreach ($array as $cur_entity) {
			if ($strict ? $this->equals($cur_entity) : $this->is($cur_entity))
				return true;
		}
		return false;
	}

	public function info($type) {
		if ($type == 'name' && isset($this->name))
			return $this->name;
		elseif ($type == 'type')
			return 'entity';
		elseif ($type == 'types')
			return 'entities';
		return null;
	}

	public function is(&$object) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if (!(is_a($object, 'Entity') || is_a($object, 'hook_override')))
			return false;
		if (isset($this->guid) || isset($object->guid)) {
			return ($this->guid == $object->guid);
		} elseif (!is_callable(array($object, 'getData'))) {
			return false;
		} else {
			$ob_data = $object->getData();
			$my_data = $this->getData();
			return ($ob_data == $my_data);
		}
	}

	public function jsonSerialize() {
		$object = (object) array();
		if ($this->isASleepingReference)
			return $this->sleepingReference;
		$object->guid = $this->guid;
		$object->cdate = $this->cdate;
		$object->mdate = $this->mdate;
		$object->tags = $this->tags;
		$object->info = array(
			'name' => $this->info('name'),
			'type' => $this->info('type'),
			'types' => $this->info('types')
		);
		if ($this->info('url_view'))
			$object->info['url_view'] = $this->info('url_view');
		if ($this->info('url_edit'))
			$object->info['url_edit'] = $this->info('url_edit');
		if ($this->info('url_list'))
			$object->info['url_list'] = $this->info('url_list');
		if ($this->info('icon'))
			$object->info['icon'] = $this->info('icon');
		if ($this->info('image'))
			$object->info['image'] = $this->info('image');
		$object->data = array();
		foreach ($this->data as $key => $val) {
			if ($key !== 'cdate' && $key !== 'mdate' && !in_array($key, $this->privateData))
				$object->data[$key] = $val;
		}
		foreach ($this->sdata as $key => $val) {
			if ($key !== 'cdate' && $key !== 'mdate' && !in_array($key, $this->privateData))
				$object->data[$key] = $this->$key;
		}
		$object->class = get_class($this);
		return $object;
	}

	public function jsonAcceptTags($tags) {
		if ($this->isASleepingReference)
			$this->referenceWake();

		$currentTags = $this->getTags();
		$protectedTags = array_intersect($this->protectedTags, $currentTags);
		$tags = array_diff($tags, $this->protectedTags);

		if ($this->whitelistTags !== false) {
			$tags = array_intersect($tags, $this->whitelistTags);
		}

		$this->removeTag($currentTags);
		$this->addTag(array_keys(array_flip(array_merge($tags, $protectedTags))));
	}

	public function jsonAcceptData($data) {
		if ($this->isASleepingReference)
			$this->referenceWake();

		foreach ($this->objectData as $var) {
			if (isset($data[$var]) && (array) $data[$var] === $data) {
				$data[$var] = (object) $data[$var];
			}
		}

		$privateData = array();
		foreach ($this->privateData as $var) {
			if (key_exists($var, $this->data) || key_exists($var, $this->sdata))
				$privateData[$var] = $this->$var;
			if (key_exists($var, $data))
				unset($data[$var]);
		}

		$protectedData = array();
		foreach ($this->protectedData as $var) {
			if (key_exists($var, $this->data) || key_exists($var, $this->sdata))
				$protectedData[$var] = $this->$var;
			if (key_exists($var, $data))
				unset($data[$var]);
		}

		if ($this->whitelistData !== false) {
			foreach ($data as $var => $val) {
				if (!in_array($var, $this->whitelistData))
					unset($data[$var]);
			}
		}

		$data = array_merge($data, $protectedData, $privateData);

		if (!isset($data['cdate']))
			$data['cdate'] = $this->cdate;
		if (!isset($data['mdate']))
			$data['mdate'] = $this->mdate;

		$this->putData($data);
	}

	public function putData($data, $sdata = array()) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((array) $data !== $data)
			$data = array();
		// Erase the entity cache.
		$this->entityCache = array();
		foreach ($data as $name => $value) {
			if ((array) $value === $value && $value[0] === 'nymph_entity_reference') {
				// Don't load the entity yet, but make the entry in the array,
				// so we know it is an entity reference. This will speed up
				// retrieving entities with lots of references, especially
				// recursive references.
				$this->entityCache[$name] = 0;
			}
		}
		foreach ($sdata as $name => $value) {
			if (strpos($value, 'a:3:{i:0;s:22:"nymph_entity_reference";') === 0) {
				// Don't load the entity yet, but make the entry in the array,
				// so we know it is an entity reference. This will speed up
				// retrieving entities with lots of references, especially
				// recursive references.
				$this->entityCache[$name] = 0;
			}
		}
		$this->data = $data;
		$this->sdata = $sdata;
	}

	/**
	 * Set up a sleeping reference.
	 * @param array $reference The reference to use to wake.
	 */
	public function referenceSleep($reference) {
		$this->isASleepingReference = true;
		$this->sleepingReference = $reference;
	}

	/**
	 * Check if an item is a reference, and if it is, convert it to an entity.
	 *
	 * This function will recurse into deeper arrays.
	 *
	 * @param mixed &$item The item to check.
	 * @param mixed $key Unused.
	 * @access private
	 */
	private function referenceToEntity(&$item, $key) {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if ((array) $item === $item) {
			if (isset($item[0]) && $item[0] === 'nymph_entity_reference') {
				if (!isset($this->entityCache["reference_guid: {$item[1]}"]))
					$this->entityCache["reference_guid: {$item[1]}"] = call_user_func(array($item[2], 'factoryReference'), $item);
				$item = $this->entityCache["reference_guid: {$item[1]}"];
			} else {
				array_walk($item, array($this, 'referenceToEntity'));
			}
		} elseif ((object) $item === $item && !(((is_a($item, 'Entity') || is_a($item, 'hook_override'))) && is_callable(array($item, 'toReference')))) {
			// Only do this for non-entity objects.
			foreach ($item as &$cur_property)
				$this->referenceToEntity($cur_property, null);
			unset($cur_property);
		}
	}

	/**
	 * Wake from a sleeping reference.
	 *
	 * @return bool True on success, false on failure.
	 */
	private function referenceWake() {
		if (!$this->isASleepingReference)
			return true;
		$entity = RPHP::_('Nymph')->getEntity(array('class' => $this->sleepingReference[2], 'skip_ac' => (bool) $this->_nUseSkipAC), array('&', 'guid' => $this->sleepingReference[1]));
		if (!isset($entity))
			return false;
		$this->isASleepingReference = false;
		$this->sleepingReference = null;
		$this->guid = $entity->guid;
		$this->tags = $entity->tags;
		$this->putData($entity->getData(), $entity->getSData());
		return true;
	}

	public function refresh() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		if (!isset($this->guid))
			return false;
		$refresh = RPHP::_('Nymph')->getEntity(array('class' => get_class($this)), array('&', 'guid' => $this->guid));
		if (!isset($refresh))
			return 0;
		$this->tags = $refresh->tags;
		$this->putData($refresh->getData(), $refresh->getSData());
		return true;
	}

	public function removeTag() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		$tag_array = func_get_args();
		if ((array) $tag_array[0] === $tag_array[0])
			$tag_array = $tag_array[0];
		foreach ($tag_array as $tag) {
			// Can't use array_search, because $tag may exist more than once.
			foreach ($this->tags as $cur_key => $cur_tag) {
				if ( $cur_tag === $tag )
					unset($this->tags[$cur_key]);
			}
		}
		$this->tags = array_values($this->tags);
	}

	public function save() {
		if ($this->isASleepingReference)
			$this->referenceWake();
		return RPHP::_('Nymph')->saveEntity($this);
	}

	public function toReference() {
		if ($this->isASleepingReference)
			return $this->sleepingReference;
		if (!isset($this->guid))
			return $this;
		return array('nymph_entity_reference', $this->guid, get_class($this));
	}
}
