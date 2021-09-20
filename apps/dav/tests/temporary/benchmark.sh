#!/bin/bash

set -eu

FILE_COUNT=(10 100)
FILE_SIZE=(1 10)

md_output="# Bulk upload benchmark\n"
md_output+="| Nb | Size (kb) | Bundle (sec) | Single (sec) |\n"
md_output+="|---|---|---|---|\n"

for nb in "${FILE_COUNT[@]}"
do
	for kb in "${FILE_SIZE[@]}"
	do
		echo "- Upload of $nb tiny file of ${kb}kb"
		echo "	- Bundled"
		start=$(date +%s)
		./bundle_upload.sh "$nb" "$kb"
		end=$(date +%s)
		bundle_exec_time=$((end-start))
		echo "${bundle_exec_time}s"

		echo "	- Single"
		start=$(date +%s)
		./single_upload.sh "$nb" "$kb"
		end=$(date +%s)
		single_exec_time=$((end-start))
		echo "${single_exec_time}s"

		md_output+="| $nb | $kb | $bundle_exec_time | $single_exec_time |\n"
	done
done

echo -en "$md_output"