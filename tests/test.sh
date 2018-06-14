#Xvfb :10 -ac &
#export DISPLAY=:10
export DBUS_SESSION_BUS_ADDRESS=/dev/null
./node_modules/.bin/nightwatch --verbose -e staging --config nightwatchjs.lin.json test.js