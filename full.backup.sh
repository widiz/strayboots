D=`date +%F`
mysqldump -h mysql.go.strayboots.com -u backup newplay -pBa8c93Xu4p34$ > /var/www/newplay/db-$D.sql
if [ ! -f /var/www/newplay/db-$D.sql ]; then
	echo "Failed to create DB backup";
	exit 5;
fi
cd /var/www/;
#tar --exclude='*/clilogs/*' --exclude='*/logs/*' --exclude='*/cache/*' --exclude='*/tmp/*' --exclude='*.tar.gz' -czf /var/www/backups/backup-$D.tar.gz newplay;
tar --exclude='*/clilogs/*' --exclude='*/logs/*' --exclude='*/cache/*' --exclude='*/tmp/*' --exclude='*.tar.gz' -czf - newplay | ssh root@backups.go.strayboots.com "cat > /backups/backup-$D.tar.gz";
rm -f /var/www/newplay/db-$D.sql;
cd /volume0/newplay/frontend;
tar -czf - uploads | ssh root@backups.go.strayboots.com "cat > /backups/backup-uploads-$D.tar.gz";
#scp /var/www/backups/backup-$D.tar.gz root@backups.strayboots:/backups;
#rm -f /var/www/backups/backup-$D.tar.gz;
echo "Backup done" > "/var/www/newplay/clilogs/fullbackup $(date '+%Y-%m-%d %H:%M:%S') $RANDOM.txt";