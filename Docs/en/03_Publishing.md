Publishing     {#publishing}
==========

[TOC]

One of the most important functions in depage-cms is *publishing*. If you publish, depage-cms copies the current version of pages to the live server and synchronizes the file library, so that all changes will be visible for the visitors.

Pages can be published locally on the same server or the can be copied to a remote server with (s)ftp/sftp. The live version of the published page is therefor independent of the edit-server with your depage-cms installation.


Releasing Pages
===============

Releasing a page saved the current version of a page in the document history. This version will not be publicly visible, but will be used to generate the pages during the next publishing process.

Page status
-----------

The current state of the page will be displayed in the [Meta Property](@ref meta-property). A page can be:

- not published yet,
- published as is or
- published, but have unreleased changes.


Request page release
--------------------

Editors cannot release pages by themselves – only users that have the rights to publish the project can. But editors can request a page to be release. This is available in the [Meta Propery](@ref meta-property).

When the *Request Release* button gets clicked, all user that can published the project will get an email notification about the request.

This button is available as long a page is not released, so that editor can request a page release again if necessary.

![Request Release button](images/request-release.png)


Release Pages
-------------

User, that can publish a project, can release pages by themselves and don't have to request a release.

You can release a page in the meta property or on the publish project page.

![Release Page Button](images/release-page.png)


Publishing
==========

Publishing copies all released pages to the live server and synchronizes the file library. Publishing can also updated the search-indes of the site and update the database schemes, if this is supported by the live site.

Users that are allowed to published can start the process on the dashboard with the *Publish* button or through the project menu.

The publishing dialog contains a list of all changed and not yet published pages, where you can preview the changes and select pages to be published.

The button *Publish Now* starts the publishing process, which works in the background. Because the publishing task is independent of the user, users are allowed to login and logout, while the task is still running. Users can also edit other pages, without interrupting the publishing process.


Server structure
----------------

The following graphic shows the structure between live- and edit-server.

![The structure between live- and edit-server](images/server-structure-publishing.svg)



> [Go to the chapter: File Library](@ref file-library)
