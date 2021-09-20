#!/bin/bash

set -eu

export NB=$1
export SIZE=$2

export USER="admin"
export PASS="password"
export SERVER="nextcloud.test"
export UPLOAD_ID="single_$(openssl rand --hex 8)"
export LOCAL_FOLDER="/tmp/bundle_upload/${UPLOAD_ID}_${NB}_${SIZE}"
export REMOTE_FOLDER="/bundle_upload/${UPLOAD_ID}_${NB}_${SIZE}"

mkdir --parent "$LOCAL_FOLDER"

curl \
	-X MKCOL \
	-k \
	--cookie "XDEBUG_SESSION=MROW4A;path=/;" \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/$USER/$REMOTE_FOLDER"

upload_file() {
	printf "%s/$NB\r" "$1"

	file_id=$(openssl rand --hex 8)
	file_local_path="$LOCAL_FOLDER/$file_id.txt"
	file_remote_path="$REMOTE_FOLDER/$file_id.txt"
	head -c "$SIZE" /dev/urandom > "$file_local_path"

	curl \
		-X PUT \
		-k \
		--cookie "XDEBUG_SESSION=MROW4A;path=/;" \
		--data-binary @"$file_local_path" "https://$USER:$PASS@$SERVER/remote.php/webdav/$file_remote_path"
}
export -f upload_file

file_list=''
for ((i=1; i<"$NB"; i++))
do
	file_list+="$i "
done

echo "$file_list$NB" | xargs -d ' ' -P 20 -I{} bash -c "upload_file {}"

printf "\n"

rm -rf "${LOCAL_FOLDER:?}"/*