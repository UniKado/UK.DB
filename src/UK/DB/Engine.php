<?php
/**
 * @author         UniKado <unikado+pubcode@protonmail.com>
 * @copyright  (c) 2016, UniKado
 * @package        UK\DB
 * @since          2016-06-27
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace UK\DB;


/**
 * The UK\DB\Engine enum fake interface.
 *
 * @since v0.1.0
 */
interface Engine
{

   /**
    * Using the MySQL DB engine.
    */
   const MYSQL = 'mysql';

   /**
    * Using the PostGre-SQL DB engine.
    */
   const PGSQL = 'pgsql';

   /**
    * Use the SQLite DB engine.
    */
   const SQLITE = 'sqlite';

   /**
    * All known engines as numeric indicated array
    */
   const KNOWN_ENGINES = [ self::MYSQL, self::PGSQL, self::SQLITE ];

}

