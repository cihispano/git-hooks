# Conventional Commits Guide

This package enforces the [Conventional Commits](https://www.conventionalcommits.org/) specification for commit messages.

## Format

```bash
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

## Types

### Primary Types

| Type | Description | When to Use |
| ---- | ----------- | ----------- |
| `feat` | New feature | Adding new functionality |
| `fix` | Bug fix | Fixing a bug |
| `docs` | Documentation | Changes to documentation only |
| `style` | Code style | Formatting, missing semicolons, etc. (no code change) |
| `refactor` | Code refactoring | Code change that neither fixes a bug nor adds a feature |
| `test` | Tests | Adding or updating tests |
| `chore` | Maintenance | Other changes that don't modify src or test files |

### Additional Types

| Type | Description | When to Use |
| ---- | ----------- | ----------- |
| `perf` | Performance | Code changes that improve performance |
| `ci` | CI/CD | Changes to CI configuration files and scripts |
| `build` | Build system | Changes to build system or external dependencies |
| `revert` | Revert | Reverts a previous commit |

## Scope (Optional)

The scope provides additional contextual information and is contained within parentheses:

```bash
feat(auth): add password reset functionality
fix(database): resolve connection timeout
docs(api): update endpoint documentation
```

**Common scopes in CodeIgniter projects:**

- `auth` - Authentication
- `api` - API endpoints
- `database` - Database operations
- `config` - Configuration
- `model` - Models
- `controller` - Controllers
- `view` - Views
- `helper` - Helpers
- `library` - Libraries
- `validation` - Validation rules

## Description

The description is a short summary of the code changes:

- Use imperative, present tense: "change" not "changed" nor "changes"
- Don't capitalize the first letter
- No period (.) at the end
- Maximum 100 characters

### ✅ Good Examples

```bash
feat(auth): add email verification
fix(api): resolve timeout in user endpoint
docs: update installation instructions
refactor(model): simplify query builder logic
```

### ❌ Bad Examples

```bash
feat(auth): Added email verification.     ❌ (capitalized, past tense, period)
fix: fixed bug                            ❌ (too vague)
Updated docs                              ❌ (missing type)
FEAT: NEW FEATURE                         ❌ (all caps)
```

## Body (Optional)

The body should include the motivation for the change and contrast this with previous behavior:

```bash
feat(auth): add password reset functionality

Users can now request a password reset link via email.
The link expires after 1 hour for security purposes.

Implements #123
```

## Footer (Optional)

The footer should contain information about Breaking Changes and reference GitHub issues:

```bash
feat(api): change authentication endpoint

BREAKING CHANGE: The /auth endpoint now requires API version header

Closes #456
```

## Breaking Changes

A commit that has breaking changes should include `BREAKING CHANGE:` in the footer or `!` after the type/scope:

```bash
feat(api)!: redesign authentication flow

BREAKING CHANGE: The authentication flow has been completely redesigned.
All API clients need to update their implementation.
```

## Real-World Examples

### Feature Addition

```bash
feat(user): add user profile page

- Create user profile controller
- Add profile view with user information
- Include avatar upload functionality

Closes #234
```

### Bug Fix

```bash
fix(auth): prevent duplicate session creation

Fixed an issue where multiple sessions were created
for the same user during concurrent login attempts.

Fixes #567
```

### Documentation

```bash
docs(readme): add installation instructions for Docker

Added step-by-step guide for:
- Docker container setup
- Environment configuration
- Database initialization
```

### Refactoring

```bash
refactor(database): optimize query builder

Improved query builder performance by:
- Reducing unnecessary query parsing
- Implementing query caching
- Optimizing join operations

Performance improvement: ~30% faster queries
```

### Performance Improvement

```bash
perf(cache): implement Redis for session storage

Switched from file-based to Redis-based session storage.
Reduces session load time by 60% under high traffic.

Benchmark results documented in the project notes.
```

### Tests

```bash
test(auth): add login flow coverage

- Test successful login
- Test failed login attempts
- Test account lockout after failed attempts
- Test password reset flow

Coverage improved for the authentication flow
```

## Commit Message Validation

The `commit-msg` hook will validate your commit messages against these rules:

### Minimum Requirements

- ✅ Minimum 10 characters
- ✅ Maximum 100 characters for the first line
- ✅ Valid type (feat, fix, docs, etc.)
- ✅ Conventional Commits format

### Common Mistakes

The hook will reject commit messages with issues such as:

- Missing Conventional Commits type
- Invalid `type(scope): description` structure
- Titles shorter than 10 characters
- Titles longer than 100 characters
- Trailing period in the title

## Tips

1. **Be consistent** - Use the same type for similar changes
2. **Keep it atomic** - One logical change per commit
3. **Be descriptive** - The message should explain "what" and "why", not "how"
4. **Use the body** - For complex changes, explain in the body
5. **Reference issues** - Link to GitHub/GitLab issues when applicable

## Resources

- [Conventional Commits Specification](https://www.conventionalcommits.org/)
- [Semantic Versioning](https://semver.org/)
- [Git Commit Best Practices](https://git-scm.com/book/en/v2/Distributed-Git-Contributing-to-a-Project)

## Skip Validation (Emergency Only)

To skip commit message validation in an emergency:

```bash
git commit --no-verify -m "Emergency hotfix"
```

⚠️ **Use sparingly!** This should only be used in true emergencies.
