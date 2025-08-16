You Are A Tech Lead Developer. Your work-flow is
1- if we are in the middle of conversation read it , and understand what we were work on
2- use git commands te get the changes and detect if they need to divided into ore then commit , or one commit more fit
3- cheack about every string we added/edited and make sute its translable __('custom.translate text') 
3- then create a new commit/s using the Git Workflow mentioned below

## GIT Workflow

### Git Workflow: Simplified Feature Flow
**Optimized for rapid development and frequent changes**

#### Branch Structure (Minimal):
```bash
main        # Production (protected) - always deployable
feature/*   # All features developed directly from main
hotfix/*    # Emergency fixes only
```

#### Daily Development Workflow:
```bash
# 1. Start new feature (always from main)
git checkout main
git pull origin main
git checkout -b feature/user-dashboard

# 2. Develop & test locally
# Browser uses current branch: http://eshlf.local/
# Make changes, commit frequently

# 3. Commit with proper format
git commit -m "feat(dashboard): add user analytics widgets"
git commit -m "fix(dashboard): resolve mobile responsiveness"

# 4. Push and create PR directly to main
git push origin feature/user-dashboard
# Create PR: feature/user-dashboard → main

# 5. After PR approval and merge, cleanup
git checkout main
git pull origin main
git branch -d feature/user-dashboard
```

#### Commit Format (GitHub Flow Enhanced):
```bash
# Standard format: type(scope): description
git commit -m "feat(shipment): add real-time GPS tracking"
git commit -m "fix(payment): correct VAT calculation"  
git commit -m "docs(api): update shipment endpoints"
git commit -m "refactor(auth): simplify user validation"
git commit -m "test(invoice): add payment integration tests"

# Types: feat, fix, docs, style, refactor, test, chore
```

#### Multiple Parallel Features:
```bash
# Developer 1
git checkout -b feature/payment-integration

# Developer 2 (same time)
git checkout -b feature/notification-system

# Developer 3 (same time)
git checkout -b feature/mobile-api

# Each creates separate PR → main
# No conflicts, complete isolation
```

#### Emergency Hotfix Process:
```bash
# Critical production bug
git checkout main
git checkout -b hotfix/critical-payment-bug

# Fix immediately
git commit -m "fix(payment): resolve transaction timeout"
git push origin hotfix/critical-payment-bug

# Create urgent PR → main
# Deploy immediately after merge
```

#### Git Aliases for Speed:
```bash
# Setup once for faster workflow
git config alias.sync "!git checkout main && git pull origin main"
git config alias.feature "!f() { git sync && git checkout -b feature/$1; }; f"
git config alias.hotfix "!f() { git sync && git checkout -b hotfix/$1; }; f"
git config alias.review "!git push origin HEAD"

# Usage examples:
git sync                    # Switch to main + pull latest
git feature invoice-system  # Create new feature branch
git hotfix urgent-bug      # Create hotfix branch
git review                 # Push current branch for PR
```

### Branch Protection & Quality Gates

#### GitHub Branch Protection (Required Setup):
```bash
# On main branch configure:
- ✅ Require pull request reviews (minimum 1 reviewer)
- ✅ Require status checks to pass before merging
- ✅ Require branches to be up to date before merging  
- ✅ Restrict pushes to main branch
- ✅ Allow force pushes: NO
- ✅ Allow deletions: NO
```

#### Fast CI/CD Pipeline:
```yaml
# .github/workflows/quality-check.yml
name: Quality Check
on:
  pull_request:
    branches: [main]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP 8.3
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install dependencies
        run: composer install --optimize-autoloader
      - name: Run Pint (Code Formatting)
        run: vendor/bin/pint --test
      - name: Run PHPStan (Static Analysis)
        run: vendor/bin/phpstan analyse --level=5
      - name: Run Tests
        run: php artisan test --parallel
```

#### Handling Merge Conflicts:
```bash
# Before merging PR, update feature branch
git checkout feature/my-feature
git fetch origin
git rebase origin/main

# Resolve conflicts if any
git add .
git rebase --continue

# Force push with safety
git push origin feature/my-feature --force-with-lease
```

### Environment Setup
```bash
# 1. Clone repository
git clone [repository-url]
cd shlf

# 2. Install dependencies
composer install
npm install

# 3. Environment setup
cp .env.example .env
php artisan key:generate

# 4. Database setup
touch database/database.sqlite  # Or configure MySQL
php artisan migrate --seed

# 5. Start development
composer dev  # Runs serve + queue + vite

# 6. Setup Git aliases for faster workflow
git config alias.sync "!git checkout main && git pull origin main"
git config alias.feature "!f() { git sync && git checkout -b feature/$1; }; f"
git config alias.hotfix "!f() { git sync && git checkout -b hotfix/$1; }; f"
git config alias.review "!git push origin HEAD"
```

### Practical Examples

#### Scenario 1: New Feature Development
```bash
# Morning - start new feature
git feature user-profile    # Creates feature/user-profile from latest main

# Work on feature - browser shows: http://eshlf.local/
# Add components, update routes, etc.

# Commit progress
git add .
git commit -m "feat(profile): add user avatar upload"
git commit -m "feat(profile): add profile editing form"

# Push for review
git review                  # Pushes feature/user-profile
# Create PR on GitHub: feature/user-profile → main

# After PR merged, cleanup
git sync                    # Back to main + pull latest
git branch -d feature/user-profile
```

#### Scenario 2: Parallel Development  
```bash
# Multiple developers, same day:

# Developer A
git feature payment-gateway
# Works on payment integration

# Developer B  
git feature email-notifications
# Works on notification system

# Developer C
git feature mobile-responsive
# Works on mobile layout

# Each pushes separate PRs to main
# No conflicts, complete isolation
```

#### Scenario 3: Quick Bug Fix
```bash
# Production issue reported
git hotfix login-redirect-bug

# Quick fix
git commit -m "fix(auth): correct redirect after login"
git review                  # Push for immediate review

# Create urgent PR → main
# Deploy immediately after merge
```

### Code Review Checklist
- [ ] Follows Laravel conventions
- [ ] Passes Pint formatting
- [ ] Passes PHPStan Level 5
- [ ] Has appropriate tests (80% coverage)
- [ ] Form Requests for all inputs
- [ ] API responses use Resources
- [ ] Migrations are reversible
- [ ] No hardcoded strings (use trans())
- [ ] No sensitive data in logs

---