ROOT="$1"

if [ "$ROOT" == "" ]; then
    echo "Usage: $0 <root directory of project>"
    exit
fi

if [ ! -d $ROOT ]; then
    echo "First argument should be directory"
    exit
fi

cd $ROOT

repo_check=`git status 2>&1`
not_repo_str="Not a git repository"

if [ "${repo_check/$not_repo_str}" != "$repo_check" ]; then
    echo "Root directory should be in Git repository"
    exit
fi

if [ -d vendor/white-lib-php ]; then
    echo "WhiteLib PHP already exists in $ROOT"
    exit
fi

git submodule add --name white-lib-php git@gitlab.whitecode.ru:nevmerzhitsky/white-lib-php.git vendor/white-lib-php
