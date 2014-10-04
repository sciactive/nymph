<?php
/**
 * NymphDriverMySQL class.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */

/**
 * MySQL ORM based Nymph driver.
 *
 * @package Nymph
 */
class NymphDriverMySQL extends NymphDriver {
	/**
	 * The MySQL link identifier for this instance.
	 *
	 * @access private
	 * @var mixed
	 */
	private $link = null;

	/**
	 * Disconnect from the database on destruction.
	 */
	public function __destruct() {
		$this->disconnect();
	}

	/**
	 * Connect to the MySQL database.
	 *
	 * @return bool Whether this instance is connected to a MySQL database after the method has run.
	 */
	public function connect() {
		// Check that the MySQL extension is installed.
		if (!is_callable('mysql_connect')) {
			throw new NymphUnableToConnectException('MySQL PHP extension is not available. It probably has not been installed. Please install and configure it in order to use MySQL.');
		}
		$host = $this->config->MySQL->host['value'];
		$user = $this->config->MySQL->user['value'];
		$password = $this->config->MySQL->password['value'];
		$database = $this->config->MySQL->database['value'];
		// Connecting, selecting database
		if (!$this->connected) {
			if ( $this->link = @mysql_connect($host, $user, $password) ) {
				if ( @mysql_select_db($database, $this->link) ) {
					$this->connected = true;
				} else {
					$this->connected = false;
					if ($host == 'localhost' && $user == 'nymph' && $password == 'password' && $database == 'nymph') {
						throw new NymphNotConfiguredException();
					} else {
						throw new NymphUnableToConnectException('Could not select database: ' . mysql_error());
					}
				}
			} else {
				$this->connected = false;
				if ($host == 'localhost' && $user == 'nymph' && $password == 'password' && $database == 'nymph') {
					throw new NymphNotConfiguredException();
				} else {
					throw new NymphUnableToConnectException('Could not connect: ' . mysql_error());
				}
			}
		}
		return $this->connected;
	}

	/**
	 * Disconnect from the MySQL database.
	 *
	 * @return bool Whether this instance is connected to a MySQL database after the method has run.
	 */
	public function disconnect() {
		if ($this->connected) {
			mysql_close($this->link);
			$this->connected = false;
		}
		return $this->connected;
	}

	/**
	 * Create entity tables in the database.
	 *
	 * @param string $etype The entity type to create a table for. If this is blank, the default tables are created.
	 * @return bool True on success, false on failure.
	 */
	private function createTables($etype = null) {
		if (isset($etype))
			$etype =  '_'.mysql_real_escape_string($etype, $this->link);
		if ( !(mysql_query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";', $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');
		}
		// Create the entity table.
		$query = sprintf("CREATE TABLE IF NOT EXISTS `%sentities%s` (`guid` bigint(20) unsigned NOT NULL, `tags` text, `varlist` text, `cdate` decimal(18,6) NOT NULL, `mdate` decimal(18,6) NOT NULL, PRIMARY KEY (`guid`), KEY `id_tags` (`tags`(1000)), KEY `id_varlist` (`varlist`(1000))) DEFAULT CHARSET=utf8;",
			$this->config->MySQL->prefix['value'],
			$etype);
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		// Create the data table.
		$query = sprintf("CREATE TABLE IF NOT EXISTS `%sdata%s` (`guid` bigint(20) unsigned NOT NULL, `name` text NOT NULL, `value` longtext NOT NULL, PRIMARY KEY (`guid`,`name`(255))) DEFAULT CHARSET=utf8;",
			$this->config->MySQL->prefix['value'],
			$etype);
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		if (!isset($etype)) {
			// Create the GUID table.
			$query = sprintf("CREATE TABLE IF NOT EXISTS `%sguids` (`guid` bigint(20) unsigned NOT NULL, PRIMARY KEY (`guid`)) DEFAULT CHARSET=utf8;",
				$this->config->MySQL->prefix['value']);
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
			// Create the UID table.
			$query = sprintf("CREATE TABLE IF NOT EXISTS `%suids` (`name` text NOT NULL, `cur_uid` bigint(20) unsigned NOT NULL, PRIMARY KEY (`name`(100))) DEFAULT CHARSET=utf8;",
				$this->config->MySQL->prefix['value']);
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
		}
		return true;
	}

	public function deleteEntityByID($guid, $etype = null) {
		$etype = isset($etype) ? '_'.mysql_real_escape_string($etype, $this->link) : '';
		$query = sprintf("DELETE e, d FROM `%sentities%s` e LEFT JOIN `%sdata%s` d ON e.`guid`=d.`guid` WHERE e.`guid`='%u';",
			$this->config->MySQL->prefix['value'],
			$etype,
			$this->config->MySQL->prefix['value'],
			$etype,
			(int) $guid);
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$query = sprintf("DELETE FROM `%sguids` WHERE `guid`='%u';",
			$this->config->MySQL->prefix['value'],
			(int) $guid);
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		// Removed any cached versions of this entity.
		if ($this->config->cache['value'])
			$this->cleanCache($guid);
		return true;
	}

	public function deleteUID($name) {
		if (!$name)
			return false;
		$query = sprintf("DELETE FROM `%suids` WHERE `name`='%s';",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link));
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		return true;
	}

	public function export($filename) {
		$filename = clean_filename((string) $filename);
		if (!$fhandle = fopen($filename, 'w'))
			throw new NymphInvalidParametersException('Provided filename is not writeable.');
		fwrite($fhandle, "# Nymph Entity Exchange\n");
		fwrite($fhandle, "# Nymph Version 1.0.0\n");
		fwrite($fhandle, "# sciactive.com\n");
		fwrite($fhandle, "#\n");
		fwrite($fhandle, "# Generation Time: ".date('r')."\n");

		fwrite($fhandle, "#\n");
		fwrite($fhandle, "# UIDs\n");
		fwrite($fhandle, "#\n\n");

		// Export UIDs.
		$query = sprintf("SELECT * FROM `%suids`;",
			$this->config->MySQL->prefix['value']);
		if ( !($result = mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$row = mysql_fetch_assoc($result);
		while ($row) {
			$row['name'];
			$row['cur_uid'];
			fwrite($fhandle, "<{$row['name']}>[{$row['cur_uid']}]\n");
			// Make sure that $row is incremented :)
			$row = mysql_fetch_assoc($result);
		}

		fwrite($fhandle, "#\n");
		fwrite($fhandle, "# Entities\n");
		fwrite($fhandle, "#\n\n");

		// Export entities.
		$query = sprintf("SELECT e.*, d.`name` AS `dname`, d.`value` AS `dvalue` FROM `%sentities` e LEFT JOIN `%sdata` d ON e.`guid`=d.`guid` ORDER BY e.`guid`;",
			$this->config->MySQL->prefix['value'],
			$this->config->MySQL->prefix['value']);
		if ( !($result = mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$row = mysql_fetch_assoc($result);
		while ($row) {
			$guid = (int) $row['guid'];
			$tags = $row['tags'] === ',,' ? array() : explode(',', trim($row['tags'], ','));
			$cdate = (float) $row['cdate'];
			$mdate = (float) $row['mdate'];
			fwrite($fhandle, "{{$guid}}[".implode(',', $tags)."]\n");
			fwrite($fhandle, "\tcdate=".json_encode(serialize($cdate))."\n");
			fwrite($fhandle, "\tmdate=".json_encode(serialize($mdate))."\n");
			if (isset($row['dname'])) {
				// This do will keep going and adding the data until the
				// next entity is reached. $row will end on the next entity.
				do {
					fwrite($fhandle, "\t{$row['dname']}=".json_encode($row['dvalue'])."\n");
					$row = mysql_fetch_assoc($result);
				} while ((int) $row['guid'] === $guid);
			} else {
				// Make sure that $row is incremented :)
				$row = mysql_fetch_assoc($result);
			}
		}
		return fclose($fhandle);
	}

	public function exportPrint() {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=entities.nex;');
		// End all output buffering.
		while (@ob_end_clean());
		echo "# Nymph Entity Exchange\n";
		echo "# Nymph Version 1.0.0\n";
		echo "# sciactive.com\n";
		echo "#\n";
		echo "# Generation Time: ".date('r')."\n";

		echo "#\n";
		echo "# UIDs\n";
		echo "#\n\n";

		// Export UIDs.
		$query = sprintf("SELECT * FROM `%suids`;",
			$this->config->MySQL->prefix['value']);
		if ( !($result = mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$row = mysql_fetch_assoc($result);
		while ($row) {
			$row['name'];
			$row['cur_uid'];
			echo "<{$row['name']}>[{$row['cur_uid']}]\n";
			// Make sure that $row is incremented :)
			$row = mysql_fetch_assoc($result);
		}

		echo "#\n";
		echo "# Entities\n";
		echo "#\n\n";

		// Export entities.
		$query = sprintf("SELECT e.*, d.`name` AS `dname`, d.`value` AS `dvalue` FROM `%sentities` e LEFT JOIN `%sdata` d ON e.`guid`=d.`guid` ORDER BY e.`guid`;",
			$this->config->MySQL->prefix['value'],
			$this->config->MySQL->prefix['value']);
		if ( !($result = mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$row = mysql_fetch_assoc($result);
		while ($row) {
			$guid = (int) $row['guid'];
			$tags = $row['tags'] === ',,' ? array() : explode(',', trim($row['tags'], ','));
			$cdate = (float) $row['cdate'];
			$mdate = (float) $row['mdate'];
			echo "{{$guid}}[".implode(',', $tags)."]\n";
			echo "\tcdate=".json_encode(serialize($cdate))."\n";
			echo "\tmdate=".json_encode(serialize($mdate))."\n";
			if (isset($row['dname'])) {
				// This do will keep going and adding the data until the
				// next entity is reached. $row will end on the next entity.
				do {
					echo "\t{$row['dname']}=".json_encode($row['dvalue'])."\n";
					$row = mysql_fetch_assoc($result);
				} while ((int) $row['guid'] === $guid);
			} else {
				// Make sure that $row is incremented :)
				$row = mysql_fetch_assoc($result);
			}
		}
		return true;
	}

	public function getEntities() {
		if (!$this->connected)
			throw new NymphUnableToConnectException();
		// Set up options and selectors.
		$selectors = func_get_args();
		if (!$selectors) {
			$options = $selectors = array();
		} else {
			$options = $selectors[0];
			unset($selectors[0]);
		}
		foreach ($selectors as $key => $selector) {
			if (!$selector || (count($selector) === 1 && in_array($selector[0], array('!&', '!|', '|', '!|'))))
				unset($selectors[$key]);
		}

		$entities = array();
		$class = isset($options['class']) ? $options['class'] : Entity;
		if (isset($options['etype'])) {
			$etype_dirty = $options['etype'];
			$etype = '_'.mysql_real_escape_string($etype_dirty, $this->link);
		} else {
			if (method_exists($class, 'etype')) {
				$etype_dirty = $class::etype();
				$etype = '_'.mysql_real_escape_string($etype_dirty, $this->link);
			} else {
				$etype_dirty = null;
				$etype = '';
			}
		}
		$sort = isset($options['sort']) ? $options['sort'] : 'cdate';
		$count = $ocount = 0;

		// Check if the requested entity is cached.
		if ($this->config->cache['value'] && is_int($selectors[1]['guid'])) {
			// Only safe to use the cache option with no other selectors than a GUID and tags.
			if (
					count($selectors) == 1 &&
					$selectors[1][0] == '&' &&
					(
						(count($selectors[1]) == 2) ||
						(count($selectors[1]) == 3 && isset($selectors[1]['tag']))
					)
				) {
				$entity = $this->pull_cache($selectors[1]['guid'], $class);
				if (isset($entity) && (!isset($selectors[1]['tag']) || $entity->hasTag($selectors[1]['tag']))) {
					$entity->_nUseSkipAC = (bool) $options['skip_ac'];
					return array($entity);
				}
			}
		}

		$query_parts = array();
		foreach ($selectors as &$cur_selector) {
			$cur_selector_query = '';
			foreach ($cur_selector as $key => &$value) {
				if ($key === 0) {
					$type = $value;
					$type_is_not = ($type == '!&' || $type == '!|');
					$type_is_or = ($type == '|' || $type == '!|');
					continue;
				}
				$clause_not = $key[0] === '!';
				$cur_query = '';
				if ((array) $value !== $value)
					$value = array(array($value));
				elseif ((array) $value[0] !== $value[0])
					$value = array($value);
				// Any options having to do with data only return if the entity has
				// the specified variables.
				foreach ($value as $cur_value) {
					switch ($key) {
						case 'guid':
						case '!guid':
							foreach ($cur_value as $cur_guid) {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`guid`=\''.(int) $cur_guid.'\'';
							}
							break;
						case 'tag':
						case '!tag':
							foreach ($cur_value as $cur_tag) {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'LOCATE(\','.mysql_real_escape_string($cur_tag, $this->link).',\', e.`tags`)';
							}
							break;
						case 'isset':
						case '!isset':
							if (!($type_is_not xor $clause_not)) {
								foreach ($cur_value as $cur_var) {
									if ( $cur_query )
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									$cur_query .= 'LOCATE(\','.mysql_real_escape_string($cur_var, $this->link).',\', e.`varlist`)';
								}
							}
							break;
						case 'data':
						case '!data':
						case 'strict':
						case '!strict':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`='.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`='.((float) $cur_value[1]);
								break;
							}
						case 'gt':
						case '!gt':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`>'.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`>'.((float) $cur_value[1]);
								break;
							}
						case 'gte':
						case '!gte':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`>='.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`>='.((float) $cur_value[1]);
								break;
							}
						case 'lt':
						case '!lt':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`<'.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`<'.((float) $cur_value[1]);
								break;
							}
						case 'lte':
						case '!lte':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`<='.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`<='.((float) $cur_value[1]);
								break;
							}
						case 'array':
						case '!array':
						case 'match':
						case '!match':
						case 'ref':
						case '!ref':
							if (!($type_is_not xor $clause_not)) {
								if ( $cur_query )
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								$cur_query .= 'LOCATE(\','.mysql_real_escape_string($cur_value[0], $this->link).',\', e.`varlist`)';
							}
							break;
					}
				}
				if ( $cur_query ) {
					if ($cur_selector_query)
						$cur_selector_query .= $type_is_or ? ' OR ' : ' AND ';
					$cur_selector_query .= $cur_query;
				}
			}
			unset($value);
			if ($cur_selector_query)
				$query_parts[] = $cur_selector_query;
		}
		unset($cur_selector);

		switch ($sort) {
			case 'cdate':
			default:
				$sort = 'e.`cdate`';
				break;
			case 'mdate':
				$sort = 'e.`mdate`';
				break;
			case 'guid':
				$sort = 'e.`guid`';
				break;
		}
		if ($query_parts) {
			$query = sprintf("SELECT e.`guid`, e.`tags`, e.`cdate`, e.`mdate`, d.`name`, d.`value`, e.`varlist` FROM `%sentities%s` e LEFT JOIN `%sdata%s` d ON e.`guid`=d.`guid` HAVING %s ORDER BY %s;",
				$this->config->MySQL->prefix['value'],
				$etype,
				$this->config->MySQL->prefix['value'],
				$etype,
				'('.implode(') AND (', $query_parts).')',
				$options['reverse'] ? $sort.' DESC' : $sort);
		} else {
			$query = sprintf("SELECT e.`guid`, e.`tags`, e.`cdate`, e.`mdate`, d.`name`, d.`value` FROM `%sentities%s` e LEFT JOIN `%sdata%s` d ON e.`guid`=d.`guid` ORDER BY %s;",
				$this->config->MySQL->prefix['value'],
				$etype,
				$this->config->MySQL->prefix['value'],
				$etype,
				$options['reverse'] ? $sort.' DESC' : $sort);
		}
		if ( !($result = @mysql_query($query, $this->link)) ) {
			// If the tables don't exist yet, create them.
			if (mysql_errno() == 1146 && $this->createTables()) {
				if (isset($etype_dirty))
					$this->createTables($etype_dirty);
				if ( !($result = @mysql_query($query, $this->link)) ) {
					throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
				}
			} else {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
		}

		$row = mysql_fetch_row($result);
		while ($row) {
			$guid = (int) $row[0];
			$tags = $row[1];
			$data = array('cdate' => (float) $row[2], 'mdate' => (float) $row[3]);
			// Serialized data.
			$sdata = array();
			if (isset($row[4])) {
				// This do will keep going and adding the data until the
				// next entity is reached. $row will end on the next entity.
				do {
					$sdata[$row[4]] = $row[5];
					$row = mysql_fetch_row($result);
				} while ((int) $row[0] === $guid);
			} else {
				// Make sure that $row is incremented :)
				$row = mysql_fetch_row($result);
			}
			// Check all conditions.
			$pass_all = true;
			foreach ($selectors as &$cur_selector) {
				$pass = false;
				foreach ($cur_selector as $key => &$value) {
					if ($key === 0) {
						$type = $value;
						$type_is_not = ($type == '!&' || $type == '!|');
						$type_is_or = ($type == '|' || $type == '!|');
						$pass = !$type_is_or;
						continue;
					}
					$clause_not = $key[0] === '!';
					// Check if it doesn't pass any for &, check if it
					// passes any for |.
					foreach ($value as $cur_value) {
						if (($key === 'ref' || $key === '!ref') && isset($sdata[$cur_value[0]])) {
							// If possible, do a quick entity reference check
							// instead of unserializing all the data.
							if ((array) $cur_value[1] === $cur_value[1]) {
								foreach ($cur_value[1] as $cur_entity) {
									if ((object) $cur_entity === $cur_entity) {
										$pass = ((strpos($sdata[$cur_value[0]], "a:3:{i:0;s:22:\"nymph_entity_reference\";i:1;i:{$cur_entity->guid};") !== false) xor ($type_is_not xor $clause_not));
										if (!($type_is_or xor $pass)) break;
									} elseif ((array) $cur_entity === $cur_entity) {
										$pass = ((strpos($sdata[$cur_value[0]], "a:3:{i:0;s:22:\"nymph_entity_reference\";i:1;i:{$cur_entity['guid']};") !== false) xor ($type_is_not xor $clause_not));
										if (!($type_is_or xor $pass)) break;
									} else {
										$pass = ((strpos($sdata[$cur_value[0]], "a:3:{i:0;s:22:\"nymph_entity_reference\";i:1;i:{$cur_entity};") !== false) xor ($type_is_not xor $clause_not));
										if (!($type_is_or xor $pass)) break;
									}
								}
							} elseif ((object) $cur_value[1] === $cur_value[1]) {
								$pass = ((strpos($sdata[$cur_value[0]], "a:3:{i:0;s:22:\"nymph_entity_reference\";i:1;i:{$cur_value[1]->guid};") !== false) xor ($type_is_not xor $clause_not));
							} elseif ((array) $cur_value[1] === $cur_value[1]) {
								$pass = ((strpos($sdata[$cur_value[0]], "a:3:{i:0;s:22:\"nymph_entity_reference\";i:1;i:{$cur_value[1]['guid']};") !== false) xor ($type_is_not xor $clause_not));
							} else {
								$pass = ((strpos($sdata[$cur_value[0]], "a:3:{i:0;s:22:\"nymph_entity_reference\";i:1;i:{$cur_value[1]};") !== false) xor ($type_is_not xor $clause_not));
							}
						} else {
							// Unserialize the data for this variable.
							if (isset($sdata[$cur_value[0]])) {
								$data[$cur_value[0]] = unserialize($sdata[$cur_value[0]]);
								unset($sdata[$cur_value[0]]);
							}
							switch ($key) {
								case 'guid':
								case '!guid':
								case 'tag':
								case '!tag':
									// These are handled by the query.
									$pass = true;
									break;
								case 'isset':
								case '!isset':
									$pass = (isset($data[$cur_value[0]]) xor ($type_is_not xor $clause_not));
									break;
								case 'data':
								case '!data':
									$pass = (($data[$cur_value[0]] == $cur_value[1]) xor ($type_is_not xor $clause_not));
									break;
								case 'strict':
								case '!strict':
									$pass = (($data[$cur_value[0]] === $cur_value[1]) xor ($type_is_not xor $clause_not));
									break;
								case 'array':
								case '!array':
									$pass = (((array) $data[$cur_value[0]] === $data[$cur_value[0]] && in_array($cur_value[1], $data[$cur_value[0]])) xor ($type_is_not xor $clause_not));
									break;
								case 'match':
								case '!match':
									$pass = ((isset($data[$cur_value[0]]) && preg_match($cur_value[1], $data[$cur_value[0]])) xor ($type_is_not xor $clause_not));
									break;
								case 'gt':
								case '!gt':
									$pass = (($data[$cur_value[0]] > $cur_value[1]) xor ($type_is_not xor $clause_not));
									break;
								case 'gte':
								case '!gte':
									$pass = (($data[$cur_value[0]] >= $cur_value[1]) xor ($type_is_not xor $clause_not));
									break;
								case 'lt':
								case '!lt':
									$pass = (($data[$cur_value[0]] < $cur_value[1]) xor ($type_is_not xor $clause_not));
									break;
								case 'lte':
								case '!lte':
									$pass = (($data[$cur_value[0]] <= $cur_value[1]) xor ($type_is_not xor $clause_not));
									break;
								case 'ref':
								case '!ref':
									$pass = ((isset($data[$cur_value[0]]) && (array) $data[$cur_value[0]] === $data[$cur_value[0]] && $this->entityReferenceSearch($data[$cur_value[0]], $cur_value[1])) xor ($type_is_not xor $clause_not));
									break;
							}
						}
						if (!($type_is_or xor $pass)) break;
					}
					if (!($type_is_or xor $pass)) break;
				}
				unset($value);
				if (!$pass) {
					$pass_all = false;
					break;
				}
			}
			unset($cur_selector);
			if ($pass_all) {
				if ($ocount < $options['offset']) {
					// We must be sure this entity is actually a match before
					// incrementing the offset.
					$ocount++;
					continue;
				}
				if ($this->config->cache['value'])
					$entity = $this->pull_cache($guid, $class);
				else
					$entity = null;
				if (!isset($entity) || $data['mdate'] > $entity->mdate) {
					$entity = call_user_func(array($class, 'factory'));
					$entity->guid = $guid;
					if ($tags !== ',,')
						$entity->tags = explode(',', trim($tags, ','));
					$entity->putData($data, $sdata);
					if ($this->config->cache['value'])
						$this->pushCache($entity, $class);
				}
				$entity->_nUseSkipAC = (bool) $options['skip_ac'];
				$entities[] = $entity;
				$count++;
				if ($options['limit'] && $count >= $options['limit'])
					break;
			}
		}

		mysql_free_result($result);

		return $entities;
	}

	public function getUID($name) {
		if (!$name)
			throw new NymphInvalidParametersException('Name not given for UID');
		$query = sprintf("SELECT `cur_uid` FROM `%suids` WHERE `name`='%s';",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link));
		if ( !($result = mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$row = mysql_fetch_row($result);
		mysql_free_result($result);
		return isset($row[0]) ? (int) $row[0] : null;
	}

	public function import($filename) {
		$filename = clean_filename((string) $filename);
		if (!$fhandle = fopen($filename, 'r'))
			throw new NymphInvalidParametersException('Provided filename is unreadable.');
		$line = '';
		$data = array();
		while (!feof($fhandle)) {
			$line .= fgets($fhandle, 8192);
			if (substr($line, -1) != "\n")
				continue;
			if (preg_match('/^\s*#/S', $line)) {
				$line = '';
				continue;
			}
			$matches = array();
			if (preg_match('/^\s*{(\d+)}\[([\w,]+)\]\s*$/S', $line, $matches)) {
				// Save the current entity.
				if ($guid) {
					$query = sprintf("REPLACE INTO `%sentities` (`guid`, `tags`, `varlist`, `cdate`, `mdate`) VALUES (%u, '%s', '%s', %F, %F);",
						$this->config->MySQL->prefix['value'],
						$guid,
						mysql_real_escape_string(','.$tags.',', $this->link),
						mysql_real_escape_string(','.implode(',', array_keys($data)).',', $this->link),
						unserialize($data['cdate']),
						unserialize($data['mdate']));
					if ( !(mysql_query($query, $this->link)) ) {
						throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
					}
					$query = sprintf("DELETE FROM `%sdata` WHERE `guid`='%u';",
						$this->config->MySQL->prefix['value'],
						$guid);
					if ( !(mysql_query($query, $this->link)) ) {
						throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
					}
					unset($data['cdate'], $data['mdate']);
					if ($data) {
						$query = "INSERT INTO `{$this->config->MySQL->prefix['value']}data` (`guid`, `name`, `value`) VALUES ";
						foreach ($data as $name => $value) {
							$query .= sprintf("(%u, '%s', '%s'),",
								$guid,
								mysql_real_escape_string($name, $this->link),
								mysql_real_escape_string($value, $this->link));
						}
						$query = substr($query, 0, -1).';';
						if ( !(mysql_query($query, $this->link)) ) {
							throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
						}
					}
					$guid = null;
					$tags = array();
					$data = array();
				}
				// Record the new entity's info.
				$guid = (int) $matches[1];
				$tags = $matches[2];
			} elseif (preg_match('/^\s*([\w,]+)\s*=\s*(\S.*\S)\s*$/S', $line, $matches)) {
				// Add the variable to the new entity.
				if ($guid)
					$data[$matches[1]] = json_decode($matches[2]);
			} elseif (preg_match('/^\s*<([^>]+)>\[(\d+)\]\s*$/S', $line, $matches)) {
				// Add the UID.
				$query = sprintf("INSERT INTO `%suids` (`name`, `cur_uid`) VALUES ('%s', %u) ON DUPLICATE KEY UPDATE `cur_uid`=%u;",
					$this->config->MySQL->prefix['value'],
					mysql_real_escape_string($matches[1], $this->link),
					(int) $matches[2],
					(int) $matches[2]);
				if ( !(mysql_query($query, $this->link)) ) {
					throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
				}
			}
			$line = '';
			// Clear the entity cache.
			$this->entityCache = array();
		}
		// Save the last entity.
		if ($guid) {
			$query = sprintf("REPLACE INTO `%sentities` (`guid`, `tags`, `varlist`, `cdate`, `mdate`) VALUES (%u, '%s', '%s', %F, %F);",
				$this->config->MySQL->prefix['value'],
				$guid,
				mysql_real_escape_string(','.$tags.',', $this->link),
				mysql_real_escape_string(','.implode(',', array_keys($data)).',', $this->link),
				unserialize($data['cdate']),
				unserialize($data['mdate']));
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
			$query = sprintf("DELETE FROM `%sdata` WHERE `guid`='%u';",
				$this->config->MySQL->prefix['value'],
				$guid);
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
			if ($data) {
				$query = "INSERT INTO `{$this->config->MySQL->prefix['value']}data` (`guid`, `name`, `value`) VALUES ";
				unset($data['cdate'], $data['mdate']);
				foreach ($data as $name => $value) {
					$query .= sprintf("(%u, '%s', '%s'),",
						$guid,
						mysql_real_escape_string($name, $this->link),
						mysql_real_escape_string($value, $this->link));
				}
				$query = substr($query, 0, -1).';';
				if ( !(mysql_query($query, $this->link)) ) {
					throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
				}
			}
		}
		return true;
	}

	public function newUID($name) {
		if (!$name)
			throw new NymphInvalidParametersException('Name not given for UID');
		$query = sprintf("SELECT GET_LOCK('%suids_%s', 10);",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link));
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$query = sprintf("INSERT INTO `%suids` (`name`, `cur_uid`) VALUES ('%s', 1) ON DUPLICATE KEY UPDATE `cur_uid`=`cur_uid`+1;",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link));
		if ( !(mysql_query($query, $this->link))  ) {
			// If the tables don't exist yet, create them.
			if (mysql_errno() == 1146 && $this->createTables()) {
				if (isset($etype_dirty))
					$this->createTables($etype_dirty);
				if ( !(mysql_query($query, $this->link)) ) {
					throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
				}
			} else {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
		}
		$query = sprintf("SELECT `cur_uid` FROM `%suids` WHERE `name`='%s';",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link));
		if ( !($result = mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		$row = mysql_fetch_row($result);
		mysql_free_result($result);
		$query = sprintf("SELECT RELEASE_LOCK('%suids_%s');",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link));
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		return isset($row[0]) ? (int) $row[0] : null;
	}

	public function renameUID($old_name, $new_name) {
		if (!$old_name || !$new_name)
			throw new NymphInvalidParametersException('Name not given for UID');
		$query = sprintf("UPDATE `%suids` SET `name`='%s' WHERE `name`='%s';",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($new_name, $this->link),
			mysql_real_escape_string($old_name, $this->link));
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		return true;
	}

	/**
	 * @todo Check that the big insert query doesn't fail.
	 */
	public function saveEntity(&$entity) {
		// Save the created date.
		if ( !isset($entity->guid) )
			$entity->cdate = microtime(true);
		// Save the modified date.
		$entity->mdate = microtime(true);
		$data = $entity->getData();
		$sdata = $entity->getSData();
		$varlist = array_merge(array_keys($data), array_keys($sdata));
		$class = get_class($entity);
		$etype_dirty = $class::etype();
		$etype = '_'.mysql_real_escape_string($etype_dirty, $this->link);
		if ( !isset($entity->guid) ) {
			while (true) {
				$new_id = @mt_rand(1, pow(2, 53)); // 2^53 is the maximum number in JavaScript (http://ecma262-5.com/ELS5_HTML.htm#Section_8.5)
				// That number might be too big on some machines. :(
				if ($new_id < 1)
					$new_id = rand(1, 0x7FFFFFFF);
				$query = sprintf("SELECT `guid` FROM `%sguids` WHERE `guid`='%u';",
					$this->config->MySQL->prefix['value'],
					$new_id);
				if ( !($result = mysql_query($query, $this->link)) ) {
					throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
				}
				$row = mysql_fetch_row($result);
				mysql_free_result($result);
				if (!isset($row[0]))
					break;
			}
			$entity->guid = $new_id;
			$query = sprintf("INSERT INTO `%sguids` (`guid`) VALUES (%u);",
				$this->config->MySQL->prefix['value'],
				$entity->guid);
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
			$query = sprintf("INSERT INTO `%sentities%s` (`guid`, `tags`, `varlist`, `cdate`, `mdate`) VALUES (%u, '%s', '%s', %F, %F);",
				$this->config->MySQL->prefix['value'],
				$etype,
				$entity->guid,
				mysql_real_escape_string(','.implode(',', array_diff($entity->tags, array(''))).',', $this->link),
				mysql_real_escape_string(','.implode(',', $varlist).',', $this->link),
				(float) $data['cdate'],
				(float) $data['mdate']);
			if ( !(mysql_query($query, $this->link))  ) {
				// If the tables don't exist yet, create them.
				if (mysql_errno() == 1146 && $this->createTables()) {
					if (isset($etype_dirty))
						$this->createTables($etype_dirty);
					if ( !(mysql_query($query, $this->link)) ) {
						throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
					}
				} else {
					throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
				}
			}
			unset($data['cdate'], $data['mdate']);
			$values = array();
			foreach ($data as $name => $value) {
				$values[] = sprintf('(%u, \'%s\', \'%s\')',
					$entity->guid,
					mysql_real_escape_string($name, $this->link),
					mysql_real_escape_string(serialize($value), $this->link));
			}
			foreach ($sdata as $name => $value) {
				$values[] = sprintf('(%u, \'%s\', \'%s\')',
					$entity->guid,
					mysql_real_escape_string($name, $this->link),
					mysql_real_escape_string($value, $this->link));
			}
			$query = sprintf("INSERT INTO `%sdata%s` (`guid`, `name`, `value`) VALUES %s;",
				$this->config->MySQL->prefix['value'],
				$etype,
				implode(',', $values));
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
		} else {
			// Removed any cached versions of this entity.
			if ($this->config->cache['value'])
				$this->cleanCache($entity->guid);
			$query = sprintf("UPDATE `%sentities%s` SET `tags`='%s', `varlist`='%s', `mdate`=%F WHERE `guid`='%u';",
				$this->config->MySQL->prefix['value'],
				$etype,
				mysql_real_escape_string(','.implode(',', array_diff($entity->tags, array(''))).',', $this->link),
				mysql_real_escape_string(','.implode(',', $varlist).',', $this->link),
				(float) $data['mdate'],
				(int) $entity->guid);
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
			$query = sprintf("DELETE FROM `%sdata%s` WHERE `guid`='%u';",
				$this->config->MySQL->prefix['value'],
				$etype,
				(int) $entity->guid);
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
			unset($data['cdate'], $data['mdate']);
			$values = array();
			foreach ($data as $name => $value) {
				$values[] = sprintf('(%u, \'%s\', \'%s\')',
					(int) $entity->guid,
					mysql_real_escape_string($name, $this->link),
					mysql_real_escape_string(serialize($value), $this->link));
			}
			foreach ($sdata as $name => $value) {
				$values[] = sprintf('(%u, \'%s\', \'%s\')',
					(int) $entity->guid,
					mysql_real_escape_string($name, $this->link),
					mysql_real_escape_string($value, $this->link));
			}
			$query = sprintf("INSERT INTO `%sdata%s` (`guid`, `name`, `value`) VALUES %s;",
				$this->config->MySQL->prefix['value'],
				$etype,
				implode(',', $values));
			if ( !(mysql_query($query, $this->link)) ) {
				throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
			}
		}
		// Cache the entity.
		if ($this->config->cache['value']) {
			$class = get_class($entity);
			// Replace hook override in the class name.
			if (strpos($class, 'hook_override_') === 0)
				$class = substr($class, 14);
			$this->pushCache($entity, $class);
		}
		return true;
	}

	public function setUID($name, $value) {
		if (!$name)
			throw new NymphInvalidParametersException('Name not given for UID');
		$query = sprintf("INSERT INTO `%suids` (`name`, `cur_uid`) VALUES ('%s', %u) ON DUPLICATE KEY UPDATE `cur_uid`=%u;",
			$this->config->MySQL->prefix['value'],
			mysql_real_escape_string($name, $this->link),
			(int) $value,
			(int) $value);
		if ( !(mysql_query($query, $this->link)) ) {
			throw new NymphQueryFailedException('Query failed: ' . mysql_errno() . ': ' . mysql_error(), 0, null, $query);
		}
		return true;
	}
}