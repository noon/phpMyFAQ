<?php
/**
 * The PMF_DB_Sqlsrv class provides methods and functions for SQL Server 2005
 * Driver for PHP from Microsoft
 * databases.
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-02-18
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id$
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */

/**
 * PMF_DB_Sqlsrv
 *
 * @package    phpMyFAQ
 * @subpackage PMF_DB
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-02-18
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id$
 */
class PMF_DB_Sqlsrv implements PMF_DB_Driver 
{
    /**
     * The connection object
     *
     * @var PMF_DB_Driver
     */
    private $conn = false;

    /**
     * The query log string
     *
     * @var string
     */
    private $sqllog = '';
    
    /**
     * Connection options array
     * 
     * @var array
     */
    private $connectionOptions = array();

    /**
     * Tables
     *
     * @var array
     */
    public $tableNames = array();

    /**
     * Connects to the database.
     *
     * This function connects to a MySQL database
     *
     * @param  string $host     A string specifying the name of the server to which a connection is being established
     * @param  string $user     Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param  string $passwd   Specifies the password associated with the User ID to be used when connecting with 
     *                          SQL Server Authentication
     * @param  string $database Specifies the name of the database in use for the connection being established
     * @return boolean true, if connected, otherwise false
     */
    public function connect($host, $user, $passwd, $database)
    {
    	$this->setConnectionOptions($user, $passwd, $database);
    	
        $this->conn = sqlsrv_connect($host, $this->connectionOptions);
        if (!$this->conn) {
            PMF_Db::errorPage(sqlsrv_errors());
            die();
        }
        return true;
    }
    
    /**
     * Sets the connection options
     *
     * @param  string $user     Specifies the User ID to be used when connecting with SQL Server Authentication
     * @param  string $passwd   Specifies the password associated with the User ID to be used when connecting with 
     *                          SQL Server Authentication
     * @param  string $database Specifies the name of the database in use for the connection being established
     * @return void
     */
    private function setConnectionOptions($user, $passwd, $database)
    {
    	$this->connectionOptions = array(
    	   'UID'      => $user,
    	   'PWD'      => $passwd,
    	   'Database' => $database);
    }
    
    /**
     * Sends a query to the database.
     *
     * @param  string $query Query
     * @return mixed
     */
    public function query($query)
    {
    	return sqlsrv_query($this->conn, $query);
    }

    /**
     * Escapes a string for use in a query
     *
     * @param  string $string String
     * @return string
     */
    public function escape_string($string)
    {
    	return str_replace("'", "''", $string);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  resource $result Resultset
     * @return resource
     */
    public function fetch_object($result)
    {
    	return sqlsrv_fetch_object($result);
    }

    /**
     * Fetch a result row as an object
     *
     * @param  resource $result Resultset
     * @return array
     */
    public function fetch_assoc($result)
    {
    	return sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    }

    /**
     * Fetches a complete result as an object
     *
     * @param  resource     $result Resultset
     * @return PMF_DB_Mysql
     */
    public function fetchAll($result)
    {
        $ret = array();
        if (false === $result) {
            throw new Exception('Error while fetching result: ' . $this->error());
        }
        
        while ($row = $this->fetch_object($result)) {
            $ret[] = $row;
        }
        
        return $ret;
    }
    
    /**
     * Number of rows in a result
     *
     * @param  resource $result Resultset
     * @return integer
     */
    public function num_rows($result)
    {
    	
    }

    /**
     * Logs the queries
     *
     * @return integer
     */
    public function sqllog()
    {
        return $this->sqllog;
    }

    /**
     * Generates a result based on search a search string.
     *
     * @param  string $table       Table for search
     * @param  array  $assoc       Associative array with columns for the resulset
     * @param  string $joinedTable Table to do a JOIN, e.g. for faqcategoryrelations
     * @param  array  $joinAssoc   Associative array with comlumns for the JOIN
     * @param  string $string      Search term
     * @param  array  $cond        Conditions
     * @param  array  $orderBy     ORDER BY columns
     * @return mixed
     */
    public function search($table, Array $assoc, $joinedTable = '', Array $joinAssoc = array(), $match = array(), $string = '', Array $cond = array(), Array $orderBy = array())
    {
    	
    }

    /**
     * This function returns the table status.
     *
     * @return array
     */
    public function getTableStatus()
    {
        $tables = array();
        $query  = "
            SELECT
                obj.name AS table_name,
                idx.rows AS table_rows
            FROM
                sysobjects obj, sysindexes idx
            WHERE
                    idx.id = OBJECT_ID(obj.name)
                AND idx.indid < 2
                AND obj.xtype = 'u'
            ORDER BY obj.name";
        $result = $this->query($query);

        while ($row = $this->fetch_object($result)) {
            if ('dtproperties' != $row->table_name) {
                $tables[$row->table_name] = $row->table_rows;
            }
        }

        return $tables;
    }

    /**
     * Returns the next ID of a table
     *
     * @param  string $table the name of the table
     * @param  string $id    the name of the ID column
     * @return int
     */
    public function nextID($table, $id)
    {
    	$select = sprintf("
    	   SELECT 
    	       max(%d) as current_id 
    	   FROM 
    	       %s",
    	   $id,
    	   $table);
    	
        $result = $this->query($select);
        $stmt   = sqlsrv_fetch($result);
        return (sqlsrv_get_field($stmt, 0) + 1);
    }

    /**
     * Returns the error string.
     *
     * This function returns the last error string.
     *
     * @access  public
     */
    public function error()
    {
    	$errors = sqlsrv_errors();
    	return $errors['SQLSTATE'] . ': ' . $errors['message'];
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function client_version()
    {
    	$client_info = sqlsrv_client_info($this->conn);
    	return $client_info['DriverODBCVer'] . ' ' . $client_info['DriverVer'];
    }

    /**
     * Returns the libary version string.
     *
     * @return string
     */
    public function server_version()
    {
    	$server_info = sqlsrv_server_info($this->conn);
    	return $server_info['SQLServerVersion'];
    }

    /**
     * Gets an array with all table names
     *
     * @return void
     */
    public function getTableNames($prefix = '')
    {
    	
    }

    /**
     * Closes the connection to the database.
     *
     * @return void
     */
    public function dbclose()
    {
    	sqlsrv_close($this->conn);
    }
}
