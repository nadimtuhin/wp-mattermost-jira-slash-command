#!/bin/bash

# WP Mattermost Jira Slash Command - Release Builder
# This script creates a clean release zip of the plugin

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Get plugin version from main plugin file
get_plugin_version() {
    local version=$(grep -o "Version: [0-9]\+\.[0-9]\+\.[0-9]\+" wp-mm-slash-jira.php | cut -d' ' -f2)
    if [ -z "$version" ]; then
        print_error "Could not extract version from wp-mm-slash-jira.php"
        exit 1
    fi
    echo "$version"
}

# Get plugin name from main plugin file
get_plugin_name() {
    local name=$(grep -o "Plugin Name: [^$]*" wp-mm-slash-jira.php | cut -d':' -f2 | xargs)
    if [ -z "$name" ]; then
        print_error "Could not extract plugin name from wp-mm-slash-jira.php"
        exit 1
    fi
    echo "$name"
}

# Check if we're in the plugin directory
check_plugin_directory() {
    if [ ! -f "wp-mm-slash-jira.php" ]; then
        print_error "This script must be run from the plugin root directory"
        print_error "Current directory: $(pwd)"
        exit 1
    fi
}

# Create temporary build directory
create_build_dir() {
    local build_dir="build-temp"
    if [ -d "$build_dir" ]; then
        print_warning "Removing existing build directory: $build_dir"
        rm -rf "$build_dir"
    fi
    mkdir -p "$build_dir"
    echo "$build_dir"
}

# Copy files to build directory
copy_plugin_files() {
    local build_dir="$1"
    local plugin_name="$2"
    
    print_status "Copying plugin files to build directory..."
    
    # Create plugin directory in build
    mkdir -p "$build_dir/$plugin_name"
    
    # Copy main plugin files
    cp wp-mm-slash-jira.php "$build_dir/$plugin_name/"
    cp uninstall.php "$build_dir/$plugin_name/"
    cp README.md "$build_dir/$plugin_name/"
    
    # Copy includes directory
    if [ -d "includes" ]; then
        cp -r includes "$build_dir/$plugin_name/"
    fi
    
    # Copy assets directory
    if [ -d "assets" ]; then
        cp -r assets "$build_dir/$plugin_name/"
    fi
    
    print_success "Plugin files copied successfully"
}

# Clean up build directory (remove development files)
clean_build_directory() {
    local build_dir="$1"
    local plugin_name="$2"
    
    print_status "Cleaning build directory..."
    
    # Remove development and test files
    find "$build_dir/$plugin_name" -name "*.log" -delete
    find "$build_dir/$plugin_name" -name "*.tmp" -delete
    find "$build_dir/$plugin_name" -name ".DS_Store" -delete
    find "$build_dir/$plugin_name" -name "Thumbs.db" -delete
    
    # Remove any backup files
    find "$build_dir/$plugin_name" -name "*.bak" -delete
    find "$build_dir/$plugin_name" -name "*.backup" -delete
    
    # Remove any IDE files
    find "$build_dir/$plugin_name" -name ".vscode" -type d -exec rm -rf {} + 2>/dev/null || true
    find "$build_dir/$plugin_name" -name ".idea" -type d -exec rm -rf {} + 2>/dev/null || true
    
    # Remove any hidden files (except .gitignore if it exists)
    find "$build_dir/$plugin_name" -name ".*" ! -name ".gitignore" -delete 2>/dev/null || true
    
    print_success "Build directory cleaned"
}

# Create release zip
create_release_zip() {
    local build_dir="$1"
    local plugin_name="$2"
    local version="$3"
    
    local zip_name="wp-mm-slash-jira-v${version}.zip"
    
    print_status "Creating release zip: $zip_name" >&2
    
    # Change to build directory
    cd "$build_dir"
    
    # Create zip file (suppress verbose output)
    zip -r "../$zip_name" "$plugin_name" -x "*.DS_Store" "*/.*" "*/__pycache__/*" "*/node_modules/*" > /dev/null 2>&1
    
    # Return to original directory
    cd ..
    
    # Check if zip was created successfully
    if [ ! -f "$zip_name" ]; then
        print_error "Failed to create zip file" >&2
        exit 1
    fi
    
    print_success "Release zip created: $zip_name" >&2
    echo "$zip_name"
}

# Clean up build directory
cleanup_build() {
    local build_dir="$1"
    
    print_status "Cleaning up build directory..."
    rm -rf "$build_dir"
    print_success "Build directory removed"
}

# Display release information
display_release_info() {
    local zip_name="$1"
    local version="$2"
    local plugin_name="$3"
    
    # Check if zip file exists
    if [ ! -f "$zip_name" ]; then
        print_error "Zip file not found: $zip_name"
        return 1
    fi
    
    local zip_size=$(du -h "$zip_name" | cut -f1)
    
    echo ""
    print_success "=== RELEASE BUILD COMPLETE ==="
    echo ""
    echo "Plugin Name: $plugin_name"
    echo "Version: $version"
    echo "Release File: $zip_name"
    echo "File Size: $zip_size"
    echo ""
    echo "Files included in release:"
    echo "  ✓ Main plugin file (wp-mm-slash-jira.php)"
    echo "  ✓ Uninstall script (uninstall.php)"
    echo "  ✓ Documentation (README.md)"
    echo "  ✓ Includes directory (PHP classes)"
    echo "  ✓ Assets directory (CSS/JS files)"
    echo ""
    echo "Files excluded from release:"
    echo "  ✗ Tests directory (development only)"
    echo "  ✗ .git directory (version control)"
    echo "  ✗ Build scripts (development only)"
    echo "  ✗ IDE configuration files"
    echo "  ✗ Temporary and backup files"
    echo ""
    print_success "Release is ready for distribution!"
}

# Main execution
main() {
    echo "=========================================="
    echo "WP Mattermost Jira Slash Command"
    echo "Release Builder Script"
    echo "=========================================="
    echo ""
    
    # Check if we're in the right directory
    check_plugin_directory
    
    # Get plugin information
    local plugin_name=$(get_plugin_name)
    local version=$(get_plugin_version)
    
    print_status "Building release for: $plugin_name v$version"
    
    # Create build directory
    local build_dir=$(create_build_dir)
    
    # Copy plugin files
    copy_plugin_files "$build_dir" "wp-mm-slash-jira"
    
    # Clean build directory
    clean_build_directory "$build_dir" "wp-mm-slash-jira"
    
    # Create release zip
    local zip_name=$(create_release_zip "$build_dir" "wp-mm-slash-jira" "$version")
    
    # Clean up
    cleanup_build "$build_dir"
    
    # Display release information
    display_release_info "$zip_name" "$version" "$plugin_name"
}

# Run main function
main "$@" 