## Summary

<!-- Provide a brief description of what this PR does and why it's needed -->

## Type of Change

- [ ] 🐛 Bug fix (non-breaking change which fixes an issue)
- [ ] ✨ New feature (non-breaking change which adds functionality)
- [ ] 💥 Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] 📚 Documentation update
- [ ] ⚡ Performance improvement
- [ ] ♻️ Code refactoring
- [ ] 🧪 Test improvements
- [ ] 🔧 Chore (build process, dependencies, etc.)

## Pre-Submission Checklist

<!-- Verify all items before submitting this PR -->

### Code Quality

- [ ] **PHPStan analysis passes**: `composer test:analysis`
- [ ] **PHP compatibility check passes**: `composer compatibility`
- [ ] **Coding standards pass**: `vendor/bin/phpcs`
- [ ] **All tests pass**: `slic run wpunit && slic run integration`
- [ ] **No debug code** (var_dump, error_log, etc.) left in production code
- [ ] **No commented-out code** unless specifically needed for reference

### Documentation & Communication

- [ ] **Documentation updated** for any new features or changed behavior
- [ ] **CLAUDE.md updated** if changes affect AI assistance context
- [ ] **Commit messages** follow [conventional commits format](/.github/CONTRIBUTING.md#commit-message-format)
- [ ] **PR title** follows conventional format: `<type>[scope]: description`

### Branch & Conflicts

- [ ] **Branch is up to date** with target branch (usually `main`)
- [ ] **No merge conflicts** exist
- [ ] **CHANGELOG.md updated** (if applicable)

## Testing

### Automated Testing

- [ ] New tests have been added for new functionality
- [ ] All existing tests continue to pass
- [ ] Test coverage is maintained or improved

### Manual Testing

<!-- Describe how you tested this change manually -->

**Test Environment:**

- WordPress version:
- PHP version:

**Test Steps:**

1. **Setup**: What initial setup is required? (e.g., `composer install`, activate plugins)
2. **Steps to Reproduce**:
   - Go to '...'
   - Click on '....'
   - Execute '....'
   - Verify '....'
3. **Verification**: What is the expected outcome?

**Expected Results:**
<!-- What should happen -->

**Actual Results:**
<!-- What actually happened -->

## Documentation Updates

- [ ] Code comments added/updated for complex logic
- [ ] PHPDoc blocks added/updated for public methods
- [ ] API documentation updated (`docs/api-reference.md`)
- [ ] User documentation updated (if applicable)
- [ ] Examples provided for new features

## Breaking Changes

<!-- If this is a breaking change, describe the impact -->

**What breaks:**
<!-- Describe what existing functionality will no longer work -->

**Migration path:**
<!-- Provide clear instructions for updating existing code -->

**Justification:**
<!-- Explain why this breaking change is necessary -->

## Related Issues

<!-- Link to related issues using GitHub keywords -->

Closes #<!-- issue number -->
Fixes #<!-- issue number -->
Related to #<!-- issue number -->

## Additional Context

<!-- Add any other context, screenshots, or information about the PR here -->

### Performance Impact
<!-- If applicable, describe any performance implications -->

### Security Considerations
<!-- If applicable, describe any security implications -->

### Screenshots

<!-- If your changes include UI components, provide before/after screenshots -->

**Before:**
<!-- Add screenshot here -->

**After:**
<!-- Add screenshot here -->

---

## For Reviewers

### Review Checklist

- [ ] Code follows WordPress coding standards
- [ ] Proper error handling is implemented
- [ ] Security best practices are followed
- [ ] Performance implications are acceptable
- [ ] Documentation is complete and accurate
- [ ] Tests provide adequate coverage
- [ ] Breaking changes are properly documented

---

<!--
By submitting this PR, I confirm that:
- I have read and followed the contributing guidelines
- My code follows the project's coding standards
- I have performed a self-review of my own code
- I have made corresponding changes to the documentation
- My changes generate no new warnings or errors
- I have added tests that prove my fix is effective or that my feature works
- New and existing unit tests pass locally with my changes
-->

**📖 Read the full contributing guidelines: [CONTRIBUTING.md](/.github/CONTRIBUTING.md)**
