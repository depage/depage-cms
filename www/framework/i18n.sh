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


echo "processing languages"
for lang in $languages; do
    echo "updating $lang..."
    if [[ ! -a $localeDir/$lang/LC_MESSAGES/messages.po ]] ; then
        echo "copy template..."
        mkdir -p $localeDir/$lang/LC_MESSAGES
        cp $potAll $localeDir/$lang/LC_MESSAGES/messages.po
    fi
    if [[ -a framework/locale/$lang/LC_MESSAGES/messages.po ]] ; then
        msgmerge --compendium=framework/locale/$lang/LC_MESSAGES/messages.po --backup=none --update $localeDir/$lang/LC_MESSAGES/messages.po $potAll
    else
        msgmerge --backup=none --update $localeDir/$lang/LC_MESSAGES/messages.po $potAll
    fi

    if [ $localeDir/$lang/LC_MESSAGES/messages.mo -ot $localeDir/$lang/LC_MESSAGES/messages.po ]; then
        msgfmt -o $localeDir/$lang/LC_MESSAGES/messages.mo $localeDir/$lang/LC_MESSAGES/messages.po
    fi

done

rm $filesPHP $filesXML $filesJS $potAll
