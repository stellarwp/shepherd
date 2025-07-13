# Contributing to Shepherd

Thank you for contributing to Shepherd! This guide outlines the standards and processes for commits and pull requests.

## Table of Contents

- [Before You Commit](#before-you-commit)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Guidelines](#pull-request-guidelines)
- [Code Standards](#code-standards)
- [Testing Requirements](#testing-requirements)
- [Documentation Requirements](#documentation-requirements)

## Before You Commit

### Pre-Commit Checklist

**MANDATORY**: Complete this checklist before every commit:

- [ ] **Run static analysis**: `composer test:analysis`
- [ ] **Check PHP compatibility**: `composer compatibility`
- [ ] **Run coding standards check**: `vendor/bin/phpcs`
- [ ] **Run tests**: `slic run wpunit && slic run integration`
- [ ] **Update documentation** if adding new features or changing behavior
- [ ] **Update CLAUDE.md** if changes affect AI assistance context
- [ ] **No debug code** (var_dump, error_log, etc.) left in production code
- [ ] **No commented-out code** unless specifically needed for reference

### Code Quality Requirements

All code must pass:

1. **PHPStan analysis** (level defined in `phpstan.neon.dist`)
2. **WordPress Coding Standards** via PHPCS
3. **PHP Compatibility** for supported versions (7.4+)
4. **All existing tests** must continue to pass

## Commit Guidelines

### Commit Message Format

Use the **Conventional Commits** specification:

```markdown
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

### Commit Types

- **feat**: A new feature
- **fix**: A bug fix
- **docs**: Documentation only changes
- **style**: Changes that do not affect the meaning of the code (white-space, formatting, etc)
- **refactor**: A code change that neither fixes a bug nor adds a feature
- **perf**: A code change that improves performance
- **test**: Adding missing tests or correcting existing tests
- **chore**: Changes to the build process or auxiliary tools

### Commit Message Examples

#### Good Examples

```markdown
feat(tasks): add HTTP request task with retry logic

Add new HTTP_Request task that can make GET/POST requests
with configurable retry attempts and timeout settings.

- Supports GET, POST, PUT, DELETE methods
- Configurable timeout and retry logic
- Validates URLs and handles common HTTP errors
- Includes comprehensive tests

Closes #123
```

```markdown
fix(regulator): prevent duplicate task scheduling race condition

Fix race condition where rapid successive calls to dispatch()
could result in duplicate tasks being scheduled.

- Add database transaction around task existence check
- Improve error handling for constraint violations
- Add test coverage for concurrent dispatching

Fixes #456
```

```markdown
docs(api): update Email task documentation

- Add troubleshooting section
- Include WordPress hook examples
- Fix incorrect retry count in examples
```

#### Bad Examples

```markdown
❌ Fixed stuff
❌ Update
❌ Changes
❌ WIP
❌ Quick fix
```

### Atomic Commits

- **One logical change per commit**
- **Don't mix** feature changes with formatting changes
- **Don't mix** multiple unrelated fixes
- **Keep commits focused** and easy to review

### Examples of Good Commit Separation

```bash
# Good - separate commits
git commit -m "feat(logger): add custom logger interface"
git commit -m "docs(logger): add custom logger documentation"
git commit -m "test(logger): add custom logger integration tests"

# Bad - mixed changes
git commit -m "add logger and fix typos and update tests"
```

## Pull Request Guidelines

### Before Opening a PR

**MANDATORY** Pre-PR Checklist:

- [ ] **All commit guidelines** are followed
- [ ] **Branch is up to date** with target branch (usually `main`)
- [ ] **All tests pass** locally
- [ ] **Documentation is updated** for any new features
- [ ] **CHANGELOG.md is updated** (if applicable)
- [ ] **No merge conflicts** exist
- [ ] **PR description** is complete and follows template

### PR Title Format

Use the same format as commit messages:

```markdown
<type>[optional scope]: <brief description>
```

Examples:

- `feat(tasks): add HTTP request task`
- `fix(regulator): prevent duplicate task race condition`
- `docs(tasks): improve Email task documentation`

### PR Description Template

```markdown
## Summary

Brief description of what this PR does and why.

## Type of Change

- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update
- [ ] Performance improvement
- [ ] Code refactoring

## Testing

- [ ] New tests have been added for new functionality
- [ ] All existing tests pass
- [ ] Manual testing has been performed

### Test Plan

Describe how you tested this change:

1. Step 1
2. Step 2
3. Expected result

## Documentation

- [ ] Code comments added/updated
- [ ] API documentation updated
- [ ] User documentation updated (if applicable)
- [ ] CLAUDE.md updated (if applicable)

## Breaking Changes

If this is a breaking change, describe:

1. What breaks
2. How to migrate existing code
3. Why this change was necessary

## Related Issues

Closes #123
Fixes #456
Related to #789
```

### PR Size Guidelines

- **Small PRs are preferred** (< 500 lines changed)
- **Large PRs** (> 500 lines) must be justified and well-documented
- **Consider splitting** large changes into multiple PRs
- **Focus on one feature/fix** per PR

### Code Review Process

1. **Self-review** your PR before requesting review
2. **Address all feedback** before requesting re-review
3. **Don't merge** until all conversations are resolved
4. **Squash related commits** before merging (if requested)

## Code Standards

### PHP Standards

- Follow **WordPress Coding Standards**
- Use **strict types** when possible: `declare(strict_types=1);`
- **Type hint** all parameters and return values
- Use **PHPDoc blocks** for all public methods
- **No unused imports** or variables

### Documentation Standards

- **Every public method** must have PHPDoc
- **Complex logic** should have inline comments
- **API changes** must be documented
- **Examples** should be included for new features

### Example of Good PHPDoc

```php
/**
 * Schedules a task for background execution.
 *
 * @since 1.0.0
 *
 * @param Task $task  The task to schedule.
 * @param int  $delay Delay in seconds before execution. Default 0.
 *
 * @throws ShepherdTaskException If task scheduling fails.
 * @throws ShepherdTaskAlreadyExistsException If duplicate task exists.
 *
 * @return void
 */
public function dispatch( Task $task, int $delay = 0 ): void {
    // Implementation
}
```

## Testing Requirements

### Required Tests

- **Unit tests** for all new classes and methods
- **Integration tests** for features that interact with WordPress/database
- **Regression tests** for bug fixes

### Test Organization

```markdown
tests/
├── wpunit/         # Unit tests (fast, isolated)
│   ├── Tasks/
│   ├── Loggers/
│   └── ...
├── integration/    # Integration tests (slower, uses DB)
│   ├── Tasks/
│   ├── Regulator/
│   └── ...
└── _support/       # Test helpers and fixtures
```

### Writing Good Tests

```php
/**
 * @test
 * @group tasks
 * @group email
 */
public function it_should_retry_failed_emails(): void {
    // Arrange
    $email = new Email( 'test@example.com', 'Subject', 'Body' );

    // Act & Assert
    $this->assertSame( 4, $email->get_max_retries() );
}
```

## Documentation Requirements

### Files to Update

When making changes, consider updating:

- **API Reference** (`docs/api-reference.md`) - for API changes
- **User Documentation** - for new features
- **CLAUDE.md** - for changes affecting AI assistance
- **CHANGELOG.md** - for notable changes
- **README.md** - for installation/setup changes

### Documentation Standards

- **Clear, concise language**
- **Working code examples**
- **Link to related documentation**
- **Include troubleshooting** for complex features

## Enforcement

### Automated Checks

The following are automatically checked:

- ✅ **PHPStan analysis**
- ✅ **Coding standards** (PHPCS)
- ✅ **PHP compatibility**
- ✅ **Test suite** execution

### Manual Review Points

Reviewers will check:

- ✅ **Commit message quality**
- ✅ **Code organization**
- ✅ **Documentation completeness**
- ✅ **Test coverage**
- ✅ **Breaking changes** are justified and documented

## Getting Help

- **Questions about guidelines?** Open a discussion
- **Need help with tests?** Check existing test examples
- **Stuck on standards?** Run the quality checks locally first

## Quick Reference

### Before Every Commit

```bash
composer test:analysis
composer compatibility
vendor/bin/phpcs
slic run wpunit
slic run integration
```

### Before Every PR

- Run all pre-commit checks
- Update documentation
- Fill out PR template completely

---

**Remember**: These guidelines exist to maintain code quality and make collaboration easier. When in doubt, ask for clarification rather than guessing!
