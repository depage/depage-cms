Changelog     {#changelog}
=========

[TOC]

Version 2.1   {#v2-1}
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


v2.1.4 / 19.05.2020      {#v2-1-4}
-------------------

**Frontend**
- fixed language of previews

**Backend**
- updated definition xml templates
- enhanced base xsl templates
- various bugfixes


v2.1.3 / 16.04.2020      {#v2-1-3}
-------------------

**Backend**
- various bugfixes


v2.1.2 / 03.04.2020      {#v2-1-2}
-------------------

**Frontend**
- enhanced preview to updated some changes directly in html dom
- enhanced performance of preview updates
- fixed bug with link dialog in Google Chrome
- fixed bug in newsletter preview
- fixed bugs in richtext editor

**Backend**
- added publish notification with urls of changed pages
- updated publishing order to upload last changes first
- fixed bug with file previews
- refactored xml navigation related code
- added php 7.4 related changes


v2.1.1 / 24.01.2020      {#v2-1-1}
-------------------

**Frontend**
- added ability to clear transform cache before publishing

**Backend**
- optimized publishing process


v2.1.0 / 22.01.2020      {#v2-1-0}
-------------------

**Frontend**
- added ability to protect pages from changes
- added ability to browse and rollback page data from history
- enhanced styling of tree component
- updated and enhanced setting dialogs
- fixed autosaving of forms

**Backend**

- Updated and enhanced *XmlDb*
- Optimized performance of *XmlDb*
- added page history browser
- added ability to clear page trash
- updated support packages
- added integration of the depage-analytics plugin
- fixed bug where user for new pages was not assigned correctly


v2.0.9 / 25.11.2019      {#v2-0-9}
-------------------

**Frontend**
- enhanced usability of pagedata-tree on selection
- enhanced file upload

**Backend**

- enhanced project-update method
- enhanced login/logout behavior


v2.0.8 / 14.03.2019      {#v2-0-8}
-------------------

**Frontend**

- added new *number* type to doc-properties

**Backend**

- made *$projectName* available to use in xsl templates
- fixed bug in task-runner
- fixed bug in project shortcut handling

v2.0.7 / 07.03.2019      {#v2-0-7}
-------------------

**Frontend**

- added option to copy live-url of published file

**Backend**

- enhanced publishing to allow redirect to new url when renaming published page
- updated published to autogenerate custom image sizes
- fixed bug when loading xml templates
- enhanced xsl templates when generating absolute references
- optimized performance of publishing task


v2.0.6 / 31.01.2019      {#v2-0-6}
-------------------

**Frontend**

- Fixed bug in autosaving doc-properties


v2.0.5 / 18.01.2019      {#v2-0-5}
-------------------

**Backend**

- Fixed a bug in XmlForm when saving attribute nodes with special characters


v2.0.4 / 26.12.2018      {#v2-0-4}
-------------------

**Backend**

- Added better error handling for FsFtp
- Fixed bug in XmlDb


v2.0.3 / 10.12.2018      {#v2-0-3}
-------------------

**Backend**

- Extended session lifetime for up to a week
- Added missing translations


v2.0.2 / 06.12.2018      {#v2-0-2}
-------------------

**User Interface**

- Fixed editing of footnotes
- Fixed bug with preview language
- Fixed bug when displaying faile background processes
- Added ability to edit color on doc-properties

**Backend**

- Optimized base xsl template for performance


v2.0.1 / 20.11.2018      {#v2-0-1}
-------------------

**User Interface**

- Enhancement of page status
- Update of file library to show publishing state of current file
- Optimization of delete dialog


v2.0.0 / 03.11.2018      {#v2-0-0}
-------------------

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
