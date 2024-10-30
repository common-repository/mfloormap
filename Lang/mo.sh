for file in $(find . -name *.po -type f);
	do msgfmt "$file" -o "${file%po}mo";
done