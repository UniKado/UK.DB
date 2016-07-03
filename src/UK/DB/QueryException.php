<?php
/**
 * This file defines the exception class {@see \UK\DB\QueryException}.
 *
 * @author         UniKado <unikado+pubcode@protonmail.com>
 * @copyright  (c) 2016, UniKado
 * @package        UK\DB
 * @since          2016-06-27
 * @version        0.1.0
 */


declare( strict_types = 1 );


namespace UK\DB;


/**
 * @since v0.1
 */
class QueryException extends ConnectionException
{


   # <editor-fold desc="= = =   P U B L I C   C O N S T U C T O R   = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Initialisiert eine neue Instanz.
    *
    * @param array      $connectionSettings
    * @param string     $query
    * @param string     $message
    * @param mixed      $code
    * @param \Exception $previous
    */
   public function __construct(
      array $connectionSettings, string $query, string $message = null,
      $code = \E_USER_WARNING, \Exception $previous = null )
   {

      parent::__construct(
         $connectionSettings,
         'ERROR-QUERY: ' . $query . static::appendMessage( $message ),
         $code,
         $previous
      );

   }

   # </editor-fold>

   
}

