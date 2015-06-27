# SN Database : Core

The SN Database api aims to provide a unified interface for database access without having to worry about the various PHP extension avilable for each database type.

This particular package brings the Core portion of the api : each database type will be provided through its own package and be implementing the classes of this one.

# Download

This package is a requirement for actual drivers, and will be downloaded automatically by Composer upon requiring a driver.

# Usage

Cannot be used on its own, you need to download a driver that uses it, or create your own.
Still, most of the behaviour is defined here.

The main class is \SNDatabase\DB, where drivers are registered and constants are defined.
A driver is registered by registering an instance of its factory into this class.
This is usually done automatically by composer.

```php
<?php
use SNDatabase\DB;
use SNDatabase\MySQL\MySQLFactory;

DB::register(new MySQLFactory());
```

Once the driver is registered, you can at any time request a new connection by giving DB a connection string (like an URL) :

```php
<?php
use SNDatabase\DB;
use SNDatabase\DriverException;
use SNDatabase\ConnectionFailedException;
use SNDatabase\DBException;

try {
    $cnxString = 'mysql://user:password@localhost:3306?charset=utf8#my_database';
    $cnx = DB::getConnection($cnxString);
}
catch(DriverException $ex) {
   throw $ex; // and crash
}
catch(ConnectionFailedException $ex) {
   error_log("Connection failed : " . $ex->getMessage());
   echo "Sorry, connection failed";
}
catch(DBException $ex) {
   error_log("SQL error : " . $ex->getMessage());
   echo "Sorry, SQL error";
}
```

You can also ask DB for a connection string object, to help you build the connection string.
```php
<?php
use SNDatabase\DB;
use SNDatabase\DriverException;
use SNDatabase\ConnectionFailedException;
use SNDatabase\DBException;

try {
    $cnxString = DB::getConnectionString('mysql');
    $cnxString->setHost('localhost');
    $cnxString->setUser('user');
    $cnxString->setPwd('password');
    $cnxString->setPort(3306);
    $cnxString->setCharset('utf8');
    $cnxString->setDbname('my_database');
    $cnx = DB::getConnection($cnxString->toString());
}
catch(DriverException $ex) {
   throw $ex; // and crash
}
catch(ConnectionFailedException $ex) {
   error_log("Connection failed : " . $ex->getMessage());
   echo "Sorry, connection failed";
}
catch(DBException $ex) {
   error_log("SQL error : " . $ex->getMessage());
   echo "Sorry, SQL error";
}
```

Either way, once you have a connection, you can use it to perform various tasks.
If you want to execute a statement without bothering with a result set, use the exec() method.
If you do want the result set, use the query() method.

For statemets where you need to add parameters, you'll need to use either perform() or prepare() methods to get thet Statement object first.
The perform() gives you parameterized, but not prepared, statement, which is the suggested way to go if you do not need to execute your statement repeatedly.

```php
<?php
use SNDatabase\DB;
use SNDatabase\DriverException;
use SNDatabase\ConnectionFailedException;
use SNDatabase\DBException;

try {
    $cnxString = 'mysql://user:password@localhost:3306?charset=utf8#my_database';
    $cnx = DB::getConnection($cnxString);
    $stmt = $cnx->perform('SELECT id, nick FROM Members WHERE id = :id;');
    $stmt->bindValue(':id', 5, DB::PARAM_INT); // binds value as integer
    $stmt->execute();
    $result = $stmt->getResultset();
}
catch(DriverException $ex) {
   throw $ex; // and crash
}
catch(ConnectionFailedException $ex) {
   error_log("Connection failed : " . $ex->getMessage());
   echo "Sorry, connection failed";
}
catch(DBException $ex) {
   error_log("SQL error : " . $ex->getMessage());
   echo "Sorry, SQL error";
}
```

The result set can be fetched manually, or you can set a fetch mode (DB::FETCH_ASSOC by default) and let foreach() do the job.
Indeed, the result set is iterable.

# Creating your own databse driver

You can create your own database driver. An instance of the driver factory must be registered to the DB class before the driver can be used.
This can be automatized by using the "files" subsection of the composer autoload descriptor for the driver.

# API Reference

To generate the documentation, use the apigen.neon file to generate it in a "docs" folder

```
> apigen generate
```

# Testing

Coming soon, in /tests subfolder...

# Contributors

Samy Naamani <samy@namani.net>

# License

MIT