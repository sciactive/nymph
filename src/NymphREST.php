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

	protected function PUT($action = '', $data = '') {
		if (!in_array($action, array('entity', 'entities', 'uid'))) {
			return $this->httpError(400, "Bad Request");
		}
		if (in_array($action, array('entity', 'entities'))) {
			$ents = json_decode($data, true);
			if ($action === 'entity')
				$ents = array($ents);
			$created = array();
			$invalidData = false;
			foreach ($ents as $newEnt) {
				if (!class_exists($newEnt['class']))
					continue;
				$entity = new $newEnt['class'];
				$entity->addTag($newEnt['tags']);
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
		return true;
	}

	protected function GET($action = '', $data = '') {
		if (!in_array($action, array('getEntity', 'getEntities', 'getUID'))) {
			return $this->httpError(400, "Bad Request");
		}
		if (in_array($action, array('getEntity', 'getEntities'))) {
			$args = json_decode($data, true);
			if (is_int($args)) {
				$result = $this->nymph->$action($args);
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
				$result = call_user_func_array(array($this->nymph, $action), $args);
			}
			if (empty($result)) {
				return $this->httpError(404, "Not Found");
			}
			echo json_encode($result);
			return true;
		} else {
			$result = $this->nymph->$action("$data");
			if (empty($result)) {
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