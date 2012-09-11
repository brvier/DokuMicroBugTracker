DokuMicroBugTracker
=================
**Licence :** GPLv3  
**Download :** [DokuMicroBugTracker Version 2011_10_18](/downloads/dokumicrobugtracker_latest.zip)  
**Sources :** [GitHub Repository](https://github.com/khertan/dokumicrobugtracker)  
**Bug Tracker :** [Bug Tracker](https://github.com/khertan/DokuMicroBugTracker/issues)  

DokuMicroBugTracker is a DokuWiki plugin which integrates a really small bug tracker in your wiki by displaying a small form that enable everyone to report bug and every users with edit right to edit any information. 

![Screenshot](http://khertan.net/medias/dokumicrobugtracker_screenshot.png)

Download and Installation
----------------------

Download and install the plugin using the [Plugin Manager](http://www.dokuwiki.org/plugin:plugin) using the URL given above.

[Download Version 2011_10_18](http://khertan.net/downloads/dokumicrobugtracker_latest.zip)

Syntax and Usage
--------------

        {{dokumicrobugtracker>project=projectname|status=all|display=bugs}}

| Parameter  | Description                                                                                                             | Default                          |
|------------|-------------------------------------------------------------------------------------------------------------------------|----------------------------------|
| [project]  | the project of your bugtracker (eg : name of your software)                                                             | required                         |
| [status]   | a filter to display only some status                                                                                    | default is all                   |
| [display]  | a filter to display only the report form, or only the bugs list, or count the number of different status in the project | default is both form and report  |

Storage
------

Each project have is own data file, which are nammed from an md5 of the project name with the bugs extension, and store in the datas/metas doku wiki folder.

Examples
-------

**Display all bugs of project KhtEditor :**

        {{dokumicrobugtracker>project=KhtEditor|status=all|display=bugs}}
  
  
**Display just the report forms for project KhtEditor :**

        {{dokumicrobugtracker>project=KhtEditor|status=all|display=form}}
           
        
**Display all fixed bugs for project KhtEditor with a report form :**

        {{dokumicrobugtracker>project=KhtEditor|status=Fixed}}
          
          
**Display the number of report group by status :**

        {{dokumicrobugtracker>project=KhtEditor|display=Count}}
  
Still in developpment
-------------------

This plugin is a first shot, it s still in development and your all welcome to submit any ideas, or review.
There is currently a bug on the sort of header when displaying multiple form in the same page.

| Version            | Release Note            |
|--------------------|-------------------------|
| 2010-08-13 | Initial Release |
| 2010-08-14 | Fix bug creating first bug report |
| 2010-08-16 | Add report to user saying that bug is well saved (#4) |
| 2010-08-17 | Add several feature, email notification, delete (for admin only), dynamic captcha (#3, #5, #6) |
| 2010-08-26 | Fix bug on the dynamic captcha, notifications, clear description area after report |
| 2010-08-27 | Fix bug on insert report due to deletion |
| 2011-01-05 | Implement Ajax edition, add count feature, remove delete button |
| 2011-02-18 | Fix #16, #20 : Add missing / and table class inline. |
| 2011-03-07 | Fix #11, #21 : Fix edit and url callback. |
| 2011-04-13 | Fix #27, remove the forced title in the form, fix multiple post occurring when displaying multiple bugs list and report in the same page. |
| 2011-08-24 | Implement #65 (Configurable severity), Implement #62 (Inform user of modification on a report), Implement #64 (No report without information), Use captcha helper plugin for consistent captcha on the wiki, Fix #34. |
| 2011-10-17 | Use jquery, datatables plugin and jEditable to manage table, and fix a security issue. |
| 2011-10-18 | Fix link to the stylesheet in action.php, fix the delete feature. |
