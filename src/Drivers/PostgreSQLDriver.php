<?php namespace Nymph\Drivers;
/**
 * PostgreSQLDriver class.
 *
 * @package Nymph
 * @license http://www.gnu.org/licenses/lgpl.html
 * @author Hunter Perrin <hperrin@gmail.com>
 * @copyright SciActive.com
 * @link http://sciactive.com/
 */
use Nymph\Exceptions;

/**
 * PostgreSQL ORM based Nymph driver.
 *
 * @package Nymph
 */
class PostgreSQLDriver implements DriverInterface {
	use DriverTrait {
		DriverTrait::__construct as private __traitConstruct;
	}
	/**
	 * The PostgreSQL link identifier for this instance.
	 *
	 * @access private
	 * @var mixed
	 */
	private $link = null;
	/**
	 * Whether to use PL/Perl.
	 *
	 * @access private
	 * @var string
	 */
	private $usePLPerl;
	private $prefix;

	public function __construct($NymphConfig) {
		$this->__traitConstruct($NymphConfig);
		$this->usePLPerl = $this->config->use_plperl['value'];
		$this->prefix = $this->config->PostgreSQL->prefix['value'];
	}

	/**
	 * Disconnect from the database on destruction.
	 */
	public function __destruct() {
		$this->disconnect();
	}

	/**
	 * Connect to the PostgreSQL database.
	 *
	 * @return bool Whether this instance is connected to a PostgreSQL database after the method has run.
	 */
	public function connect() {
		// Check that the PostgreSQL extension is installed.
		if (!is_callable('pg_connect')) {
			throw new Exceptions\UnableToConnectException('PostgreSQL PHP extension is not available. It probably has not been installed. Please install and configure it in order to use PostgreSQL.');
		}
		$connection_type = $this->config->PostgreSQL->connection_type['value'];
		$host = $this->config->PostgreSQL->host['value'];
		$port = $this->config->PostgreSQL->port['value'];
		$user = $this->config->PostgreSQL->user['value'];
		$password = $this->config->PostgreSQL->password['value'];
		$database = $this->config->PostgreSQL->database['value'];
		// Connecting, selecting database
		if (!$this->connected) {
			if ($connection_type == 'host') {
				$connect_string = 'host=\''.addslashes($host).'\' port=\''.addslashes($port).'\'dbname=\''.addslashes($database).'\' user=\''.addslashes($user).'\' password=\''.addslashes($password).'\' connect_timeout=5';
			} else {
				$connect_string = 'dbname=\''.addslashes($database).'\' user=\''.addslashes($user).'\' password=\''.addslashes($password).'\' connect_timeout=5';
			}
			if ($this->config->PostgreSQL->allow_persistent['value']) {
				$this->link = pg_connect($connect_string.' options=\'-c enable_hashjoin=off -c enable_mergejoin=off\'');
			} else {
				$this->link = pg_connect($connect_string.' options=\'-c enable_hashjoin=off -c enable_mergejoin=off\'', PGSQL_CONNECT_FORCE_NEW); // Don't think this is necessary, but if put in options, will guarantee connection is new. " -c timezone='.round(rand(10001000, 10009999)).'"
			}
			if ($this->link) {
				$this->connected = true;
			} else {
				$this->connected = false;
				if ($host == 'localhost' && $user == 'nymph' && $password == 'password' && $database == 'nymph' && $connection_type == 'host') {
					throw new Exceptions\NotConfiguredException();
				} else {
					throw new Exceptions\UnableToConnectException('Could not connect: ' . pg_last_error());
				}
			}
		}
		return $this->connected;
	}

	/**
	 * Disconnect from the PostgreSQL database.
	 *
	 * @return bool Whether this instance is connected to a PostgreSQL database after the method has run.
	 */
	public function disconnect() {
		if ($this->connected) {
			if (is_resource($this->link)) {
				pg_close($this->link);
			}
			$this->connected = false;
		}
		return $this->connected;
	}

	/**
	 * Create entity tables in the database.
	 *
	 * @param string $etype The entity type to create a table for. If this is blank, the default tables are created.
	 */
	private function createTables($etype = null) {
		$this->query('ROLLBACK; BEGIN;');
		if (isset($etype)) {
			$etype =  '_'.pg_escape_string($this->link, $etype);
			// Create the entity table.
			$this->query("CREATE TABLE IF NOT EXISTS \"{$this->prefix}entities{$etype}\" ( guid bigint NOT NULL, tags text[], varlist text[], cdate numeric(18,6) NOT NULL, mdate numeric(18,6) NOT NULL, PRIMARY KEY (guid) ) WITH ( OIDS=FALSE ); "
				. "ALTER TABLE \"{$this->prefix}entities{$etype}\" OWNER TO \"".pg_escape_string($this->link, $this->config->PostgreSQL->user['value'])."\"; "
				. "DROP INDEX IF EXISTS \"{$this->prefix}entities{$etype}_id_cdate\"; "
				. "CREATE INDEX \"{$this->prefix}entities{$etype}_id_cdate\" ON \"{$this->prefix}entities{$etype}\" USING btree (cdate); "
				. "DROP INDEX IF EXISTS \"{$this->prefix}entities{$etype}_id_mdate\"; "
				. "CREATE INDEX \"{$this->prefix}entities{$etype}_id_mdate\" ON \"{$this->prefix}entities{$etype}\" USING btree (mdate); "
				. "DROP INDEX IF EXISTS \"{$this->prefix}entities{$etype}_id_tags\"; "
				. "CREATE INDEX \"{$this->prefix}entities{$etype}_id_tags\" ON \"{$this->prefix}entities{$etype}\" USING gin (tags); "
				. "DROP INDEX IF EXISTS \"{$this->prefix}entities{$etype}_id_varlist\"; "
				. "CREATE INDEX \"{$this->prefix}entities{$etype}_id_varlist\" ON \"{$this->prefix}entities{$etype}\" USING gin (varlist);");
			// Create the data table.
			$this->query("CREATE TABLE IF NOT EXISTS \"{$this->prefix}data{$etype}\" ( guid bigint NOT NULL, \"name\" text NOT NULL, \"value\" text NOT NULL, \"references\" bigint[], compare_true boolean, compare_one boolean, compare_zero boolean, compare_negone boolean, compare_emptyarray boolean, compare_string text, PRIMARY KEY (guid, \"name\"), FOREIGN KEY (guid) REFERENCES \"{$this->prefix}entities{$etype}\" (guid) MATCH SIMPLE ON UPDATE NO ACTION ON DELETE CASCADE ) WITH ( OIDS=FALSE ); "
				. "ALTER TABLE \"{$this->prefix}data{$etype}\" OWNER TO \"".pg_escape_string($this->link, $this->config->PostgreSQL->user['value'])."\"; "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_guid\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_guid\" ON \"{$this->prefix}data{$etype}\" USING btree (\"guid\"); "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_name\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_name\" ON \"{$this->prefix}data{$etype}\" USING btree (\"name\"); "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_references\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_references\" ON \"{$this->prefix}data{$etype}\" USING gin (\"references\"); "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_guid_name_compare_true\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_guid_name_compare_true\" ON \"{$this->prefix}data{$etype}\" USING btree (\"guid\", \"name\") WHERE \"compare_true\" = TRUE; "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_guid_name_not_compare_true\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_guid_name_not_compare_true\" ON \"{$this->prefix}data{$etype}\" USING btree (\"guid\", \"name\") WHERE \"compare_true\" <> TRUE; "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_guid_name__user\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_guid_name__user\" ON \"{$this->prefix}data{$etype}\" USING btree (\"guid\") WHERE \"name\" = 'user'::text; "
				. "DROP INDEX IF EXISTS \"{$this->prefix}data{$etype}_id_guid_name__group\"; "
				. "CREATE INDEX \"{$this->prefix}data{$etype}_id_guid_name__group\" ON \"{$this->prefix}data{$etype}\" USING btree (\"guid\") WHERE \"name\" = 'group'::text;");
		} else {
			// Create the GUID table.
			$this->query("CREATE TABLE IF NOT EXISTS \"{$this->prefix}guids\" ( \"guid\" bigint NOT NULL, PRIMARY KEY (\"guid\")); "
				. "ALTER TABLE \"{$this->prefix}guids\" OWNER TO \"".pg_escape_string($this->link, $this->config->PostgreSQL->user['value'])."\";");
			// Create the UID table.
			$this->query("CREATE TABLE IF NOT EXISTS \"{$this->prefix}uids\" ( \"name\" text NOT NULL, cur_uid bigint NOT NULL, PRIMARY KEY (\"name\") ) WITH ( OIDS = FALSE ); "
				. "ALTER TABLE \"{$this->prefix}uids\" OWNER TO \"".pg_escape_string($this->link, $this->config->PostgreSQL->user['value'])."\";");
			if ($this->usePLPerl) {
				// Create the perl_match function. It's separated into two calls so
				// Postgres will ignore the error if plperl already exists.
				$this->query("CREATE OR REPLACE PROCEDURAL LANGUAGE plperl;");
				$this->query("CREATE OR REPLACE FUNCTION {$this->prefix}match_perl( TEXT, TEXT, TEXT ) RETURNS BOOL AS \$code$ "
					. "my (\$str, \$pattern, \$mods) = @_; "
					. "if (\$pattern eq \'\') { "
						. "return true; "
					. "} "
					. "if (\$mods eq \'\') { "
						. "if (\$str =~ /(\$pattern)/) { "
							. "return true; "
						. "} else { "
							. "return false; "
						. "} "
					. "} else { "
						. "if (\$str =~ /(?\$mods)(\$pattern)/) { "
							. "return true; "
						. "} else { "
							. "return false; "
						. "} "
					. "} \$code$ LANGUAGE plperl IMMUTABLE STRICT COST 10000;");
			}
		}
		$this->query('COMMIT;');
		return true;
	}

	private function query($query, $etype_dirty = null) {
		while (pg_get_result($this->link)) {
			// Clear the connection of all results.
			continue;
		}
		if ( !(pg_send_query($this->link, $query)) ) {
			throw new Exceptions\QueryFailedException('Query failed: ' . pg_last_error(), 0, null, $query);
		}
		if ( !($result = pg_get_result($this->link)) ) {
			throw new Exceptions\QueryFailedException('Query failed: ' . pg_last_error(), 0, null, $query);
		}
		if ($error = pg_result_error_field($result, PGSQL_DIAG_SQLSTATE)) {
			// If the tables don't exist yet, create them.
			if ($error == '42P01' && $this->createTables()) {
				if (isset($etype_dirty)) {
					$this->createTables($etype_dirty);
				}
				if ( !($result = pg_query($this->link, $query)) ) {
					throw new Exceptions\QueryFailedException('Query failed: ' . pg_last_error(), 0, null, $query);
				}
			} else {
				throw new Exceptions\QueryFailedException('Query failed: ' . pg_last_error(), 0, null, $query);
			}
		}
		return $result;
	}

	public function deleteEntityByID($guid, $etype = null) {
		$guid = (int) $guid;
		$etype = isset($etype) ? '_'.pg_escape_string($this->link, $etype) : '';
		$this->query("DELETE FROM \"{$this->prefix}entities{$etype}\" WHERE \"guid\"={$guid}; DELETE FROM \"{$this->prefix}data{$etype}\" WHERE \"guid\"={$guid};");
		$this->query("DELETE FROM \"{$this->prefix}guids\" WHERE \"guid\"={$guid};");
		// Removed any cached versions of this entity.
		if ($this->config->cache['value']) {
			$this->cleanCache($guid);
		}
		return true;
	}

	public function deleteUID($name) {
		if (!$name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID');
		}
		$this->query("DELETE FROM \"{$this->prefix}uids\" WHERE \"name\"='".pg_escape_string($this->link, $name)."';");
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
		$result = $this->query("SELECT * FROM \"{$this->prefix}uids\";");
		$row = pg_fetch_assoc($result);
		while ($row) {
			$row['name'];
			$row['cur_uid'];
			fwrite($fhandle, "<{$row['name']}>[{$row['cur_uid']}]\n");
			// Make sure that $row is incremented :)
			$row = pg_fetch_assoc($result);
		}

		fwrite($fhandle, "\n#\n");
		fwrite($fhandle, "# Entities\n");
		fwrite($fhandle, "#\n\n");

		// Get the etypes.
		$result = $this->query("SELECT relname FROM pg_stat_user_tables ORDER BY relname;");
		$etypes = array();
		$row = pg_fetch_array($result);
		while ($row) {
			if (strpos($row[0], $this->prefix.'entities_') === 0) {
				$etypes[] = substr($row[0], strlen($this->prefix.'entities_'));
			}
			$row = pg_fetch_array($result);
		}

		foreach ($etypes as $etype) {
			// Export entities.
			$result = $this->query("SELECT e.*, d.\"name\" AS \"dname\", d.\"value\" AS \"dvalue\" FROM \"{$this->prefix}entities_{$etype}\" e LEFT JOIN \"{$this->prefix}data_{$etype}\" d ON e.\"guid\"=d.\"guid\" ORDER BY e.\"guid\";");
			$row = pg_fetch_assoc($result);
			while ($row) {
				$guid = (int) $row['guid'];
				$tags = explode(',', substr($row['tags'], 1, -1));
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
						$row = pg_fetch_assoc($result);
					} while ((int) $row['guid'] === $guid);
				} else {
					// Make sure that $row is incremented :)
					$row = pg_fetch_assoc($result);
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
		$result = $this->query("SELECT * FROM \"{$this->prefix}uids\";");
		$row = pg_fetch_assoc($result);
		while ($row) {
			$row['name'];
			$row['cur_uid'];
			echo "<{$row['name']}>[{$row['cur_uid']}]\n";
			// Make sure that $row is incremented :)
			$row = pg_fetch_assoc($result);
		}

		echo "\n#\n";
		echo "# Entities\n";
		echo "#\n\n";

		// Get the etypes.
		$result = $this->query("SELECT relname FROM pg_stat_user_tables ORDER BY relname;");
		$etypes = array();
		$row = pg_fetch_array($result);
		while ($row) {
			if (strpos($row[0], $this->prefix.'entities_') === 0) {
				$etypes[] = substr($row[0], strlen($this->prefix.'entities_'));
			}
			$row = pg_fetch_array($result);
		}

		foreach ($etypes as $etype) {
			// Export entities.
			$result = $this->query("SELECT e.*, d.\"name\" AS \"dname\", d.\"value\" AS \"dvalue\" FROM \"{$this->prefix}entities_{$etype}\" e LEFT JOIN \"{$this->prefix}data_{$etype}\" d ON e.\"guid\"=d.\"guid\" ORDER BY e.\"guid\";");
			$row = pg_fetch_assoc($result);
			while ($row) {
				$guid = (int) $row['guid'];
				$tags = explode(',', substr($row['tags'], 1, -1));
				$cdate = (float) $row['cdate'];
				$mdate = (float) $row['mdate'];
				echo "{{$guid}}<{$etype}>[".implode(',', $tags)."]\n";
				echo "\tcdate=".json_encode(serialize($cdate))."\n";
				echo "\tmdate=".json_encode(serialize($mdate))."\n";
				if (isset($row['dname'])) {
					// This do will keep going and adding the data until the
					// next entity is reached. $row will end on the next entity.
					do {
						echo "\t{$row['dname']}=".json_encode(($row['dvalue'][0] === '~' ? stripcslashes(substr($row['dvalue'], 1)) : $row['dvalue']))."\n";
						$row = pg_fetch_assoc($result);
					} while ((int) $row['guid'] === $guid);
				} else {
					// Make sure that $row is incremented :)
					$row = pg_fetch_assoc($result);
				}
			}
		}
		return true;
	}

	public function getEntities() {
		if (!$this->connected) {
			throw new Exceptions\UnableToConnectException();
		}
		// Set up options and selectors.
		$selectors = func_get_args();
		if (!$selectors) {
			$options = $selectors = array();
		} else {
			$options = $selectors[0];
			unset($selectors[0]);
		}
		foreach ($selectors as $key => $selector) {
			if (!$selector || (count($selector) === 1 && isset($selector[0]) && in_array($selector[0], array('&', '!&', '|', '!|')))) {
				unset($selectors[$key]);
				continue;
			}
			if (!isset($selector[0]) || !in_array($selector[0], array('&', '!&', '|', '!|'))) {
				throw new Exceptions\InvalidParametersException('Invalid query selector passed: '.print_r($selector, true));
			}
		}

		$entities = array();
		$class = isset($options['class']) ? $options['class'] : Entity;
		if (isset($options['etype'])) {
			$etype_dirty = $options['etype'];
			$etype = '_'.pg_escape_string($this->link, $etype_dirty);
		} else {
			$etype_dirty = $class::etype;
			$etype = '_'.pg_escape_string($this->link, $etype_dirty);
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
				if ((array) $value !== $value) {
					$value = array(array($value));
				} elseif ((array) $value[0] !== $value[0]) {
					$value = array($value);
				}
				// Any options having to do with data only return if the entity has
				// the specified variables.
				foreach ($value as $cur_value) {
					$query_made = false;
					switch ($key) {
						case 'guid':
						case '!guid':
							foreach ($cur_value as $cur_guid) {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid"='.(int) $cur_guid;
							}
							break;
						case 'tag':
						case '!tag':
							foreach ($cur_value as $cur_tag) {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'\'{'.pg_escape_string($this->link, $cur_tag).'}\' <@ e."tags"';
							}
							break;
						case 'isset':
						case '!isset':
							foreach ($cur_value as $cur_var) {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= '('.(($type_is_not xor $clause_not) ? 'NOT ' : '' ).'\'{'.pg_escape_string($this->link, $cur_var).'}\' <@ e."varlist"';
								if ($type_is_not xor $clause_not) {
									$cur_query .= ' OR e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_var).'\' AND "value"=\'N;\')';
								}
								$cur_query .= ')';
							}
							break;
						case 'ref':
						case '!ref':
							$guids = array();
							if ((array) $cur_value[1] === $cur_value[1]) {
								foreach ($cur_value[1] as $cur_entity) {
									if ((object) $cur_entity === $cur_entity) {
										$guids[] = (int) $cur_entity->guid;
									} elseif ((array) $cur_entity === $cur_entity) {
										$guids[] = (int) $cur_entity['guid'];
									} else {
										$guids[] = (int) $cur_entity;
									}
								}
							} elseif ((object) $cur_value[1] === $cur_value[1]) {
								$guids[] = (int) $cur_value[1]->guid;
							} elseif ((array) $cur_value[1] === $cur_value[1]) {
								$guids[] = (int) $cur_value[1]['guid'];
							} else {
								$guids[] = (int) $cur_value[1];
							}
							if ($guids) {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND (';
								//$cur_query .= '(POSITION(\'a:3:{i:0;s:22:"nymph_entity_reference";i:1;i:';
								//$cur_query .= implode(';\' IN "value") != 0) '.($type_is_or ? 'OR' : 'AND').' (POSITION(\'a:3:{i:0;s:22:"nymph_entity_reference";i:1;i:', $guids);
								//$cur_query .= ';\' IN "value") != 0)';
								$cur_query .= '\'{';
								$cur_query .= implode('}\' <@ "references"'.($type_is_or ? ' OR ' : ' AND ').'\'{', $guids);
								$cur_query .= '}\' <@ "references"';
								$cur_query .=  '))';
							}
							break;
						case 'strict':
						case '!strict':
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
							} else {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								if (is_callable(array($cur_value[1], 'toReference'))) {
									$svalue = serialize($cur_value[1]->toReference());
								} else {
									$svalue = serialize($cur_value[1]);
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "value"=\''.pg_escape_string($this->link, (strpos($svalue, "\0") !== false ? '~'.addcslashes($svalue, chr(0).'\\') : $svalue)).'\')';
							}
							break;
						case 'like':
						case '!like':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e."cdate" LIKE \''.pg_escape_string($this->link, $cur_value[1]).'\')';
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e."mdate" LIKE \''.pg_escape_string($this->link, $cur_value[1]).'\')';
								break;
							} else {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_string" ILIKE \''.pg_escape_string($this->link, $cur_value[1]).'\')';
							}
							break;
						case 'pmatch':
						case '!pmatch':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e."cdate" ~ \''.pg_escape_string($this->link, $cur_value[1]).'\')';
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'(e."mdate" ~ \''.pg_escape_string($this->link, $cur_value[1]).'\')';
								break;
							} else {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_string" ~ \''.pg_escape_string($this->link, $cur_value[1]).'\')';
							}
							break;
						case 'match':
						case '!match':
							if ($this->usePLPerl) {
								if ($cur_value[0] == 'cdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'match_perl(e."cdate", \''.pg_escape_string($this->link, $regex).'\', \''.pg_escape_string($this->link, $mods).'\')';
									break;
								} elseif ($cur_value[0] == 'mdate') {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'match_perl(e."mdate", \''.pg_escape_string($this->link, $regex).'\', \''.pg_escape_string($this->link, $mods).'\')';
									break;
								} else {
									if ( $cur_query ) {
										$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
									}
									$lastslashpos = strrpos($cur_value[1], '/');
									$regex = substr($cur_value[1], 1, $lastslashpos - 1);
									$mods = substr($cur_value[1], $lastslashpos + 1) ?: '';
									$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_string" IS NOT NULL AND '.$this->prefix.'match_perl("compare_string", \''.pg_escape_string($this->link, $regex).'\', \''.pg_escape_string($this->link, $mods).'\'))';
								}
							} else {
								if (!($type_is_not xor $clause_not)) {
									if ( $cur_query ) {
										$cur_query .= $type_is_or ? ' OR ' : ' AND ';
									}
									$cur_query .= '\'{'.pg_escape_string($this->link, $cur_value[0]).'}\' <@ e."varlist"';
								}
								break;
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
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_true"='.($cur_value[1] ? 'TRUE' : 'FALSE').')';
								break;
							} elseif ($cur_value[1] === 1) {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_one"=TRUE)';
								break;
							} elseif ($cur_value[1] === 0) {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_zero"=TRUE)';
								break;
							} elseif ($cur_value[1] === -1) {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_negone"=TRUE)';
								break;
							} elseif ($cur_value[1] === array()) {
								if ( $cur_query ) {
									$cur_query .= ($type_is_or ? ' OR ' : ' AND ');
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."guid" IN (SELECT "guid" FROM "'.$this->prefix.'data'.$etype.'" WHERE "name"=\''.pg_escape_string($this->link, $cur_value[0]).'\' AND "compare_emptyarray"=TRUE)';
								break;
							}
						case 'gt':
						case '!gt':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."cdate">'.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."mdate">'.((float) $cur_value[1]);
								break;
							}
						case 'gte':
						case '!gte':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."cdate">='.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."mdate">='.((float) $cur_value[1]);
								break;
							}
						case 'lt':
						case '!lt':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."cdate"<'.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."mdate"<'.((float) $cur_value[1]);
								break;
							}
						case 'lte':
						case '!lte':
							if ($cur_value[0] == 'cdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."cdate"<='.((float) $cur_value[1]);
								break;
							} elseif ($cur_value[0] == 'mdate') {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= (($type_is_not xor $clause_not) ? 'NOT ' : '' ).'e."mdate"<='.((float) $cur_value[1]);
								break;
							}
						case 'array':
						case '!array':
							if (!($type_is_not xor $clause_not)) {
								if ( $cur_query ) {
									$cur_query .= $type_is_or ? ' OR ' : ' AND ';
								}
								$cur_query .= '\'{'.pg_escape_string($this->link, $cur_value[0]).'}\' <@ e."varlist"';
							}
							break;
					}
				}
				if ( $cur_query ) {
					if ($cur_selector_query) {
						$cur_selector_query .= $type_is_or ? ' OR ' : ' AND ';
					}
					$cur_selector_query .= $cur_query;
				}
			}
			unset($value);
			if ($cur_selector_query) {
				$query_parts[] = $cur_selector_query;
			}
		}
		unset($cur_selector);

		switch ($sort) {
			case 'guid':
				$sort = 'e."guid"';
				break;
			case 'mdate':
				$sort = 'e."mdate"';
				break;
			case 'cdate':
			default:
				$sort = 'e."cdate"';
				break;
		}
		if ($query_parts) {
			$query = "SELECT e.\"guid\", e.\"tags\", e.\"cdate\", e.\"mdate\", d.\"name\", d.\"value\" FROM \"{$this->prefix}entities{$etype}\" e LEFT JOIN \"{$this->prefix}data{$etype}\" d USING (\"guid\") WHERE (".implode(') AND (', $query_parts).") ORDER BY ".(isset($options['reverse']) && $options['reverse'] ? $sort.' DESC' : $sort).";";
		} else {
			$query = "SELECT e.\"guid\", e.\"tags\", e.\"cdate\", e.\"mdate\", d.\"name\", d.\"value\" FROM \"{$this->prefix}entities{$etype}\" e LEFT JOIN \"{$this->prefix}data{$etype}\" d USING (\"guid\") ORDER BY ".(isset($options['reverse']) && $options['reverse'] ? $sort.' DESC' : $sort).";";
		}
		$result = $this->query($query, $etype_dirty);

		$row = pg_fetch_row($result);
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
					$sdata[$row[4]] = ($row[5][0] === '~' ? stripcslashes(substr($row[5], 1)) : $row[5]);
					$row = pg_fetch_row($result);
				} while ((int) $row[0] === $guid);
			} else {
				// Make sure that $row is incremented :)
				$row = pg_fetch_row($result);
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
					} elseif (($key === 'match' || $key === '!match') && $this->usePLPerl) {
						// Handled by the query.
						$pass = true;
					} else {
						// Check if it doesn't pass any for &, check if it
						// passes any for |.
						foreach ($value as $cur_value) {
							if (($key === 'data' || $key === '!data') && ($cur_value[1] === true || $cur_value[1] === false || $cur_value[1] === 1 || $cur_value[1] === 0 || $cur_value[1] === -1 || $cur_value[1] === array())) {
								// Handled by the query.
								$pass = true;
							} else {
								// Unserialize the data for this variable.
								if (isset($sdata[$cur_value[0]])) {
									$data[$cur_value[0]] = unserialize($sdata[$cur_value[0]]);
									unset($sdata[$cur_value[0]]);
								}
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
										// If we get here, plperl functions are off.
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
							if (!($type_is_or xor $pass)) {
								break;
							}
						}
					}
					if (!($type_is_or xor $pass)) {
						break;
					}
				}
				unset($value);
				if (!$pass) {
					$pass_all = false;
					break;
				}
			}
			unset($cur_selector);
			if ($pass_all) {
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
					$entity = call_user_func(array($class, 'factory'));
					$entity->guid = $guid;
					if (strlen($tags) > 2) {
						$entity->tags = explode(',', substr($tags, 1, -1));
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

		pg_free_result($result);

		return $entities;
	}

	public function getUID($name) {
		if (!$name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID.');
		}
		$result = $this->query("SELECT \"cur_uid\" FROM \"{$this->prefix}uids\" WHERE \"name\"='".pg_escape_string($this->link, $name)."';");
		$row = pg_fetch_row($result);
		pg_free_result($result);
		return isset($row[0]) ? (int) $row[0] : null;
	}

	public function import($filename) {
		if (!$fhandle = fopen($filename, 'r')) {
			throw new Exceptions\InvalidParametersException('Provided filename is unreadable.');
		}
		$line = '';
		$data = array();
		$this->query('BEGIN;');
		while (!feof($fhandle)) {
			$line .= fgets($fhandle, 8192);
			if (substr($line, -1) != "\n") {
				continue;
			}
			if (preg_match('/^\s*#/S', $line)) {
				$line = '';
				continue;
			}
			$matches = array();
			if (preg_match('/^\s*{(\d+)}<([\w-_]+)>\[([\w,]+)\]\s*$/S', $line, $matches)) {
				// Save the current entity.
				if ($guid) {
					$result = $this->query("DELETE FROM \"{$this->prefix}guids\" WHERE \"guid\"={$guid}; INSERT INTO \"{$this->prefix}guids\" (\"guid\") VALUES ({$guid});");
					$this->query("DELETE FROM \"{$this->prefix}entities_{$etype}\" WHERE \"guid\"={$guid}; INSERT INTO \"{$this->prefix}entities_{$etype}\" (\"guid\", \"tags\", \"varlist\", \"cdate\", \"mdate\") VALUES ({$guid}, '".pg_escape_string($this->link, '{'.$tags.'}')."', '".pg_escape_string($this->link, '{'.implode(',', array_keys($data)).'}')."', ".unserialize($data['cdate']).", ".unserialize($data['mdate']).");", $etype);
					$this->query("DELETE FROM \"{$this->prefix}data_{$etype}\" WHERE \"guid\"={$guid};");
					unset($data['cdate'], $data['mdate']);
					if ($data) {
						$query = '';
						foreach ($data as $name => $value) {
							preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
							$uvalue = unserialize($value);
							$query .= "INSERT INTO \"{$this->prefix}data_{$etype}\" (\"guid\", \"name\", \"value\", \"references\", \"compare_true\", \"compare_one\", \"compare_zero\", \"compare_negone\", \"compare_emptyarray\", \"compare_string\") VALUES ";
							$query .= sprintf("(%u, '%s', '%s', '%s', %s, %s, %s, %s, %s, %s); ",
								$guid,
								pg_escape_string($this->link, $name),
								pg_escape_string($this->link, (strpos($value, "\0") !== false ? '~'.addcslashes($value, chr(0).'\\') : $value)),
								pg_escape_string($this->link, '{'.implode(',', $references[1]).'}'),
								$uvalue == true ? 'TRUE' : 'FALSE',
								(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
								(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
								(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
								$uvalue == array() ? 'TRUE' : 'FALSE',
								is_string($uvalue) ? '\''.pg_escape_string($this->link, $uvalue).'\'' : 'NULL');
						}
						$this->query($query);
					}
					$guid = null;
					$tags = array();
					$data = array();
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
				$this->query("DELETE FROM \"{$this->prefix}uids\" WHERE \"name\"='".pg_escape_string($this->link, $matches[1])."'; INSERT INTO \"{$this->prefix}uids\" (\"name\", \"cur_uid\") VALUES ('".pg_escape_string($this->link, $matches[1])."', ".((int) $matches[2]).");");
			}
			$line = '';
			// Clear the entity cache.
			$this->entityCache = array();
		}
		// Save the last entity.
		if ($guid) {
			$result = $this->query("DELETE FROM \"{$this->prefix}guids\" WHERE \"guid\"={$guid}; INSERT INTO \"{$this->prefix}guids\" (\"guid\") VALUES ({$guid});");
			$this->query("DELETE FROM \"{$this->prefix}entities_{$etype}\" WHERE \"guid\"={$guid}; INSERT INTO \"{$this->prefix}entities_{$etype}\" (\"guid\", \"tags\", \"varlist\", \"cdate\", \"mdate\") VALUES ({$guid}, '".pg_escape_string($this->link, '{'.$tags.'}')."', '".pg_escape_string($this->link, '{'.implode(',', array_keys($data)).'}')."', ".unserialize($data['cdate']).", ".unserialize($data['mdate']).");", $etype);
			$this->query("DELETE FROM \"{$this->prefix}data_{$etype}\" WHERE \"guid\"={$guid};");
			unset($data['cdate'], $data['mdate']);
			if ($data) {
				$query = '';
				foreach ($data as $name => $value) {
					preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
					$uvalue = unserialize($value);
					$query .= "INSERT INTO \"{$this->prefix}data_{$etype}\" (\"guid\", \"name\", \"value\", \"references\", \"compare_true\", \"compare_one\", \"compare_zero\", \"compare_negone\", \"compare_emptyarray\", \"compare_string\") VALUES ";
					$query .= sprintf("(%u, '%s', '%s', '%s', %s, %s, %s, %s, %s, %s); ",
						$guid,
						pg_escape_string($this->link, $name),
						pg_escape_string($this->link, (strpos($value, "\0") !== false ? '~'.addcslashes($value, chr(0).'\\') : $value)),
						pg_escape_string($this->link, '{'.implode(',', $references[1]).'}'),
						$uvalue == true ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
						$uvalue == array() ? 'TRUE' : 'FALSE',
						is_string($uvalue) ? '\''.pg_escape_string($this->link, $uvalue).'\'' : 'NULL');
				}
				$this->query($query);
			}
		}
		$this->query('COMMIT;');
		return true;
	}

	public function newUID($name) {
		if (!$name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID.');
		}
		$this->query('BEGIN;');
		$result = $this->query("SELECT \"cur_uid\" FROM \"{$this->prefix}uids\" WHERE \"name\"='".pg_escape_string($this->link, $name)."' FOR UPDATE;");
		$row = pg_fetch_row($result);
		$cur_uid = (int) $row[0];
		pg_free_result($result);
		if (!$cur_uid) {
			$cur_uid = 1;
			$this->query("INSERT INTO \"{$this->prefix}uids\" (\"name\", \"cur_uid\") VALUES ('".pg_escape_string($this->link, $name)."', {$cur_uid});");
		} else {
			$cur_uid++;
			$this->query("UPDATE \"{$this->prefix}uids\" SET \"cur_uid\"={$cur_uid} WHERE \"name\"='".pg_escape_string($this->link, $name)."';");
		}
		$this->query('COMMIT;');
		return $cur_uid;
	}

	public function renameUID($old_name, $new_name) {
		if (!$old_name || !$new_name) {
			throw new Exceptions\InvalidParametersException('Name not given for UID.');
		}
		$this->query("UPDATE \"{$this->prefix}uids\" SET \"name\"='".pg_escape_string($this->link, $new_name)."' WHERE \"name\"='".pg_escape_string($this->link, $old_name)."';");
		return true;
	}

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
		$etype = '_'.pg_escape_string($this->link, $etype_dirty);
		$this->query('BEGIN;');
		if ( !isset($entity->guid) ) {
			while (true) {
				$new_id = mt_rand(1, pow(2, 53)); // 2^53 is the maximum number in JavaScript (http://ecma262-5.com/ELS5_HTML.htm#Section_8.5)
				// That number might be too big on some machines. :(
				if ($new_id < 1) {
					$new_id = rand(1, 0x7FFFFFFF);
				}
				$result = $this->query("SELECT \"guid\" FROM \"{$this->prefix}guids\" WHERE \"guid\"={$new_id};", $etype_dirty);
				$row = pg_fetch_row($result);
				pg_free_result($result);
				if (!isset($row[0])) {
					break;
				}
			}
			$entity->guid = $new_id;
			$this->query("INSERT INTO \"{$this->prefix}guids\" (\"guid\") VALUES ({$new_id});");
			$this->query("INSERT INTO \"{$this->prefix}entities{$etype}\" (\"guid\", \"tags\", \"varlist\", \"cdate\", \"mdate\") VALUES ({$entity->guid}, '".pg_escape_string($this->link, '{'.implode(',', array_diff($entity->tags, array(''))).'}')."', '".pg_escape_string($this->link, '{'.implode(',', $varlist).'}')."', ".((float) $data['cdate']).", ".((float) $data['mdate']).");", $etype_dirty);
			unset($data['cdate'], $data['mdate']);
			$values = array();
			foreach ($data as $name => $value) {
				$svalue = serialize($value);
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $svalue, $references, PREG_PATTERN_ORDER);
				$values[] = "INSERT INTO \"{$this->prefix}data{$etype}\" (\"guid\", \"name\", \"value\", \"references\", \"compare_true\", \"compare_one\", \"compare_zero\", \"compare_negone\", \"compare_emptyarray\", \"compare_string\") VALUES ".
					sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s);',
						$entity->guid,
						pg_escape_string($this->link, $name),
						pg_escape_string($this->link, (strpos($svalue, "\0") !== false ? '~'.addcslashes($svalue, chr(0).'\\') : $svalue)),
						pg_escape_string($this->link, '{'.implode(',', $references[1]).'}'),
						$value == true ? 'TRUE' : 'FALSE',
						(!is_object($value) && $value == 1) ? 'TRUE' : 'FALSE',
						(!is_object($value) && $value == 0) ? 'TRUE' : 'FALSE',
						(!is_object($value) && $value == -1) ? 'TRUE' : 'FALSE',
						$value == array() ? 'TRUE' : 'FALSE',
						is_string($value) ? '\''.pg_escape_string($this->link, $value).'\'' : 'NULL'
					);
			}
			foreach ($sdata as $name => $value) {
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
				$uvalue = unserialize($value);
				$values[] = "INSERT INTO \"{$this->prefix}data{$etype}\" (\"guid\", \"name\", \"value\", \"references\", \"compare_true\", \"compare_one\", \"compare_zero\", \"compare_negone\", \"compare_emptyarray\", \"compare_string\") VALUES ".
					sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s);',
						$entity->guid,
						pg_escape_string($this->link, $name),
						pg_escape_string($this->link, (strpos($value, "\0") !== false ? '~'.addcslashes($value, chr(0).'\\') : $value)),
						pg_escape_string($this->link, '{'.implode(',', $references[1]).'}'),
						$uvalue == true ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
						$uvalue == array() ? 'TRUE' : 'FALSE',
						is_string($uvalue) ? '\''.pg_escape_string($this->link, $uvalue).'\'' : 'NULL'
					);
			}
			$result = $this->query(implode(' ', $values), $etype_dirty);
		} else {
			// Removed any cached versions of this entity.
			if ($this->config->cache['value']) {
				$this->cleanCache($entity->guid);
			}
			$this->query("UPDATE \"{$this->prefix}entities{$etype}\" SET \"tags\"='".pg_escape_string($this->link, '{'.implode(',', array_diff($entity->tags, array(''))).'}')."', \"varlist\"='".pg_escape_string($this->link, '{'.implode(',', $varlist).'}')."', \"cdate\"=".((float) $data['cdate']).", \"mdate\"=".((float) $data['mdate'])." WHERE \"guid\"={$entity->guid};", $etype_dirty);
			$this->query("DELETE FROM \"{$this->prefix}data{$etype}\" WHERE \"guid\"={$entity->guid};");
			unset($data['cdate'], $data['mdate']);
			$values = array();
			foreach ($data as $name => $value) {
				$svalue = serialize($value);
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $svalue, $references, PREG_PATTERN_ORDER);
				$values[] = "INSERT INTO \"{$this->prefix}data{$etype}\" (\"guid\", \"name\", \"value\", \"references\", \"compare_true\", \"compare_one\", \"compare_zero\", \"compare_negone\", \"compare_emptyarray\", \"compare_string\") VALUES ".
					sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s);',
						(int) $entity->guid,
						pg_escape_string($this->link, $name),
						pg_escape_string($this->link, (strpos($svalue, "\0") !== false ? '~'.addcslashes($svalue, chr(0).'\\') : $svalue)),
						pg_escape_string($this->link, '{'.implode(',', $references[1]).'}'),
						$value == true ? 'TRUE' : 'FALSE',
						(!is_object($value) && $value == 1) ? 'TRUE' : 'FALSE',
						(!is_object($value) && $value == 0) ? 'TRUE' : 'FALSE',
						(!is_object($value) && $value == -1) ? 'TRUE' : 'FALSE',
						$value == array() ? 'TRUE' : 'FALSE',
						is_string($value) ? '\''.pg_escape_string($this->link, $value).'\'' : 'NULL'
					);
			}
			foreach ($sdata as $name => $value) {
				preg_match_all('/a:3:\{i:0;s:22:"nymph_entity_reference";i:1;i:(\d+);/', $value, $references, PREG_PATTERN_ORDER);
				$uvalue = unserialize($value);
				$values[] = "INSERT INTO \"{$this->prefix}data{$etype}\" (\"guid\", \"name\", \"value\", \"references\", \"compare_true\", \"compare_one\", \"compare_zero\", \"compare_negone\", \"compare_emptyarray\", \"compare_string\") VALUES ".
					sprintf('(%u, \'%s\', \'%s\', \'%s\', %s, %s, %s, %s, %s, %s);',
						(int) $entity->guid,
						pg_escape_string($this->link, $name),
						pg_escape_string($this->link, (strpos($value, "\0") !== false ? '~'.addcslashes($value, chr(0).'\\') : $value)),
						pg_escape_string($this->link, '{'.implode(',', $references[1]).'}'),
						$uvalue == true ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 1) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == 0) ? 'TRUE' : 'FALSE',
						(!is_object($uvalue) && $uvalue == -1) ? 'TRUE' : 'FALSE',
						$uvalue == array() ? 'TRUE' : 'FALSE',
						is_string($uvalue) ? '\''.pg_escape_string($this->link, $uvalue).'\'' : 'NULL'
					);
			}
			$this->query(implode(' ', $values), $etype_dirty);
		}
		pg_get_result($this->link); // Clear any pending result.
		$this->query('COMMIT;');
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
			throw new Exceptions\InvalidParametersException('Name not given for UID.');
		}
		$this->query("DELETE FROM \"{$this->prefix}uids\" WHERE \"name\"='".pg_escape_string($this->link, $name)."'; INSERT INTO \"{$this->prefix}uids\" (\"name\", \"cur_uid\") VALUES ('".pg_escape_string($this->link, $name)."', ".((int) $value).");");
		return true;
	}
}
