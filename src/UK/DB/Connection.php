<?php
/**
 * This file defines the {@see \UK\DB\Connection} class.
 *
 * @author         UniKado <unikado+pubcode@protonmail.com>
 * @copyright  (c) 2016, UniKado
 * @package        UK\DB
 * @since          2016-06-27
 * @version        0.1.1
 */


declare( strict_types = 1 );


namespace UK\DB;


/**
 * This class defines a PostGreSQL, MySQL or SQLite connection.
 *
 * The first instance, created inside a Application is usable after init, via the static ::GetInstance().
 *
 * If you will use a other Connection instance as globally instance you can set it by calling ::SetInstance(…)
 * 
 * <code>
 * use \UK\DB\Connection;
 * use \UK\DB\Engine;
 *
 * $pdo = new Connection(
 *    Engine::PGSQL,
 *    'localhost',
 *    'dbname',
 *    'username',
 *    'password',
 *    'utf-8'
 * );
 * $name = $pdo->fetchScalar( 'SELECT user_id FROM udos_user where user_name = ?', [ 'Administrator' ] );
 * var_dump( $name );
 * </code>
 *
 * Introducing Query-Vars - v0.1.1 (Pre prepared statements)
 *
 * <b>Query-Vars</b> are key-value pairs, used to replace some placeholders inside an SQL query string
 * with the associated string values.
 *
 * Its like the regular known prepared statements but usable for query parts where prepared statements will not work.
 * e.g. for an dynamic table name part or something else…
 *
 * <b>Placeholder format restrictions</b>
 *
 * Placeholders inside the SQL query string must:
 *
 * - start with an open curly bracket, followed by the Dollar symbol `{$` and end with the closing curly bracket `}`
 * - be defined by the 2 parts placeholder-name and default value, separated by the equal sign `=`
 *
 * The default value is already used if no Query-Var is defined by PHP code.
 *
 * If no default value should be used the equal sign is mandatory but it throws an exception
 * if no replacement value is declared!
 *
 * <code>
 * %PlaceholderName=DefaultValue%
 * </code>
 *
 * or 2 variants without an default value
 *
 * <code>
 * %PlaceholderName=%
 *
 * %PlaceholderName%
 * <code>
 *
 * <b>Value format restrictions</b>
 *
 * - An Query-Vars value must be an string (not null or something else)
 * - It should not contain two following dashes `--`
 * - Accepted chars are: `A-Za-z0-9 \t?_:.<=>-`
 *
 * For example:
 *
 * If you want to use the following SQL
 *
 * <code>
 * SELECT
 *       foo,
 *       bar
 *    FROM
 *       my_table
 *    WHERE
 *       foo > ?
 *    ORDER BY
 *       foo ASC
 * <code>
 *
 * but the order direction part should be dynamic, you can use
 *
 * <code>
 * SELECT
 *       foo,
 *       bar
 *    FROM
 *       my_table
 *    WHERE
 *       foo > ?
 *    ORDER BY
 *       foo {$ORDER_DIRECTION=ASC}
 * <code>
 *
 * Example to call this SQL command
 *
 * <code>
 * $records = $connectionInstance->fetchAll(
 *    // The SQL query string
 *    'SELECT foo, bar FROM my_table WHERE foo > ? ORDER BY foo {$ORDER_DIRECTION=ASC}',
 *    // Prepared statement parameters
 *    [ 0 ],
 *    \PDO::FETCH_ASSOC
 *    // The query vars
 *    [ 'ORDER_DIRECTION' => 'DESC' ]
 * );
 * <code>
 *
 * @since v0.1
 */
class Connection extends \PDO
{

   
   # <editor-fold desc="= = =   P R I V A T E   F I E L D S   = = = = = = = = = = = = = = = = = = = = = = = = =">

   private $properties = [
      'engine'   => '',
      'host'     => '',
      'dbname'   => '',
      'username' => '',
      'password' => '',
      'charset'  => '',
      'port'     => null,
      'dsn'      => ''
   ];

   # </editor-fold>

   
   # <editor-fold desc="= = =   P R I V A T E   S T A T I C   F I E L D S   = = = = = = = = = = = = = = = = = =">

   /**
    * @var \UK\DB\Connection
    */
   private static $instance = null;

   # </editor-fold>

   
   # <editor-fold desc="= = =   P U B L I C   C O N S T U C T O R   = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Init's a new instance.
    *
    * @param  string  $engine   (see {@see \UK\DB\Engine::MYSQL}, {@see \UK\DB\Engine::PGSQL}
    *                           or {@see \UK\DB\Engine::SQLITE})
    * @param  string  $host     The DBMS server host name or IP address.
    * @param  string  $dbName   The name of the database. For SQLite define the db file path here
    * @param  string  $username The login user name.
    * @param  string  $passwd   The login password
    * @param  string  $charset  The client + connection charset (default='UTF8')
    * @param  int     $port     Optional port if different from default.
    * @throws \UK\DB\ConnectionException  If init fails.
    */
   public function __construct( string $engine, string $host, string $dbName, string $username, string $passwd,
                                string $charset = 'UTF8', int $port = null )
   {

      $options = [ ];

      $this->properties = [
         'engine'   => $engine,
         'host'     => $host,
         'dbname'   => $dbName,
         'username' => $username,
         'password' => $passwd,
         'charset'  => $charset,
         'port'     => $port,
         'dsn'      => ''
      ];

      $this->buildDSN();

      switch ( $engine )
      {

         case Engine::PGSQL:
            try
            {
               parent::__construct( $this->properties[ 'dsn' ], $username, $passwd );
               $this->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
            }
            catch ( \Exception $ex )
            {
               throw new ConnectionException( $this->properties, 'Connection init fails!', \E_ERROR, $ex );
            }
            if ( ! empty( $charset ) )
            {
               try
               {
                  $this->query( 'set client_encoding to ' . $charset );
               }
               catch ( \Exception $ex )
               {
                  throw new ConnectionException(
                     $this->properties,
                     'Setting the connection charset fails!',
                     \E_ERROR,
                     $ex
                  );
               }
            }
            break;

         case Engine::MYSQL:
            if ( ! empty( $charset ) )
            {
               $options = array( \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . $charset );
            }
            try
            {
               parent::__construct( $this->properties[ 'dsn' ], $username, $passwd, $options );
               $this->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
            }
            catch ( \Exception $ex )
            {
               throw new ConnectionException( $this->properties, 'Connection init fails!', \E_ERROR, $ex );
            }
            break;

         case Engine::SQLITE:
            try
            {
               parent::__construct( $this->properties[ 'dsn' ] );
               $this->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
            }
            catch ( \Exception $ex )
            {
               throw new ConnectionException( $this->properties, 'Connection init fails!', \E_ERROR, $ex );
            }
            break;

         default:
            throw new ConnectionException( $this->properties, 'Connection init fails!' );

      }

      if ( \is_null( self::$instance ) )
      {
         self::$instance = $this;
      }

   }

   # </editor-fold>

   
   # <editor-fold desc="= = =   P U B L I C   M E T H O D S   = = = = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Fetches the first found record and returns it as a array. FALSE is returned if nothing was found.
    *
    * @param  string  $sql        The SQL statement to use.
    * @param  array   $bindParams Optional binding params for prepared statements (default=array())
    * @param  integer $fetchStyle The fetch style (default=\PDO::FETCH_ASSOC)
    * @param  array   $queryVars  Pre prepared statement Query-Vars bind values. (since v0.1.1)
    * @return array|FALSE
    * @throws \PDOException
    * @throws \UK\DB\QueryException
    */
   public function fetchRecord(
      string $sql, array $bindParams = [], int $fetchStyle = \PDO::FETCH_ASSOC, array $queryVars = [] )
   {

      if ( ! \preg_match( '~\s+LIMIT\s\d+~i', $sql ) )
      {
         $sql = \rtrim( $sql, ';' ) . ' LIMIT 1';
      }

      if ( \count( $bindParams ) > 0 )
      {
         $stm = $this->prepare( $this->parseQueryVars( $sql, $queryVars ) );
         $stm->execute( $bindParams );
         $rows = $stm->fetchAll( $fetchStyle );
      }
      else
      {
         $rows = $this->query( $this->parseQueryVars( $sql, $queryVars ) )
                      ->fetchAll( $fetchStyle );
      }

      if ( ! \is_array( $rows ) || \count( $rows ) < 1 )
      {
         return false;
      }

      return $rows[ 0 ];

   }

   /**
    * Fetches the first column value from the resulting found first record.
    *
    * @param  string  $sql        The SQL statement to use.
    * @param  array   $bindParams Optional binding params for prepared statements (default=array())
    * @param  mixed   $defaultValue THis value is returned if no record was found by the query.
    * @param  array   $queryVars  Pre prepared statement Query-Vars bind values. (since v0.1.1)
    * @return mixed
    * @throws \PDOException
    * @throws \UK\DB\QueryException
    */
   public function fetchScalar( string $sql, array $bindParams = [], $defaultValue = false, array $queryVars = [] )
   {

      if ( false === ( $row = $this->fetchRecord( $sql, $bindParams, \PDO::FETCH_NUM, $queryVars ) ) )
      {
         return $defaultValue;
      }

      if ( \count( $row ) < 1 )
      {
         // No arms no cookies :-/
         return $defaultValue;
      }

      // Ensure we can access the required value by a numeric key 0
      $row = \array_values( $row );

      return $row[ 0 ];

   }

   /**
    * Fetches the found records and returns it as a array. FALSE is returned if nothing was found.
    *
    * @param  string  $sql        The SQL statement to use.
    * @param  array   $bindParams Optional binding params for prepared statements (default=array())
    * @param  integer $fetchStyle The fetch style (default=\PDO::FETCH_ASSOC)
    * @param  array   $queryVars  Pre prepared statement Query-Vars bind values. (since v0.1.1)
    * @return array|FALSE
    * @throws \PDOException
    * @throws \UK\DB\QueryException
    */
   public function fetchAll(
      string $sql, array $bindParams = [], int $fetchStyle = \PDO::FETCH_ASSOC, array $queryVars = [] )
   {

      if ( \count( $bindParams ) > 0 )
      {
         $stm = $this->prepare( $this->parseQueryVars( $sql, $queryVars ) );
         $stm->execute( $bindParams );
         $rows = $stm->fetchAll( $fetchStyle );
      }
      else
      {
         $rows = $this->query( $this->parseQueryVars( $sql, $queryVars ) )->fetchAll( $fetchStyle );
      }

      if ( ! \is_array( $rows ) || \count( $rows ) < 1 )
      {
         return false;
      }

      return $rows;
   }

   /**
    * Sets an connection charset.
    *
    * @param  string $charset
    * @return bool
    */
   public final function setConnectionCharset( string $charset = 'utf8' ) : bool
   {

      if ( $this->properties[ 'engine' ] == Engine::SQLITE ||
           \strtolower( $this->properties[ 'charset' ] ) === \strtolower( $charset ) )
      {
         return true;
      }

      try
      {
         if ( $this->properties[ 'engine' ] == Engine::PGSQL )
         {
            $this->exec( 'set client_encoding to ' . $charset );
         }
         else
         {
            $this->exec( 'SET NAMES ' . $charset );
         }
      }
      catch ( \Exception $ex )
      {
         $ex = null;
         return false;
      }

      $this->properties[ 'charset' ] = $charset;

      return true;

   }

   /**
    * Execute the SQL query with the bind params as prepared statement.
    *
    * @param  string $sql
    * @param  array  $bindParams
    * @param  array  $queryVars  Pre prepared statement Query-Vars bind values. (since v0.1.1)
    * @return bool
    * @throws \UK\DB\QueryException
    * @throws \PDOException
    * @throws \UK\DB\QueryException
    */
   public function execute( string $sql, array $bindParams = [], array $queryVars = [] )
   {
      try
      {
         $stmt = $this->prepare( $this->parseQueryVars( $sql, $queryVars ) );
         return $stmt->execute( $bindParams );
      }
      catch ( \Exception $ex )
      {
         throw new QueryException( $this->properties, $sql, '', E_USER_ERROR, $ex );
      }
   }

   /**
    * Returns the type of the based DBMS engine ({@see \UK\DB\Engine::PGSQL}, {@see \UK\DB\Engine::MYSQL}
    * or {@see \UK\DB\Engine::SQLITE})
    *
    * @return string
    */
   public function getEngine() : string
   {

      return $this->properties[ 'engine' ];

   }

   /**
    * Returns the current used Server host name or IP address.
    *
    * @return string
    */
   public function getHost() : string
   {

      return $this->properties[ 'host' ];

   }

   /**
    * Returns the name of the currently connected database.
    *
    * @return string
    */
   public function getDatabaseName() : string
   {

      return $this->properties[ 'dbname' ];

   }

   /**
    * Returns the username to login at DBMS server
    *
    * @return string
    */
   public function getUserName() : string
   {

      return $this->properties[ 'username' ];

   }

   /**
    * Returns the connection/client charset.
    *
    * @return string
    */
   public function getCharset() : string
   {

      return $this->properties[ 'charset' ];

   }

   /**
    * Returns the currently used server port. If no special is defined the default port of current engine is returned
    *
    * @return integer
    */
   public function getPort() : int
   {

      $port = ( $this->properties[ 'engine' ] === Engine::PGSQL )
         ? 5432
         : ( $this->properties[ 'engine' ] === Engine::PGSQL )
            ? 3306
            : 0;

      return empty( $this->properties[ 'port' ] )
         ? $port
         : $this->properties[ 'port' ];

   }

   public function getSettings() : array
   {

      return $this->properties;

   }

   /**
    * Returns how many records was found.
    *
    * @param  string       $table    The name of the table
    * @param  string|array $where    Optional WHERE clause
    * @param  array        $bindings Binding params for prepared statements of WHERE clause part
    * @return int                    Returns the count of found records.
    */
   public final function count( string $table, $where = null, array $bindings = null ) : int
   {

      $sql = "SELECT COUNT(*) AS cnt FROM {$table}" . $this->formatWhere( $where );

      return \intval( $this->fetchScalar( $sql, $bindings, 0 ) );

   }

   # </editor-fold>

   
   # <editor-fold desc="= = =   P R O T E C T E D   M E T H O D S   = = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Builds the DSN from current settings and saves it internally.
    *
    * @throws \UK\DB\ConnectionException
    */
   protected function buildDSN()
   {

      switch ( $this->properties[ 'engine' ] )
      {

         case Engine::PGSQL:
            $dsn = \sprintf( 'pgsql:host=%s', $this->properties[ 'host' ] );
            if ( ! empty( $this->properties[ 'dbname' ] ) )
            {
               $dsn .= \sprintf( ';dbname=%s', $this->properties[ 'dbname' ] );
            }
            if ( \is_int( $this->properties[ 'port' ] ) && ! empty( $this->properties[ 'port' ] ) )
            {
               $dsn .= ';port=' . $this->properties[ 'port' ];
            }
            $this->properties[ 'dsn' ] = $dsn;
            break;

         case Engine::MYSQL:
            $dsn = \sprintf( 'mysql:host=%s', $this->properties[ 'host' ] );
            if ( ! empty( $this->properties[ 'dbname' ] ) )
            {
               $dsn .= \sprintf( ';dbname=%s', $this->properties[ 'dbname' ] );
            }
            if ( \is_int( $this->properties[ 'port' ] ) && ! empty( $this->properties[ 'port' ] ) )
            {
               $dsn .= ';port=' . $this->properties[ 'port' ];
            }
            $this->properties[ 'dsn' ] = $dsn;
            break;

         case Engine::SQLITE:
            $dsn = 'sqlite:';
            if ( ! empty( $this->properties[ 'dbname' ] ) ) { $dsn .= $this->properties[ 'dbname' ]; }
            else { $dsn .= 'memory:'; }
            $this->properties[ 'dsn' ] = $dsn;
            break;

         default:
            throw new ConnectionException( $this->properties, 'Connection init fails!' );

      }

   }

   # </editor-fold>

   
   # <editor-fold desc="= = =   P R I V A T E   M E T H O D S   = = = = = = = = = = = = = = = = = = = = = = = = =">

   private function formatWhere( $where )
   {

      if ( empty( $where ) )
      {
         // No WHERE clause => return a empty string
         return '';
      }

      if ( \is_array( $where ) )
      {
         // WHERE defined by a array
         $whereStr = ' WHERE ';

         if ( \join( '', \range( 0, \count( $where ) - 1 ) ) === \join( '', \array_keys( $where ) ) )
         {
            // Numeric indicated array [ 'Condition 1', 'Condition 2', … ]

            // Loop the defined WHERE conditions
            for ( $i = 0; $i < \count( $where ); ++$i )
            {
               if ( $i > 0 )
               {
                  // Combine with OR
                  $whereStr .= ' OR ';
               }
               $whereStr .= $where[ $i ];
            }
            return $whereStr;
         }

         // This is an associative array

         $i = 0;
         foreach ( $where as $k => $v )
         {
            if ( \is_array( $v ) && \count( $v ) >= 2 )
            {
               if ( $i > 0 )
               {
                  $whereStr .= " {$v[0]} ";
               }
               if ( isset( $v[ 2 ] ) )
               {
                  $whereStr .= "(`{$k}`=" . $this->quote( $v[ 1 ], $v[ 2 ] ) . ')';
               }
               else
               {
                  if ( \is_bool( $v[ 1 ] ) )
                  {
                     $whereStr .= "(`{$k}`=" . ($v[ 1 ] ? 1 : 0) . ')';
                  }
                  else if ( \is_integer( $v[ 1 ] ) || \is_double( $v[ 1 ] ) || $v[ 1 ] = '?' )
                  {
                     $whereStr .= "(`{$k}`=" . $v[ 1 ] . ')';
                  }
                  else
                  {
                     $whereStr .= "(`{$k}`=" . $this->quote( $v[ 1 ] ) . ')';
                  }
               }
               ++$i;
               continue;
            }
            if ( $i > 0 )
            {
               $whereStr .= " OR ";
            }
            if ( \is_bool( $v ) )
            {
               $whereStr .= "(`{$k}`=" . ($v ? 1 : 0) . ')';
            }
            else if ( \is_integer( $v ) || \is_double( $v ) || $v = '?' )
            {
               $whereStr .= "(`{$k}`=" . $v . ')';
            }
            else
            {
               $whereStr .= "(`{$k}`=" . $this->quote( $v ) . ')';
            }
            ++$i;
         }

         return $whereStr;

      }

      $whereStr = \trim( $where );
      if ( \strlen( $whereStr ) > 6 )
      {
         if ( \strtolower( \substr( $whereStr, 0, 6 ) ) != 'where ' )
         {
            return ' WHERE ' . $whereStr;
         }
         return " {$whereStr}";
      }

      return " WHERE {$whereStr}";

   }

   /**
    * …
    *
    * @param  string $sql
    * @param  array $queryVars
    * @return string
    * @since  v0.1.1
    */
   private function parseQueryVars( string $sql, array $queryVars ) : string
   {

      if ( \count( $queryVars ) < 1 ) { return $sql; }

      return \preg_replace_callback(
         '~\\{\\$([A-Za-z0-9_.-]+)((\s*=)([A-Za-z0-9 \t?_:.<=>-]+)?)?\\}~',
         function( $m ) use ( $queryVars, $sql )
         {
            $defaultValue = null;
            if ( ! empty( $m[ 4 ] ) )
            {
               $defaultValue = \trim( $m[ 4 ] );
            }
            if ( isset( $queryVars[ $m[ 1 ] ] ) )
            {
               if ( false !== \strpos( $queryVars[ $m[ 1 ] ], '--' ) ||
                    ! \preg_match( '~^[A-Za-z0-9 \t?_:.<=>-]+$~', $queryVars[ $m[ 1 ] ] ) )
               {
                  throw new \UK\DB\QueryException(
                     $this->properties,
                     $sql,
                     'The defined query variable "' . $m[ 1 ] . '" defines an value with invalid format!'
                  );
               }
               return $queryVars[ $m[ 1 ] ];
            }
            if ( \is_null( $defaultValue ) )
            {
               throw new \UK\DB\QueryException(
                  $this->properties,
                  $sql,
                  'The query declares an query variable placeholder "' . $m[ 1 ] .
                  '" without default value and without and assigned replacement value!'
               );
            }
            return $defaultValue;
         },
         $sql
      );

   }

   # </editor-fold>

   
   # <editor-fold desc="= = =   P U B L I C   S T A T I C   M E T H O D S   = = = = = = = = = = = = = = = = = = =">

   /**
    * Returns, if a global Connection instance is defined.
    *
    * @return bool
    */
   public static function HasInstance() : bool
   {

      return ! \is_null( self::$instance );

   }

   /**
    * Returns the currently defined global \UK\DB\Connection instance.
    *
    * @return \UK\DB\Connection|null
    */
   public static function GetInstance() : Connection
   {

      return self::$instance;

   }

   public static function SetInstance( Connection $instance ) : Connection
   {

      self::$instance = $instance;

      return self::$instance;

   }

   /**
    * Returns if the current connection known an database with defined name.
    *
    * @param  string $db
    * @return bool
    */
   public static function DatabaseExists( string $db ) : bool
   {

      if ( ! self::HasInstance() ||
             self::$instance->properties[ 'engine' ] == Engine::SQLITE ||
           ! \preg_match( '~^[A-Za-z_][A-Za-z_0-9]*$~', $db ) )
      {
         return false;
      }

      $instance = self::GetInstance();

      if ( $instance->properties[ 'engine' ] == Engine::PGSQL )
      {
         $sql = "SELECT EXISTS ( SELECT true FROM information_schema.tables WHERE table_name = '{$db}');";
         return $instance->fetchScalar( $sql );
      }

      $query = "SHOW DATABASES LIKE '{$db}'";

      return ( false !== $instance->fetchScalar( $query ) );

   }

   /**
    * Returns if the defined table exists inside the used database.
    *
    * If $db is defined it is used as database name. Otherwise the db name of current global connection instance
    * is used.
    *
    * @param  string $table
    * @param  string $db
    * @return boolean
    */
   public static function TableExists( string $table, $db = null ) : bool
   {

      if ( ! self::HasInstance() || ! \preg_match( '~^[A-Za-z_][A-Za-z_0-9.-]*$~', $table ) )
      {
         return false;
      }

      $instance = self::GetInstance();

      if ( empty( $db ) )
      {
         $db = $instance->properties[ 'dbname' ];
      }

      switch ( $instance->properties[ 'engine' ] )
      {

         case Engine::PGSQL:
            $query = "
               SELECT
                  EXISTS
                  (
                     SELECT
                           true
                        FROM
                           information_schema.tables
                        WHERE
                           table_name = ?
                              AND
                           table_catalog = ?
                  )";
            return $instance->fetchScalar( $query, [ $table, $db ], false );

         case Engine::MYSQL:
            $query = "
               SELECT
                     COUNT(*)
                  FROM
                     information_schema.TABLES
                  WHERE
                     TABLE_SCHEMA=?
                        AND
                     TABLE_NAME=?;";
            $res = \intval( $instance->fetchScalar( $query, array( $db, $table ), 0 ) );
            return ! empty( $res );

         //case Engine::SQLITE:
         default:
            $query = "
               SELECT
                     COUNT(*)
                  FROM
                     sqlite_master
                  WHERE
                     type='table'
                        AND
                     name=:tablename;";
            #$tmp = $instance->fetchScalar( $query, [ ':tablename' => $table ], 0 );
            #var_dump( $tmp ); exit;
            $res = \intval( $instance->fetchScalar( $query, [ ':tablename' => $table ], 0 ) );
            return ! empty( $res );

      }

   }

   /**
    * Creates a new PGSQL connection and returns it.
    *
    * @param  string   $host           The DBMS host name or IP address
    * @param  string   $dbName         The name of the database that should be selected.
    * @param  string   $username       The login username.
    * @param  string   $passwd         The login password.
    * @param  string   $charset        Optional connection charset (Default is 'UTF8')
    * @param  int|null $port           Optional DB Port (Default is 5432)
    * @param  bool     $registerGlobal Register the created instance globally?
    * @return \UK\DB\Connection
    */
   public static function CreatePgSQL(
         string $host, string $dbName, string $username, string $passwd, string $charset = 'UTF8',
         int $port = 5432, bool $registerGlobal = true )
      : Connection
   {

      if ( ! $registerGlobal )
      {
         $conn = new Connection( Engine::PGSQL, $host, $dbName, $username, $passwd, $charset, $port );
         return $conn;
      }

      self::$instance = new Connection( Engine::PGSQL, $host, $dbName, $username, $passwd, $charset, $port );
      return self::$instance;

   }

   /**
    * Creates a new SQLITE connection and returns it.
    *
    * @param  string   $dbFile         The SQLITE db file. Empty means create in memory
    * @param  bool     $registerGlobal Register the created instance globally?
    * @return \UK\DB\Connection
    */
   public static function CreateSQLite( string $dbFile = null, bool $registerGlobal = true ) : Connection
   {

      if ( ! $registerGlobal )
      {
         $conn = new Connection( Engine::SQLITE, '', $dbFile, '', '' );
         return $conn;
      }

      self::$instance = new Connection( Engine::SQLITE, '', $dbFile, '', '' );
      return self::$instance;

   }

   # </editor-fold>

   
}

