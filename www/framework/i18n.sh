#!/bin/bash
if ! test -d locale
then
    # search for locale dir if not in current path
    localeDir=$( find . -type d -name locale | grep locale )
else
    localeDir=locale
fi

if ! test -d $localeDir
then
    echo "no locale dir found below current path"
    exit 1
else
    echo "found locale directory in $localeDir"
fi

languages=$( ls $localeDir )

echo "extract strings..."
filesPHP=$(mktemp /tmp/localize.XXXXXXX)
filesXML=$(mktemp /tmp/localize.XXXXXXX)
filesJS=$(mktemp /tmp/localize.XXXXXXX)
potAll=$(mktemp /tmp/localize.XXXXXXX)
potJS=$(mktemp /tmp/localize.XXXXXXX)

# find php files
find . -name "*.php" -or -name "*.tpl" > $filesPHP
xgettext \
    -f $filesPHP \
    --from-code=UTF-8 -L PHP -o - \
    | sed -e 's/charset=CHARSET/charset=UTF-8/' > $potAll

# find xml files for navigation
find . -name "nav*.xml" > $filesXML
xgettext \
    -f $filesXML \
    --from-code=UTF-8 \
    -L perl -k -k"name" -j -o $potAll

# find js files
find . -name "*.js" > $filesJS
xgettext \
    -f $filesJS \
    --from-code=UTF-8 \
    -L perl -o $potJS


echo "processing languages"
for lang in $languages; do
    echo "updating $lang..."
    if [[ ! -a $localeDir/$lang/LC_MESSAGES/messages.po ]] ; then
        echo "copy template..."
        mkdir -p $localeDir/$lang/LC_MESSAGES
        cp $potAll $localeDir/$lang/LC_MESSAGES/messages.po
    fi
    cp $localeDir/$lang/LC_MESSAGES/messages.po $localeDir/$lang/LC_MESSAGES/messages_old.po
    cp $localeDir/$lang/LC_MESSAGES/messages.po $localeDir/$lang/LC_MESSAGES/messages_old_bak.po
    msgmerge $localeDir/$lang/LC_MESSAGES/messages_old.po $potAll -o $localeDir/$lang/LC_MESSAGES/messages.po
    if [[ -a framework/locale/$lang/LC_MESSAGES/messages.po ]] ; then
        cp $localeDir/$lang/LC_MESSAGES/messages.po $localeDir/$lang/LC_MESSAGES/messages_old.po
        msgcat --use-first $localeDir/$lang/LC_MESSAGES/messages_old.po framework/$localeDir/$lang/LC_MESSAGES/messages.po -o $localeDir/$lang/LC_MESSAGES/messages.po
    fi

    chanchedLines=$( diff -I ".POT-Creation-Date:.*" -I "#.*" $localeDir/$lang/LC_MESSAGES/messages_old_bak.po $localeDir/$lang/LC_MESSAGES/messages.po | grep -v '^[<>-]' | wc -l | grep -o "[0-9]\+" )

    if [ "$chanchedLines" != "0" ] ; then
        # there are changes
        rm $localeDir/$lang/LC_MESSAGES/messages_old.po $localeDir/$lang/LC_MESSAGES/messages_old_bak.po
    else
        # no changes -> keep old version
        rm $localeDir/$lang/LC_MESSAGES/messages_old.po
        mv $localeDir/$lang/LC_MESSAGES/messages_old_bak.po $localeDir/$lang/LC_MESSAGES/messages.po
    fi
    msgfmt -o $localeDir/$lang/LC_MESSAGES/messages.mo $localeDir/$lang/LC_MESSAGES/messages.po
done

rm $filesPHP $filesXML $filesJS $potAll $potJS
