#!/bin/bash
/var/www/newplay/cli.php Nikkipostevent --env=production > "/var/www/newplay/clilogs/nikki $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php Nikkipostevent --env=portugal > "/var/www/newplay/clilogs/nikki $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php Nikkipostevent --env=india > "/var/www/newplay/clilogs/nikki $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;