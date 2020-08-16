#/bin/bash

clear
echo "Welcome! Before we begin..."
echo "Make sure your WP installation is complete or already restored from backup.."
echo ""
read -n 1 -s -r -p "Press any key to continue..."

echo ""
echo ""
echo "Checking WP-CLI version..."
wp --version

# Delete old files
echo "Deleting old files..."
rm -f blog-local.sql
rm -f blog-production.sql

# Backup first
echo "Backing up..."
wp db export blog-local.sql

# Begin migration
# TODO: Error Handling for first time setup
echo "Checking paths...                                   (DRY RUN)"
wp search-replace '/wp-content' '/web/app' --dry-run

echo "Checking upload paths...                            (DRY RUN)"
wp search-replace '/web/app/uploads' '/app/uploads' --dry-run

echo "Checking URLs...                                    (DRY RUN)"
wp search-replace 'https://jccorsanes.site' 'https://bedrock-blog.test' --dry-run

echo "Dry run tasks done. PLEASE CHECK THE RESULTS ABOVE!"
echo ""
echo "Press any key to continue"
read -n 1 -s -r -p "Or press CTRL+C to cancel.."

echo ""
echo "Replacing paths...                                  (MIGRATING   0%)"
wp search-replace '/wp-content' '/web/app'

echo "Replacing upload paths...                           (MIGRATING   3%)"
wp search-replace '/web/app/uploads' '/app/uploads'

echo "Replacing URLs...                                   (MIGRATING  25%)"
wp search-replace 'https://jccorsanes.site' 'https://bedrock-blog.test'

echo "Exporting production dump...                        (MIGRATING  90%)"
wp db export blog-production.sql

# echo "Reverting changes after export...                   (MIGRATING  95%)"
# wp db import blog-local.sql

echo "Migration complete!                                 (MIGRATING 100%)"

read -n 1 -s -r -p "Press any key to exit..."
