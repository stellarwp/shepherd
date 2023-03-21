# Pigeon Message Delivery

[![CI](https://github.com/stellarwp/pigeon/workflows/CI/badge.svg)](https://github.com/stellarwp/pigeon/actions?query=branch%3Amain) [![Static Analysis](https://github.com/stellarwp/pigeon/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/stellarwp/pigeon/actions/workflows/static-analysis.yml)

A library for delivering transactional emails asynchronously in WordPress.

## Installation

It's recommended that you install Pigeon as a project dependency via [Composer](https://getcomposer.org/):

```bash
composer require stellarwp/pigeon
```

## Getting started

For a full understanding of what is available in this library and how to use it, definitely read through the full [documentation](#documentation). But for folks that want to get rolling with the basics quickly, try out the following.

### Initializing the library

### Creating a new Delivery Module

Let's say you want a new custom table called `sandwiches` (with the default WP prefix, it'd be `wp_sandwiches`). You'll need a class file for the table. For the sake of this example, we'll be assuming this class is going into a `Tables/` directory and is reachable via the `Boom\Shakalaka\Tables` namespace.

### That's it!

The table will be automatically registered, created, and updated during the `plugins_loaded` action at priority `1000`! _(that priority number is filterable via the `stellarwp_schema_up_plugins_loaded_priority` filter)_

## Documentation

Here's some more advanced documentation to get you rolling on using this library at a deeper level:

1. [Setting up Strauss](/docs/strauss-setup.md)
1. [Schema management](/docs/schemas.md)
1. [Table schemas](/docs/schemas-table.md)
	1. [Versioning](/docs/schemas-table.md#versioning)
	1. [Registering tables](/docs/schemas-table.md#registering-tables)
	1. [Deregistering tables](/docs/schemas-table.md#deregistering-tables)
	1. [Table collection](/docs/schemas-table.md#table-collection)
	1. [Publicly accessible methods](/docs/schemas-table.md#publicly-accessible-methods)
1. [Field schemas](/docs/schemas-field.md)
	1. [Versioning](/docs/schemas-field.md#versioning)
	1. [Registering fields](/docs/schemas-field.md#registering-field)
	1. [Deregistering fields](/docs/schemas-field.md#deregistering-fields)
	1. [Field collection](/docs/schemas-field.md#field-collection)
	1. [Publicly accessible methods](/docs/schemas-field.md#publicly-accessible-methods)
1. [Automated testing](/docs/automated-testing.md)

## Acknowledgements

Special props go to [@lucatume](https://github.com/lucatume) and [@stratease](https://github.com/stratease) for their initial work on this structure before it was extracted into a standalone library.
