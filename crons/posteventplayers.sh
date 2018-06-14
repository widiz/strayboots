#!/bin/bash
/var/www/newplay/cli.php postevent players --env=production > "/var/www/newplay/clilogs/posteventplayers $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php postevent players --env=portugal > "/var/www/newplay/clilogs/posteventplayers $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
/var/www/newplay/cli.php postevent players --env=india > "/var/www/newplay/clilogs/posteventplayers $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt" &
wait;