<?php
/**
 * Dynamically handles many centralized object-relational mapping tasks
 * 
 * @copyright  Copyright (c) 2014-2019 Alan Johnston, Velus Universe Ltd
 * @author     Alan Johnston [aj] <alan.johnston@velusuniverse.co.uk>
 * @author     Alan Johnston, Velus Universe Ltd [aj-vu] <alan.johnston@velusuniverse.co.uk>
 * @license    http://veluslib.opensource.velusuniverse.com/license
 * 
 * @package    Velus Lib
 * 
 * @version    0.0.1b
 * @changes    0.0.1b    The initial implementation [aj, 2014-12-13]
 * 
 * @link       http://veluslib.opensource.velusuniverse.com/vORM
 */
class vORM
{
	// The following constants allow for nice looking callbacks to static methods
	const callHookCallbacks          = 'vORM::callHookCallbacks';
	const callInspectCallbacks       = 'vORM::callInspectCallbacks';
	const callReflectCallbacks       = 'vORM::callReflectCallbacks';
	const checkHookCallback          = 'vORM::checkHookCallback';
	const classize                   = 'vORM::classize';
	const defineActiveRecordClass    = 'vORM::defineActiveRecordClass';
	const enableSchemaCaching        = 'vORM::enableSchemaCaching';
	const getActiveRecordMethod      = 'vORM::getActiveRecordMethod';
	const getClass                   = 'vORM::getClass';
	const getColumnName              = 'vORM::getColumnName';
	const getDatabaseName            = 'vORM::getDatabaseName';
	const getRecordName              = 'vORM::getRecordName';
	const getRecordSetMethod         = 'vORM::getRecordSetMethod';
	const getRelatedClass            = 'vORM::getRelatedClass';
	const isClassMappedToTable       = 'vORM::isClassMappedToTable';
	const mapClassToDatabase         = 'vORM::mapClassToDatabase';
	const mapClassToTable            = 'vORM::mapClassToTable';
	const objectify                  = 'vORM::objectify';
	const overrideColumnName         = 'vORM::overrideColumnName';
	const overrideRecordName         = 'vORM::overrideRecordName';
	const parseMethod                = 'vORM::parseMethod';
	const registerActiveRecordMethod = 'vORM::registerActiveRecordMethod';
	const registerHookCallback       = 'vORM::registerHookCallback';
	const registerInspectCallback    = 'vORM::registerInspectCallback';
	const registerObjectifyCallback  = 'vORM::registerObjectifyCallback';
	const registerRecordSetMethod    = 'vORM::registerRecordSetMethod';
	const registerReflectCallback    = 'vORM::registerReflectCallback';
	const registerReplicateCallback  = 'vORM::registerReplicateCallback';
	const registerScalarizeCallback  = 'vORM::registerScalarizeCallback';
	const replicate                  = 'vORM::replicate'; 
	const reset                      = 'vORM::reset';
	const scalarize                  = 'vORM::scalarize';
	const tablize                    = 'vORM::tablize';
	
	
	/**
	 * An array of `{method} => {callback}` mappings for vActiveRecord
	 * 
	 * @var array
	 */
	static private $active_record_method_callbacks = array();
	
	/**
	 * Cache for repetitive computation
	 * 
	 * @var array
	 */
	static private $cache = array(
		'parseMethod'           => array(),
		'getActiveRecordMethod' => array(),
		'objectify'             => array()
	);
	
	/**
	 * Custom mappings for class -> database
	 * 
	 * @var array
	 */
	static private $class_database_map = array(
		'vActiveRecord' => 'default'
	);
	
	/**
	 * Custom mappings for class <-> table
	 * 
	 * @var array
	 */
	static private $class_table_map = array();
	
	/**
	 * Custom column names for columns in vActiveRecord classes
	 * 
	 * @var array
	 */
	static private $column_names = array();
	
	/**
	 * Tracks callbacks registered for various vActiveRecord hooks
	 * 
	 * @var array
	 */
	static private $hook_callbacks = array();
	
	/**
	 * Callbacks for ::callInspectCallbacks()
	 * 
	 * @var array
	 */
	static private $inspect_callbacks = array();
	
	/**
	 * Callbacks for ::objectify()
	 * 
	 * @var array
	 */
	static private $objectify_callbacks = array();
	
	/**
	 * Custom record names for vActiveRecord classes
	 * 
	 * @var array
	 */
	static private $record_names = array(
		'vActiveRecord' => 'Active Record'
	);
	
	/**
	 * An array of `{method} => {callback}` mappings for vRecordSet
	 * 
	 * @var array
	 */
	static private $record_set_method_callbacks = array();
	
	/**
	 * Callbacks for ::callReflectCallbacks()
	 * 
	 * @var array
	 */
	static private $reflect_callbacks = array();
	
	/**
	 * A cache for resolving related class names for vActiveRecord classes in a PHP 5.3 namespace
	 * 
	 * @var array
	 */
	static private $related_class_names = array();
	
	/**
	 * Callbacks for ::replicate()
	 * 
	 * @var array
	 */
	static private $replicate_callbacks = array();
	
	/**
	 * Callbacks for ::scalarize()
	 * 
	 * @var array
	 */
	static private $scalarize_callbacks = array();
	
	
	/**
	 * Calls the hook callbacks for the class and hook specified
	 * 
	 * @internal
	 * 
	 * @param  vActiveRecord $object            The instance of the class to call the hook for
	 * @param  string        $hook              The hook to call
	 * @param  array         &$values           The current values of the record
	 * @param  array         &$old_values       The old values of the record
	 * @param  array         &$related_records  Records related to the current record
	 * @param  array         &$cache            The cache array of the record
	 * @param  mixed         &$parameter        The parameter to send the callback
	 * @return void
	 */
	static public function callHookCallbacks($object, $hook, &$values, &$old_values, &$related_records, &$cache, &$parameter=NULL)
	{
		$class = get_class($object);
		
		if (empty(self::$hook_callbacks[$class][$hook]) && empty(self::$hook_callbacks['*'][$hook])) {
			return;
		}
		
		// Get all of the callbacks for this hook, both for this class or all classes
		$callbacks = array();
		
		if (isset(self::$hook_callbacks[$class][$hook])) {
			$callbacks = array_merge($callbacks, self::$hook_callbacks[$class][$hook]);
		}
		
		if (isset(self::$hook_callbacks['*'][$hook])) {
			$callbacks = array_merge($callbacks, self::$hook_callbacks['*'][$hook]);
		}
		
		foreach ($callbacks as $callback) {
			call_user_func_array(
				$callback,
				// This is the only way to pass by reference
				array(
					$object,
					&$values,
					&$old_values,
					&$related_records,
					&$cache,
					&$parameter
				)
			);
		}
	}
	
	
	/**
	 * Calls all inspect callbacks for the class and column specified
	 * 
	 * @internal
	 * 
	 * @param  string $class      The class to inspect the column of
	 * @param  string $column     The column to inspect
	 * @param  array  &$metadata  The associative array of data about the column
	 * @return void
	 */
	static public function callInspectCallbacks($class, $column, &$metadata)
	{
		if (!isset(self::$inspect_callbacks[$class][$column])) {
			return;
		}
		
		foreach (self::$inspect_callbacks[$class][$column] as $callback) {
			// This is the only way to pass by reference
			$parameters = array(
				$class,
				$column,
				&$metadata
			);
			call_user_func_array($callback, $parameters);
		}
	}
	
	
	/**
	 * Calls all reflect callbacks for the class passed
	 * 
	 * @internal
	 * 
	 * @param  string  $class                 The class to call the callbacks for
	 * @param  array   &$signatures           The associative array of `{method_name} => {signature}`
	 * @param  boolean $include_doc_comments  If the doc comments should be included in the signature
	 * @return void
	 */
	static public function callReflectCallbacks($class, &$signatures, $include_doc_comments)
	{
		if (!isset(self::$reflect_callbacks[$class]) && !isset(self::$reflect_callbacks['*'])) {
			return;
		}
		
		if (!empty(self::$reflect_callbacks['*'])) {
			foreach (self::$reflect_callbacks['*'] as $callback) {
				// This is the only way to pass by reference
				$parameters = array(
					$class,
					&$signatures,
					$include_doc_comments
				);
				call_user_func_array($callback, $parameters);
			}	
		}
		
		if (!empty(self::$reflect_callbacks[$class])) {
			foreach (self::$reflect_callbacks[$class] as $callback) {
				// This is the only way to pass by reference
				$parameters = array(
					$class,
					&$signatures,
					$include_doc_comments
				);
				call_user_func_array($callback, $parameters);
			}
		}
	}
	
	
	/**
	 * Checks to see if any (or a specific) callback has been registered for a specific hook
	 *
	 * @internal
	 * 
	 * @param  string $class     The name of the class
	 * @param  string $hook      The hook to check
	 * @param  array  $callback  The specific callback to check for
	 * @return boolean  If the specified callback exists
	 */
	static public function checkHookCallback($class, $hook, $callback=NULL)
	{
		if (empty(self::$hook_callbacks[$class][$hook]) && empty(self::$hook_callbacks['*'][$hook])) {
			return FALSE;
		}
		
		if (!$callback) {
			return TRUE;
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		if (!empty(self::$hook_callbacks[$class][$hook]) && in_array($callback, self::$hook_callbacks[$class][$hook])) {
			return TRUE;	
		}
		
		if (!empty(self::$hook_callbacks['*'][$hook]) && in_array($callback, self::$hook_callbacks['*'][$hook])) {
			return TRUE;	
		}
		
		return FALSE;
	}
	
	
	/**
	 * Takes a table and turns it into a class name - uses custom mapping if set
	 * 
	 * @param  string $table  The table name
	 * @return string  The class name
	 */
	static public function classize($table)
	{
		if (!$class = array_search($table, self::$class_table_map)) {
			$class = vGrammar::camelize(vGrammar::singularize($table), TRUE);
			self::$class_table_map[$class] = $table;
		}
		
		return $class;
	}
	
	
	/**
	 * Will dynamically create an vActiveRecord-based class for a database table
	 * 
	 * Normally this would be called from an `__autoload()` function.
	 * 
	 * This method will only create classes for tables in the default ORM
	 * database.
	 * 
	 * @param  string $class  The name of the class to create
	 * @return void
	 */
	static public function defineActiveRecordClass($class)
	{
		if (class_exists($class, FALSE)) {
			return;
		}
		$schema = vORMSchema::retrieve();
		$tables = $schema->getTables();
		$table  = self::tablize($class);
		if (in_array($table, $tables)) {
			eval('class ' . $class . ' extends vActiveRecord { };');
			return;
		}
		
		throw new vProgrammerException(
			'The class specified, %s, does not correspond to a database table',
			$class
		);
	}
	
	
	/**
	 * Enables caching on the vDatabase, vSQLTranslation and vSchema objects used for the ORM
	 * 
	 * This method will cache database schema information to the three objects
	 * that use it during normal ORM operation: vDatabase, vSQLTranslation and
	 * vSchema. To allow for schema changes without having to manually clear
	 * the cache, all cached information will be cleared if any
	 * vUnexpectedException objects are thrown.
	 * 
	 * This method should be called right after vORMDatabase::attach().
	 *          
	 * @param  vCache $cache          The object to cache schema information to
	 * @param  string $database_name  The database to enable caching for
	 * @param  string $key_token      This is a token that is used in cache keys to prevent conflicts for server-wide caches - when non-NULL the document root is used 
	 * @return void
	 */
	static public function enableSchemaCaching($cache, $database_name='default', $key_token=NULL)
	{
		if ($key_token === NULL) {
			$key_token = $_SERVER['DOCUMENT_ROOT'];	
		}
		$token = 'vORM::' . $database_name . '::' . $key_token . '::';
		
		$db = vORMDatabase::retrieve('name:' . $database_name);
		$db->enableCaching($cache, $token);
		vException::registerCallback($db->clearCache, 'vUnexpectedException');
		
		$sql_translation = $db->getSQLTranslation();
		$sql_translation->enableCaching($cache, $token);
		
		$schema = vORMSchema::retrieve('name:' . $database_name);
		$schema->enableCaching($cache, $token);
		vException::registerCallback($schema->clearCache, 'vUnexpectedException');	
	}
	
	
	/**
	 * Returns a matching callback for the class and method specified
	 * 
	 * The callback returned will be determined by the following logic:
	 * 
	 *  1. If an exact callback has been defined for the method, it will be returned
	 *  2. If a callback in the form `{prefix}*` has been defined that matches the method, it will be returned
	 *  3. `NULL` will be returned
	 * 
	 * @internal
	 * 
	 * @param  string $class   The name of the class
	 * @param  string $method  The method to get the callback for
	 * @return string|null  The callback for the method or `NULL` if none exists - see method description for details
	 */
	static public function getActiveRecordMethod($class, $method)
	{
		// This caches method lookups, providing a significant performance
		// boost to pages with lots of method calls that get passed to
		// vActiveRecord::__call()
		if (isset(self::$cache['getActiveRecordMethod'][$class . '::' . $method])) {
			return (!$method = self::$cache['getActiveRecordMethod'][$class . '::' . $method]) ? NULL : $method; 	
		}
		
		$callback = NULL;
		
		if (isset(self::$active_record_method_callbacks[$class][$method])) {
			$callback = self::$active_record_method_callbacks[$class][$method];	
		
		} elseif (isset(self::$active_record_method_callbacks['*'][$method])) {
			$callback = self::$active_record_method_callbacks['*'][$method];	
		
		} elseif (preg_match('#[A-Z0-9]#', $method)) {
			list($action, $subject) = self::parseMethod($method);
			if (isset(self::$active_record_method_callbacks[$class][$action . '*'])) {
				$callback = self::$active_record_method_callbacks[$class][$action . '*'];	
			} elseif (isset(self::$active_record_method_callbacks['*'][$action . '*'])) {
				$callback = self::$active_record_method_callbacks['*'][$action . '*'];	
			}	
		}
		
		self::$cache['getActiveRecordMethod'][$class . '::' . $method] = ($callback === NULL) ? FALSE : $callback;
		return $callback;
	}
	
	
	/**
	 * Takes a class name or class and returns the class name
	 *
	 * @internal
	 * 
	 * @param  mixed $class  The object to get the name of, or possibly a string already containing the class
	 * @return string  The class name
	 */
	static public function getClass($class)
	{
		if (is_object($class)) { return get_class($class); }
		return $class;
	}
	
	
	/**
	 * Returns the column name
	 * 
	 * The default column name is the result of calling vGrammar::humanize()
	 * on the column.
	 * 
	 * @internal
	 * 
	 * @param  string $class   The class name the column is part of
	 * @param  string $column  The database column
	 * @return string  The column name for the column specified
	 */
	static public function getColumnName($class, $column)
	{
		if (!isset(self::$column_names[$class])) {
			self::$column_names[$class] = array();
		}
		
		if (!isset(self::$column_names[$class][$column])) {
			self::$column_names[$class][$column] = vGrammar::humanize($column);
		}
		
		// If vText is loaded, use it
		if (class_exists('vText', FALSE)) {
			return call_user_func(
				array('vText', 'compose'),
				str_replace('%', '%%', self::$column_names[$class][$column])
			);
		}
		
		return self::$column_names[$class][$column];
	}
	
	
	/**
	 * Returns the name for the database used by the class specified
	 * 
	 * @internal
	 * 
	 * @param  string $class   The class name to get the database name for
	 * @return string  The name of the database to use
	 */
	static public function getDatabaseName($class)
	{
		if (!isset(self::$class_database_map[$class])) {
			$class = 'vActiveRecord';	
		}
		
		return self::$class_database_map[$class];
	}
	
	
	/**
	 * Returns the record name for a class
	 * 
	 * The default record name is the result of calling vGrammar::humanize()
	 * on the class.
	 * 
	 * @internal
	 * 
	 * @param  string $class  The class name to get the record name of
	 * @return string  The record name for the class specified
	 */
	static public function getRecordName($class)
	{
		if (!isset(self::$record_names[$class])) {
			self::$record_names[$class] = vGrammar::humanize(
				// Strip the namespace off the class name
				preg_replace(
					'#^.*\\\\#',
					'',
					$class
				)
			);
		}
		
		// If vText is loaded, use it
		if (class_exists('vText', FALSE)) {
			return call_user_func(
				array('vText', 'compose'),
				str_replace('%', '%%', self::$record_names[$class])
			);
		}
		
		return self::$record_names[$class];
	}
	
	
	/**
	 * Returns a matching callback for the method specified
	 * 
	 * The callback returned will be determined by the following logic:
	 * 
	 *  1. If an exact callback has been defined for the method, it will be returned
	 *  2. If a callback in the form `{action}*` has been defined that matches the method, it will be returned
	 *  3. `NULL` will be returned
	 * 
	 * @internal
	 * 
	 * @param  string $method  The method to get the callback for
	 * @return string|null  The callback for the method or `NULL` if none exists - see method description for details
	 */
	static public function getRecordSetMethod($method)
	{
		if (isset(self::$record_set_method_callbacks[$method])) {
			return self::$record_set_method_callbacks[$method];	
		}
		
		if (preg_match('#[A-Z0-9]#', $method)) {
			list($action, $subject) = self::parseMethod($method);
			if (isset(self::$record_set_method_callbacks[$action . '*'])) {
				return self::$record_set_method_callbacks[$action . '*'];	
			}	
		}
		
		return NULL;	
	}
	
	
	/**
	 * Takes a class name and related class name and ensures the related class has the appropriate namespace prefix
	 *
	 * @internal
	 * 
	 * @param  string $class          The primary class
	 * @param  string $related_class  The related class name
	 * @return string  The related class name, with the appropriate namespace prefix
	 */
	static public function getRelatedClass($class, $related_class)
	{
		if (isset(self::$related_class_names[$class][$related_class])) {
			return self::$related_class_names[$class][$related_class];
		}
		
		$original_related_class = $related_class;
		if (strpos($class, '\\') !== FALSE && strpos($related_class, '\\') === FALSE) {
			$reflection = new ReflectionClass($class);
	        $related_class = $reflection->getNamespaceName() . '\\' . $related_class;
		}
		self::$related_class_names[$class][$original_related_class] = $related_class;
		
		return $related_class;
	}
	
	
	/**
	 * Checks if a class has been mapped to a table
	 * 
	 * @internal
	 * 
	 * @param  mixed  $class  The name of the class
	 * @return boolean  If the class has been mapped to a table
	 */
	static public function isClassMappedToTable($class)
	{
		$class = self::getClass($class);
		
		return isset(self::$class_table_map[$class]);
	}
	
	
	/**
	 * Sets a class to use a database other than the "default"
	 * 
	 * Multiple database objects can be attached for the ORM by passing a
	 * unique `$name` to the ::attach() method.
	 * 
	 * @param  mixed  $class          The name of the class, or an instance of it
	 * @param  string $database_name  The name given to the database when passed to ::attach()
	 * @return void
	 */
	static public function mapClassToDatabase($class, $database_name)
	{
		$class = vORM::getClass($class);
		
		self::$class_database_map[$class] = $database_name;
	}
	
	
	/**
	 * Allows non-standard class to table mapping
	 * 
	 * By default, all database tables are assumed to be plural nouns in
	 * `underscore_notation` and all class names are assumed to be singular
	 * nouns in `UpperCamelCase`. This method allows arbitrary class to 
	 * table mapping.
	 * 
	 * @param  mixed  $class  The name of the class, or an instance of it
	 * @param  string $table  The name of the database table
	 * @return void
	 */
	static public function mapClassToTable($class, $table)
	{
		$class = self::getClass($class);
		
		self::$class_table_map[$class] = $table;
	}
	
	
	/**
	 * Takes a scalar value and turns it into an object if applicable
	 *
	 * @internal
	 * 
	 * @param  string $class   The class name of the class the column is part of
	 * @param  string $column  The database column
	 * @param  mixed  $value   The value to possibly objectify
	 * @return mixed  The scalar or object version of the value, depending on the column type and column options
	 */
	static public function objectify($class, $column, $value)
	{
		// This short-circuits computation for already checked columns, providing
		// a nice little performance boost to pages with lots of records
		if (isset(self::$cache['objectify'][$class . '::' . $column])) {
			return $value;	
		}
		
		if (!empty(self::$objectify_callbacks[$class][$column])) {
			return call_user_func(self::$objectify_callbacks[$class][$column], $class, $column, $value);
		}
		
		$table  = self::tablize($class);
		$schema = vORMSchema::retrieve($class);
		
		// Turn date/time values into objects
		$column_type = $schema->getColumnInfo($table, $column, 'type');
		
		if (in_array($column_type, array('date', 'time', 'timestamp'))) {
			
			if ($value === NULL) {
				return $value;	
			}
			
			try {
				
				// Explicit calls to the constructors are used for dependency detection
				switch ($column_type) {
					case 'date':      $value = new vDate($value);      break;
					case 'time':      $value = new vTime($value);      break;
					case 'timestamp': $value = new vTimestamp($value); break;
				}
				
			} catch (vValidationException $e) {
				// Validation exception results in the raw value being saved
			}
		
		} else {
			self::$cache['objectify'][$class . '::' . $column] = TRUE;	
		}
		
		return $value;
	}
	
	
	/**
	 * Allows overriding of default column names
	 * 
	 * By default a column name is the result of vGrammar::humanize() called
	 * on the column.
	 * 
	 * @param  mixed  $class        The class name or instance of the class the column is located in
	 * @param  string $column       The database column
	 * @param  string $column_name  The name for the column
	 * @return void
	 */
	static public function overrideColumnName($class, $column, $column_name)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$column_names[$class])) {
			self::$column_names[$class] = array();
		}
		
		self::$column_names[$class][$column] = $column_name;
	}
	
	
	/**
	 * Allows overriding of default record names
	 * 
	 * By default a record name is the result of vGrammar::humanize() called
	 * on the class.
	 * 
	 * @param  mixed  $class        The class name or instance of the class to override the name of
	 * @param  string $record_name  The human version of the record
	 * @return void
	 */
	static public function overrideRecordName($class, $record_name)
	{
		$class = self::getClass($class);
		self::$record_names[$class] = $record_name;
	}
	
	
	/**
	 * Parses a `camelCase` method name for an action and subject in the form `actionSubject()`
	 *
	 * @internal
	 * 
	 * @param  string $method  The method name to parse
	 * @return array  An array of `0 => {action}, 1 => {subject}`
	 */
	static public function parseMethod($method)
	{
		if (isset(self::$cache['parseMethod'][$method])) {
			return self::$cache['parseMethod'][$method];	
		}
		
		if (!preg_match('#^([a-z]+)(.*)$#D', $method, $matches)) {
			throw new vProgrammerException(
				'Invalid method, %s(), called',
				$method
			);	
		}
		self::$cache['parseMethod'][$method] = array($matches[1], $matches[2]);
		return self::$cache['parseMethod'][$method];
	}
	
	
	/**
	 * Registers a callback for an vActiveRecord method that falls through to vActiveRecord::__call() or hits a predefined method hook
	 *  
	 * The callback should accept the following parameters:
	 * 
	 *  - **`$object`**:           The vActiveRecord instance
	 *  - **`&$values`**:          The values array for the record
	 *  - **`&$old_values`**:      The old values array for the record
	 *  - **`&$related_records`**: The related records array for the record
	 *  - **`&$cache`**:           The cache array for the record
	 *  - **`$method_name`**:      The method that was called
	 *  - **`&$parameters`**:      The parameters passed to the method
	 * 
	 * @param  mixed    $class     The class name or instance of the class to register for, `'*'` will register for all classes
	 * @param  string   $method    The method to hook for - this can be a complete method name or `{prefix}*` where `*` will match any column name
	 * @param  callback $callback  The callback to execute - see method description for parameter list
	 * @return void
	 */
	static public function registerActiveRecordMethod($class, $method, $callback)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$active_record_method_callbacks[$class])) {
			self::$active_record_method_callbacks[$class] = array();	
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$active_record_method_callbacks[$class][$method] = $callback;
		
		self::$cache['getActiveRecordMethod'] = array();
	}
	
	
	/**
	 * Registers a callback for one of the various vActiveRecord hooks - multiple callbacks can be registered for each hook
	 * 
	 * The method signature should include the follow parameters:
	 * 
	 *  - **`$object`**:           The vActiveRecord instance
	 *  - **`&$values`**:          The values array for the record - see the [http://veluslib.opensource.velusuniverse.com/docs/vORM#values $values] documentation for details
	 *  - **`&$old_values`**:      The old values array for the record - see the [http://veluslib.opensource.velusuniverse.com/docs/vORM#old_values $old_values] documentation for details
	 *  - **`&$related_records`**: The related records array for the record - see the [http://veluslib.opensource.velusuniverse.com/docs/vORM#related_records $related_records] documentation for details
	 *  - **`&$cache`**:           The cache array for the record - see the [http://veluslib.opensource.velusuniverse.com/docs/vORM#cache $cache] documentation for details
	 * 
	 * The `'pre::validate()'` and `'post::validate()'` hooks have an extra
	 * parameter:
	 * 
	 *  - **`&$validation_messages`**: An ordered array of validation errors that will be returned or tossed as an vValidationException - see the [http://veluslib.opensource.velusuniverse.com/docs/vORM#validation_messages $validation_messages] documentation for details
	 * 
	 * The `'pre::replicate()'`, `'post::replicate()'` and
	 * `'cloned::replicate()'` hooks have an extra parameter:
	 * 
	 *  - **`$replication_level`**: An integer representing the level of recursion - the object being replicated will be `0`, children will be `1`, grandchildren `2` and so on.
	 *  
	 * Below is a list of all valid hooks:
	 * 
	 *  - `'post::__construct()'`
	 *  - `'pre::delete()'`
	 *  - `'post-begin::delete()'`
	 *  - `'pre-commit::delete()'`
	 *  - `'post-commit::delete()'`
	 *  - `'post-rollback::delete()'`
	 *  - `'post::delete()'`
	 *  - `'post::loadFromIdentityMap()'`
	 *  - `'post::loadFromResult()'`
	 *  - `'pre::populate()'`
	 *  - `'post::populate()'`
	 *  - `'pre::replicate()'`
	 *  - `'post::replicate()'`
	 *  - `'cloned::replicate()'`
	 *  - `'pre::store()'`
	 *  - `'post-begin::store()'`
	 *  - `'post-validate::store()'`
	 *  - `'pre-commit::store()'`
	 *  - `'post-commit::store()'`
	 *  - `'post-rollback::store()'`
	 *  - `'post::store()'`
	 *  - `'pre::validate()'`
	 *  - `'post::validate()'`
	 * 
	 * @param  mixed    $class     The class name or instance of the class to hook, `'*'` will hook all classes
	 * @param  string   $hook      The hook to register for
	 * @param  callback $callback  The callback to register - see the method description for details about the method signature
	 * @return void
	 */
	static public function registerHookCallback($class, $hook, $callback)
	{
		$class = self::getClass($class);
		
		static $valid_hooks = array(
			'post::__construct()',
			'pre::delete()',
			'post-begin::delete()',
			'pre-commit::delete()',
			'post-commit::delete()',
			'post-rollback::delete()',
			'post::delete()',
			'post::loadFromIdentityMap()',
			'post::loadFromResult()',
			'pre::populate()',
			'post::populate()',
			'pre::replicate()',
			'post::replicate()',
			'cloned::replicate()',
			'pre::store()',
			'post-begin::store()',
			'post-validate::store()',
			'pre-commit::store()',
			'post-commit::store()',
			'post-rollback::store()',
			'post::store()',
			'pre::validate()',
			'post::validate()'
		);
		
		if (!in_array($hook, $valid_hooks)) {
			throw new vProgrammerException(
				'The hook specified, %1$s, should be one of: %2$s.',
				$hook,
				join(', ', $valid_hooks)
			);
		}
		
		if (!isset(self::$hook_callbacks[$class])) {
			self::$hook_callbacks[$class] = array();
		}
		
		if (!isset(self::$hook_callbacks[$class][$hook])) {
			self::$hook_callbacks[$class][$hook] = array();
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$hook_callbacks[$class][$hook][] = $callback;
	}
	
	
	/**
	 * Registers a callback to modify the results of vActiveRecord::inspect() methods
	 * 
	 * @param  mixed    $class     The class name or instance of the class to register for
	 * @param  string   $column    The column to register for
	 * @param  callback $callback  The callback to register. Callback should accept a single parameter by reference, an associative array of the various metadata about a column.
	 * @return void
	 */
	static public function registerInspectCallback($class, $column, $callback)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$inspect_callbacks[$class])) {
			self::$inspect_callbacks[$class] = array();
		}
		if (!isset(self::$inspect_callbacks[$class][$column])) {
			self::$inspect_callbacks[$class][$column] = array();
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$inspect_callbacks[$class][$column][] = $callback;
	}
	
	
	/**
	 * Registers a callback for when ::objectify() is called on a specific column
	 * 
	 * @param  mixed    $class     The class name or instance of the class to register for
	 * @param  string   $column    The column to register for
	 * @param  callback $callback  The callback to register. Callback should accept a single parameter, the value to objectify and should return the objectified value.
	 * @return void
	 */
	static public function registerObjectifyCallback($class, $column, $callback)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$objectify_callbacks[$class])) {
			self::$objectify_callbacks[$class] = array();
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$objectify_callbacks[$class][$column] = $callback;
		
		self::$cache['objectify'] = array();
	}
	
	
	/**
	 * Registers a callback for an vRecordSet method that fall through to vRecordSet::__call()
	 *  
	 * The callback should accept the following parameters:
	 * 
	 *  - **`$object`**:      The actual record set
	 *  - **`$class`**:       The class of each record
	 *  - **`&$records`**:    The ordered array of vActiveRecord objects
	 *  - **`$method_name`**: The method name that was called
	 *  - **`$parameters`**:  Any parameters passed to the method
	 * 
	 * @param  string   $method    The method to hook for
	 * @param  callback $callback  The callback to execute - see method description for parameter list
	 * @return void
	 */
	static public function registerRecordSetMethod($method, $callback)
	{
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		self::$record_set_method_callbacks[$method] = $callback;
	}
	
	
	/**
	 * Registers a callback to modify the results of vActiveRecord::reflect()
	 * 
	 * Callbacks registered here can override default method signatures and add
	 * method signatures, however any methods that are defined in the actual class
	 * will override these signatures.
	 * 
	 * The callback should accept three parameters:
	 * 
	 *  - **`$class`**: the class name
	 *  - **`&$signatures`**: an associative array of `{method_name} => {signature}`
	 *  - **`$include_doc_comments`**: a boolean indicating if the signature should include the doc comment for the method, or just the signature
	 * 
	 * @param  mixed    $class     The class name or instance of the class to register for, `'*'` will register for all classes
	 * @param  callback $callback  The callback to register. Callback should accept a three parameters - see method description for details.
	 * @return void
	 */
	static public function registerReflectCallback($class, $callback)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$reflect_callbacks[$class])) {
			self::$reflect_callbacks[$class] = array();
		} elseif (in_array($callback, self::$reflect_callbacks[$class])) {
			return;
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$reflect_callbacks[$class][] = $callback;
	}
	
	
	/**
	 * Registers a callback for when a value is replicated for a specific column
	 * 
	 * @param  mixed    $class     The class name or instance of the class to register for
	 * @param  string   $column    The column to register for
	 * @param  callback $callback  The callback to register. Callback should accept a single parameter, the value to replicate and should return the replicated value.
	 * @return void
	 */
	static public function registerReplicateCallback($class, $column, $callback)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$replicate_callbacks[$class])) {
			self::$replicate_callbacks[$class] = array();
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$replicate_callbacks[$class][$column] = $callback;
	}
	
	
	/**
	 * Registers a callback for when ::scalarize() is called on a specific column
	 * 
	 * @param  mixed    $class     The class name or instance of the class to register for
	 * @param  string   $column    The column to register for
	 * @param  callback $callback  The callback to register. Callback should accept a single parameter, the value to scalarize and should return the scalarized value.
	 * @return void
	 */
	static public function registerScalarizeCallback($class, $column, $callback)
	{
		$class = self::getClass($class);
		
		if (!isset(self::$scalarize_callbacks[$class])) {
			self::$scalarize_callbacks[$class] = array();
		}
		
		if (is_string($callback) && strpos($callback, '::') !== FALSE) {
			$callback = explode('::', $callback);	
		}
		
		self::$scalarize_callbacks[$class][$column] = $callback;
	}
	
	
	/**
	 * Takes and value and returns a copy is scalar or a clone if an object
	 * 
	 * The ::registerReplicateCallback() allows for custom replication code
	 *
	 * @internal
	 * 
	 * @param  string $class   The class the column is part of
	 * @param  string $column  The database column
	 * @param  mixed  $value   The value to copy/clone
	 * @return mixed  The copied/cloned value
	 */
	static public function replicate($class, $column, $value)
	{
		if (!empty(self::$replicate_callbacks[$class][$column])) {
			return call_user_func(self::$replicate_callbacks[$class][$column], $class, $column, $value);
		}
		
		if (!is_object($value)) {
			return $value;	
		}
		
		return clone $value;
	}
	
	
	/**
	 * Resets the configuration of the class
	 * 
	 * @internal
	 * 
	 * @return void
	 */
	static public function reset()
	{
		self::$active_record_method_callbacks = array();
		self::$cache                          = array(
			'parseMethod'           => array(),
			'getActiveRecordMethod' => array(),
			'objectify'             => array()
		);
		self::$class_database_map             = array(
			'vActiveRecord' => 'default'
		);
		self::$class_table_map                = array();
		self::$column_names                   = array();
		self::$hook_callbacks                 = array();
		self::$inspect_callbacks              = array();
		self::$objectify_callbacks            = array();
		self::$record_names                   = array(
			'vActiveRecord' => 'Active Record'
		);
		self::$record_set_method_callbacks    = array();
		self::$reflect_callbacks              = array();
		self::$related_class_names            = array();
		self::$replicate_callbacks            = array();
		self::$scalarize_callbacks            = array();
	}
	
	
	/**
	 * If the value passed is an object, calls `__toString()` on it
	 *
	 * @internal
	 * 
	 * @param  mixed  $class   The class name or instance of the class the column is part of
	 * @param  string $column  The database column
	 * @param  mixed  $value   The value to get the scalar value of
	 * @return mixed  The scalar value of the value
	 */
	static public function scalarize($class, $column, $value)
	{
		$class = self::getClass($class);
		
		if (!empty(self::$scalarize_callbacks[$class][$column])) {
			return call_user_func(self::$scalarize_callbacks[$class][$column], $class, $column, $value);
		}
		
		if (is_object($value) && is_callable(array($value, '__toString'))) {
			return $value->__toString();
		} elseif (is_object($value)) {
			return (string) $value;
		}
		
		return $value;
	}
	
	
	/**
	 * Takes a class name (or class) and turns it into a table name - Uses custom mapping if set
	 * 
	 * @param  string $class  The class name
	 * @return string  The table name
	 */
	static public function tablize($class)
	{
		if (!isset(self::$class_table_map[$class])) {
			self::$class_table_map[$class] = vGrammar::underscorize(vGrammar::pluralize(
				// Strip the namespace off the class name
				preg_replace(
					'#^.*\\\\#',
					'',
					$class
				)
			));
		}
		return self::$class_table_map[$class];
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return vORM
	 */
	private function __construct() { }
}



/**
 * Copyright (c) Alan Johnston of Velus Universe Ltd <alan.johnston@velusuniverse.co.uk>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
