# Build Script Documentation

## Overview

The `build-release.sh` script creates a clean, production-ready zip file of the WP Mattermost Jira Slash Command plugin for distribution.

## Features

- ✅ Automatically extracts version from plugin header
- ✅ Creates clean release without development files
- ✅ Includes only necessary plugin files
- ✅ Excludes tests, git files, and development artifacts
- ✅ Generates properly named zip file with version
- ✅ Colored output for better readability
- ✅ Error handling and validation

## Usage

### Basic Usage

```bash
./build-release.sh
```

### Prerequisites

- Bash shell
- `zip` command (usually pre-installed on macOS/Linux)
- Script must be run from the plugin root directory

### What Gets Included

The release zip will contain:

- `wp-mm-slash-jira.php` - Main plugin file
- `uninstall.php` - Plugin uninstall script
- `README.md` - Documentation
- `includes/` - PHP class files
- `assets/` - CSS and JavaScript files

### What Gets Excluded

The following files/directories are automatically excluded:

- `tests/` - Development test files
- `.git/` - Version control files
- `build-release.sh` - This build script
- `BUILD.md` - This documentation
- IDE configuration files (`.vscode/`, `.idea/`)
- Temporary files (`.log`, `.tmp`, `.bak`)
- System files (`.DS_Store`, `Thumbs.db`)

## Output

The script will create a zip file named:
```
wp-mm-slash-jira-v1.0.0.zip
```

Where `1.0.0` is the version extracted from the plugin header.

## Example Output

```
==========================================
WP Mattermost Jira Slash Command
Release Builder Script
==========================================

[INFO] Building release for: WP Mattermost Jira Slash Command v1.0.0
[INFO] Copying plugin files to build directory...
[SUCCESS] Plugin files copied successfully
[INFO] Cleaning build directory...
[SUCCESS] Build directory cleaned
[INFO] Creating release zip: wp-mm-slash-jira-v1.0.0.zip
[SUCCESS] Release zip created: wp-mm-slash-jira-v1.0.0.zip
[INFO] Cleaning up build directory...
[SUCCESS] Build directory removed

[SUCCESS] === RELEASE BUILD COMPLETE ===

Plugin Name: WP Mattermost Jira Slash Command
Version: 1.0.0
Release File: wp-mm-slash-jira-v1.0.0.zip
File Size: 45K

Files included in release:
  ✓ Main plugin file (wp-mm-slash-jira.php)
  ✓ Uninstall script (uninstall.php)
  ✓ Documentation (README.md)
  ✓ Includes directory (PHP classes)
  ✓ Assets directory (CSS/JS files)

Files excluded from release:
  ✗ Tests directory (development only)
  ✗ .git directory (version control)
  ✗ Build scripts (development only)
  ✗ IDE configuration files
  ✗ Temporary and backup files

[SUCCESS] Release is ready for distribution!
```

## Troubleshooting

### "Permission denied" error
```bash
chmod +x build-release.sh
```

### "zip command not found" error
Install zip utility:
- **macOS**: `brew install zip` (if using Homebrew)
- **Ubuntu/Debian**: `sudo apt-get install zip`
- **CentOS/RHEL**: `sudo yum install zip`

### "This script must be run from the plugin root directory" error
Make sure you're in the directory containing `wp-mm-slash-jira.php`:
```bash
cd /path/to/wp-mm-slash-jira
./build-release.sh
```

## Version Management

To update the plugin version:

1. Edit the version in `wp-mm-slash-jira.php`:
   ```php
   Version: 1.0.1
   ```

2. Run the build script:
   ```bash
   ./build-release.sh
   ```

3. The new zip will be named `wp-mm-slash-jira-v1.0.1.zip`

## Distribution

The generated zip file is ready for:
- WordPress.org plugin repository submission
- Direct distribution to users
- Manual installation via WordPress admin
- Version control releases (GitHub releases, etc.) 