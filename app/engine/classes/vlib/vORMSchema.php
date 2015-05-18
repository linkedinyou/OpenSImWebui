<?php
/**
 * Provides vSchema class related functions for ORM code
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
 * @link       http://veluslib.opensource.velusuniverse.com/vORMSchema
 */
class vORMSchema
{
	// The following constants allow for nice looking callbacks to static methods
	const attach                       = 'vORMSchema::attach';
	const getRoute                     = 'vORMSchema::getRoute';
	const getRouteName                 = 'vORMSchema::getRouteName';
	const getRouteNameFromRelationship = 'vORMSchema::getRouteNameFromRelationship';
	const getRoutes                    = 'vORMSchema::getRoutes';
	const isOneToOne                   = 'vORMSchema::isOneToOne';
	const reset                        = 'vORMSchema::reset';
	const retrieve                     = 'vORMSchema::retrieve';
	
	
	/**
	 * A cache for computed information
	 * 
	 * @var array
	 */
	static private $cache = array(
		'getRoutes' => array()
	);
	
	
	/**
	 * The schema objects to use for all ORM functionality
	 * 
	 * @var array
	 */
	static private $schema_objects = array();
	
	
	/**
	 * Allows attaching an vSchema-compatible object as the schema singleton for ORM code
	 * 
	 * @param  vSchema $schema  An object that is compatible with vSchema
	 * @param  string  $name    The name of the database this schema is for
	 * @return void
	 */
	static public function attach($schema, $name='default')
	{
		self::$schema_objects[$name] = $schema;
	}
	
	
	/**
	 * Returns information about the specified route
	 * 
	 * @internal
	 * 
	 * @param  vSchema $schema             The schema object to get the route from
	 * @param  string  $table              The main table we are searching on behalf of
	 * @param  string  $related_table      The related table we are searching under
	 * @param  string  $route              The route to get info about
	 * @param  string  $relationship_type  The relationship type: `NULL`, `'*-to-many'`, `'*-to-one'`, `'!many-to-one'`, `'one-to-one'`, `'one-to-meny'`, `'many-to-one'`, `'many-to-many'`
	 * @return void
	 */
	static public function getRoute($schema, $table, $related_table, $route, $relationship_type=NULL)
	{
		$valid_relationship_types = array(
			NULL,
			'*-to-many',
			'*-to-one',
			'!many-to-one',
			'many-to-many',
			'many-to-one',
			'one-to-many',
			'one-to-one'
		);
		if (!in_array($relationship_type, $valid_relationship_types)) {
			$valid_relationship_types[0] = '{null}';
			throw new vProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$relationship_type,
				join(', ', $valid_relationship_types)
			);
		}
		
		if ($route === NULL) {
			$route = self::getRouteName($schema, $table, $related_table, $route, $relationship_type);
		}
		
		$routes = self::getRoutes($schema, $table, $related_table, $relationship_type);
		
		if (!isset($routes[$route])) {
			throw new vProgrammerException(
				'The route specified, %1$s, for the%2$srelationship between %3$s and %4$s does not exist. Must be one of: %5$s.',
				$route,
				($relationship_type) ? ' ' . $relationship_type . ' ' : ' ',
				$table,
				$related_table,
				join(', ', array_keys($routes))
			);
		}
		
		return $routes[$route];
	}
	
	
	/**
	 * Returns the name of the only route from the specified table to one of its related tables
	 * 
	 * @internal
	 * 
	 * @param  vSchema $schema             The schema object to get the route name from
	 * @param  string  $table              The main table we are searching on behalf of
	 * @param  string  $related_table      The related table we are trying to find the routes for
	 * @param  string  $route              The route that was preselected, will be verified if present
	 * @param  string  $relationship_type  The relationship type: `NULL`, `'*-to-many'`, `'*-to-one'`, `'!many-to-one'`, `'one-to-one'`, `'one-to-many'`, `'many-to-one'`, `'many-to-many'`
	 * @return string  The only route from the main table to the related table
	 */
	static public function getRouteName($schema, $table, $related_table, $route=NULL, $relationship_type=NULL)
	{
		$valid_relationship_types = array(
			NULL,
			'*-to-many',
			'*-to-one',
			'!many-to-one',
			'many-to-many',
			'many-to-one',
			'one-to-many',
			'one-to-one'
		);
		if (!in_array($relationship_type, $valid_relationship_types)) {
			$valid_relationship_types[0] = '{null}';
			throw new vProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$relationship_type,
				join(', ', $valid_relationship_types)
			);
		}
		
		$routes = self::getRoutes($schema, $table, $related_table, $relationship_type);
		
		if (!empty($route)) {
			if (isset($routes[$route])) {
				return $route;
			}
			throw new vProgrammerException(
				'The route specified, %1$s, is not a valid route between %2$s and %3$s. Must be one of: %4$s.',
				$route,
				$table,
				$related_table,
				join(', ', array_keys($routes))
			);
		}
		
		$keys = array_keys($routes);
		
		if (sizeof($keys) > 1) {
			throw new vProgrammerException(
				'There is more than one route for the%1$srelationship between %2$s and %3$s. Please specify one of the following: %4$s.',
				($relationship_type) ? ' ' . $relationship_type . ' ' : ' ',
				$table,
				$related_table,
				join(', ', array_keys($routes))
			);
		}
		if (sizeof($keys) == 0) {
			throw new vProgrammerException(
				'The table %1$s is not in a%2$srelationship with the table %3$s',
				$table,
				($relationship_type) ? ' ' . $relationship_type . ' ' : ' ',
				$related_table
			);
		}
		
		return $keys[0];
	}
	
	
	/**
	 * Returns the name of the route specified by the relationship
	 * 
	 * @internal
	 * 
	 * @param  string $type          The type of relationship: `'*-to-one'`, `'one-to-one'`, `'one-to-many'`, `'many-to-one'`, `'many-to-many'`
	 * @param  array  $relationship  The relationship array from vSchema::getKeys()
	 * @return string  The name of the route
	 */
	static public function getRouteNameFromRelationship($type, $relationship)
	{
		$valid_types = array('*-to-one', 'one-to-one', 'one-to-many', 'many-to-one', 'many-to-many');
		if (!in_array($type, $valid_types)) {
			throw new vProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$type,
				join(', ', $valid_types)
			);
		}
		
		if (isset($relationship['join_table']) || $type == 'many-to-many') {
			return $relationship['join_table'];
		}
		
		if ($type == 'one-to-many') {
			return $relationship['related_column'];
		}
		
		return $relationship['column'];
	}
	
	
	/**
	 * Returns an array of all routes from a table to one of its related tables
	 * 
	 * @internal
	 * 
	 * @param  vSchema $schema             The schema object to get the routes for
	 * @param  string  $table              The main table we are searching on behalf of
	 * @param  string  $related_table      The related table we are trying to find the routes for
	 * @param  string  $relationship_type  The relationship type: `NULL`, `'*-to-many'`, `'*-to-one'`, `'!many-to-one'`, `'one-to-one'`, `'one-to-many'`, `'many-to-one'`, `'many-to-many'`
	 * @return array  All of the routes from the main table to the related table
	 */
	static public function getRoutes($schema, $table, $related_table, $relationship_type=NULL)
	{
		$key = $table . '::' . $related_table . '::' . $relationship_type;
		if (isset(self::$cache['getRoutes'][$key])) {
			return self::$cache['getRoutes'][$key];	
		}
		
		$valid_relationship_types = array(
			NULL,
			'*-to-many',
			'*-to-one',
			'!many-to-one',
			'many-to-many',
			'many-to-one',
			'one-to-many',
			'one-to-one'
		);
		if (!in_array($relationship_type, $valid_relationship_types)) {
			$valid_relationship_types[0] = '{null}';
			throw new vProgrammerException(
				'The relationship type specified, %1$s, is invalid. Must be one of: %2$s.',
				$relationship_type,
				join(', ', $valid_relationship_types)
			);
		}
		
		$all_relationships = $schema->getRelationships($table);
		
		if (!in_array($related_table, $schema->getTables())) {
			throw new vProgrammerException(
				'The related table specified, %1$s, does not exist in the database',
				$related_table
			);
		}
		
		$routes = array();
		
		foreach ($all_relationships as $type => $relationships) {
			
			// Filter the relationships by the relationship type
			if ($relationship_type !== NULL) {
				if ($relationship_type == '!many-to-one') {
					if ($type == 'many-to-one') {
						continue;
					}
				} else {
					if (strpos($type, str_replace('*', '', $relationship_type)) === FALSE) {
						continue;
					}
				}
			}
			
			foreach ($relationships as $relationship) {
				if ($relationship['related_table'] == $related_table) {
					if ($type == 'many-to-many') {
						$routes[$relationship['join_table']] = $relationship;
					} elseif ($type == 'one-to-many') {
						$routes[$relationship['related_column']] = $relationship;
					} else {
						$routes[$relationship['column']] = $relationship;
					}
				}
			}
		}
		
		self::$cache['getRoutes'][$key] = $routes;
		
		return $routes;
	}
	
	
	/**
	 * Indicates if the relationship specified is a one-to-one relationship
	 * 
	 * @internal
	 * 
	 * @param  vSchema $schema         The schema object the tables are from
	 * @param  string  $table          The main table we are searching on behalf of
	 * @param  string  $related_table  The related table we are trying to find the routes for
	 * @param  string  $route          The route between the two tables
	 * @return boolean  If the table is in a one-to-one relationship with the related table over the route specified
	 */
	static public function isOneToOne($schema, $table, $related_table, $route=NULL)
	{
		$relationships = self::getRoutes($schema, $table, $related_table, 'one-to-one', $route);
		
		if ($route === NULL && sizeof($relationships) > 1) {
			throw new vProgrammerException(
				'There is more than one route for the%1$srelationship between %2$s and %3$s. Please specify one of the following: %4$s.',
				' one-to-one ',
				$table,
				$related_table,
				join(', ', array_keys($relationships))
			);
		}
		if (!$relationships) {
			return FALSE;	
		}
		
		foreach ($relationships as $relationship) {
			if ($route === NULL || $route == $relationship['column']) {
				return TRUE;
			} 		
		}
		
		return FALSE;
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
		self::$schema_objects = array();
	}
	
	
	/**
	 * Return the instance of the vSchema class
	 * 
	 * @param  string $class  The class the object will be used with
	 * @return vSchema  The schema instance
	 */
	static public function retrieve($class='vActiveRecord')
	{
		if (substr($class, 0, 5) == 'name:') {
			$database_name = substr($class, 5);
		} else {
			$database_name = vORM::getDatabaseName($class);
		}
		
		if (!isset(self::$schema_objects[$database_name])) {
			self::$schema_objects[$database_name] = new vSchema(vORMDatabase::retrieve($class));
		}
		
		return self::$schema_objects[$database_name];
	}
	
	
	/**
	 * Forces use as a static class
	 * 
	 * @return vORMSchema
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