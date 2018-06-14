#!/bin/bash
/var/www/newplay/cli.php Shaunapostevent --env=production > "/var/www/newplay/clilogs/shauna $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php Shaunapostevent --env=portugal > "/var/www/newplay/clilogs/shauna $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php Shaunapostevent --env=india > "/var/www/newplay/clilogs/shauna $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;