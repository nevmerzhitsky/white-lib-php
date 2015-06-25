# Scripts search all directories with WhiteLib from delivered root dir and
# execute some command in each.

START_PWD=$1/
CMD=${*:2}

if [ -z "$START_PWD" ] || [ ! -d $START_PWD ]; then
    echo "Root directory does not accessible"
    exit
fi

if [ -z "$CMD" ]; then
    echo "Usage: $0 <root dir> <git command>"
    exit
fi

cd $START_PWD
START_PWD=`pwd`/

for d in `find -type d | grep vendor/white-lib-php$`
do
    cd $START_PWD$d
    echo -e '###' '\033[37;1m'`pwd`'\033[0m'\$ $CMD
    $CMD
done
