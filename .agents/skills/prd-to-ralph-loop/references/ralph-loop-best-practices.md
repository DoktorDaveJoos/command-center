# Ralph Loop Best Practices

Comprehensive guidance for writing robust ralph-loop instructions that succeed through iteration.

## Table of Contents

1. [Explicit Completion Criteria](#explicit-completion-criteria)
2. [Incremental Phases](#incremental-phases)
3. [Self-Correction Patterns](#self-correction-patterns)
4. [Verification Commands](#verification-commands)
5. [Handling Edge Cases](#handling-edge-cases)
6. [Anti-Patterns to Avoid](#anti-patterns-to-avoid)
7. [Framework-Specific Patterns](#framework-specific-patterns)

---

## Explicit Completion Criteria

The most critical element of a ralph-loop instruction is defining **exactly** when the task is complete.

### The Promise Marker

Always use this exact format at the end of instructions:

```
Output `<promise>DONE</promise>` when complete
```

### Good vs Bad Completion Criteria

**Bad (vague):**
- "When the feature is working"
- "When everything looks good"
- "When the code is clean"

**Good (measurable):**
- "When `npm test` exits with code 0 and all 47 tests pass"
- "When `php artisan test --filter=AuthTest` shows 12/12 passing"
- "When `npm run build` completes without errors and `dist/` contains index.html"

### Completion Criteria Template

```markdown
## Completion Criteria

This task is complete when ALL of the following are true:

1. **Tests pass**: `[test command]` exits with 0 and shows [X] passing tests
2. **Build succeeds**: `[build command]` completes without errors
3. **Lint passes**: `[lint command]` reports no errors
4. **Functionality verified**: [specific verification steps]

Only output `<promise>DONE</promise>` when ALL criteria are met.
```

---

## Incremental Phases

Break work into logical phases that build upon each other.

### Phase Sizing Guidelines

- **Too small**: 1-2 file changes with trivial logic
- **Right size**: 3-8 file changes with coherent functionality
- **Too large**: 15+ file changes spanning multiple features

### Phase Independence

Each phase should:
1. Have a clear starting point
2. Produce verifiable output
3. Not depend on future phases
4. Be recoverable if it fails

### Phase Ordering Strategies

**By Layer:**
```
Phase 1: Database/Models
Phase 2: Business Logic/Services
Phase 3: Controllers/Routes
Phase 4: Views/Frontend
Phase 5: Tests
```

**By Feature Slice:**
```
Phase 1: User Registration (full stack)
Phase 2: User Login (full stack)
Phase 3: Password Reset (full stack)
```

**By Risk:**
```
Phase 1: High-risk core functionality
Phase 2: Medium-risk features
Phase 3: Low-risk polish/optimization
```

### Phase Template

```markdown
## Phase N: [Descriptive Name]

### Objectives
- [Specific deliverable 1]
- [Specific deliverable 2]

### Prerequisites
- Phase N-1 completed successfully
- [Any other dependencies]

### Implementation Steps
1. [Step with expected outcome]
2. [Step with expected outcome]
3. [Step with expected outcome]

### Verification
```bash
[command 1]  # Expected: [specific output]
[command 2]  # Expected: [specific output]
```

### Success Criteria
- [ ] [Verifiable criterion]
- [ ] [Verifiable criterion]

### Rollback Plan
If this phase fails irreparably:
1. [Recovery step]
2. [Recovery step]
```

---

## Self-Correction Patterns

Include explicit instructions for handling common failure scenarios.

### Test Failure Pattern

```markdown
### When Tests Fail

1. Read the complete error output
2. Identify:
   - Test file and test name
   - Expected vs actual values
   - Stack trace location
3. Check the implementation:
   - Is the logic correct?
   - Are edge cases handled?
   - Are mocks/stubs properly configured?
4. Make targeted fixes
5. Re-run the specific failing test: `[test command] --filter=[test_name]`
6. Once passing, run full test suite
```

### Build Failure Pattern

```markdown
### When Build Fails

1. Read the error message completely
2. Common causes:
   - Missing imports → Add the import
   - Type errors → Fix the type annotation
   - Syntax errors → Check for typos, missing brackets
   - Missing dependencies → Install with [package manager]
3. After fixing, run build again
4. If build passes, run tests
```

### Lint Failure Pattern

```markdown
### When Lint Fails

1. Run lint with auto-fix: `[lint command] --fix`
2. For remaining errors, fix manually:
   - Unused variables → Remove or prefix with _
   - Import order → Reorder per project conventions
   - Line length → Break long lines
3. Re-run lint to verify
```

### Infinite Loop Prevention

```markdown
### Preventing Loops

If the same error occurs 3 times in a row:
1. STOP attempting the same fix
2. Document what was tried
3. Try a fundamentally different approach
4. If still failing, note the blocker and proceed to next phase
```

---

## Verification Commands

Provide specific, copy-paste-ready commands for verification.

### Command Patterns by Stack

**Laravel/PHP:**
```bash
# Tests
php artisan test --compact
php artisan test --filter=FeatureName
vendor/bin/pest --filter=test_name

# Lint
vendor/bin/pint --test
vendor/bin/phpstan analyse

# Build/Compile
npm run build
php artisan route:cache
php artisan config:cache
```

**React/Node:**
```bash
# Tests
npm test
npm test -- --testNamePattern="test name"
npx jest --coverage

# Lint
npm run lint
npx eslint src/ --fix

# Build
npm run build
npm run type-check
```

**Python:**
```bash
# Tests
pytest
pytest -k "test_name"
pytest --cov=src

# Lint
ruff check .
mypy src/

# Build
python -m build
pip install -e .
```

### Verification Output Expectations

Always specify what success looks like:

```markdown
### Verification

Run: `php artisan test --filter=UserAuthTest`
Expected output should include:
- "PASS" for all tests
- "Tests: 8 passed"
- Exit code 0
```

---

## Handling Edge Cases

### Missing Files

```markdown
### If Required Files Don't Exist

Before modifying a file, verify it exists. If not:
1. Check if it should be created
2. Create with minimal boilerplate
3. Then add the required functionality
```

### Dependency Conflicts

```markdown
### If Dependencies Conflict

1. Check current installed versions: `[package manager] list`
2. Review compatibility requirements
3. Try updating to compatible versions
4. If unresolvable, document the conflict
```

### Permission Errors

```markdown
### If Permission Errors Occur

1. Note the file/directory with permission issues
2. Do NOT use sudo for application files
3. Check if the path is correct
4. Verify the file should be writable by the application
```

---

## Anti-Patterns to Avoid

### Vague Instructions

**Bad:**
> Implement the user authentication feature

**Good:**
> Implement user authentication:
> 1. Create User model with email (unique, required), password_hash (required), created_at, updated_at
> 2. Create AuthController with login(email, password) and logout() methods
> 3. Add routes: POST /login, POST /logout
> 4. Add middleware to protect routes requiring authentication

### Missing Verification

**Bad:**
> Create the migration and model

**Good:**
> Create the migration and model
> Verify: `php artisan migrate` exits without errors
> Verify: `php artisan tinker` can create model instance

### Assuming Context

**Bad:**
> Update the config file

**Good:**
> Update config/auth.php:
> - Set 'driver' to 'session'
> - Set 'provider' to 'users'

### Unbounded Scope

**Bad:**
> Make the code production-ready

**Good:**
> Production readiness checklist:
> - [ ] All tests pass
> - [ ] No hardcoded credentials
> - [ ] Environment variables documented
> - [ ] Error handling for external services

---

## Framework-Specific Patterns

### Laravel Projects

```markdown
## Laravel-Specific Verification

After each phase:
1. Clear caches: `php artisan cache:clear && php artisan config:clear`
2. Run migrations: `php artisan migrate`
3. Run tests: `php artisan test --compact`
4. Check routes: `php artisan route:list | grep [feature]`
```

### React Projects

```markdown
## React-Specific Verification

After each phase:
1. Type check: `npm run type-check` or `npx tsc --noEmit`
2. Lint: `npm run lint`
3. Test: `npm test -- --watchAll=false`
4. Build: `npm run build`
```

### Full-Stack (Inertia/Livewire)

```markdown
## Full-Stack Verification

After backend changes:
1. `php artisan test --compact`
2. `php artisan route:list`

After frontend changes:
1. `npm run build` (check for compilation errors)
2. Browser test if available: `php artisan test --filter=Browser`

After both:
1. Full test suite: `php artisan test`
```
