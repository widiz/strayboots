#!/bin/bash
/var/www/newplay/cli.php watchdog --env=production > "/var/www/newplay/clilogs/watchdog $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php watchdog --env=portugal > "/var/www/newplay/clilogs/watchdog $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php watchdog --env=india > "/var/www/newplay/clilogs/watchdog $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;