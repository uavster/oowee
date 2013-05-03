OOWEE
=====
Oowee is a PHP website engine with a Content Management System (CMS). Yes, yet another one. I have developed it during the last year in my spare time, as a zen therapy. In the beginning, coding Oowee just helped me to relax after work but finally it has become something useful and I felt the need to share. After all, it's powering my website at http://uavster.com

Use Oowee if you:
- Flee from huge CMS monsters
- Like to control every PHP line on your site
- Want to experiment with a simple-yet-powerful website engine code base
- Want to see the insides on an understandable website engine
- Want to be the coolest of your coder friends contributing to a completely non-mainstream project

DO NOT use Oowee if you:
- Want to build your site by point-and-click
- Need to access thousands of widgets from a large active comunity (make some instead and contribute!)
- Want a default supercool admin backend (make your own and contribute!)

Features
--------
- Dynamic templates
- Widgets
- Content Management System
- Generate AJAX queries with a single PHP line
- Generate useful client script snippets from PHP
- Natively integrated with RedBeanPHP and CKEditor
- Easy integration with JQuery (optional)
- PHP helpers for: database access, MIME types, URLs, XML files, strings
- JavaScript helpers and controls: html editor, date picker, ajax, animations, cookies, graphics, form submit, URLs
- Virtual file system
- Hierarchical site configuration
- Inter-widget communication
- Browser cache control
- Multiple sites with the same engine
- Per site logs
- Clear model-view separation
- Engine and sites never mix (separate folders)
- All code is object-oriented
- Support for multiple encodings

Installation
------------
To install Oowee follow these steps:
1. Copy all Oowee files to a folder accessible under your HTTP server public directory. For instance: {HTTP public directory}/oowee
2. Copy the files in the 'install' folder to your HTTP server public directory (i.e. public_html, www, ...)
3. If you placed Oowee files in '{HTTP public directory}/oowee', skip this step. Otherwise, edit the copied index.php and change the include line so it points to your Oowee directory.
4. Edit {HTTP public directory}/sites/SitesConfig.php and follow the comments to configure your sites.

Documentation
-------------
I'm in the process of writing it. Please, be patient and shoot any questions to ignacio (dot) mellado (at) gmail (dot) com

Happy coding!
