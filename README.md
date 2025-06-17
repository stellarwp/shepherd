# Pigeon Message Delivery

[![WPunit](https://github.com/stellarwp/pigeon/actions/workflows/tests-php.yml/badge.svg)](https://github.com/stellarwp/pigeon/actions?query=branch%3Amain)
[![Static Analysis](https://github.com/stellarwp/pigeon/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/stellarwp/pigeon/actions/workflows/static-analysis.yml)
[![Compatibility](https://github.com/stellarwp/pigeon/actions/workflows/compatibility.yml/badge.svg)](https://github.com/stellarwp/pigeon/actions/workflows/compatibility.yml)

A library for offloading tasks to be handled asynchronously in WordPress.

## What is Pigeon?

Pigeon is a library for offloading tasks to be handled asynchronously in WordPress. It's designed to be a lightweight and flexible solution for handling tasks that need to be processed in the background, such as sending emails, processing payments, or updating data in the database.

Pigeon is built on top of the [Action Scheduler](https://actionscheduler.org/) library, which provides a robust and flexible way to manage background tasks.

## Why prefer Pigeon over Action Scheduler?

Pigeon is a wrapper of Action Scheduler. Whatever you can do with Action Scheduler, you can do with Pigeon.

Pigeon does offer out of the box the following features that are not available with Action Scheduler:

- A simple API for offloading already defined tasks. Such as sending emails or generating PDFs. You can see more in the [tasks documentation](./docs/tasks.md)!
- A retry system for failed tasks.
- A debounce system for tasks that are called multiple times in a short period of time.
- Supporting tasks dependencies.
- Being able to recognize tasks being rate limited.
- The arguments being passed to the task's handler can be significantly larger than what Action Scheduler allows. Action scheduler uses the column the arguments are stored in the database as an index. Pigeon instead uses a hash of the arguments as an index, and stores the arguments in a long text column.
