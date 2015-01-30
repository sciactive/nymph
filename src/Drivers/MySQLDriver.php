<?php namespace Nymph\Drivers;
/**
 * MySQLDriver class.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
use Nymph\Exceptions;

/**
 * MySQL ORM based Nymph driver.
 *
 * @package Nymph
 */
class MySQLDriver implements DriverInterface {
	use DriverTrait {
		DriverTrait::__construct as private __traitConstruct;
	}
	/**
	 * The MySQL link identifier for this instance.
	 *
	 * @access private
	 * @var mixed
	 */
	private $link = null;
	private $prefix;

	public function __construct($NymphConfig) {
		$this->__traitConstruct($NymphConfig);
		$this->prefix = $this->config->MySQL->prefix['value'];
	}

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
		// Check that the MySQLi extension is installed.
		if (!is_callable('mysqli_connect')) {
			throw new Exceptions\UnableToConnectException('MySQL PHP extension is not available. It probably has not been installed. Please install and configure it in order to use MySQL.');
		}
		$host = $this->config->MySQL->host['value'];
		$user = $this->config->MySQL->user['value'];
		$password = $this->config->MySQL->password['value'];
		$database = $this->config->MySQL->database['value'];
		$port = $this->config->MySQL->port['value'];
		// Connecting, selecting database
		if (!$this->connected) {
			if ( $this->link = mysqli_connect($host,  $user,  $password, $database, $port) ) {
				$this->connected = true;
			} else {
				$this->connected = false;
				if ($host == 'localhost' && $user == 'nymph' && $password == 'password' && $database == 'nymph') {
					throw new Exceptions\NotConfiguredException();
				} else {
					throw new Exceptions\UnableToConnectException('Could not connect: ' . mysqli_error($this->link));
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
			if (is_a($this->link, 'mysqli')) {
				unset($this->link);
			}
			$this->link = null;
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
		$this->query('SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";');
		if (isset($etype)) {
			$etype =  '_'.mysqli_real_escape_string($this->link, $etype);
			// Create the entity table.
			$this->query("CREATE TABLE IF NOT EXISTS `{$this->prefix}entities{$etype}` (`guid` bigint(20) unsigned NOT NULL, `tags` text, `varlist` text, `cdate` decimal(18,6) NOT NULL, `mdate` decimal(18,6) NOT NULL, PRIMARY KEY (`guid`), FULLTEXT `id_tags` (`tags`), FULLTEXT `id_varlist` (`varlist`)) ENGINE ".$this->config->MySQL->engine['value']." CHARACTER SET utf8 COLLATE utf8_bin;");
			// Create the data table.
			$this->query("CREATE TABLE IF NOT EXISTS `{$this->prefix}data{$etype}` (`guid` bigint(20) unsigned NOT NULL, `name` text NOT NULL, `value` longtext NOT NULL, `references` longtext, `compare_true` boolean, `compare_one` boolean, `compare_zero` boolean, `compare_negone` boolean, `compare_emptyarray` boolean, `compare_string` longtext, PRIMARY KEY (`guid`,`name`(255)), FULLTEXT `id_references` (`references`)) ENGINE ".$this->config->MySQL->engine['value']." CHARACTER SET utf8 COLLATE utf8_bin;");
		} else {
			// Create the GUID table.
			$this->query("CREATE TABLE IF NOT EXISTS `{$this->prefix}guids` (`guid` bigint(20) unsigned NOT NULL, PRIMARY KEY (`guid`)) ENGINE ".$this->config->MySQL->engine['value']." CHARACTER SET utf8 COLLATE utf8_bin;");
			// Create the UID table.
			$this->query("CREATE TABLE IF NOT EXISTS `{$this->prefix}uids` (`name` text NOT NULL, `cur_uid` bigint(20) unsigned NOT NULL, PRIMARY KEY (`name`(100))) ENGINE ".$this->config->MySQL->engine['value']." CHARACTER SET utf8 COLLATE utf8_bin;");
		}
		return true;
	}

	private function query($query, $etype_dirty = null) {
		if ( !($result = mysqli_query($this->link, $query)) ) {
			// If the tables don't exist yet, create them.
			if (mysqli_errno($this->link) == 1146 && $this->createTables()) {
				if (isset($etype_dirty)) {
					$this->createTables($etype_dirty);
				}
				if ( !($result = mysqli_query($this->link, $query)) ) {
					throw new Exceptions\QueryFailedException('Query failed: ' . mysqli_errno($this->link) . ': ' . mysqli_error($this->link), 0, null, $query);
				}
			} else {
				throw new Exceptions\QueryFailedException('Query failed: ' . mysqli_errno($this->link) . ': ' . mysqli_error($this->link), 0, null, $query);
			}
		}
		return $result;
	}

	public function deleteEntityByID($guid, $etype = null) {
		$etype = isset($etype) ? '_'.mysqli_real_escape_string($this->link, $etype) : '';
		$this->query("DELETE e, d FROM `{$this->prefix}entities{$etype}` e LEFT JOIN `{$this->prefix}data{$etype}` d ON e.`guid`=d.`guid` WHERE e.`guid`='".((int) $guid)."';", $etype);
		$this->query("DELETE FROM `{$this->prefix}guids` WHERE `guid`='".((int) $guid)."';", $etype);
		// Removed any cached versions of this entity.
		if ($this->config->cache['value']) {
			$this->cleanCache($guid);
		}
		return true;
	}

	public function deleteUID($name) {
		if (!$name) {
			return false;
		}
		$this->query("DELETE FROM `{$this->prefix}uids` WHERE `name`='".mysqli_real_escape_string($this->link, $name)."';");
		return true;
	}

	public function export($filename) {
		if (!$fhandle = fopen($filename, 'w')) {
			throw new Exceptions\InvalidParametersException('Provided filename is not writeable.');
		}
		fwrite($fhandle, "# Nymph Entity Exchange\n");
		fwrite($fhandle, "# Nymph Version ".NYMPH_VERSION."\n");
		fwrite($fhandle, "# sciactive.com\n");
		fwrite($fhandle, "#\n");
		fwrite($fhandle, "# Generation Time: ".date('r')."\n");

		fwrite($fhandle, "#\n");
		fwrite($fhandle, "# UIDs\n");
		fwrite($fhandle, "#\n\n");

		// Export UIDs.
		$result = $this->query("SELECT * FROM `{$this->prefix}uids`;");
		$row = mysqli_fetch_assoc($result);
		while ($row) {
			$row['name'];
			$row['cur_uid'];
			fwrite($fhandle, "<{$row['name']}>[{$row['cur_uid']}]\n");
			// Make sure that $row is incremented :)
			$row = mysqli_fetch_assoc($result);
		}

		fwrite($fhandle, "\n#\n");
		fwrite($fhandle, "# Entities\n");
		fwrite($fhandle, "#\n\n");

		// Get the etypes.
		$result = $this->query("SHOW TABLES;");
		$etypes = [];
		$row = mysqli_fetch_row($result);
		while ($row) {
			if (strpos($row[0], $this->prefix.'entities_') === 0) {
				$etypes[] = substr($row[0], strlen($this->prefix.'entities_'));
			}
			$row = mysqli_fetch_row($result);
		}

		foreach ($etypes as $etype) {
			// Export entities.
			$result = $this->query("SELECT e.*, d.`name` AS `dname`, d.`value` AS `dvalue` FROM `{$this->prefix}entities_{$etype}` e LEFT JOIN `{$this->prefix}data_{$etype}` d ON e.`guid`=d.`guid` ORDER BY e.`guid`;");
			$row = mysqli_fetch_assoc($result);
			while ($row) {
				$guid = (int) $row['guid'];
				$tags = $row['tags'] === '' ? [] : explode(' ', $row['tags']);
				$cdate = (float) $row['cdate'];
				$mdate = (float) $row['mdate'];
				fwrite($fhandle, "{{$guid}}<{$etype}>[".implode(',', $tags)."]\n");
				fwrite($fhandle, "\tcdate=".json_encode(serialize($cdate))."\n");
				fwrite($fhandle, "\tmdate=".json_encode(serialize($mdate))."\n");
				if (isset($row['dname'])) {
					// This do will keep going and adding the data until the
					// next entity is reached. $row will end on the next entity.
					do {
						fwrite($fhandle, "\t{$row['dname']}=".json_encode($row['dvalue'])."\n");
						$row = mysqli_fetch_assoc($result);
					} while ((int) $row['guid'] === $guid);
				} else {
					// Make sure that $row is incremented :)
					$row = mysqli_fetch_assoc($result);
				}
			}
		}
		return fclose($fhandle);
	}

	public function exportPrint() {
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=entities.nex;');
		// End all output buffering.
		while (ob_end_clean());
		echo "# Nymph Entity Exchange\n";
		echo "# Nymph Version ".NYMPH_VERSION."\n";
		echo "# sciactive.com\n";
		echo "#\n";
		echo "# Generation Time: ".date('r')."\n";

		echo "#\n";
		echo "# UIDs\n";
		echo "#\n\n";

		// Export UIDs.
		$result = $this->query("SELECT * FROM `{$this->prefix}uids`;");
		$row = mysqli_fetch_assoc($result);
		while ($row) {
			$row['name'];
			$row['cur_uid'];
			echo "<{$row['name']}>[{$row['cur_uid']}]\n";
			// Make sure that $row is incremented :)
			$row = mysqli_fetch_assoc($result);
		}

		echo "\n#\n";
		echo "# Entities\n";
		echo "#\n\n";

		// Get the etypes.
		$result = $this->query("SHOW TABLES;");
		$etypes = [];
		$row = mysqli_fetch_row($result);
		while ($row) {
			if (strpos($row[0], $this->prefix.'entities_') === 0) {
				$etypes[] = substr($row[0], strlen($this->prefix.'entities_'));
			}
			$row = mysqli_fetch_row($result);
		}

		foreach ($etypes as $etype) {
			// Export entities.
			$result = $this->query("SELECT e.*, d.`name` AS `dname`, d.`value` AS `dvalue` FROM `{$this->prefix}entities_{$etype}` e LEFT JOIN `{$this->prefix}data_{$etype}` d ON e.`guid`=d.`guid` ORDER BY e.`guid`;");
			$row = mysqli_fetch_assoc($result);
			while ($row) {
				$guid = (int) $row['guid'];
				$tags = $row['tags'] === '' ? [] : explode(' ', $row['tags']);
				$cdate = (float) $row['cdate'];
				$mdate = (float) $row['mdate'];
				echo "{{$guid}}<{$etype}>[".implode(' ', $tags)."]\n";
				echo "\tcdate=".json_encode(serialize($cdate))."\n";
				echo "\tmdate=".json_encode(serialize($mdate))."\n";
				if (isset($row['dname'])) {
					// This do will keep going and adding the data until the
					// next entity is reached. $row will end on the next entity.
					do {
						echo "\t{$row['dname']}=".json_encode($row['dvalue'])."\n";
						$row = mysqli_fetch_assoc($result);
					} while ((int) $row['guid'] === $guid);
				} else {
					// Make sure that $row is incremented :)
					$row = mysqli_fetch_assoc($result);
				}
			}
		}
		return true;
	}

	/**
	 * Generate the MySQL query.
	 * @param array $options The options array.
	 * @param array $selectors The formatted selector array.
	 * @param string $etype_dirty
	 * @param bool $subquery Whether only a subquery should be returned.
	 * @param array $data_aliases The data alias array to use for subqueries.
	 * @return string The SQL query.
	 */
	private function makeEntityQuery($options, $selectors, $etype_dirty, $subquery = false, &$data_aliases = []) {
		$sort = isset($options['sort']) ? $options['sort'] : 'cdate';
		$etype = '_'.mysqli_real_escape_string($this->link, $etype_dirty);
		$query_parts = [];
		foreach ($selectors as $cur_selector) {
			$cur_selector_query = '';
			foreach ($cur_selector as $key => $value) {
				if ($key === 0) {
					$type = $value;
					$type_is_not = ($type == '!&' || $type == '!|');
					$type_is_or = ($type == '|' || $type == '!|');
					continue;
				}
				$cur_query = '';
				if (is_numeric($key)) {
					if ( $cur_query ) {
						$cur_query .= $type_is_or ? ' OR ' : ' AND ';
					}
					$cur_query .= $this->makeEntityQuery($options, [$value], $etype_dirty, true, $data_aliases);
				} else {
					$clause_not = $key[0] === '!';
					// Any options having to do with data only return if the
					// entity has the specified variables.
					foreach ($value as $cur_value) {
						switch ($key) {
							case 'guid':
							case '!guid':
								foreach ($cur_value as $cur_guid) {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`guid`=\''.(int) $cur_guid.'\'';
								}
								break;
							case 'tag':
							case '!tag':
								if ($type_is_not xor $clause_not) {
									if ($type_is_or) {
										foreach ($cur_value as $cur_tag) {
											if ( $cur_query ) {
												$cur_query .= ' OR ';
											}
											$cur_query .= 'e.`tags` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_tag).'[[:>:]]\'';
										}
									} else {
										$cur_query .= 'e.`tags` NOT REGEXP \'[[:<:]]('.mysqli_real_escape_string($this->link, implode('|', $cur_value)).')[[:>:]]\'';
									}
								} else {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$groupQuery = '';
									foreach ($cur_value as $cur_tag) {
										$groupQuery .= ($type_is_or ? ' ' : ' +').$cur_tag;
									}
									$cur_query .= 'MATCH (e.`tags`) AGAINST (\''.mysqli_real_escape_string($this->link, $groupQuery).'\' IN BOOLEAN MODE)';
								}
								break;
							case 'isset':
							case '!isset':
								if ($type_is_not xor $clause_not) {
									foreach ($cur_value as $cur_var) {
										if ( $cur_query ) {
											$cur_query .= $type_is_or ? ' OR ' : ' AND ';
										}
										$cur_query .= '(e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_var).'[[:>:]]\'';
										$alias = $this->addDataAlias($data_aliases);
										$cur_query .= ' OR (e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_var).'\' AND '.$alias.'.`value`=\'N;\')';
										$cur_query .= ')';
									}
								} else {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$groupQuery = '';
									foreach ($cur_value as $cur_var) {
										$groupQuery .= ($type_is_or ? ' ' : ' +').$cur_var;
									}
									$cur_query .= 'MATCH (e.`varlist`) AGAINST (\''.mysqli_real_escape_string($this->link, $groupQuery).'\' IN BOOLEAN MODE)';
								}
								break;
							case 'ref':
							case '!ref':
								$guids = [];
								if ((array) $cur_value[1] === $cur_value[1]) {
									if (key_exists('guid', $cur_value[1])) {
										$guids[] = (int) $cur_value[1]['guid'];
									} else {
										foreach ($cur_value[1] as $cur_entity) {
											if ((object) $cur_entity === $cur_entity) {
												$guids[] = (int) $cur_entity->guid;
											} elseif ((array) $cur_entity === $cur_entity) {
												$guids[] = (int) $cur_entity['guid'];
											} else {
												$guids[] = (int) $cur_entity;
											}
										}
									}
								} elseif ((object) $cur_value[1] === $cur_value[1]) {
									$guids[] = (int) $cur_value[1]->guid;
								} else {
									$guids[] = (int) $cur_value[1];
								}
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								if ($type_is_not xor $clause_not) {
									$cur_query .= '(e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR (';
									$noPrepend = true;
									foreach ($guids as $cur_qguid) {
										if ( !$noPrepend ) {
											$cur_query .= $type_is_or ? ' OR ' : ' AND ';
										} else {
											$noPrepend = false;
										}
										$alias = $this->addDataAlias($data_aliases);
										$cur_query .= '(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.$alias.'.`references` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_qguid).'[[:>:]]\')';
									}
									$cur_query .= '))';
								} else {
									$groupQuery = '';
									foreach ($guids as $cur_qguid) {
										$groupQuery .= ($type_is_or ? ' ' : ' +').$cur_qguid;
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND MATCH ('.$alias.'.`references`) AGAINST (\''.mysqli_real_escape_string($this->link, $groupQuery).'\' IN BOOLEAN MODE))';
								}
								break;
							case 'strict':
							case '!strict':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`='.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`='.((float) $cur_value[1]);
									break;
								} else {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									if (is_callable([$cur_value[1], 'toReference'])) {
										$svalue = serialize($cur_value[1]->toReference());
									} else {
										$svalue = serialize($cur_value[1]);
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`value`=\''.mysqli_real_escape_string($this->link, $svalue).'\'))';
								}
								break;
							case 'like':
							case '!like':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e.`cdate` LIKE \''.mysqli_real_escape_string($this->link, $cur_value[1]).'\')';
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e.`mdate` LIKE \''.mysqli_real_escape_string($this->link, $cur_value[1]).'\')';
									break;
								} else {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_string` LIKE \''.mysqli_real_escape_string($this->link, $cur_value[1]).'\'))';
								}
								break;
							case 'pmatch':
							case '!pmatch':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e.`cdate` REGEXP \''.mysqli_real_escape_string($this->link, $cur_value[1]).'\')';
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e.`mdate` REGEXP \''.mysqli_real_escape_string($this->link, $cur_value[1]).'\')';
									break;
								} else {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_string` REGEXP \''.mysqli_real_escape_string($this->link, $cur_value[1]).'\'))';
								}
								break;
							case 'match':
							case '!match':
								if (!($type_is_not xor $clause_not)) {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= 'MATCH (e.`varlist`) AGAINST (\'+'.mysqli_real_escape_string($this->link, $cur_value[0]).'\' IN BOOLEAN MODE)';
								}
								break;
							// Cases after this point contains special values where
							// it can be solved by the query, but if those values
							// don't match, just check the variable exists.
							case 'data':
							case '!data':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."cdate"='.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."mdate"='.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[1] === true || $cur_value[1] === false) {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_true`='.($cur_value[1] ? 'TRUE' : 'FALSE').'))';
									break;
								} elseif ($cur_value[1] === 1) {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_one`=TRUE))';
									break;
								} elseif ($cur_value[1] === 0) {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_zero`=TRUE))';
									break;
								} elseif ($cur_value[1] === -1) {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_negone`=TRUE))';
									break;
								} elseif ($cur_value[1] === []) {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$alias = $this->addDataAlias($data_aliases);
									$cur_query .= '('.(($type_is_not xor $clause_not) ? 'e.`varlist` NOT REGEXP \'[[:<:]]'.mysqli_real_escape_string($this->link, $cur_value[0]).'[[:>:]]\' OR ' : '').'(e.`guid`='.$alias.'.`guid` AND '.$alias.'.`name`=\''.mysqli_real_escape_string($this->link, $cur_value[0]).'\' AND '.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).$alias.'.`compare_emptyarray`=TRUE))';
									break;
								}
							case 'gt':
							case '!gt':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`>'.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`>'.((float) $cur_value[1]);
									break;
								}
							case 'gte':
							case '!gte':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`>='.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`>='.((float) $cur_value[1]);
									break;
								}
							case 'lt':
							case '!lt':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`<'.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`<'.((float) $cur_value[1]);
									break;
								}
							case 'lte':
							case '!lte':
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`cdate`<='.((float) $cur_value[1]);
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e.`mdate`<='.((float) $cur_value[1]);
									break;
								}
							case 'array':
							case '!array':
								if (!($type_is_not xor $clause_not)) {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= 'MATCH (e.`varlist`) AGAINST (\'+'.mysqli_real_escape_string($this->link, $cur_value[0]).'\' IN BOOLEAN MODE)';
								}
								break;
						}
					}
				}
				if ( $cur_query ) {
					if ($cur_selector_query) {
						$cur_selector_query .= $type_is_or ? ' OR ' : ' AND ';
					}
					$cur_selector_query .= $cur_query;
				}
			}
			if ($cur_selector_query) {
				$query_parts[] = $cur_selector_query;
			}
		}

		switch ($sort) {
			case 'guid':
				$sort = 'e.`guid`';
				break;
			case 'mdate':
				$sort = 'e.`mdate`';
				break;
			case 'cdate':
			default:
				$sort = 'e.`cdate`';
				break;
		}
		if ($query_parts) {
			if ($subquery) {
				$query = "((".implode(') AND (', $query_parts)."))";
			} else {
				if ($data_aliases) {
					foreach ($data_aliases as &$cur_alias) {
						$cur_alias = '`'.$this->prefix.'data'.$etype.'` '.$cur_alias;
					}
					unset($cur_alias);
					$data_part = ', '.implode(', ', $data_aliases);
				} else {
					$data_part = '';
				}
				$query = "SELECT e.`guid`, e.`tags`, e.`cdate`, e.`mdate`, d.`name`, d.`value` FROM `{$this->prefix}entities{$etype}` e LEFT JOIN `{$this->prefix}data{$etype}` d ON e.`guid`=d.`guid`{$data_part} WHERE (".implode(') AND (', $query_parts).") ORDER BY ".(isset($options['reverse']) && $options['reverse'] ? $sort.' DESC' : $sort).";";
			}
		} else {
			if ($subquery) {
				$query = '';
			} else {
				$query = "SELECT e.`guid`, e.`tags`, e.`cdate`, e.`mdate`, d.`name`, d.`value` FROM `{$this->prefix}entities{$etype}` e LEFT JOIN `{$this->prefix}data{$etype}` d ON e.`guid`=d.`guid` ORDER BY ".(isset($options['reverse']) && $options['reverse'] ? $sort.' DESC' : $sort).";";
			}
		}

		return $query;
	}

	public function getEntities() {
		if (!$this->connected) {
			throw new Exceptions\UnableToConnectException();
		}
		// Set up options and selectors.
		$selectors = func_get_args();
		if (!$selectors) {
			$options = $selectors = [];
		} else {
			$options = $selectors[0];
			unset($selectors[0]);
		}
		foreach ($selectors as $key => $selector) {
			if (!$selector || (count($selector) === 1 && isset($selector[0]) && in_array($selector[0], ['&', '!&', '|', '!|']))) {
				unset($selectors[$key]);
				continue;
			}
			if (!isset($selector[0]) || !in_array($selector[0], ['&', '!&', '|', '!|'])) {
				throw new Exceptions\InvalidParametersException('Invalid query selector passed: '.print_r($selector, true));
			}
		}

		$entities = [];
		$class = isset($options['class']) ? $options['class'] : Entity;
		$etype_dirty = isset($options['etype']) ? $options['etype'] : $class::etype;

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
					return [$entity];
				}
			}
		}

		$this->formatSelectors($selectors);
		$result = $this->query($this->makeEntityQuery($options, $selectors, $etype_dirty), $etype_dirty);

		$row = mysqli_fetch_row($result);
		while ($row) {
			$guid = (int) $row[0];
			$tags = $row[1];
			$data = ['cdate' => (float) $row[2], 'mdate' => (float) $row[3]];
			// Serialized data.
			$sdata = [];
			if (isset($row[4])) {
				// This do will keep going and adding the data until the
				// next entity is reached. $row will end on the next entity.
				do {
					$sdata[$row[4]] = $row[5];
					$row = mysqli_fetch_row($result);
				} while ((int) $row[0] === $guid);
			} else {
				// Make sure that $row is incremented :)
				$row = mysqli_fetch_row($result);
			}
			// Check all conditions.
			if ($this->checkData($data, $sdata, $selectors)) {
				if (isset($options['offset']) && ($ocount < $options['offset'])) {
					// We must be sure this entity is actually a match before
					// incrementing the offset.
					$ocount++;
					continue;
				}
				if ($this->config->cache['value']) {
					$entity = $this->pull_cache($guid, $class);
				} else {
					$entity = null;
				}
				if (!isset($entity) || $data['mdate'] > $entity->mdate) {
					$entity = call_user_func([$class, 'factory']);
					$entity->guid = $guid;
					if ($tags !== '') {
						$entity->tags = explode(' ', $tags);
					}
					$entity->putData($data, $sdata);
					if ($this->config->cache['value']) {
						$this->pushCache($entity, $class);
					}
				}
                if (isset($options['skip_ac'])) {
                    $entity->_nUseSkipAC = (bool) $options['skip_ac'];
				}
				$entities[] = $entity;
				$count++;
				if (isset($options['limit']) && $count >= $options['limit']) {
					break;
				}
			}
		}

		mysqli_free_result($result);

		return $entities;
	}

	private function checkData(&$data, &$sdata, $selectors) {
		foreach ($selectors as $cur_selector) {
			$pass = false;
			foreach ($cur_selector as $key => $value) {
				if ($key === 0) {
					$type = $value;
					$type_is_not = ($type == '!&' || $type == '!|');
					$type_is_or = ($type == '|' || $type == '!|');
					$pass = !$type_is_or;
					continue;
				}
				if (is_numeric($key)) {
					$tmpArr = [$value];
					$pass = $this->checkData($data, $sdata, $tmpArr);
				} else {
					$clause_not = $key[0] === '!';
					if ($key === 'ref' || $key === '!ref') {
						// Handled by the query.
						$pass = true;
					} elseif ($key === 'guid' || $key === '!guid' || $key === 'tag' || $key === '!tag') {
						// Handled by the query.
						$pass = true;
					} elseif ($key === 'isset' || $key === '!isset') {
						// Handled by the query.
						$pass = true;
					} elseif ($key === 'strict' || $key === '!strict') {
						// Handled by the query.
						$pass = true;
					} elseif ($key === 'like' || $key === '!like') {
						// Handled by the query.
						$pass = true;
					} elseif ($key === 'pmatch' || $key === '!pmatch') {
						// Handled by the query.
						$pass = true;
					} else {
						// Check if it doesn't pass any for &, check if it
						// passes any for |.
						foreach ($value as $cur_value) {
							if (($key === 'data' || $key === '!data') && ($cur_value[1] === true || $cur_value[1] === false || $cur_value[1] === 1 || $cur_value[1] === 0 || $cur_value[1] === -1 || $cur_value[1] === [])) {
								// Handled by the query.
								$pass = true;
							} else {
								// Unserialize the data for this variable.
								if (isset($sdata[$cur_value[0]])) {
									$data[$cur_value[0]] = unserialize($sdata[$cur_value[0]]);
									unset($sdata[$cur_value[0]]);
								}
								if (!key_exists($cur_value[0], $data)) {
									$pass = false;
								} else {
									switch ($key) {
										case 'data':
										case '!data':
											// If we get here, it's not one of those simple data values above.
											$pass = (($data[$cur_value[0]] == $cur_value[1]) xor ($type_is_not xor $clause_not));
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
									}
								}
							}
							if (!($type_is_or xor $pass)) {
								break;
							}
						}
					}
				}
				if (!($type_is_or xor $pass)) {
					break;
				}
			}
			if (!$pass) {
				return false;
			}
		}
		return true;
	}

	public function getUID($name) {
		if (!$name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID');
		}
		$result = $this->query("SELECT `cur_uid` FROM `{$this->prefix}uids` WHERE `name`='".mysqli_real_escape_string($this->link, $name)."';");
		$row = mysqli_fetch_row($result);
		mysqli_free_result($result);
		return isset($row[0]) ? (int) $row[0] : null;
	}

	public function import($filename) {
		if (!$fhandle = fopen($filename, 'r')) {
			throw new Exceptions\InvalidParametersException('Provided filename is unreadable.');
		}
		$line = '';
		$data = [];
		while (!feof($fhandle)) {
			$line .= fgets($fhandle, 8192);
			if (substr($line, -1) != "\n") {
				continue;
			}
			if (preg_match('/^\s*#/S', $line)) {
				$line = '';
				continue;
			}
			$matches = [];
			if (preg_match('/^\s*{(\d+)}<([\w-_]+)>\[([\w,]*)\]\s*$/S', $line, $matches)) {
				// Save the current entity.
				if ($guid) {
					$this->query("REPLACE INTO `{$this->prefix}guids` (`guid`) VALUES ({$guid});");
					$this->query("REPLACE INTO `{$this->prefix}entities_{$etype}` (`guid`, `tags`, `varlist`, `cdate`, `mdate`) VALUES ({$guid}, '".mysqli_real_escape_string($this->link, implode(' ', explode(',', $tags)))."', '".mysqli_real_escape_string($this->link, implode(' ', array_keys($data)))."', ".unserialize($data['cdate']).", ".unserialize($data['mdate']).");", $etype);
					$this->query("DELETE FROM `{$this->prefix}data_{$etype}` WHERE `guid`='{$guid}';");
					unset($data['cdate'], $data['mdate']);
					if ($data) {
						$query = "INSERT INTO `{$this->prefix}data_{$etype}` (`guid`, `name`, `value`,`references`,`compare_true`, `compare_one`, `compare_zero`, `compare_negone`, `compare_emptyarray`, `compare_string`) VALUES ";
						foreach ($data as $name => $value) {
							preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
							$uvalue = unserialize($value);
							$query .= sprintf("(%u, '%s', '%s', '%s', %s, %s, %s, %s, %s, %s),",
								$guid,
								mysqli_real_escape_string($this->link, $name),
								mysqli_real_escape_string($this->link, $value),
								mysqli_real_escape_string($this->link, implode(' ', $references[1])),
								$uvalue == true ? 'TRUE' : 'FALSE',
								(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
								(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
								(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
								$uvalue == [] ? 'TRUE' : 'FALSE',
								is_string($uvalue) ? '\''.mysqli_real_escape_string($this->link, $uvalue).'\'' : 'NULL');
						}
						$query = substr($query, 0, -1).';';
						$this->query($query);
					}
					$guid = null;
					$tags = [];
					$data = [];
				}
				// Record the new entity's info.
				$guid = (int) $matches[1];
				$etype = $matches[2];
				$tags = $matches[3];
			} elseif (preg_match('/^\s*([\w,]+)\s*=\s*(\S.*\S)\s*$/S', $line, $matches)) {
				// Add the variable to the new entity.
				if ($guid) {
					$data[$matches[1]] = json_decode($matches[2]);
				}
			} elseif (preg_match('/^\s*<([^>]+)>\[(\d+)\]\s*$/S', $line, $matches)) {
				// Add the UID.
				$this->query("INSERT INTO `{$this->prefix}uids` (`name`, `cur_uid`) VALUES ('".mysqli_real_escape_string($this->link, $matches[1])."', ".((int) $matches[2]).") ON DUPLICATE KEY UPDATE `cur_uid`=".((int) $matches[2]).";");
			}
			$line = '';
			// Clear the entity cache.
			$this->entityCache = [];
		}
		// Save the last entity.
		if ($guid) {
			$this->query("REPLACE INTO `{$this->prefix}guids` (`guid`) VALUES ({$guid});");
			$this->query("REPLACE INTO `{$this->prefix}entities_{$etype}` (`guid`, `tags`, `varlist`, `cdate`, `mdate`) VALUES ({$guid}, '".mysqli_real_escape_string($this->link, implode(' ', explode(',', $tags)))."', '".mysqli_real_escape_string($this->link, implode(' ', array_keys($data)))."', ".unserialize($data['cdate']).", ".unserialize($data['mdate']).");", $etype);
			$this->query("DELETE FROM `{$this->prefix}data_{$etype}` WHERE `guid`='{$guid}';");
			unset($data['cdate'], $data['mdate']);
			if ($data) {
				$query = "INSERT INTO `{$this->prefix}data_{$etype}` (`guid`, `name`, `value`,`references`,`compare_true`, `compare_one`, `compare_zero`, `compare_negone`, `compare_emptyarray`, `compare_string`) VALUES ";
				foreach ($data as $name => $value) {
					preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
					$uvalue = unserialize($value);
					$query .= sprintf("(%u, '%s', '%s', '%s', %s, %s, %s, %s, %s, %s),",
						$guid,
						mysqli_real_escape_string($this->link, $name),
						mysqli_real_escape_string($this->link, $value),
						mysqli_real_escape_string($this->link, implode(' ', $references[1])),
						$uvalue == true ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
						$uvalue == [] ? 'TRUE' : 'FALSE',
						is_string($uvalue) ? '\''.mysqli_real_escape_string($this->link, $uvalue).'\'' : 'NULL');
				}
				$query = substr($query, 0, -1).';';
				$this->query($query);
			}
		}
		return true;
	}

	public function newUID($name) {
		if (!$name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID');
		}
		$this->query("SELECT GET_LOCK('{$this->prefix}uids_".mysqli_real_escape_string($this->link, $name)."', 10);");
		$this->query("INSERT INTO `{$this->prefix}uids` (`name`, `cur_uid`) VALUES ('".mysqli_real_escape_string($this->link, $name)."', 1) ON DUPLICATE KEY UPDATE `cur_uid`=`cur_uid`+1;");
		$result = $this->query("SELECT `cur_uid` FROM `{$this->prefix}uids` WHERE `name`='".mysqli_real_escape_string($this->link, $name)."';");
		$row = mysqli_fetch_row($result);
		mysqli_free_result($result);
		$this->query("SELECT RELEASE_LOCK('{$this->prefix}uids_".mysqli_real_escape_string($this->link, $name)."');");
		return isset($row[0]) ? (int) $row[0] : null;
	}

	public function renameUID($old_name, $new_name) {
		if (!$old_name || !$new_name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID');
		}
		$this->query("UPDATE `{$this->prefix}uids` SET `name`='".mysqli_real_escape_string($this->link, $new_name)."' WHERE `name`='".mysqli_real_escape_string($this->link, $old_name)."';");
		return true;
	}

	/**
	 * @todo Check that the big insert query doesn't fail.
	 */
	public function saveEntity(&$entity) {
		// Save the created date.
		if ( !isset($entity->guid) ) {
			$entity->cdate = microtime(true);
		}
		// Save the modified date.
		$entity->mdate = microtime(true);
		$data = $entity->getData();
		$sdata = $entity->getSData();
		$varlist = array_merge(array_keys($data), array_keys($sdata));
		$class = get_class($entity);
		$etype_dirty = $class::etype;
		$etype = '_'.mysqli_real_escape_string($this->link, $etype_dirty);
		if ( !isset($entity->guid) ) {
			while (true) {
				$new_id = mt_rand(1, pow(2, 53)); // 2^53 is the maximum number in JavaScript (http://ecma262-5.com/ELS5_HTML.htm#Section_8.5)
				// That number might be too big on some machines. :(
				if ($new_id < 1) {
					$new_id = rand(1, 0x7FFFFFFF);
				}
				$result = $this->query("SELECT `guid` FROM `{$this->prefix}guids` WHERE `guid`='{$new_id}';", $etype_dirty);
				$row = mysqli_fetch_row($result);
				mysqli_free_result($result);
				if (!isset($row[0])) {
					break;
				}
			}
			$entity->guid = $new_id;
			$this->query("INSERT INTO `{$this->prefix}guids` (`guid`) VALUES ({$entity->guid});");
			$this->query("INSERT INTO `{$this->prefix}entities{$etype}` (`guid`, `tags`, `varlist`, `cdate`, `mdate`) VALUES ({$entity->guid}, '".mysqli_real_escape_string($this->link, implode(' ', array_diff($entity->tags, [''])))."', '".mysqli_real_escape_string($this->link, implode(' ', $varlist))."', ".((float) $data['cdate']).", ".((float) $data['mdate']).");", $etype_dirty);
			unset($data['cdate'], $data['mdate']);
			$values = [];
			foreach ($data as $name => $value) {
				$svalue = serialize($value);
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $svalue, $references, PREG_PATTERN_ORDER);
				$values[] = sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s)',
					$entity->guid,
					mysqli_real_escape_string($this->link, $name),
					mysqli_real_escape_string($this->link, $svalue),
					mysqli_real_escape_string($this->link, implode(' ', $references[1])),
					$value == true ? 'TRUE' : 'FALSE',
					(!is_object($value) && $value == 1) ? 'TRUE' : 'FALSE',
					(!is_object($value) && $value == 0) ? 'TRUE' : 'FALSE',
					(!is_object($value) && $value == -1) ? 'TRUE' : 'FALSE',
					$value == [] ? 'TRUE' : 'FALSE',
					is_string($value) ? '\''.mysqli_real_escape_string($this->link, $value).'\'' : 'NULL');
			}
			foreach ($sdata as $name => $value) {
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
				$uvalue = unserialize($value);
				$values[] = sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s)',
					$entity->guid,
					mysqli_real_escape_string($this->link, $name),
					mysqli_real_escape_string($this->link, $value),
					mysqli_real_escape_string($this->link, implode(' ', $references[1])),
					$uvalue == true ? 'TRUE' : 'FALSE',
					(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
					(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
					(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
					$uvalue == [] ? 'TRUE' : 'FALSE',
					is_string($uvalue) ? '\''.mysqli_real_escape_string($this->link, $uvalue).'\'' : 'NULL');
			}
			$query = "INSERT INTO `{$this->prefix}data{$etype}` (`guid`, `name`, `value`, `references`, `compare_true`, `compare_one`, `compare_zero`, `compare_negone`, `compare_emptyarray`, `compare_string`) VALUES ";
			$query .= implode(',', $values).';';
			$this->query($query);
		} else {
			// Removed any cached versions of this entity.
			if ($this->config->cache['value']) {
				$this->cleanCache($entity->guid);
			}
			$this->query("UPDATE `{$this->prefix}entities{$etype}` SET `tags`='".mysqli_real_escape_string($this->link, implode(' ', array_diff($entity->tags, [''])))."', `varlist`='".mysqli_real_escape_string($this->link, implode(' ', $varlist))."', `mdate`=".((float) $data['mdate'])." WHERE `guid`='".((int) $entity->guid)."';", $etype_dirty);
			$this->query("DELETE FROM `{$this->prefix}data{$etype}` WHERE `guid`='".((int) $entity->guid)."';");
			unset($data['cdate'], $data['mdate']);
			$values = [];
			foreach ($data as $name => $value) {
				$svalue = serialize($value);
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $svalue, $references, PREG_PATTERN_ORDER);
				$values[] = sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s)',
					(int) $entity->guid,
					mysqli_real_escape_string($this->link, $name),
					mysqli_real_escape_string($this->link, $svalue),
					mysqli_real_escape_string($this->link, implode(' ', $references[1])),
					$value == true ? 'TRUE' : 'FALSE',
					(!is_object($value) && $value == 1) ? 'TRUE' : 'FALSE',
					(!is_object($value) && $value == 0) ? 'TRUE' : 'FALSE',
					(!is_object($value) && $value == -1) ? 'TRUE' : 'FALSE',
					$value == [] ? 'TRUE' : 'FALSE',
					is_string($value) ? '\''.mysqli_real_escape_string($this->link, $value).'\'' : 'NULL');
			}
			foreach ($sdata as $name => $value) {
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
				$uvalue = unserialize($value);
				$values[] = sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s)',
					(int) $entity->guid,
					mysqli_real_escape_string($this->link, $name),
					mysqli_real_escape_string($this->link, $value),
					mysqli_real_escape_string($this->link, implode(' ', $references[1])),
					$uvalue == true ? 'TRUE' : 'FALSE',
					(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
					(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
					(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
					$uvalue == [] ? 'TRUE' : 'FALSE',
					is_string($uvalue) ? '\''.mysqli_real_escape_string($this->link, $uvalue).'\'' : 'NULL');
			}
			$query = "INSERT INTO `{$this->prefix}data{$etype}` (`guid`, `name`, `value`, `references`, `compare_true`, `compare_one`, `compare_zero`, `compare_negone`, `compare_emptyarray`, `compare_string`) VALUES ";
			$query .= implode(',', $values).';';
			$this->query($query);
		}
		// Cache the entity.
		if ($this->config->cache['value']) {
			$class = get_class($entity);
			// Replace hook override in the class name.
			if (strpos($class, '\\SciActive\\HookOverride_') === 0) {
				$class = substr($class, 14);
			}
			$this->pushCache($entity, $class);
		}
		return true;
	}

	public function setUID($name, $value) {
		if (!$name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID');
		}
		$this->query("INSERT INTO `{$this->prefix}uids` (`name`, `cur_uid`) VALUES ('".mysqli_real_escape_string($this->link, $name)."', ".((int) $value).") ON DUPLICATE KEY UPDATE `cur_uid`=".((int) $value).";");
		return true;
	}

	private function addDataAlias(&$data_aliases) {
		do {
			$new_alias = 'd'.rand(1, 9999999999);
		} while (in_array($new_alias, $data_aliases));
		$data_aliases[] = $new_alias;
		return $new_alias;
	}
}
