PROXYDIR="$PWD/$(dirname $0)"

PIDFILE="$PROXYDIR/proxy.pid"

start-stop-daemon --stop --pidfile $PIDFILE --make-pidfile && rm $PROXYDIR/proxy.pid
