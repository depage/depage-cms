Changelog     {#changelog}
=========

[TOC]

Version 2.0   {#v2-0}
===========

**User Interface Highlights**

- Enhanced [Dashboard](@ref dashboard)
- Complete revamp of the [Edit Interface](@ref editing-pages)
- Completely overhauled [text editor](@ref text-editor)
- New [Project shortcuts](@ref project-shortcuts) to quickly add new news- or blog posts
- Revamped [file library](@ref file-library)
- New editor for [color schemes](@ref colors)
- Enhanced [preview](@ref page-preview) to automatically show currently edited language
- Enhanced preview to [highlight](@ref page-preview) the currently selected document property
- New online [user manual](https://docs.depage.net/depage-cms-manual/de/)


v2.0.7      {#v2-0-7}
------

**Frontend**

- added option to copy live-url of published file

**Backend**

- enhanced publishing to allow redirect to new url when renaming published page
- updated published to autogenerate custom image sizes
- fixed bug when loading xml templates
- enhanced xsl templates when generating absolute references
- optimized performance of publishing task


v2.0.6      {#v2-0-6}
------

**Frontend**

- Fixed bug in autosaving doc-properties


v2.0.5      {#v2-0-5}
------

**Backend**

- Fixed a bug in XmlForm when saving attribute nodes with special characters


v2.0.4      {#v2-0-4}
------

**Backend**

- Added better error handling for FsFtp
- Fixed bug in XmlDb


v2.0.3      {#v2-0-3}
------

**Backend**

- Extended session lifetime for up to a week
- Added missing translations


v2.0.2      {#v2-0-2}
------

**User Interface**

- Fixed editing of footnotes
- Fixed bug with preview language
- Fixed bug when displaying faile background processes
- Added ability to edit color on doc-properties

**Backend**

- Optimized base xsl template for performance

v2.0.1      {#v2-0-1}
------

**User Interface**

- Enhancement of page status
- Update of file library to show publishing state of current file
- Optimization of delete dialog

v2.0.0      {#v2-0-0}
------

**User Interface**

- Complete overhaul of the edit interface

**Backend**

- Enhanced, simplified and optimized base xsl templates
- Added project wide config
- New routing options
- Alias support
- Updated release and publish workflow
- Websocket server for instant task, notification and document updates
- Updated and enhanced *xmldb*
- New states *isPublished* and *isReleased* in document tree
- Removed old dependencies
- Enhanced api
- Better IPv6 support
