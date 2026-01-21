---
name: prd-to-ralph-loop
description: Transform a PRD (Product Requirements Document) markdown file into a robust ralph-loop instruction file. This skill should be used when the user wants to "convert PRD to ralph-loop", "create ralph-loop from PRD", "generate ralph-loop instructions", "make ralph-loop prompt from spec", or needs to turn requirements documents into iterative AI-driven implementation instructions. The skill performs thorough planning before generating comprehensive, self-correcting ralph-loop instructions optimized for autonomous execution.
---

# PRD to Ralph Loop Converter

Transform Product Requirements Documents into robust ralph-loop instructions that enable autonomous, iterative implementation.

## What is Ralph Loop?

Ralph loop is an iterative AI development methodology using a bash loop that repeatedly feeds Claude a prompt until task completion. Core principles:

- **Iteration over perfection** - Refinement through repetition
- **Failures as data** - Setbacks provide informative feedback
- **Persistence wins** - The loop handles retry logic automatically

## Workflow

### Phase 1: Read and Analyze the PRD

1. Read the PRD file completely using the Read tool
2. Extract and categorize:
   - **Core objectives** - Primary goals and deliverables
   - **Functional requirements** - Specific features to implement
   - **Non-functional requirements** - Performance, security, accessibility constraints
   - **Technical constraints** - Framework, language, architecture requirements
   - **Success criteria** - How completion is measured
   - **Dependencies** - External services, packages, APIs needed

### Phase 2: Planning Before Implementation

Before writing the ralph-loop instruction, create an implementation plan:

1. **Break into phases** - Identify logical implementation phases (3-7 typically)
2. **Define milestones** - Each phase needs verifiable completion criteria
3. **Identify verification methods** - Tests, linters, build commands for each phase
4. **Map dependencies** - Order phases by technical dependencies
5. **Anticipate failure modes** - Common errors and recovery strategies

Document the plan structure:

```
Phase 1: [Name] - [Description]
  - Deliverables: [Specific outputs]
  - Verification: [Commands/tests to verify]
  - Success criteria: [Measurable outcomes]

Phase 2: [Name] - [Description]
  ...
```

### Phase 3: Generate Ralph Loop Instruction

Create the instruction file in the **same folder as the PRD** with naming: `{prd-name}-ralph-loop.md`

#### Instruction Structure

```markdown
# [Project Name] - Ralph Loop Implementation

## Project Overview
[1-2 paragraph summary from PRD]

## Completion Criteria
[Explicit, measurable criteria - when to output the promise marker]

## Phase 1: [Phase Name]

### Objectives
- [Specific objective 1]
- [Specific objective 2]

### Implementation Steps
1. [Detailed step with expected outcome]
2. [Next step with verification method]

### Verification
Run these commands to verify phase completion:
- `[verification command 1]`
- `[verification command 2]`

### Success Criteria
- [ ] [Measurable criterion 1]
- [ ] [Measurable criterion 2]

## Phase 2: [Phase Name]
[Same structure...]

## Self-Correction Patterns

### When Tests Fail
1. Read the error message completely
2. Identify the failing test file and line
3. Check the implementation against requirements
4. Fix and re-run tests

### When Build Fails
1. Check dependency versions
2. Verify import statements
3. Review syntax errors
4. Rebuild

## Final Verification Checklist
- [ ] All tests pass: `[test command]`
- [ ] Build succeeds: `[build command]`
- [ ] Lint passes: `[lint command]`
- [ ] [Additional verification...]

## Completion
When ALL verification checks pass and ALL success criteria are met:

Output `<promise>DONE</promise>` when complete
```

### Phase 4: Write the File

1. Use the Write tool to save to the same directory as the PRD
2. File name format: `{prd-basename}-ralph-loop.md`
   - Example: `/path/to/my-feature-prd.md` → `/path/to/my-feature-prd-ralph-loop.md`
3. Confirm the file was written successfully

## Best Practices for Robust Instructions

See `references/ralph-loop-best-practices.md` for comprehensive guidance on:
- Writing explicit completion criteria
- Structuring incremental phases
- Including self-correction patterns
- Setting appropriate verification commands
- Handling edge cases and failures

## Output Quality Checklist

Before finalizing the ralph-loop instruction, verify:

- [ ] **Explicit completion** - Promise marker usage is clearly specified
- [ ] **Measurable criteria** - All success criteria are verifiable by commands
- [ ] **Incremental phases** - Work is broken into distinct, sequential phases
- [ ] **Self-correction** - Error handling patterns are included
- [ ] **Verification commands** - Each phase has specific verification steps
- [ ] **No ambiguity** - Instructions are specific, not vague
- [ ] **Escape conditions** - Clear criteria for when to stop

## Example Transformation

**PRD excerpt:**
> Build a user authentication system with email/password login, password reset, and session management.

**Ralph-loop instruction excerpt:**

```markdown
## Phase 1: Database Schema and Models

### Objectives
- Create users table migration
- Implement User model with password hashing
- Add password reset tokens table

### Implementation Steps
1. Generate migration for users table with: email (unique), password_hash, created_at, updated_at
2. Generate migration for password_reset_tokens with: email, token, created_at
3. Create User model with fillable fields and hidden password attribute
4. Implement password hashing mutator

### Verification
- `php artisan migrate --pretend` (verify migration SQL)
- `php artisan migrate`
- `php artisan tinker --execute="User::factory()->create()"` (verify model works)

### Success Criteria
- [ ] Migrations run without errors
- [ ] User model can be created with factory
- [ ] Password is automatically hashed on save
```
