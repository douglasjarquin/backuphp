<?php
// Load the standalone S3 class
require_once('vendor/s3.php');

// Setup S3 class
$s3 = new S3(awsAccessKey, awsSecretKey);

// Delete old backups on the Grandfather-Father-Son schedule
deleteBackups($BACKUP_BUCKET);

////
// Backup functions

// Backup and compress files for storage
function backupFiles($targets, $prefix = '') {
  global $BACKUP_BUCKET, $s3;

  foreach ($targets as $target) {
    // compress local files
    # FIXME Create bzip files in the tmp directory
    $cleanTarget = urlencode($target);
    `tar cjf tmp/$prefix-$cleanTarget.tar.bz2 $target`;
 
    // upload to s3
    $s3->putObjectFile("tmp/$prefix-$cleanTarget.tar.bz2", $BACKUP_BUCKET, s3Path($prefix, $target.".tar.bz2"));
    
    // remove temp file
    `rm -rf tmp/$prefix-$cleanTarget.tar.bz2`;
  }
}

// Backup all Mysql DBs using mysqldump
function backupDBs($hostname, $username, $password, $prefix = '') {
  global $MYSQL_OPTIONS, $DATE, $s3;
  global $BACKUP_BUCKET;
  
  // Connecting, selecting database
  $link = mysql_connect($hostname, $username, $password) or die('Could not connect: ' . mysql_error());
  
  $query = 'SHOW DATABASES';
  $result = mysql_query($query) or die('Query failed: ' . mysql_error());
  
  $databases = array();
  
  while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      foreach ($row as $database) {
      $databases[] = $database;
      }
  }

  // Free resultset
  mysql_free_result($result);

  // Closing connection
  mysql_close($link);

  // Run backups on each database in the array
  foreach ($databases as $database) {
    `/usr/bin/mysqldump $MYSQL_OPTIONS --no-data --host=$hostname --user=$username --password='$password' $database | bzip2  > tmp/$database-structure.sql.bz2`;
    `/usr/bin/mysqldump $MYSQL_OPTIONS --host=$hostname --user=$username --password='$password' $database | bzip2 > tmp/$database-data.sql.bz2`;
    $s3->putObjectFile("tmp/$database-structure.sql.bz2", $BACKUP_BUCKET, s3Path($prefix,"/".$database."-structure.sql.bz2"));
    $s3->putObjectFile("tmp/$database-data.sql.bz2", $BACKUP_BUCKET, s3Path($prefix,"/".$database."-data.sql.bz2"));

    `rm -rf tmp/$database-structure.sql.bz2 tmp/$database-data.sql.bz2`;
  }

}

function deleteBackups($bucket) {
  global $s3;

  // Delete the backup from 2 months ago 
  $set_date = strtotime('-2 months');

  // Only if it wasn't the first of the month
  if ((int)date('j',$set_date) === 1) return true;

  // Set s3 "dir" to delete
  $prefix = s3Path('', '', $set_date);

  // Find files to delete
  $keys = $s3->getBucket($bucket, $prefix);

  // Delete each key found
  foreach ($keys as $key => $meta) {
    $s3->deleteObject($bucket, $key);
  }

  // Delete the backup from 2 weeks ago
  $set_date = strtotime('-2 weeks');

  // Only if it wasn't a saturday or the 1st
  if ((int)date('j', $set_date) === 1 || (string)date('l',$set_date) === "Saturday") return true;

  // Set s3 "dir" to delete
  $prefix = s3Path('', '', $set_date);

  // Find files to delete
  $keys = $s3->getBucket($bucket, $prefix);

  // Delete each key found
  foreach ($keys as $key => $meta) {
    $s3->deleteObject($bucket, $key);
  }

}

function s3Path($prefix, $name, $timestamp = null) {
  if (is_null($timestamp)) $timestamp = time();

  $date = date("Y/m/d/", $timestamp);

  return $date.$prefix.$name;
}

?>
