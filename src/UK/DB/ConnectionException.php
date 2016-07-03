<?php
/**
 * This file defines the exception class {@see \UK\DB\Exception}.
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
class ConnectionException extends Exception
{


   # <editor-fold desc="= = =   P U B L I C   C O N S T U C T O R   = = = = = = = = = = = = = = = = = = = = = =">

   /**
    * Initialisiert eine neue Instanz.
    *
    * @param array      $connectionSettings
    * @param string     $message
    * @param mixed      $code
    * @param \Exception $previous
    */
   public function __construct(
      array $connectionSettings, string $message = null, $code = \E_USER_WARNING, \Exception $previous = null )
   {

      parent::__construct(
         \sprintf(
            "%s connection error (host=%s%s; dbname=%s; user=%s; password=%s)",
            \ucfirst( $connectionSettings[ 'engine' ] ),
            $connectionSettings[ 'host' ],
            empty( $connectionSettings[ 'port' ] ) ? '' : ( '; port=' . $connectionSettings[ 'port' ] ),
            $connectionSettings[ 'dbname'],
            empty( $connectionSettings[ 'username' ] ) ? '[undefined]' : '[defined]',
            empty( $connectionSettings[ 'password' ] ) ? '[undefined]' : '[defined]',
            empty( $connectionSettings[ 'charset' ] ) ? '' : ( '; charset=' . $connectionSettings[ 'charset' ] )
         ) . $this->appendMessage( $message ),
         $code,
         $previous
      );

   }

   # </editor-fold>


}

