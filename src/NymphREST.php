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
	 * @access protected
	 * @var NymphDriver
	 */
	protected $nymph;

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
		global $NymphRequire;
		$this->nymph = $NymphRequire('Nymph');

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
					if ($this->nymph->deleteEntityByID($guid, $etype)) {
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
			if (!$this->nymph->deleteUID("$data")) {
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
				if (!class_exists($newEnt['class'])) {
					$invalidData = true;
					continue;
				}
				$entity = new $newEnt['class'];
				$entity->addTag($newEnt['tags']);
				$entity->jsonUnserializeData($newEnt['data']);
				foreach ($newEnt['data'] as $name => $value) {
					$entity->$name = $value;
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
			$result = $this->nymph->newUID("$data");
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
		if (!in_array($action, array('entity', 'entities'))) {
			return $this->httpError(400, "Bad Request");
		}
		ob_start();
		$ents = json_decode($data, true);
		if ($action === 'entity')
			$ents = array($ents);
		$saved = array();
		$invalidData = false;
		$notfound = false;
		foreach ($ents as $newEnt) {
			if (!class_exists($newEnt['class']) || !is_numeric($newEnt['guid']) || (int)$newEnt['guid'] <= 0) {
				$invalidData = true;
				continue;
			}
			$entity = $this->nymph->getEntity(
					array('class' => $newEnt['class']),
					array('&',
						'guid' => (int)$newEnt['guid']
					)
				);
			if ($entity === null) {
				$notfound = true;
				continue;
			}
			if (!$entity) {
				$invalidData = true;
				continue;
			}
			$entity->removeTag($entity->getTags());
			$entity->addTag($newEnt['tags']);
			$entity->jsonUnserializeData($newEnt['data']);
			$privateData = array();
			foreach ($entity->privateData as $var) {
				$privateData[$var] = $entity->$var;
			}
			$entity->putData(array_merge($privateData, $newEnt['data']));
			$entity->cdate = $newEnt['cdate'];
			$entity->mdate = $newEnt['mdate'];
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
				$result = $this->nymph->$method($args);
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
				$result = call_user_func_array(array($this->nymph, $method), $args);
			}
			if (empty($result)) {
				global $NymphRequire;
				$config = $NymphRequire('NymphConfig');
				if ($action === 'entity' || $config->empty_list_error['value']) {
					return $this->httpError(404, "Not Found");
				}
			}
			echo json_encode($result);
			return true;
		} else {
			$result = $this->nymph->$method("$data");
			if ($result === null) {
				return $this->httpError(404, "Not Found");
			} elseif (empty($result)) {
				return $this->httpError(500, "Internal Server Error");
			}
			echo $result;
			return true;
		}
	}

	protected function httpError($errorCode, $message) {
		header("HTTP/1.1 $errorCode $message", true, $errorCode);
		echo "$errorCode $message";
		return false;
	}
}