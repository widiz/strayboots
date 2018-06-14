#!/bin/bash
/var/www/newplay/cli.php preevent --env=production > "/var/www/newplay/clilogs/preevent $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php preevent --env=portugal > "/var/www/newplay/clilogs/preevent $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php preevent --env=india > "/var/www/newplay/clilogs/preevent $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;