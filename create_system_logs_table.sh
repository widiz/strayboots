#!/bin/bash

# Get database credentials
echo "Enter MySQL username (usually root):"
read -r DB_USER

echo "Enter MySQL password:"
read -rs DB_PASS

echo "Enter database name:"
read -r DB_NAME

# Execute the SQL script
mysql -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < create_system_logs_table.sql

if [ $? -eq 0 ]; then
  echo "Successfully created system_logs table!"
  echo "Now login to the admin panel to test if logging works."
else
  echo "Error creating system_logs table. Check MySQL credentials and permissions."
fi 