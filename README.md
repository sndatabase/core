# SN Database : Core

The SN Database api aims to provide a unified interface for database access without having to worry about the various PHP extension avilable for each database type.

This particular package brings the Core portion of the api : each database type will be provided through its own package and be implementing the classes of this one.

# Download

This package is a requirement for actual drivers, and will be downloaded automatically by Composer upon requiring a driver.

# Usage

Cannot be used on its own, you need to download a driver that uses it, or create your own.

# Creating your own databse driver

You can create your own database driver. An instance of the driver factory must be registered to the DB class before the driver can be used.
This can be automatized by using the "files" subsection of the composer autoload descriptor for the driver.

# API Reference

More detailed documentation is avilable as HTML files, in the docs/ subfolder.

# Testing

Unit tests have been provided, using PHPUnit, in the tests/ subfolder.

# Contributors

Samy Naamani <samy@namani.net>

# License

MIT