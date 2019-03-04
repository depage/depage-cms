Versionshistorie     {#changelog}
================

[TOC]

Version 2.0   {#v2-0}
===========

**User Interface Highlights**

- Verbessertes [Dashboard](@ref dashboard)
- Vollständig neues [Editier Interface](@ref editing-pages)
- Vollständig überarbeiteter [Text Editor](@ref text-editor)
- Neuer [Projektschnellzugriff](@ref project-shortcuts), um schnell neue News- oder Blog-Einträge hinzuzufügen
- Neue [Dateibibliothek](@ref file-library)
- Neues Interface zur Bearbeitung der Farbschemata [color schemes](@ref colors)
- Verbesserung der [Vorschau](@ref page-preview), so dass diese automatisch in der gerade bearbeiteten Sprache angezeigt wird
- Verbesserung der Vorschau, um das gerade aktivierte Element [hervorzuheben](@ref page-preview)
- Neues online [Benutzerhandbuch](https://docs.depage.net/depage-cms-manual/de/)


v2.0.7      {#v2-0-7}
------

**Frontend**

- Option hinzugefügt, um die Live-Url veröffentlichter Bilder zu kopieren

**Backend**

- Veröffentlichung verbessert, um umbenannte Seiten automatisch den der neuen URL weiterzuleiten
- Veröffentlichung verbessert, um automatisch alle benötigten Bilder zu generieren
- Bug beim Laden der XML-Templates behoben
- XSL Templates verbessert, um absolute Verweise zu generieren
- Performance des Veröffentlichens optimiert


v2.0.6      {#v2-0-6}
------

**Frontend**

- Fehler beim Autospeichern der Dokumenteigenschaften behoben


v2.0.5      {#v2-0-5}
------

**Backend**

- Fehler in XmlForm behoben, wenn Attribute mit Sonderzeichen gespeichert werden


v2.0.4      {#v2-0-4}
------

**Backend**

- Besseres Fehlerhandling in FsFtp hinzugfügt
- Fehler in XmlDb behoben


v2.0.3      {#v2-0-3}
------

**Backend**

- Session Laufzeit auf maximal eine Woche verlängert
- Fehlende Übersetzungen hinzugefügt


v2.0.2      {#v2-0-2}
------

**User Interface**

- Bug bei der Bearbeitung von Fußnoten behoben
- Bug bei der Sprachauswahl der Seitenvorschau behoben
- Bug in der Anzeige fehlgeschlagender Hintergrund-Tasks behoben
- Option zur Beareitung von Farben im Seitencontent hinzugefügt.

**Backend**

- Performance-Optimierung der XSL Basis-Vorlagen

v2.0.1      {#v2-0-1}
------

**User Interface**

- Verbesserung des Seitenstatus
- Aktualisierung der Seitenbibliotek zu Anzeige des Veröffentlichungsstatus der jeweilgen Datei
- Verbesserung des Löschen-Dialogs

v2.0.0      {#v2-0-0}
------

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

