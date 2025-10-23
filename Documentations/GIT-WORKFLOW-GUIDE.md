# Git & GitHub Workflow Guide

**Project:** TechCompare - Smartphone Accessories  
**Repository:** https://github.com/Ronin2023/Smartphone-Accessories  
**Owner:** Ronin2023  
**Branch:** main  

---

## Current Git Configuration

✅ **Repository Status:** Connected to GitHub  
✅ **User Name:** Ronin2023  
✅ **User Email:** 131849416+Ronin2023@users.noreply.github.com  
✅ **Remote URL:** https://github.com/Ronin2023/Smartphone-Accessories.git  

---

## Essential Git Commands

### 1. Check Status
```bash
git status
```
Shows modified, staged, and untracked files.

### 2. View Changes
```bash
# See what changed in files
git diff

# See staged changes
git diff --cached

# See changes in a specific file
git diff filename.php
```

### 3. Stage Changes
```bash
# Stage all changes
git add -A

# Stage specific file
git add filename.php

# Stage all files in a directory
git add css/

# Stage all PHP files
git add *.php
```

### 4. Commit Changes
```bash
# Commit with message
git commit -m "Your commit message"

# Commit all tracked files (skip git add)
git commit -am "Your commit message"

# Commit with detailed description
git commit -m "Short summary" -m "Detailed description here"
```

### 5. Push to GitHub
```bash
# Push to main branch
git push origin main

# Push and set upstream (first time)
git push -u origin main

# Force push (use with caution!)
git push --force origin main
```

### 6. Pull from GitHub
```bash
# Pull latest changes
git pull origin main

# Pull with rebase
git pull --rebase origin main
```

### 7. View History
```bash
# View commit history
git log

# View last 5 commits (short format)
git log --oneline -5

# View commits with file changes
git log --stat

# View detailed history with graph
git log --graph --oneline --all
```

---

## Standard Workflow for Updates

### Complete Workflow (After Making Changes)

```bash
# 1. Check what changed
git status

# 2. Review changes
git diff

# 3. Stage all changes
git add -A

# 4. Commit with descriptive message
git commit -m "Description of changes"

# 5. Push to GitHub
git push origin main
```

### Quick Commit (One-Liner)
```bash
git add -A && git commit -m "Your message" && git push origin main
```

---

## Common Scenarios

### Scenario 1: After Adding Navigation Bar to Pages
```bash
git add -A
git commit -m "feat: Add consistent navigation bar to all pages

- Added navigation to contact.php, compare.php, about.php
- Added Login/Sign Up buttons to all pages
- Updated products.php with nav-actions
- Ensured consistent styling across pages"
git push origin main
```

### Scenario 2: After Implementing Dark Mode
```bash
git add -A
git commit -m "feat: Implement dark mode/light mode theme system

- Added theme.css with CSS custom properties
- Created theme.js for theme management
- Integrated theme toggle on all pages
- Added localStorage for theme persistence"
git push origin main
```

### Scenario 3: After PHP/HTML Consolidation
```bash
git add -A
git commit -m "refactor: Consolidate HTML files into PHP files

- Merged index.html into index.php
- Merged contact.html into contact.php
- Merged products.html into products.php
- Merged compare.html into compare.php
- Created about.php from about.html
- Updated all internal links from .html to .php
- Maintained backward compatibility with .htaccess redirects"
git push origin main
```

### Scenario 4: After Bug Fixes
```bash
git add -A
git commit -m "fix: Resolve navbar overlap on check-response.php

- Added 80px top padding for desktop
- Added 70px top padding for mobile
- Improved header gradient styling
- Added Back to Contact button"
git push origin main
```

---

## Commit Message Best Practices

### Format
```
<type>: <short summary>

<detailed description (optional)>
```

### Types
- **feat:** New feature
- **fix:** Bug fix
- **refactor:** Code refactoring
- **style:** CSS/styling changes
- **docs:** Documentation changes
- **test:** Adding tests
- **chore:** Maintenance tasks

### Examples
```bash
# Good commit messages
git commit -m "feat: Add dark mode toggle to all pages"
git commit -m "fix: Navbar overlap on mobile devices"
git commit -m "refactor: Consolidate duplicate HTML/PHP files"
git commit -m "style: Improve gradient header design"
git commit -m "docs: Update README with new features"

# Bad commit messages (avoid these)
git commit -m "updates"
git commit -m "fix"
git commit -m "changes"
git commit -m "test"
```

---

## Useful Git Shortcuts

### Create PowerShell Aliases (Add to PowerShell Profile)
```powershell
# Open profile
notepad $PROFILE

# Add these aliases
function gs { git status }
function ga { git add -A }
function gc { param($m) git commit -m $m }
function gp { git push origin main }
function gl { git log --oneline -10 }
function gd { git diff }

# Quick commit and push
function gcp { 
    param($message)
    git add -A
    git commit -m $message
    git push origin main
}
```

### Usage After Setting Up Aliases
```bash
gs                          # git status
ga                          # git add -A
gc "Your message"           # git commit -m "Your message"
gp                          # git push origin main
gl                          # git log --oneline -10
gcp "Quick commit"          # Add, commit, and push in one command
```

---

## Branch Management

### Create a New Branch
```bash
# Create and switch to new branch
git checkout -b feature-name

# Or using newer syntax
git switch -c feature-name
```

### Switch Branches
```bash
git checkout main
git checkout feature-name

# Or
git switch main
```

### Merge Branch
```bash
# Switch to main branch
git checkout main

# Merge feature branch
git merge feature-name

# Push merged changes
git push origin main
```

### Delete Branch
```bash
# Delete local branch
git branch -d feature-name

# Delete remote branch
git push origin --delete feature-name
```

---

## Undo Changes

### Unstage Files
```bash
# Unstage all files
git reset

# Unstage specific file
git reset filename.php
```

### Discard Changes
```bash
# Discard changes in a file (dangerous!)
git checkout -- filename.php

# Discard all changes (dangerous!)
git reset --hard HEAD
```

### Undo Last Commit (Keep Changes)
```bash
git reset --soft HEAD~1
```

### Undo Last Commit (Discard Changes)
```bash
git reset --hard HEAD~1
```

### Amend Last Commit
```bash
# Change commit message
git commit --amend -m "New message"

# Add forgotten files to last commit
git add forgotten-file.php
git commit --amend --no-edit
```

---

## .gitignore Configuration

Your current `.gitignore` should exclude:

```gitignore
# Environment files
.env
.env.local
.env.production

# Database files
*.sql
*.dump
database/backup/
backups/

# Sensitive files
admin/setup.php
admin/setup_simple.php

# IDE files
.vscode/
.idea/
*.swp
*.swo

# System files
.DS_Store
Thumbs.db
desktop.ini

# Logs
*.log
logs/

# Temporary files
temp/
tmp/
*.tmp

# Node modules (if using npm)
node_modules/

# Vendor files (if using composer)
vendor/

# Backup files
backup_html/
*.bak
```

---

## Checking Remote Repository

### View Remote Info
```bash
git remote -v
```

### Update Remote URL
```bash
git remote set-url origin https://github.com/Ronin2023/Smartphone-Accessories.git
```

### Add Remote
```bash
git remote add origin https://github.com/Ronin2023/Smartphone-Accessories.git
```

---

## Syncing with GitHub

### Pull Latest Changes
```bash
# Fetch and merge
git pull origin main

# Or fetch first, then merge
git fetch origin
git merge origin/main
```

### Force Pull (Overwrite Local)
```bash
git fetch origin
git reset --hard origin/main
```

### Check if Local is Behind
```bash
git fetch origin
git status
```

---

## Quick Reference Card

| Command | Description |
|---------|-------------|
| `git status` | Check status |
| `git add -A` | Stage all changes |
| `git commit -m "msg"` | Commit changes |
| `git push origin main` | Push to GitHub |
| `git pull origin main` | Pull from GitHub |
| `git log --oneline -5` | View last 5 commits |
| `git diff` | View changes |
| `git branch` | List branches |
| `git checkout -b name` | Create new branch |
| `git merge branch` | Merge branch |

---

## Today's Work Summary

Based on your recent changes, here's what you can commit:

### Session Summary (October 24, 2025)

**Changes Made:**
1. ✅ Consolidated HTML/PHP files
2. ✅ Added navigation bars to all pages
3. ✅ Implemented dark mode/light mode
4. ✅ Fixed navbar overlap on check-response.php
5. ✅ Updated all internal links from .html to .php
6. ✅ Created comprehensive documentation

**Recommended Commit:**
```bash
git add -A
git commit -m "feat: Major updates - Navigation, Dark Mode, and PHP Consolidation

- Consolidated all .html files into .php files
- Added consistent navigation bar across all pages
- Implemented dark mode/light mode theme system
- Fixed navbar overlap on check-response.php
- Added professional gradient headers
- Updated all internal links to use .php extensions
- Created comprehensive documentation (PHP-CONSOLIDATION-SUMMARY.md)
- Improved responsive design for mobile devices
- Added Login/Sign Up buttons to all pages"

git push origin main
```

---

## Troubleshooting

### Issue: Authentication Failed
**Solution:** Use Personal Access Token instead of password
```bash
# Go to GitHub > Settings > Developer settings > Personal access tokens
# Generate new token with 'repo' permissions
# Use token as password when pushing
```

### Issue: Merge Conflicts
**Solution:**
```bash
# 1. Edit conflicted files manually
# 2. Mark as resolved
git add conflicted-file.php

# 3. Complete merge
git commit -m "Resolved merge conflicts"
```

### Issue: Large Files
**Solution:** Use Git LFS for files > 50MB
```bash
git lfs install
git lfs track "*.psd"
git add .gitattributes
```

---

## Additional Resources

- **GitHub Repository:** https://github.com/Ronin2023/Smartphone-Accessories
- **Git Documentation:** https://git-scm.com/doc
- **GitHub Guides:** https://guides.github.com/

---

**Last Updated:** October 24, 2025  
**Maintained By:** Ronin2023
