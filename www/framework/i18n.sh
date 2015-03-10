#!/bin/bash
if ! test -d locale
then
    # search for locale dir if not in current path
    localeDir=$( find . -type d -name locale | grep locale )
    cd $localeDir/..
fi

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
    cp locale/$lang/LC_MESSAGES/messages.po locale/$lang/LC_MESSAGES/messages_old_bak.po
    msgmerge locale/$lang/LC_MESSAGES/messages_old.po $potAll -o locale/$lang/LC_MESSAGES/messages.po
    if [[ -a framework/locale/$lang/LC_MESSAGES/messages.po ]] ; then
        cp locale/$lang/LC_MESSAGES/messages.po locale/$lang/LC_MESSAGES/messages_old.po
        msgcat locale/$lang/LC_MESSAGES/messages_old.po framework/locale/$lang/LC_MESSAGES/messages.po -o locale/$lang/LC_MESSAGES/messages.po
    fi

    chanchedLines=$( diff -I ".POT-Creation-Date:.*" locale/$lang/LC_MESSAGES/messages_old_bak.po locale/$lang/LC_MESSAGES/messages.po | grep -v '^[<>-]' | wc -l | grep -o "[0-9]\+" )

    if [ "$chanchedLines" != "0" ] ; then
        # there are changes
        rm locale/$lang/LC_MESSAGES/messages_old.po locale/$lang/LC_MESSAGES/messages_old_bak.po
        msgfmt -o locale/$lang/LC_MESSAGES/messages.mo locale/$lang/LC_MESSAGES/messages.po
    else
        # no changes -> keep old version
        rm locale/$lang/LC_MESSAGES/messages_old.po
        mv locale/$lang/LC_MESSAGES/messages_old_bak.po locale/$lang/LC_MESSAGES/messages.po
    fi
done

rm $filesPHP $filesXML $potAll

