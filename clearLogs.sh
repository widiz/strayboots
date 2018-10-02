cd /var/www/newplay/logs
grep -rol 'exif_read_data' . | tr \n 0 | xargs -n1 rm -f
grep -rol 'guzzlehttp/guzzle/src/Handler/CurlFactory.php' . | tr \n 0 | xargs -n1 rm -f
grep -rol 'Division by zero' . | tr \n 0 | xargs -n1 rm -f
grep -rol ': stat failed for ' . | tr \n 0 | xargs -n1 rm -f
