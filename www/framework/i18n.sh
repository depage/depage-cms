#!/bin/bash
if ! test -d locale
then
	echo "run this script in the parent directory of your locale directory"
	exit 1
fi

languages=`ls -l locale | grep -v "^l" | cut -f 13 -d " " -s | sed '1d' | sed -e 's/en_US//' | sed -e 's/CVS//'`
echo "extract strings..."
tempfile=`tempfile`
find ./ -name "*.tpl" -or -name "*.php" > $tempfile
xgettext -f $tempfile -L PHP -o locale/en_US/LC_MESSAGES/messages_tmp.po
cat locale/en_US/LC_MESSAGES/messages_tmp.po | sed -e 's/CHARSET/UTF-8/' > locale/en_US/LC_MESSAGES/messages.po
rm locale/en_US/LC_MESSAGES/messages_tmp.po
echo "update languages..."
for lang in $languages
do
echo "update $lang..."
if [[ ! -a locale/$lang/LC_MESSAGES/messages.po ]]
then
echo "copy template..."
cp locale/en_US/LC_MESSAGES/messages.po locale/$lang/LC_MESSAGES/messages.po
fi
mv locale/$lang/LC_MESSAGES/messages.po locale/$lang/LC_MESSAGES/messages_old.po
msgmerge locale/$lang/LC_MESSAGES/messages_old.po locale/en_US/LC_MESSAGES/messages.po \
-o locale/$lang/LC_MESSAGES/messages.po
rm locale/$lang/LC_MESSAGES/messages_old.po
msgfmt -o locale/$lang/LC_MESSAGES/messages.mo locale/$lang/LC_MESSAGES/messages.po
done
echo "done!"

