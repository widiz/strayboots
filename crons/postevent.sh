#!/bin/bash
/var/www/newplay/cli.php postevent new --env=production > "/var/www/newplay/clilogs/postevent $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php postevent new --env=portugal > "/var/www/newplay/clilogs/postevent $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php postevent new --env=india > "/var/www/newplay/clilogs/postevent $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;