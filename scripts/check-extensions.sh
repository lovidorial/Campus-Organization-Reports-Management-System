#!/bin/bash
# Enable PHP extensions for Railway deployment

# Check if zip extension is available
if php -m | grep -q zip; then
    echo "✓ PHP zip extension is already enabled"
else
    echo "⚠ PHP zip extension not found in php -m output"
    echo "Checking php.ini configuration..."
    php -i | grep -i zip || echo "⚠ zip extension configuration not found"
fi

# Ensure the extension is loadable
php -r "if (extension_loaded('zip')) { echo '✓ ZipArchive class is available'; } else { echo '✗ ZipArchive class NOT available'; exit(1); }"
