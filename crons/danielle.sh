#!/bin/bash
/var/www/newplay/cli.php Daniellepostevent --env=production > "/var/www/newplay/clilogs/danielle $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php Daniellepostevent --env=portugal > "/var/www/newplay/clilogs/danielle $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php Daniellepostevent --env=india > "/var/www/newplay/clilogs/danielle $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;