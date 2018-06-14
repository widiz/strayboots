
BASE="/var/www/staging/newplay"
LOG=$BASE/ncr$(date +%F%T).log
$BASE/cli.php ncr post 1 --env=production > $LOG 2>&1 &
tail -f $LOG