<?php
/**
 * Simple Nymph REST server implementation.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * NymphREST class.
 *
 * Provides Nymph functionality compatible with a REST API. Allows the developer
 * to design their own API, or just use the reference implementation.
 *
 * @package Nymph
 */
class NymphREST {
	/**
	 * @access public
	 * @static
	 * @var NymphDriver
	 */
	public static $nymph;

	/**
	 * Run the Nymph REST server process.
	 *
	 * Note that on failure, an HTTP error status code will be sent, usually
	 * along with a message body.
	 *
	 * @param string $method The HTTP method.
	 * @param string $action The Nymph action.
	 * @param string $data The JSON encoded data.
	 * @return bool True on success, false on failure.
	 */
	public function run($method, $action, $data) {
		$method = strtoupper($method);
		if (is_callable(array($this, $method))) {
			return $this->$method($action, $data);
		}
		return $this->httpError(405, "Method Not Allowed");
	}

	protected function DELETE($action = '', $data = '') {
		if (!in_array($action, array('entity', 'entities', 'uid'))) {
			return $this->httpError(400, "Bad Request");
		}
		ob_start();
		if (in_array($action, array('entity', 'entities'))) {
			$ents = json_decode($data, true);
			if ($action === 'entity')
				$ents = array($ents);
			$deleted = array();
			$failures = false;
			foreach ($ents as $delEnt) {
				$guid = (int) $delEnt['guid'];
				$etype = $delEnt['etype'];
				try {
					if (NymphREST::$nymph->deleteEntityByID($guid, $etype)) {
						$deleted[] = $guid;
					} else {
						$failures = true;
					}
				} catch (Exception $e) {
					$failures = true;
				}
			}
			if (empty($deleted)) {
				if ($failures)
					return $this->httpError(400, "Bad Request");
				else
					return $this->httpError(500, "Internal Server Error");
			}
			if ($action === 'entity')
				echo json_encode($deleted[0]);
			else
				echo json_encode($deleted);
			header("HTTP/1.1 200 OK", true, 200);
		} else {
			if (!NymphREST::$nymph->deleteUID("$data")) {
				return $this->httpError(500, "Internal Server Error");
			}
			header("HTTP/1.1 204 No Content", true, 204);
		}
		ob_end_flush();
		return true;
	}

	protected function PUT($action = '', $data = '') {
		if (!in_array($action, array('entity', 'entities', 'uid'))) {
			return $this->httpError(400, "Bad Request");
		}
		ob_start();
		if (in_array($action, array('entity', 'entities'))) {
			$ents = json_decode($data, true);
			if ($action === 'entity')
				$ents = array($ents);
			$created = array();
			$invalidData = false;
			foreach ($ents as $newEnt) {
				if ((int)$newEnt['guid'] > 0) {
					$invalidData = true;
					continue;
				}
				$entity = $this->loadEntity($newEnt);
				if (!$entity) {
					$invalidData = true;
					continue;
				}
				try {
					if ($entity->save()) {
						$created[] = $entity;
					}
				} catch (EntityInvalidDataException $e) {
					$invalidData = true;
				}
			}
			if (empty($created)) {
				if ($invalidData)
					return $this->httpError(400, "Bad Request");
				else
					return $this->httpError(500, "Internal Server Error");
			}
			if ($action === 'entity')
				echo json_encode($created[0]);
			else
				echo json_encode($created);
		} else {
			$result = NymphREST::$nymph->newUID("$data");
			if (empty($result)) {
				return $this->httpError(500, "Internal Server Error");
			}
			echo $result;
		}
		header("HTTP/1.1 201 Created", true, 201);
		ob_end_flush();
		return true;
	}

	protected function POST($action = '', $data = '') {
		if (!in_array($action, array('entity', 'entities', 'method'))) {
			return $this->httpError(400, "Bad Request");
		}
		ob_start();
		if ($action === 'method') {
			$args = json_decode($data, true);
			array_walk($args['params'], array($this, 'referenceToEntity'));
			$entity = $this->loadEntity($args['entity']);
			if (!in_array($args['method'], $entity->clientEnabledMethods))
				return $this->httpError(403, "Forbidden");
			if (!$entity || ((int)$args['entity']['guid'] > 0 && !$entity->guid) || !is_callable(array($entity, $args['method'])))
				return $this->httpError(400, "Bad Request");
			try {
				$return = call_user_func_array(array($entity, $args['method']), $args['params']);
				echo json_encode(array('entity' => $entity, 'return' => $return));
			} catch (Exception $e) {
				return $this->httpError(500, "Internal Server Error");
			}
		} else {
			$ents = json_decode($data, true);
			if ($action === 'entity')
				$ents = array($ents);
			$saved = array();
			$invalidData = false;
			$notfound = false;
			foreach ($ents as $newEnt) {
				if (!is_numeric($newEnt['guid']) || (int)$newEnt['guid'] <= 0) {
					$invalidData = true;
					continue;
				}
				$entity = $this->loadEntity($newEnt);
				if (!$entity) {
					$invalidData = true;
					continue;
				}
				try {
					if ($entity->save()) {
						$saved[] = $entity;
					}
				} catch (EntityInvalidDataException $e) {
					$invalidData = true;
				}
			}
			if (empty($saved)) {
				if ($invalidData)
					return $this->httpError(400, "Bad Request");
				elseif ($notfound)
					return $this->httpError(404, "Not Found");
				else
					return $this->httpError(500, "Internal Server Error");
			}
			if ($action === 'entity')
				echo json_encode($saved[0]);
			else
				echo json_encode($saved);
		}
		header("HTTP/1.1 200 OK", true, 200);
		ob_end_flush();
		return true;
	}

	protected function GET($action = '', $data = '') {
		if (!in_array($action, array('entity', 'entities', 'uid'))) {
			return $this->httpError(400, "Bad Request");
		}
		$actionMap = array(
			'entity' => 'getEntity',
			'entities' => 'getEntities',
			'uid' => 'getUID'
		);
		$method = $actionMap[$action];
		if (in_array($action, array('entity', 'entities'))) {
			$args = json_decode($data, true);
			if (is_int($args)) {
				$result = NymphREST::$nymph->$method($args);
			} else {
				$count = count($args);
				if ($count > 1) {
					for ($i = 1; $i < $count; $i++) {
						if (!isset($args[$i]['type'])) {
							return $this->httpError(400, "Bad Request");
						}
						$newArg = array($args[$i]['type']);
						unset($args[$i]['type']);
						$newArg = array_merge($newArg, $args[$i]);
						$args[$i] = $newArg;
					}
				}
				$result = call_user_func_array(array(NymphREST::$nymph, $method), $args);
			}
			if (empty($result)) {
				global $NymphRequire;
				if ($action === 'entity' || Entity::$nymphConfig->empty_list_error['value']) {
					return $this->httpError(404, "Not Found");
				}
			}
			echo json_encode($result);
			return true;
		} else {
			$result = NymphREST::$nymph->$method("$data");
			if ($result === null) {
				return $this->httpError(404, "Not Found");
			} elseif (empty($result)) {
				return $this->httpError(500, "Internal Server Error");
			}
			echo $result;
			return true;
		}
	}

	protected function loadEntity($entityData) {
		if (!class_exists($entityData['class']))
			return false;
		if ((int)$entityData['guid'] > 0) {
			$entity = NymphREST::$nymph->getEntity(
					array('class' => $entityData['class']),
					array('&',
						'guid' => (int)$entityData['guid']
					)
				);
			if ($entity === null)
				return false;
		} else {
			$entity = new $entityData['class'];
		}
		$entity->removeTag($entity->getTags());
		$entity->addTag($entityData['tags']);
		$entity->jsonUnserializeData($entityData['data']);
		$privateData = array();
		foreach ($entity->privateData as $var) {
			$privateData[$var] = $entity->$var;
		}
		$entity->putData(array_merge($privateData, $entityData['data']));
		if (isset($entityData['cdate']))
			$entity->cdate = $entityData['cdate'];
		if (isset($entityData['mdate']))
			$entity->mdate = $entityData['mdate'];
		return $entity;
	}

	/**
	 * Return the request with an HTTP error response.
	 *
	 * @param int $errorCode The HTTP status code.
	 * @param string $message The message to place on the HTTP status header line.
	 * @return boolean Always returns false.
	 * @access protected
	 */
	protected function httpError($errorCode, $message) {
		header("HTTP/1.1 $errorCode $message", true, $errorCode);
		echo "$errorCode $message";
		return false;
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
}