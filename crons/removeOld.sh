find /var/www/newplay/apps/common/tmp/*/*.* -type f -mtime +3 -exec rm -f {} \;
find /var/www/newplay/public/cache/*.* -type f -mtime +60 -exec rm -f {} \;
find /var/www/staging/newplay/public/cache/*.* -type f -mtime +60 -exec rm -f {} \;