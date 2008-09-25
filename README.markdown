# Backuphp

A fork of the [phpBackupS3](http://github.com/ianneub/php_backup_s3/) by [Ian](http://www.ianneubert.com/). This program will backup file paths and [MySQL](http://www.mysql.com) databases to [Amazon's S3](http://www.amazonaws.com/) cloud storage service.

## Features

* Easy to use
* Backups MySQL database and files
* Written in PHP (Yes, this is a feature)
* Removes old backups according to a [grandfather-father-son](http://en.wikipedia.org/wiki/Grandfather-Father-Son_Backup) based schedule

## Installation

1. Rename backup.php.example to backup.php.
2. Edit the configuration settings in the backup.php.
4. Upload all files to your server.
5. Setup a cron job to run the backups for you!

For example, create a file called /etc/cron.daily/backup and add this code to it:

    #!/bin/bash
    /usr/bin/php /path/to/script/backup.php
    exit 0

NOTE: Make sure to set the /etc/cron.daily/backup file to be executable. Like this:

    chmod +x /etc/cron.daily/backup

## About this script

This set of scripts ships with three files:

* backup.php--This is an example of how to call the backup functions
* functions.php--All the backup functions are in here
* vendor/
  * s3.php--This is the library to access Amazon S3

### Backup deletion schedule

A unique feature of this script is the way it will store (and eventually delete) old backups to conserve space, and yet maintain significant backup history. In this method you will have the following full backups:

* Everyday for the past two weeks (14 backups)
* Every saturday for the past 2 months (8 or 9 backups)
* First day of the month going back forever

This allows you to keep a very detailed history of your files during the most recent time, but progressively remove backups as time goes on to save space. This feature can be turned off if you want to store all your backups, all the time.

To disable this feature, comment out the following line from functions.php:

    deleteBackups($BACKUP_BUCKET);

### Requirements

* PHP 5 or higher
* [PHP curl](http://php.net/manual/en/intro.curl.php)
* [PHP mysql](http://php.net/mysql)
* GNU/Linux environment
