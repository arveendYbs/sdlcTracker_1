
#!/bin/bash

##############################################
# SDLC Project Tracker - Asset Setup Script
# This script downloads Bootstrap and Chart.js
##############################################

echo "=========================================="
echo "SDLC Project Tracker - Asset Setup"
echo "=========================================="
echo ""

# Create directories
echo "Creating directories..."
mkdir -p css js

# Download Bootstrap CSS
echo "Downloading Bootstrap CSS..."
if command -v curl &> /dev/null; then
    curl -o css/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css
elif command -v wget &> /dev/null; then
    wget -O css/bootstrap.min.css https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css
else
    echo "Error: Neither curl nor wget is installed. Please install one and try again."
    exit 1
fi

# Download Bootstrap JS
echo "Downloading Bootstrap JavaScript..."
if command -v curl &> /dev/null; then
    curl -o js/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js
else
    wget -O js/bootstrap.bundle.min.js https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js
fi

# Download Chart.js
echo "Downloading Chart.js..."
if command -v curl &> /dev/null; then
    curl -o js/chart.umd.js https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js
else
    wget -O js/chart.umd.js https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js
fi

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Files downloaded:"
echo "  ✓ css/bootstrap.min.css"
echo "  ✓ js/bootstrap.bundle.min.js"
echo "  ✓ js/chart.umd.js"
echo ""
echo "Next steps:"
echo "1. Configure database credentials in db.php"
echo "2. Import database.sql into MySQL"
echo "3. Access the application via your web browser"
echo ""
echo "For detailed instructions, see README.md"
echo ""