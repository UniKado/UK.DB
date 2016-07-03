# UK.DB
The UniKado database DB library

## Usage

```php
use UK\DB\Connection;

try
{

   // Open the PGSQL connection (for example)
   $conn = Connection::CreatePgSQL(
      '127.0.0.1',
      'my_database',
      'db_user',
      'db_password',
      'UTF8',
      5432
   );

   // Fetch all records with an foo value > 0
   $records = $conn->fetchAll(
      'SELECT foo, bar, baz from my_table WHERE foo > ?',
      [ 0 ]
   );

   // Output the returned records
   print_r( $records );

}
catch ( \Exception $ex )
{

   echo $ex;
   exit;

}
```
