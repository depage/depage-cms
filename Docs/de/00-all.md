depage-cms
==========

Das Grundlegende an einer Internetseite ist ihre Struktur, ihr Aufbau. Dies ist einer der ersten und wichtigesten Schritte beim Entwurf und der Organisation eines Internetauftritts, nachdem die Ziele und Inhalte definiert worden sind. Zunächst herrscht oft Chaos, in das Ordnung gebracht werden will.
depage-cms unterstützt sie dabei. Die Inhalte der Seite werden zunächst in einer hierarchischen Struktur eingeordnet und können dort Stück
für Stück erweitert, ergänzt und umstrukturiert werden.
depage-cms funktioniert hier ähnlich wie ein Outliner. Es können Seiten erstellt, umbenannt, kopiert, verschoben oder gelöscht werden. Aus dieser Struktur läßt sich dann über die SeitenTemplates bestimmen, wie hieraus (und bei Bedarf auch zusätzlichen Meta-Informationen) die Navigation und der Seiteninhalt generiert wird.

Das Interface
Das Login
Beim Start von depage-cms begrüßt einen zunächst das Login-Fenster. Es muss der Benutzername und das dazugehörige Passwort eingegeben werden. Nach Eingabe der Zugangsdaten öffnet sich der Startscreen von depage-cms. Man ist nun am System angemeldet.
Im Startscreen kann man sich direkt die zuletzt geändert Seiten anzeigen lassen. Über Editieren wird die Editieransicht von depage-cms geöffnet. Über Vorschau lässt sich der aktuelle unveröffentlichte Stand der Seite anzeigen.
Das Split-Interface
Innerhalb von depage-cms arbeitet man die meiste Zeit innerhalb eines geteilten Fensters. Der linke Teil enthält die eigentliche Bedienoberfläche von depage-cms. Dort werden neue Seiten erstellt, Texte abgeändert oder Bilder ausgewählt. In der rechten Seite, die sich öffnet, sobald man eine Seite editiert, wird die automatische Vorschau der Seite angezeigt, die aktuell bearbeitet wird.

Der Editierbereich
Der Editierbereich ist immer in zwei Bereiche unterteilt. Links gibt es einen oder mehrere Strukturbäume, die immer mit ähnlichen Werkzeugen – wie verschieben, kopieren, umbenennen oder löschen – bearbeitet werden können. Rechts sind die jeweiligen Einstellungsfelder für das links markierte Element.
In der Titelzeile eines Strukturbaums ist ein Ladehinweis zu sehen, wenn gerade Daten zum
Server gesendet oder vom Server empfangen werden, weil man selbst oder jemand anderes eine Seite bearbeitet hat. Um Platz zu sparen, können die Strukturbäume auch eingeklappt werden.
Der Einstellungsbereich erweitert sich je nach Inhalt dynamisch nach unten, und muss bei Bedarf entsprechend nach oben oder nach unten gescrollt werden.

Die verschiedenen Bearbeitungsmodi
Im Hauptfenster steht auf der linken Seite eine Reihe von Buttons zur Verfügung, mit denen man in verschiedene Bearbeitungsmodi von depage-cms springen kann.
Zunächst wird automatisch der Bearbeitungsmodus Seiten editieren geöffnet. Dort kann man neue Seiten erstellen, verschieben oder löschen und ihr Inhalte bearbeiten: Texte verändern, Bildern auswählen etc.
Darunter befindet sich die Dateibibliothek. Dort befinden sich alle manuell hinzugefügten Dateien, die in die Seiten integriert werden sollen, aber nicht vom depage-cms System erstellt werden. Hierzu zählen beispielsweise die Bilder, die in die Seite integriert werden, wie auch Flash-Animationen, Videos, PDFs, Zip-Dateien und andere.
Über den Bearbeitungsmodus Farben und Farbschemata bearbeiten lassen sich die einzelnen Farben bearbeiten oder neue Farbschemata anlegen.
Der Vorschau-Button
Links unten befindet sich der Vorschau Button. Wenn man darauf klickt, wird das Vorschau-Fenster aktualisiert.
Wenn man die Maus etwas länger auf dem Button gedrückt hält, springt ein Menü auf, in dem man verschiedene Einstellungen für die Vorschau vornehmen kann.
Beispielsweise lässt sich dort ein anderes Template wählen – z.B.: debug anstatt html – das den Seiteninhalt dann anders darstellt.
Alternativ kann die aktuell angezeigte Seite über den Button Aktualisieren über der Vorschau neu geladen werden.


Seiten editieren
Die Strukturansicht
Die Strukturansicht im oberen linken Fensterbereich ist eine Baumdarstellung der einzelnen Seiten eines Internetauftritts. Aus dieser Seitenhirarchie und ihren Namen wird automatisch die Navigation des Internetauftritts generiert.
Am unteren Ende stehen verschiedene Funktionen zur Verfügung, die mit den Seiten vorgenommen werden können.
     löschen duplizieren
hinzufügen
Seiten und Ordner
Es gibt zwei Arten von Elementen: Seiten und Ordner. Ordner sind dadurch gekennzeichnet, dass ihr Name in eckigen Klammer steht: [Ordner].
Seiten und Ordner unterscheiden sich dadurch, dass Seiten immer einen Seiteninhalt enthalten. Ordner hingegen bilden nur ein Element in der Navigation, enthalten aber keinen Inhalt. Wird in der Navigation ein Ordner angewählt, wird immer das erste Seitenelement innerhalb des Ordners angezeigt, nicht der Ordner selbst. Seiten, wie auch Ordner können weitere Unterseiten enthalten. Sobald ein Element weitere Unterelemente enthält ist es mit einem Pfeil davor gekennzeichnet.
Seiten hinzufügen
Über den Button Seiten hinzufügen werden neue Seiten und Ordner in den Strukturbaum eingefügt. Zur Auswahl stehen zum einen der Ordner und die leere Seite, die noch keine Inhaltselemente enthält, und zum anderen eine Reihe vorgegebener Seiten, die schon verschiedene Inhaltselemente wie Text und Bilder enthalten können.
Eines neues Element wird immer an letzter Stelle innerhalb des gerade markierten Elementes eingefügt, und dann sofort markiert.
Wenn man auf der obersten Ebene ein Element hinzufügen möchte, dann muss man zunächst kein Element markieren. Dazu klickt man auf die weiße Fläche unterhalb der bestehenden Seiten und Ordner.
Ein neues Element heißt zunächst (unbenannt). Per Doppelklick kann der Name editiert werden.
Mit Enter kann man den Editiervorgang abschließen. Escape bricht den Vorgang ab.


Seiten duplizieren
Jede Seite lässt sich über den Button duplizieren vervielfachen. Es wird eine exakte Kopie der Seite erstellt und direkt unterhalb des Originals abgelegt. Allerdings wird so nur die Seite selbst, nicht aber all ihre Unterseiten und Ordner mit dupliziert.
Seiten löschen
Um eine Seite zu löschen muss man sie zunächst markieren und dann auf den Button löschen klicken. Es erscheint dann eine Sicherheitsabfrage, ob man das Element wirklich löschen möchte, die man mit Ok bestätigen oder mit Escape abbrechen kann. Ein Element wird immer komplett mit allen Unterelementen gelöscht.
Seiten kopieren
Seiten werden auf die gleiche Art und Weise kopiert, wie sie auch verschoben werden. Zum kopieren muss nur zusätzlich die Shift-Taste gehalten werden, bis das Element an der neuen Stelle losgelassen wird.


Seiten verschieben
Seiten- und Ordnerelemente lassen sich einfach mit der Maus nehmen und an eine neue Position verschieben. Wenn man sie auf einem anderen Element fallen lässt, wird die Seite in das andere Element verschoben und erscheint dort an letzter Stelle. Wird es zwischen zwei Elementen fallengelassen, wird es an diese Stelle verschoben. Eine orangefarbene Markierung zeigt an, wohin das Element gelegt wird.


Die Dokumentenstruktur
Neben der Navigationsstruktur gibt es noch eine Dokumentenstruktur. Hier wird der eigentliche Inhalt einer Seite bearbeitet. Zur Bearbeitung stehen die selben Mittel zur Verfügung, wie auch bei der Bearbeitung der Navigationsstruktur: Hinzufügen, Duplizieren, Verschieben, Kopieren und Löschen.
Allerdings verhält sich die Funktion Duplizieren etwas anders als bei der Bearbeitung der Navigation: Hier wird das Element selbst, wie auch alle Unterelemente mit dupliziert.
Das Meta* Element
Alle Seiten und Ordner in depage-cms haben ein gemeinsames Element: Das Meta* Element. Es wird automatisch markiert, wenn eine Seite ausgewählt wird.
Im Meta* Element werden Einstellungen vorgenommen, die für jede Seite zur Verfügung stehen.
Als erstes ist zu sehen, wer die Seite als letztes und zu welchem Zeitpunkt bearbeitet hat. Hier kann auch der aktuelle Stand der Seite freigegeben werden, so dass der Inhalt bei der nächsten Veröffentlichung mit übernommen wird.
Als nächstes kommt die Auswahl des Farbschemas. depage-cms arbeitet mit Farbschemata, Sätzen von verschiedenen Farben, die als Gruppe ausgewählt werden und auf die jeweilige Seite angewendet werden.
Der Punkt Navigation entscheidet darüber, ob und an welcher Stelle eine Seite in der Navigation erscheint.
Mit Tags läßt sich eine Seite in Kategorien einordnen und so zu einem späteren Zeitpunkt besser filtern.
Bei Titel kann der Titel der Seite angegeben werden, die in der Kopfzeile des Browser-Fensters erscheinen soll.
Unter Linkinfo kann ein zusätzlicher Beschreibender Text angegeben werden, der dazu benutzt werden kann den lokalisierten Seitentitel in der Navigation festzulegen.
Unter Beschreibung kann ein Beschreibungstext der Seite oder Stichworte angegeben werden, die dann – in der Regel für den Benutzer unsichtbar – in den Description-Tag der HTML-Seite eingetragen werden. Dieser Text wird unter anderem von Suchmaschinen ausgelesen und teilweise auch in den Suchergebnissen mit angezeigt.
Seitenelemente hinzufügen
Neue Elemente werden über den Button hinzufügen in das Dokument eingefügt. Es werden jeweils nur solche Elemente angezeigt, die auch in das aktuelle Elemente eingefügt werden können – beispielsweise ein neuer Link in eine Linkliste oder ein neues Bild in eine Slideshow.


Sobald ein Seitenelement markiert wird, wird der Inhalt im Eigenschaftsbereich angezeigt und kann dort bearbeitet werden. Es stehen folgende Eigenschaftselemente zur Verfügung:
Textelement (einzeilig)
Textelement (mehrzeilig)
Textelement (formatiert)
Das Textelement (einzeilig) steht für unformatierten, einzeiligen Text, wie beispielsweise einzeilige Überschriften zur Verfügung.
Das Textelement (mehrzeilig) steht für unformatierten, mehrzeiligen Text zur Verfügung, wie beispielsweise mehrzeilige Überschriften oder kurze Teaser.
Das Textelement (formatiert) steht für formatierten, mehrzeiligen Text zur Verfügung. Dort können Texte mit Auszeichnungen wie fett oder kursiv versehen werden. Außerdem können innerhalb des Textes Links zu anderen Seiten gesetzt werden.
Dazu markiert man zunächst den Textabschnitt, der als Link dienen soll und klickt dann auf das Link-Symbol. Es wird in einer Dialogbox das Linkziel abgefragt, das entweder auf eine andere Seite innerhalb des eigenen Internetauftritts, auf Dateien in der Dateibibliothek oder auf andere Internetseiten verweisen kann. Zudem kann gewählt werden, ob der Link im gleichen oder in einem neuem Browserfenster geöffnet werden soll.
Der Quelltext steht nur Administratoren und Developern zur Verfügung. Mit dessen Hilfe können einzelne HTML- oder Script-Elemente direkt in eine Seite integriert werden, ohne dass man dafür die Templates abändern müsste. Dies ist vor allem für Einzelfälle oder Scripts gedacht.
   Quelltext


Mit Hilfe des Bild-Elements kann ein Bild aus der Dateibibliothek ausgewählt werden und so in die Seite integriert werden. In das Alt-Textfeld kann ein Text eingegeben werden, der dann sichtbar ist, wenn jemand keine Bilder anzeigen kann, oder auch solange ein Bild noch nicht vollständig geladen ist.
Falls die Höhe oder Breite für ein Bild erzwungen wird, stehen in der Dateibibliothek nur solche Bilder zur Verfügung, die die richtigen Bildmaße haben.
Je nach Template kann auch noch ein Link ausgewählt werden, der geöffnet wird, wenn man auf das Bild klickt.
Das Link-Element ermöglicht es Verweise in die Seite einzufügen, die nicht innerhalb des Textes auftauchen, wie beispielsweise in Linklisten. Es kann auf eine andere Seite innerhalb des eigenen Internetauftritts, auf Dateien in der Dateibibliothek oder auf andere Internetseiten verwiesen werden.
Zudem kann gewählt werden, ob der Link im gleichen oder in einem neuem Browserfenster geöffnet werden soll:
Dieses Symbol bedeutet, dass der Link in einem neuen Fenster geöffnet wird.
Dieses Symbol bedeutet, dass der Link im gleichen Fenster geöffnet wird.




Die Dateibibliothek ist ein Satz von Dateien, die nicht von depage-cms direkt erstellt werden, sondern in Seiten eingebunden oder von dort verlinkt werden können. Es gibt zwei Ansichten:
Die Thumbnailansicht
In der Thumbnailansicht wird von allen Dateien, für die es möglich ist, eine Vorschau angezeigt. Sie ist vor allem dann praktisch, wenn es um die Auswahl von Bildern geht.
Die Listenansicht
In der Listenansicht werden nur alle Dateien eines Verzeichnisses aufgelistet, ohne dass dabei eine Vorschau angezeigt wird. Diese Ansicht
ist schneller, da keine Vorschau-Bilder geladen werden müssen.
Ordner erstellen
Über den Button Neuer Ordner kann man einen Ordner in der Dateihierarchie hinzufügen. Die Reihenfolge der Ordner in der Bibliothek ist immer von der Benennung abhängig und ihre Position im Baum kann nicht verändert werden.
Ordner umbenennen / verschieben
Ordner können ineinander mitsamt ihrem Inhalt verschoben oder auch umbenannt werden.
» Esistallerdingsdabeizubeachten, dass sich zu allen Dateien, die sich in diesem Ordner befinden und schon verlinkt sind, die Verknüpfungen lösen, und so nicht mehr auf der Seite angezeigt werden.
Ordner löschen
Ordner können über den Button löschen auch wieder gelöscht werden. Allerdings gilt auch hier: Alle zu diesem Ordner gesetzten Verknüpfungen gehen verloren.


Datei-Upload
Es können auch neue Dateien in die Dateibibliothek hochgeladen werden.


Dazu klickt man auf den Button Upload. Es öffnet sich ein Dialog, in dem man verschiedene Dateien auswählen oder diese per Drag&Drop in das aktuell gewählte Verzeichnis hochgeladen kann Nachdem der Upload abgeschlossen ist, kann der Dialog über Upload beenden geschlossen werden.
» Vorsicht:
Dateien, die bereits unter dem gleichen Namen im aktuellen Verzeichnis liegen, werden ohne Vorwarnung überschrieben!
Das dient vor allem dazu, dass Bilder einfach mit einer neuen Version aktualisiert werden können, ohne für jede Datei eine Überschreib-Warnung bestätigen zu müssen.


Dateien löschen
Da beim Veröffentlichen immer die gesamte Dateibibliothek auf dem Server aktualisiert wird empfiehlt es sich von Zeit zu Zeit unbenötigte
Dateien zu löschen. Dazu markiert man einfach die Dateien in der Dateiübersicht und drückt den Button löschen. Nach einer Sicherheitsabfrage wird die Datei aus der Dateibibliothek entfernt.


Farbschemata
 depage-cms Farbschemata
depage-cms arbeitet mit Farbschemata, d.h. Sätzen von verschiedenen Farben, die als Gruppe ausgewählt werden und auf die jeweilige Seite angewendet werden.
So können schnell verschiedene Seiten in ihrem Erscheinungsbild verändert werden, ohne dass dafür die Templates bearbeitet werden müssen.
Durch ein Farbschema können nicht nur verschiedene Farben angesprochen werden, sondern auch Logodateien oder Navigationselemente, die als Bilder vorliegen, so dass auch diese Elemente passend zum aktuellen Farbschema angezeigt werden.
Globale Farben
Neben den Farbschemata, die seitenabhän-
gig sind, kann man auch noch einen Satz von globalen Farben festlegen, die für alle Seiten zur Verfügung stehen.


Einstellungen/Veröffentlichen


Projekteinstellungen
Auf den Einstellungseiten können verschiedene Parameter des Projektes angepasst werden. In der Regel werden diese von den Administratoren verwaltet.


Tags
Tags helfen Ihnen dabei, Seiten zu kategorisieren und zu filtern. Ihre Templates müssen dies allerdings unterstützen. Die Reihenfolge der Tags kann per Drag&Drop angepasst werden.
Sprachen
depage-cms ermöglicht es Ihnen Seiten in mehreren Sprachen zu erstellen. Die erste Sprache fungiert dabei als Alternativsprache, wenn die Seite nicht in der Sprache des Besuchers verfügbar ist. Die Reihenfolge der Sprachen kann per Drag&Drop angepasst werden


Veröffentlichen
Eine der wichtigsten Funktionen in depage-cms ist das Veröffentlichen. Mit dieser Funktion wird der aktuelle Stand der Seiten und der Datei-Bibliothek auf einen Webserver kopiert, so dass sie dann für den normalen Besucher der Internetseite sichtbar werden.
Die Seiten können durch entweder lokal auf dem gleichen Server veröffentlicht werden, oder per ftp auf einem Remote-Server. depage-cms kann insofern unabhängig vom Veröffentlichungsserver genutzt werden.
Spezielle Einstellungen müssen hier nicht vorgenommen werden, da sie normalerweise vom Administrator vorgegeben werden.


Newsletter
Editieren
Projekte, die einen automatischen Newsletter unterstützen, zeigen diesen direkt in der Projektübersicht unter dem Hauptprojekt an. Wenn man dieses über den Pfeil aufklappt, wird eine Liste der vorhandenen Newsletter angezeigt, aus der diese dann editiert, veröffentlicht oder auch wieder gelöscht werden können.


Mit Klick auf den Newsletter selbst oder den Button Editieren, öffnet sich die Bearbeitungsansicht des Newsletters.

Dort muss dann der Titel des Newsletters und der Betreff für die herausgehenden E-Mails angegeben werden.
Darunter befindet sich zwei Listen der zur Verfügung stehenden News-Meldungen. Die obere Liste enthält alle Meldungen, die noch in keinem anderen Newsletter verwendeten wurden. Die untere Liste alle schon in anderen Newslettern ausgewählte Meldungen. Durch aktivieren können Sie dem aktuellen Newsletter hinzugefügt werden. Die Vorschau auf der rechten Seite zeigt dann jeweils automatisch die Vorschau, wie der Newsletter aussehen wird.
Es kann zusätzlich eine Beschreibung des Newsletters angegeben werden. Diese ist aber nicht innerhalb des Newsletters sichtbar, sondern wird als Meta-Description-Tag in die HTML-Version des Newsletters mit aufgenommen.


Veröffentlichen
Über Veröffentlichen kann der Newsletter dann entweder an eine Empfänger Liste gesendet werden.
Die Liste Default entspricht der Standardliste und enthält alle Empfänger, die sich über die Webseite angemeldet haben.
Die Liste Test ist eine interne Gruppe, der der Newsletter vorab zum Test geschickt werden kann.
Bei Auswahl von Benutzerdefinierte Empfänger kann der Newsletter auch an manuell angegebene E-Mail-Adressen geschickt werden. Die E-Mail-Adressen werden dafür unter E-Mails als kommagetrennte Liste angegeben.
Über Vorschau kann man sich die Vorschau des Newsletters in der jeweiligen Sprache anzeigen lassen. Vor Versand ist es wichtig, dabei beide Sprachversionen zu checken.
Versenden
Über Jetzt senden wird der Newsletter dann zunächst als HTML-Version auf der Webseite veröffentlicht und danach an alle ausgewählten bzw. angegeben Empfänger verschickt.
