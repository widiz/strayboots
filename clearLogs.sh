cd /var/www/newplay/logs
grep -rol 'exif_read_data' . | tr \n 0 | xargs -n1 rm -f
grep -rol ': stat failed for ' . | tr \n 0 | xargs -n1 rm -f
