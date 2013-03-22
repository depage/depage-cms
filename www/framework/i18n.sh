#!/bin/bash
if ! test -d locale
then
    echo "run this script in the parent directory of your locale directory"
    exit 1
fi

languages=`ls locale`

echo "extract strings..."
filesPHP=$(mktemp /tmp/localize.XXXXXXX)
filesXML=$(mktemp /tmp/localize.XXXXXXX)
potAll=$(mktemp /tmp/localize.XXXXXXX)

find . -name "*.php" -or -name "*.tpl" > $filesPHP
xgettext \
    -f $filesPHP \
    --from-code=UTF-8 -L PHP -o - \
    | sed -e 's/charset=CHARSET/charset=UTF-8/' > $potAll

find . -name "nav*.xml" > $filesXML
xgettext \
    -f $filesXML \
    --from-code=UTF-8 \
    -L perl -k -k"name" -j -o $potAll

echo "processing languages"
for lang in $languages; do
    echo "updating $lang..."
    if [[ ! -a locale/$lang/LC_MESSAGES/messages.po ]] ; then
        echo "copy template..."
        mkdir -p locale/$lang/LC_MESSAGES
        cp $potAll locale/$lang/LC_MESSAGES/messages.po
    fi
    cp locale/$lang/LC_MESSAGES/messages.po locale/$lang/LC_MESSAGES/messages_old.po
    msgmerge locale/$lang/LC_MESSAGES/messages_old.po $potAll -o locale/$lang/LC_MESSAGES/messages.po
    if [[ -a framework/locale/$lang/LC_MESSAGES/messages.po ]] ; then
        cp locale/$lang/LC_MESSAGES/messages.po locale/$lang/LC_MESSAGES/messages_old.po
        msgcat locale/$lang/LC_MESSAGES/messages_old.po framework/locale/$lang/LC_MESSAGES/messages.po -o locale/$lang/LC_MESSAGES/messages.po
    fi
    rm locale/$lang/LC_MESSAGES/messages_old.po

    msgfmt -o locale/$lang/LC_MESSAGES/messages.mo locale/$lang/LC_MESSAGES/messages.po
done

rm $filesPHP $filesXML $potAll

