<?php

namespace depage\media;

/**
 * Http Upload Class
 *
 * Class to file manage uploads
 *
 */
class upload
{
    /**
     * Process
     *
     * Process the uploaded $_FILES array
     *
     * @param string $destination - save location.
     * @param array $name_func - reflected function call to get filename.
     * @param array $types - allowed extensions.
     */
    public static function process($destination, array $name_func, array $types = array()) {
        if(isset($_FILES)) {
            $files = array();
            foreach($_FILES as $i => $file) {
                if ($file['size'] > 0 && !empty($file['name']) && is_uploaded_file($file['tmp_name'])) {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $name = call_user_func_array($name_func, array($ext));
                    $result = Upload::saveFile(
                        $file['tmp_name'],
                        $name,
                        $destination,
                        $types
                    );
                    $files[$name] = $file;
                }
            }
            return $files;
        }
        return false;
    }
    
    /**
     * Save File
     *
     * Takes a $tmp_name, renames it $name, and moves it to $folder.
     *
     * @access public static
     *
     * @param string $tmp_name - from $_FILES['tmp_name'] location to find file.
     * @param string $name - the new file name.
     * @param string $folder - the destination folder to move the file to.
     * @param array $types - types that can be accepted, empty for all files.
     *
     * @return array containing file saved locations
     */
    public static function saveFile ( $tmp_name, $name, $folder, array $types = array() ) {
        if (self::isAllowedType($name, $types)) {
            self::isWriteable( $folder );
            return move_uploaded_file( $tmp_name, $folder . $name );
        }
        return false;
    }
    
    /**
     * isAllowedType
     *
     * Test type based on file extension for allowed types provided.
     *
     * @access public static
     *
     * @param string $name_inc_extension - filename with extension
     * @param array $types - types that can be accepted, empty for all files.
     *
     * @return array containing file saved locations
     */
    public static function isAllowedType($name_inc_extension, array $types = array()){
        // TODO mime checking!
        $ext = self::getExtension($name);
        if ( !empty($types) && !in_array($ext, $types) ) {
            return false;
        }
    }
    
    /**
     * isWriteable
     *
     * Wraps folder checks, excepts if not writeable.
     *
     * @access private static
     *
     * @param string $folder
     *
     * @return bool true - if passes checks
     *
     */
    public static function isWriteable( $folder) {
        if ( !file_exists($folder) ) {
            throw new \Exception(
               'The folder "' . $folder . '" does not exist.');
        } elseif ( !is_writable($folder) ) {
            throw new \Exception(
               'The folder "' . $folder . '" was not writable.');
        }
        return true;
    }
    
    /**
     * Unique Rename
     *
     * Appends a unique id hash to the name, so as to prevent accidental
     * file overwrites.
     *
     * @access public static
     *
     * @param string $base - string to base the md5 hash on
     * @param string $postfix - append a postfix string to the hash
     *
     * @return string unique name
     *
     */
    public static function uniqueRename( $base='', $postfix='' ) {
        $base = empty($base) ? uniqid(mt_rand(), 1) : $base;
        $rand = substr( md5($base), 0, 20);
        if ($postfix) {
            $rand .= $postfix;
        }
        return $rand;
    }
    
    /**
     * Get Extension
     *
     * Gets the extension from a full filename
     *
     * @access public static
     *
     * @param string $filename_inc_ext
     *
     * @return string extension
     */
    public static function getExtension ( $filename_inc_ext ) {
        $ext = strtolower(pathinfo($filename_inc_ext, \PATHINFO_EXTENSION));
        $ext = ($ext === 'jpeg') ? 'jpg' : $ext;

        return $ext;
    }
}
