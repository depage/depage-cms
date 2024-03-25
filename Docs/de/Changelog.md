Versionshistorie     {#changelog}
================

[TOC]

Version 2.4   {#v2-4}
===========

**User Interface Highlights**

- Verbessertes [Dashboard](@ref dashboard)
- Vollständig neues [Editier Interface](@ref editing-pages)
- Vollständig überarbeiteter [Text Editor](@ref text-editor)
- Neuer [Projektschnellzugriff](@ref project-shortcuts), um schnell neue News- oder Blog-Einträge hinzuzufügen
- Neue [Dateibibliothek](@ref file-library)
- Neue [Dateisuche](@ref file-search)
- Neue Funktion zur [Auswahl des Bildschwerpunkts](@ref image-gravitational-center)
- Neues Interface zur Bearbeitung der Farbschemata [color schemes](@ref colors)
- Verbesserung der [Vorschau](@ref page-preview), so dass diese automatisch in der gerade bearbeiteten Sprache angezeigt wird
- Verbesserung der Vorschau, um das gerade aktivierte Element [hervorzuheben](@ref page-preview)
- Neues Interface für kleine Screens
- Neues online [Benutzerhandbuch](https://docs.depage.net/depage-cms-manual/de/)


v2.5.0 / 25.03.2024      {#v2-5-0}
-------------------

**Backend**
- Verbesserte Leistung von xmldb
- Verbesserte Sitzungsverwaltung
- Verbesserte Benutzerauthentifizierung
- Aktualisierung der verwendeten Untermodule
- Aktualisierter Websocket-Server
- Aktualisierter Grafikgenerator
- Hinzugefügte Änderungen für verbesserte PHP 8-Unterstützung
- Verbesserte Basis-XSL-Vorlagen
- Verschiedene Änderungen zur bessere Unterstützung von Galera
- Verschiedene kleinere Bugs behoben

**Frontend**
- Verbesserte Textverarbeitung und Autospeichern
- Verbesserte Dokumenteigenschaften für Dateien

v2.4.0 / 29.05.2023      {#v2-4-0}
-------------------

**Backend**
- Newsletter-Versand verbessert
- Sitemap Generator aktualisiert, um angepasste Sitemaps und Aufteilung verschiedener Sitemaps zu unterstützen
- Neuer URL-Analyzer hinzugefügt, der aus XSL Templates heraus benutzt werden kann
- Basis XSL Template verbessert
- Neuer Imagick Provider zur Bild-Generierung hinzugefügt
- Verschiedene Bug-Fixes

**Frontend**
- Neue Funktion zur Erstellung von Newslettern mit zusätzlichen Inhalt hinzugefügt
- Neue Funktion zur Bearbeitung der Benutzerrechte von Projekten hinzugefügt


v2.3.1 / 08.08.2022      {#v2-3-1}
-------------------

**Backend**
- Standard-Benutzer aktualisiert, so dass er Projekte veröffentlichen aber nicht selbst direkt Seiten freigeben kann

**Frontend**
- Seitenfreigabe Workflow optimiert


v2.3.0 / 29.03.2022      {#v2-3-0}
-------------------

**Backend**
- Versand der Newsletter verbessert
- Basis XSL-Templates erweitert, um srcsets zu vereinfachen
- Verschiedene kleinere Bugs behoben
- Composer Bibliotheken aktualisiert

**Frontend**
- Neues Interface für kleiner Bildschirme wie Mobiltelefone und Touch Screens hinzugefügt


v2.2.0 / 19.11.2021      {#v2-2-0}
-------------------

**Backend**
- Neue Dateibibliothek hinzugefügt
- Neue Dateisuche hinzugefügt
- Performance beim Veröffentlichen der Dateibibliothek verbessert
- Verschieben und Umbenennen von Ordnern in der Dateibibliothek hält Dateiverknüpfungen bei
- Neuer Task hinzugefügt, um alle Projekte zu aktualisieren
- Basis XSL-Templates optimiert

**Frontend**
- Dateibibliothek verbessert
- Neue Suchfunktion zur Dateibibliothek hinzugefügt
- Fähigkeit hinzugefügt den Schwerpunkt von Bildern festzulegen
- Live-Vorschau beim Bearbeiten von Farbschemata hinzugefügt
- Verschiedene kleinere Bugs behoben


v2.1.14 / 29.04.2021      {#v2-1-14}
-------------------

**Backend**
- Verschiedene kleine Bugfixes hinzugefügt
- Verbesserungen für PHP 8 hinzugefügt

**Frontend**
- Newsletter Formular optimiert
- Download Link Sharing zur Dateibibliothek hinzugefügt


v2.1.13 / 26.01.2021      {#v2-1-13}
-------------------

**Backend**
- Graphics classes aktualisiert und verbessert
- Support für webp Dateien verbessert
- Basis XSL Vorlagen verbessert
- Support für Picture Element verbessert


v2.1.12 / 03.11.2020      {#v2-1-12}
-------------------

**Backend**
- Project Update Task verbessert
- Basis XSL Vorlagen verbessert


v2.1.11 / 16.09.2020      {#v2-1-11}
-------------------

**Frontend**
- Neue Front-End Addons für Projekte hinzugefügt

**Backend**
- Refactoring der API Klassen
- Tasks zum Veröffentlichen und Versenden von Newsletters optimiert
- Eingebettete HTTP Klassen auf die letzte Version aktualisiert


v2.1.10 / 03.08.2020      {#v2-1-10}
-------------------

**Backend**
- Vorschau aktualisiert, um projekt-spezifische API calls in der Vorschau zuzulassen
- Task-Runner aktualisiert, um fehlgeschlagene Subtasks automatisch zu wiederholen


v2.1.9 / 11.07.2020      {#v2-1-9}
-------------------

**Backend**
- depage-fs auf die aktuelle Version aktualisiert
- Redirect Templates aktualisiert, so dass kein extra Content mit ausgegeben wird


v2.1.8 / 26.06.2020      {#v2-1-8}
-------------------

**Backend**
- Performance Optimierungen zu XmlNav hinzugefügt
- Performance Optimierungen beim Auto-Speichern von Doc-Properties hinzugefügt
- Refactoring des Veröffentlichungs-Tasks
- XSL Vorlagen für Atom-Feeds verbessert


v2.1.7 / 19.06.2020      {#v2-1-7}
-------------------

**Backend**
- Neue Benachrichtigung zur Anfrage der Seitenfreigabe für Editoren


v2.1.6 / 12.06.2020      {#v2-1-6}
-------------------

**Frontend**
- Verschiedene Bugfixe

**Backend**
- New-Node Elemente aktualisiert und mehrere Sub-Elemente zu unterstützen
- Verschiedene Bugfixe


v2.1.5 / 25.05.2020      {#v2-1-5}
-------------------

**Backend**
- Neue Helferfunktionen zu xsl templates hinzugefügt
- Performance der Basis-xsl templates optimiert


v2.1.4 / 19.05.2020      {#v2-1-4}
-------------------

**Frontend**
- Fehler in der Vorschausprache behoben

**Backend**
- Definition der xml templates aktualisiert
- Basis xsl template verbessert
- Verschiedene Bugfixes


v2.1.3 / 16.04.2020      {#v2-1-3}
-------------------

**Backend**
- Verschiedene Bugfixes


v2.1.2 / 03.04.2020      {#v2-1-2}
-------------------

**Frontend**
- Vorschau optimiert, so dass manche Änderungen direkt in der html dom aktualisiert werden
- Performance der Vorschau optimiert
- Problem mit der Link Dialog Box in Google Chrome behoben
- Problem bei der Vorschau des Newsletters behoben
- Problem im Richtext Editor behoben

**Backend**
- Veröffentlichungsbenachrichtigung mit den URLs geänderter Seiten hinzugefügt
- Reihenfolge beim Veröffentlichen geändert, so dass zuletzt geänderte Seiten zuerst veröffentlicht werden
- Problem bei der Dateivorschau behoben
- Refactoring der XML Navigtion vorgenommen
- PHP 7.4 bezogene Änderungen hinzugefügt


v2.1.1 / 24.01.2020      {#v2-1-1}
-------------------

**Frontend**
- Option den Puplikations-Cache vor der Veröffentlichung zu leeren

**Backend**
- Veröffentlichungsprozess optimiert


v2.1.0 / 22.01.2020      {#v2-1-0}
-------------------

**Frontend**
- Funktion zum Schutz von Seiten hinzugefügt
- Funktion zur Vorschau und zum Zurücksetzen der Seiten aus der Seitenhistorie
- Styling des Dokumentenbaums verbessert
- Aktualisierung und Verbesserung des Einstellungsdialogs
- Fehler beim automatischen Speichern der Formulare behoben

**Backend**

- Aktualisierung und Verbesserung der *XmlDb*
- Optimierung der Performance der *XmlDb*
- Vorschau der Seitenhistorie hinzugefügt
- Funktion zum Leeren des Seitenpapierkorbs hinzugefügt
- Support Pakete aktualisiert
- Integration des depage-analytics Plugins hinzugefügt
- Fehler bei der Zuweisung von Benutzern beim Erstellung neuer Seiten behoben


v2.0.9 / 25.11.2019      {#v2-0-9}
-------------------

**Frontend**
- Bedienbarkeit des Dokumentbaums nach Fokus verbessert
- Verbesserung des Datei-Uploads

**Backend**

- Projekt Update Funktionalität verbessert
- Login-/Logout-Verhalten verbessert


v2.0.8 / 14.03.2019      {#v2-0-8}
-------------------

**Frontend**

- Neuer Input-Type *Nummer* hinzugefügt

**Backend**

- *$projectName* nun als Variable in XSL Templates verfügbar
- Fehler im Task-Runner behoben
- Fehler beim Parsen der Projekt Shortcuts behoben

v2.0.7 / 07.03.2019      {#v2-0-7}
-------------------

**Frontend**

- Option hinzugefügt, um die Live-Url veröffentlichter Bilder zu kopieren

**Backend**

- Veröffentlichung verbessert, um umbenannte Seiten automatisch den der neuen URL weiterzuleiten
- Veröffentlichung verbessert, um automatisch alle benötigten Bilder zu generieren
- Bug beim Laden der XML-Templates behoben
- XSL Templates verbessert, um absolute Verweise zu generieren
- Performance des Veröffentlichens optimiert


v2.0.6 / 31.01.2019      {#v2-0-6}
-------------------

**Frontend**

- Fehler beim Autospeichern der Dokumenteigenschaften behoben


v2.0.5 / 18.01.2019      {#v2-0-5}
-------------------

**Backend**

- Fehler in XmlForm behoben, wenn Attribute mit Sonderzeichen gespeichert werden


v2.0.4 / 26.12.2018      {#v2-0-4}
-------------------

**Backend**

- Besseres Fehlerhandling in FsFtp hinzugfügt
- Fehler in XmlDb behoben


v2.0.3 / 10.12.2018      {#v2-0-3}
-------------------

**Backend**

- Session Laufzeit auf maximal eine Woche verlängert
- Fehlende Übersetzungen hinzugefügt


v2.0.2 / 06.12.2018      {#v2-0-2}
-------------------

**User Interface**

- Bug bei der Bearbeitung von Fußnoten behoben
- Bug bei der Sprachauswahl der Seitenvorschau behoben
- Bug in der Anzeige fehlgeschlagender Hintergrund-Tasks behoben
- Option zur Beareitung von Farben im Seitencontent hinzugefügt.

**Backend**

- Performance-Optimierung der XSL Basis-Vorlagen

v2.0.1 / 20.11.2018      {#v2-0-1}
-------------------

**User Interface**

- Verbesserung des Seitenstatus
- Aktualisierung der Seitenbibliotek zu Anzeige des Veröffentlichungsstatus der jeweilgen Datei
- Verbesserung des Löschen-Dialogs


v2.0.0 / 03.11.2018      {#v2-0-0}
-------------------

**User Interface**

- Komplett überarbeitetes Edit-Interface

**Backend**

- Verbesserung, Vereinfachung und Optimierung der Basis-XSL-Templates
- Neue Projektweite Konfiguration hinzugefügt
- Neue Routing Optionen
- Alias Support
- Release- und Publikationsworkflow aktualisiert
- Websocket server für sofortige Updates von Tasks, Benachrichtigungen und Dokumentänderungen
- Aktualisierung und Erweiterung der *xmldb*
- Neue Seiten Status im Dokumentbaum: *isPublished* und *isReleased*
- Entfernung alter Abhängigkeiten
- Api verbessert
- Verbesserter IPv6 support

