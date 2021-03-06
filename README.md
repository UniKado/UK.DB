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

## Version history

### 0.1.2

#### Fixing [first issue #1](https://github.com/UniKado/UK.DB/issues/1)

This adds 2 new methods to [\UK\DB\Connection](https://github.com/UniKado/UK.DB/blob/master/src/UK/DB/Connection.php)

* [\UK\DB\Connection::getParseQueryVarsAlways() : bool](https://github.com/UniKado/UK.DB/blob/master/src/UK/DB/Connection.php#LC546)
* [\UK\DB\Connection::setParseQueryVarsAlways( bool $value )](https://github.com/UniKado/UK.DB/blob/master/src/UK/DB/Connection.php#LC562)

The methods let you set or get a flag that declares if the parsing of used SQL query string for defined Query-Vars should
be executed always. Otherwise it will only be parsed if there are some Query-Vars defined.

### 0.1.1

#### Introducing Query-Vars. (Pre prepared statements)

**Query-Vars** are key-value pairs, used to replace some placeholders inside an SQL query string
with the associated string values.

Its like the regular known prepared statements but usable for query parts where prepared statements will not work.
e.g. for an dynamic table name part or something else…

##### Placeholder format restrictions

Placeholders inside the SQL query string must:

* start with an open curly bracket, followed by the Dollar symbol `{$` and end with the closing curly bracket `}`
* be defined by the 2 parts placeholder-name and default value, separated by the equal sign `=`

The default value is already used if no Query-Var is defined by PHP code.

If no default value should be used the equal sign is mandatory but it throws an exception
if no replacement value is declared!

```
{$PlaceholderName=DefaultValue}
```

or 2 variants without an default value

```
{$PlaceholderName=}

{$PlaceholderName}
```

##### Value format restrictions

* An Query-Vars value must be an string (not null or something else)
* It should not contain two following dashes `--`
* Accepted chars are: `A-Za-z0-9 \t?_:.<=>-`

For example:

If you want to use the following SQL

```sql
SELECT
      foo,
      bar
   FROM
      my_table
   WHERE
      foo > ?
   ORDER BY
      foo ASC
```

but the order direction part should be dynamic, you can use

```sql
SELECT
      foo,
      bar
   FROM
      my_table
   WHERE
      foo > ?
   ORDER BY
      foo {$ORDER_DIRECTION=ASC}
```

Example to call this SQL command

```php
$records = $connectionInstance->fetchAll(
   // The SQL query string
   'SELECT foo, bar FROM my_table WHERE foo > ? ORDER BY foo {$ORDER_DIRECTION=ASC}',
   // Prepared statement parameters
   [ 0 ],
   \PDO::FETCH_ASSOC
   // The query vars
   [ 'ORDER_DIRECTION' => 'DESC' ]
);
```

### v0.1

This is the initial first commit.