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
class Exception extends \UK\Exception
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
   public function __construct( string $message, $code = \E_USER_WARNING, \Exception $previous = null )
   {

      parent::__construct( "UK.DB ERROR: " . $message, $code, $previous );

   }

   # </editor-fold>


}

