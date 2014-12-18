<?php namespace Nymph\Drivers;
/**
 * Common Nymph driver.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
use Nymph\Exceptions;

/**
 * AbstractDriver class.
 *
 * Provides basic methods for a Nymph ORM/DAL.
 *
 * @package Nymph
 */
class AbstractDriver implements DriverInterface {
	const VERSION = '1.0.0';

	/**
	 * Whether this instance is currently connected to a database.
	 *
	 * @var bool
	 */
	public $connected = false;
	/**
	 * Nymph configuration object.
	 * @access protected
	 * @var object
	 */
	protected $config;
	/**
	 * A cache to make entity retrieval faster.
	 * @access protected
	 * @var array
	 */
	protected $entityCache = array();
	/**
	 * A counter for the entity cache to determine the most accessed entities.
	 * @access protected
	 * @var array
	 */
	protected $entityCount = array();
	/**
	 * Sort case sensitively.
	 * @access protected
	 * @var bool
	 */
	protected $sortCaseSensitive;
	/**
	 * Parent property to sort by.
	 * @access protected
	 * @var string
	 */
	protected $sortParent;
	/**
	 * Property to sort by.
	 * @access protected
	 * @var string
	 */
	protected $sortProperty;

	// The following methods depend on the driver. Any other methods can be
	// overridden by the driver.
	public function connect() { }
	public function deleteEntityByID($guid, $etype = null) { }
	public function deleteUID($name) { }
	public function disconnect() { }
	public function export($filename) { }
	public function exportPrint() { }
	public function getEntities() { }
	public function getUID($name) { }
	public function import($filename) { }
	public function newUID($name) { }
	public function renameUID($old_name, $new_name) { }
	public function saveEntity(&$entity) { }
	public function setUID($name, $value) { }

	public function __construct($nymphConfig) {
		$this->config = $nymphConfig;
		$this->connect();
	}

	/**
	 * Remove all copies of an entity from the cache.
	 *
	 * @param int $guid The GUID of the entity to remove.
	 * @access protected
	 */
	protected function cleanCache($guid) {
		unset($this->entityCache[$guid]);
	}

	public function deleteEntity(&$entity) {
		$class = get_class($entity);
		$return = $this->deleteEntityByID($entity->guid, $class::etype);
		if ( $return ) {
			$entity->guid = null;
		}
		return $return;
	}

	/**
	 * Search through a value for an entity reference.
	 *
	 * @param mixed $value Any value to search.
	 * @param array|Entity|int $entity An entity, GUID, or array of either to search for.
	 * @return bool True if the reference is found, false otherwise.
	 * @access protected
	 */
	protected function entityReferenceSearch($value, $entity) {
		if ((array) $value !== $value || !isset($entity)) {
			throw new Exceptions\InvalidParametersException();
		}
		// Get the GUID, if the passed $entity is an object.
		if ((array) $entity === $entity) {
			foreach($entity as &$cur_entity) {
				if ((object) $cur_entity === $cur_entity) {
					$cur_entity = $cur_entity->guid;
				}
			}
			unset($cur_entity);
		} elseif ((object) $entity === $entity) {
			$entity = array($entity->guid);
		} else {
			$entity = array((int) $entity);
		}
		if ($value[0] == 'nymph_entity_reference') {
			return in_array($value[1], $entity);
		} else {
			// Search through multidimensional arrays looking for the reference.
			foreach ($value as $cur_value) {
				if ($this->entityReferenceSearch($cur_value, $entity)) {
					return true;
				}
			}
		}
		return false;
	}

	public function getEntity() {
		// Set up options and selectors.
		$args = func_get_args();
		if (!$args) {
			$args = array(array());
		}
		if ((array) $args[0] === $args[0] && ((int) $args[1] === $args[1] || is_numeric($args[1]))) {
			$args = array($args[0], array('&', 'guid' => (int) $args[1]));
		}
		$args[0]['limit'] = 1;
		$entities = call_user_func_array(array($this, 'getEntities'), $args);
		if (!$entities) {
			return null;
		}
		return $entities[0];
	}

	public function hsort(&$array, $property = null, $parentProperty = null, $caseSensitive = false, $reverse = false) {
		// First sort by the requested property.
		$this->sort($array, $property, $caseSensitive, $reverse);
		if (!isset($parentProperty)) {
			return;
		}
		// Now sort by children.
		$new_array = array();
		// Count the children.
		$child_counter = array();
		while ($array) {
			// Look for entities ready to go in order.
			$changed = false;
			foreach ($array as $key => &$cur_entity) {
				// Must break after adding one, so any following children don't go in the wrong order.
				if (!isset($cur_entity->$parentProperty) || !$cur_entity->$parentProperty->inArray(array_merge($new_array, $array))) {
					// If they have no parent (or their parent isn't in the array), they go on the end.
					$new_array[] = $cur_entity;
					unset($array[$key]);
					$changed = true;
					break;
				} else {
					// Else find the parent.
					$pkey = $cur_entity->$parentProperty->arraySearch($new_array);
					if ($pkey !== false) {
						// And insert after the parent.
						// This makes entities go to the end of the child list.
						$cur_ancestor = $cur_entity->$parentProperty;
						while (isset($cur_ancestor)) {
							if (!isset($child_counter[$cur_ancestor->guid])) {
								$child_counter[$cur_ancestor->guid] = 0;
							}
							$child_counter[$cur_ancestor->guid]++;
							$cur_ancestor = $cur_ancestor->$parentProperty;
						}
						// Where to place the entity.
						$new_key = $pkey + $child_counter[$cur_entity->$parentProperty->guid];
						if (isset($new_array[$new_key])) {
							// If it already exists, we have to splice it in.
							array_splice($new_array, $new_key, 0, array($cur_entity));
							$new_array = array_values($new_array);
						} else {
							// Else just add it.
							$new_array[$new_key] = $cur_entity;
						}
						unset($array[$key]);
						$changed = true;
						break;
					}
				}
			}
			unset($cur_entity);
			if (!$changed) {
				// If there are any unexpected errors and the array isn't changed, just stick the rest on the end.
				$entities_left = array_splice($array, 0);
				$new_array = array_merge($new_array, $entities_left);
			}
		}
		// Now push the new array out.
		$array = $new_array;
	}

	public function psort(&$array, $property = null, $parentProperty = null, $caseSensitive = false, $reverse = false) {
		// Sort by the requested property.
		if (isset($property)) {
			$this->sortProperty = $property;
			$this->sortParent = $parentProperty;
			$this->sortCaseSensitive = $caseSensitive;
			\usort($array, array($this, 'sortProperty'));
		}
		if ($reverse) {
			$array = array_reverse($array);
		}
	}

	/**
	 * Pull an entity from the cache.
	 *
	 * @param int $guid The entity's GUID.
	 * @param string $class The entity's class.
	 * @return Entity|null The entity or null if it's not cached.
	 * @access protected
	 */
	protected function pull_cache($guid, $class) {
		// Increment the entity access count.
		if (!isset($this->entityCount[$guid])) {
			$this->entityCount[$guid] = 0;
		}
		$this->entityCount[$guid]++;
		if (isset($this->entityCache[$guid][$class])) {
			return (clone $this->entityCache[$guid][$class]);
		}
		return null;
	}

	/**
	 * Push an entity onto the cache.
	 *
	 * @param Entity &$entity The entity to push onto the cache.
	 * @param string $class The class of the entity.
	 * @access protected
	 */
	protected function pushCache(&$entity, $class) {
		if (!isset($entity->guid)) {
			return;
		}
		// Increment the entity access count.
		if (!isset($this->entityCount[$entity->guid])) {
			$this->entityCount[$entity->guid] = 0;
		}
		$this->entityCount[$entity->guid]++;
		// Check the threshold.
		if ($this->entityCount[$entity->guid] < $this->config->cache_threshold['value']) {
			return;
		}
		// Cache the entity.
		if ((array) $this->entityCache[$entity->guid] === $this->entityCache[$entity->guid]) {
			$this->entityCache[$entity->guid][$class] = clone $entity;
		} else {
			while ($this->config->cache_limit['value'] && count($this->entityCache) >= $this->config->cache_limit['value']) {
				// Find which entity has been accessed the least.
				asort($this->entityCount);
				foreach ($this->entityCount as $key => $val) {
					if (isset($this->entityCache[$key])) {
						break;
					}
				}
				// Remove it.
				if (isset($this->entityCache[$key])) {
					unset($this->entityCache[$key]);
				}
			}
			$this->entityCache[$entity->guid] = array($class => (clone $entity));
		}
		$this->entityCache[$entity->guid][$class]->clearCache();
	}

	public function sort(&$array, $property = null, $caseSensitive = false, $reverse = false) {
		// Sort by the requested property.
		if (isset($property)) {
			$this->sortProperty = $property;
			$this->sortParent = null;
			$this->sortCaseSensitive = $caseSensitive;
			\usort($array, array($this, 'sortProperty'));
		}
		if ($reverse) {
			$array = array_reverse($array);
		}
	}

	/**
	 * Determine the sort order between two entities.
	 *
	 * @param Entity $a Entity A.
	 * @param Entity $b Entity B.
	 * @return int Sort order.
	 * @access protected
	 */
	protected function sortProperty($a, $b) {
		$property = $this->sortProperty;
		$parent = $this->sortParent;
		if (isset($parent) && (isset($a->$parent->$property) || isset($b->$parent->$property))) {
			if (!$this->sortCaseSensitive && is_string($a->$parent->$property) && is_string($b->$parent->$property)) {
				$aprop = strtoupper($a->$parent->$property);
				$bprop = strtoupper($b->$parent->$property);
				if ($aprop > $bprop) {
					return 1;
				}
				if ($aprop < $bprop) {
					return -1;
				}
			} else {
				if ($a->$parent->$property > $b->$parent->$property) {
					return 1;
				}
				if ($a->$parent->$property < $b->$parent->$property) {
					return -1;
				}
			}
		}
		// If they have the same parent, order them by their own property.
		if (!$this->sortCaseSensitive && is_string($a->$property) && is_string($b->$property)) {
			$aprop = strtoupper($a->$property);
			$bprop = strtoupper($b->$property);
			if ($aprop > $bprop) {
				return 1;
			}
			if ($aprop < $bprop) {
				return -1;
			}
		} else {
			if ($a->$property > $b->$property) {
				return 1;
			}
			if ($a->$property < $b->$property) {
				return -1;
			}
		}
		return 0;
	}
}
