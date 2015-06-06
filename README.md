# SN Databse : Core

The SN Database api aims to provide a unified interface for database access without having to worry about the various PHP extension avilable for each database type.

This particular package brings the Core portion of the api : each database type will be provided through its own package and be implementing the classes of this one.

# Download

The easiest way to download the core tools is through Composer. Simply add the following to your composer requirements, where "~1.0" can be replaced by any version you need :

```
"sndb/core": "~1.0"
```

# Usage

Cannot be used on its own, you need to download a driver that uses it, or create your own.

# Creating your own databse driver

You can create your own database driver. The driver will automatically be looked for by the Factory class if you respect a few specitics :
* Factory subclass must be in \SNDatabase\Impl namespace
* Factory subclass must have a name like "FooFactory" for the "Foo" driver
* Other classes that must be extended : Connection (for connection link), PreparedStatement (for prepared statements), Result (for result sets) and Transaction (for transaction handling)

# API Reference

More detailed documentation is avilable as HTML files, in the docs/ subfolder.

# Testing

Unit tests have been provided, using PHPUnit, in the tests/ subfolder.

# Contributors

Samy Naamani <samy@namani.net>

# License

MIT