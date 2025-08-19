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

## Related Issues

<!-- Link to related issues using GitHub keywords -->

<!-- Ignore if not applicable -->

Closes #<!-- issue number -->

## Pre-Submission Checklist

<!-- Verify all items before submitting this PR -->

### Code Quality

- [ ] **PHPStan analysis passes**: `composer test:analysis`
- [ ] **PHP compatibility check passes**: `composer compatibility`
- [ ] **Coding standards pass**: `vendor/bin/phpcs`
- [ ] **All tests pass**: `slic run wpunit && slic run integration`
- [ ] **No debug code** (var_dump, error_log, etc.) left in production code
- [ ] **No commented-out code** unless specifically needed for reference
- [ ] **Documentation updated** for any new features or changed behavior
- [ ] **New tests** have been added for new functionality
- [ ] **All existing tests** continue to pass

**📖 Read the full contributing guidelines: [CONTRIBUTING.md](/.github/CONTRIBUTING.md)**
