<?php
// Handle file uploads

namespace Neutron\Model;

class Upload {

  public function uploadImage($files, $target_dir) {
    $res = array();

    // Move files into the dir
    foreach($files as $file) {
      $target_file = $target_dir . basename($file['name']);
      if(move_uploaded_file($file['tmp_name'], $target_file)) {
        array_push($res, $file['name']);
      } else {
        throw new \Exception("Could not move file {$file['name']}");
      }
    }
    return json_encode($res);
  }

  /**
   * Create a new directory
   */
  public function newDir($directory, $parentDir) {
    // Directory name cleaning
    $directory = preg_replace('/\//', '', $directory); //remove slash
    $directory = trim($directory); // remove leading/trailing whitespace

    // Check if non valid chars: should only be alpha-num and underscore
    if (preg_match("/[^\w]/", $directory)) {
      throw new \Exception("'{$directory}' is not a valid name");
    }

    $directory = $parentDir . $directory; // create full path name

    // Check if dir already exists
    if (file_exists($directory)) {
      throw new \Exception("Directory {$directory} already exists");
    }

    // Create dir
    mkdir($directory, 0755);

    return $directory;
  }

  /**
   * Get an array of directories in passed directory
   */
  public function dirsInDirectory($directory) {
    $directories = glob($directory . '/*', GLOB_ONLYDIR);
    return $directories;
  }

  /**
   * Get an array of files in a directory
   */
  public function filesInDirectory($directory) {
    $dirs = scandir($directory);
    return $dirs;
  }

  /**
   * Validate files
   */
  public function validate($files, $target_dir) {
    foreach ($files as $file) {
      $target_file = $target_dir . basename($file['name']);

      // Empty file
      if ($file['error'] == 4) {
        throw new \Exception("Missing file with name: {$file['name']}");
      }

      // Make sure no empty file
      if (getimagesize($file['tmp_name']) == false){
        throw new \Exception("Empty file");
      }
      // No empty file name
      if ($file['name'] == ''){
        throw new \Exception("File with no name");
      }

      // Allow only certain file formats
      $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
      if($imageFileType != "jpg" && $imageFileType != "png"
                    && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        $msg = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        throw new \Exception($msg);
      }

      // Check if exists
      if (file_exists($target_file)) {
        throw new \Exception("File {$target_file} already exists");
      }
    }
  }
}

?>
