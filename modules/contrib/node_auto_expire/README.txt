CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Drupal 8 implementation of the Auto Expire module created for Drupal 7. The
module is useful for content that is allowed only for a limited period of time
(bulletin boards, catalogs, etc.). Node Auto Expire allows site administrators
to set the expiration time for the chosen content types.

End users are not able to control the expiration time of their content. But
they аrе allowed to extend it for an unlimited number of times. A warning
message is sent to the author of the created node before the current node
expiration. When the node publication time has been expired, it will be
unpublished on the first cron run. Notification about the expired node will be
sent to the author. Expired nodes can be automatically purged after the
specified amount of time.

Optionally BCC email address may be specified to receive all notifications
about expired nodes.

Extend auto expire on node updates (each time when a node is saved) may be
allowed on the settings page of the Node Auto Expire module.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/node_auto_expire

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/node_auto_expire


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the Node Auto Expire module as you would normally install
   a contributed Drupal module.
   Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration » People » Permissions:
        *  administer auto expire
          "Administer the Node Auto Expire options. Setup basic configuration."
        *  extend expiring own content
          "Allow the current user to expire his own content."
        *  extend expiring all content
          "Allow the current user to expire any content."
    3. Navigate to Administration > Configuration > Content authoring >
       Node Auto Expire menu to setup a basic module configuration.
        * choose the appropriate content type to be expired
        * allow/deny node expiring on each node update
        * setup the number of days when the node will be automatically expired
        * setup the number of days when the warning message is sent to the author
        * setup the number of days when the node will be purged from the database
        * setup Expiration warning and Expired notification email body and subject


MAINTAINERS
-----------

 * Yurii Slan (Engineer_UA) - https://www.drupal.org/u/engineer_ua
