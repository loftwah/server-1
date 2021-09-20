#!/bin/bash

set -eu

NB=$1
SIZE=$2

USER="admin"
PASS="password"
SERVER="nextcloud.test"
UPLOAD_PATH="/tmp/bundle_upload_request_$(openssl rand --hex 8).txt"
BOUNDARY="boundary_$(openssl rand --hex 8)"
LOCAL_FOLDER="/tmp/bundle_upload/${BOUNDARY}_${NB}_${SIZE}"
REMOTE_FOLDER="/bundle_upload/${BOUNDARY}_${NB}_${SIZE}"


files_ids=()
metadata="<?xml version='1.0' encoding='UTF-8'?>
	<d:multipart xmlns:d=\"DAV:\">"

mkdir --parent "$LOCAL_FOLDER"

# CREATE FILES AND METADATA
for ((i=0; i<="$NB"; i++))
do
	printf "%s/$NB\r" "$i"

	file_id=$(openssl rand --hex 8)
	file_local_path="$LOCAL_FOLDER/$file_id.txt"
	file_remote_path="$REMOTE_FOLDER/$file_id.txt"
	head -c "$SIZE" /dev/urandom > "$file_local_path"
	file_mtime=$(stat -c %Y "$file_local_path")
	file_hash=$(md5sum "$file_local_path" | awk '{ print $1 }')
	file_size=$(du -sb "$file_local_path" | awk '{ print $1 }')

	files_ids+=("$file_id")
	metadata+="
	<d:part>
		<d:prop>
			<d:oc-path>$file_remote_path</d:oc-path>
			<d:oc-mtime>$file_mtime</d:oc-mtime>
			<d:oc-id>$file_id</d:oc-id>
			<d:oc-md5>$file_hash</d:oc-md5>
			<d:oc-total-length>$file_size</d:oc-total-length>
		</d:prop>
	</d:part>"
	# sleep 0.05
done

printf "\n"

metadata+="</d:multipart>"
metadata_size=$(echo -en "$metadata" | wc -c)

# BUILD REQUEST
echo -en "--$BOUNDARY\r
Content-Type: text/xml; charset=utf-8\r
Content-Length: $metadata_size\r
\r
$metadata" >> "$UPLOAD_PATH"

for file_id in "${files_ids[@]}"
do
	echo -en "\r\n--$BOUNDARY\r\nContent-ID: $file_id\r\n\r\n" >> "$UPLOAD_PATH"
	cat "$file_local_path" >> "$UPLOAD_PATH"
done

echo -en "\r\n--$BOUNDARY--\r\n" >> "$UPLOAD_PATH"

# Create remote folder
curl \
	-X MKCOL \
	-k \
	--cookie "XDEBUG_SESSION=MROW4A;path=/;" \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/$USER/$REMOTE_FOLDER"

curl \
	-X POST \
	-k \
	-H "Content-Type: multipart/related; boundary=$BOUNDARY" \
	--cookie "XDEBUG_SESSION=MROW4A;path=/;" \
	--data-binary "@$UPLOAD_PATH" \
	"https://$USER:$PASS@$SERVER/remote.php/dav/files/bundle"

rm -rf "${LOCAL_FOLDER:?}"/*
rm "$UPLOAD_PATH"