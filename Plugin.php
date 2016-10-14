<?php
/*
Plugin Name: Attachments from FTP - Debug tool
Description: Provides debug functions for the plugin “Attachments from FTP”. (Sends emails to the administration address.)
Plugin URI: #
Text Domain: mhm-attachment-from-ftp-debug
Author: Mark Howells-Mead
Author URI: https://permanenttourist.ch/
Version: 0.1
*/

namespace MHM\WordPress\AttachmentFromFtpDebug;

class Plugin
{
    public $wpversion = '4.5';

    public function __construct()
    {
        $this->email = esc_attr(get_option('admin_email'));

        add_action('mhm-attachment-from-ftp/no_files', array($this, 'noFiles'), 10, 1);
        add_action('mhm-attachment-from-ftp/no_file_date', array($this, 'noFileDate'), 10, 2);
        add_action('mhm-attachment-from-ftp/no_valid_entries', array($this, 'noValidEntries'), 10, 1);
        add_action('mhm-attachment-from-ftp/finished', array($this, 'finished'), 10, 2);
        add_action('mhm-attachment-from-ftp/source-folder-undefined', array($this, 'sourceFolderUndefined'), 10, 1);
        add_action('mhm-attachment-from-ftp/post-author-undefined', array($this, 'postAuthorUndefined'), 10, 1);
        add_action('mhm-attachment-from-ftp/source-folder-unavailable', array($this, 'sourceFolderUnavailable'), 10, 1);
        add_action('mhm-attachment-from-ftp/filetype-not-allowed', array($this, 'filetypeNotAllowed'), 10, 3);
        add_action('mhm-attachment-from-ftp/target_folder_missing', array($this, 'targetFolderMissing'), 10, 1);
        add_action('mhm-attachment-from-ftp/file_moved', array($this, 'fileMoved'), 10, 2);
        add_action('mhm-attachment-from-ftp/file_not_moved', array($this, 'fileNotMoved'), 10, 2);
        add_action('mhm-attachment-from-ftp/title_description_overwritten', array($this, 'titleDescriptionOverwritten'), 10, 2);
        add_action('mhm-attachment-from-ftp/attachment_updated', array($this, 'attachmentUpdated'), 10, 1);
        add_action('mhm-attachment-from-ftp/attachment_created', array($this, 'attachmentCreated'), 10, 1);
        add_action('mhm-attachment-from-ftp/updated_attachment_metadata', array($this, 'updatedAttachmentMetadata'), 10, 2);
    }

    private function debug($message, $method = __METHOD__)
    {
        if (gettype($message) !== 'string') {
            $message = print_r($message, 1);
        }
        error_log($message);
    }

    public function activation()
    {
        $this->checkVersion();

        if (!wp_next_scheduled('mhm-attachment-from-ftp/check_folder')) {
            wp_schedule_event(time(), $this->frequency, 'mhm-attachment-from-ftp/check_folder');
        }
        $this->setThings();
    }

    public function deactivation()
    {
        wp_clear_scheduled_hook('mhm-attachment-from-ftp/check_folder');
    }

    public function checkVersion()
    {
        // Check that this plugin is compatible with the current version of WordPress
        if (!$this->compatibleVersion()) {
            if (is_plugin_active('mhm-attachment-from-ftp')) {
                deactivate_plugins('mhm-attachment-from-ftp');
                add_action('admin_notices', array($this, 'disabledNotice'));
                if (isset($_GET['activate'])) {
                    unset($_GET['activate']);
                }
            }
        }
    }

    public function disabledNotice()
    {
        $message = sprintf(
            __('The plugin “%1$s” requires WordPress %2$s or higher!', 'mhm-attachment-from-ftp'),
            _x('Attachments from FTP - Debug tool', 'The name of the plugin', 'mhm-attachment-from-ftp'),
            $this->wpversion
        );

        printf(
            '<div class="notice notice-error is-dismissible"><p>%1$s</p></div>',
            $message
        );
    }

    private function compatibleVersion()
    {
        if (version_compare($GLOBALS['wp_version'], $this->wpversion, '<')) {
            return false;
        }

        return true;
    }

    public function noFiles($folder)
    {
        wp_mail($this->email, __METHOD__, 'There are no files in the folder '.$folder);
    }

    public function noFileDate($file, $exif)
    {
        wp_mail($this->email, __METHOD__, 'The file '.$file.' has no creation date in its EXIF.'.chr(10).chr(10).print_r($exif, 1));
    }

    public function noValidEntries($folder, $files)
    {
        wp_mail($this->email, __METHOD__, 'There are no valid files in the folder '.$folder.'.'.chr(10).chr(10).print_r($files, 1));
    }

    public function finished($files, $processed)
    {
        wp_mail($this->email, __METHOD__, 'The process was completed.'.chr(10).chr(10).'Files:'.chr(10).print_r($files, 1).chr(10).chr(10).'Processed:'.chr(10).print_r($processed, 1));
    }

    public function sourceFolderUnavailable($folder)
    {
        wp_mail($this->email, __METHOD__, 'The source folder '.$folder.' is not available.');
    }

    public function sourceFolderUndefined($folder)
    {
        wp_mail($this->email, __METHOD__, 'The source folder is not defined.');
    }

    public function postAuthorUndefined()
    {
        wp_mail($this->email, __METHOD__, 'The post author is not defined.');
    }

    public function filetypeNotAllowed($file, $mime, $allowed)
    {
        wp_mail($this->email, __METHOD__, 'The file “'.$file.'” is of the MIME type “'.$mime.'” which is not supported. Allowed file types are:'.chr(10).chr(10).print_r($allowed, 1));
    }

    public function targetFolderMissing($folder)
    {
        wp_mail($this->email, __METHOD__, 'The target folder '.$folder.' is missing and could not be created.');
    }

    public function fileMoved($from, $to)
    {
        wp_mail($this->email, __METHOD__, 'The file '.$from.' was successfully moved to '.$to.'.');
    }

    public function fileNotMoved($from, $to)
    {
        wp_mail($this->email, __METHOD__, 'The file '.$from.' could not successfully be moved to '.$to.'.');
    }

    public function titleDescriptionOverwritten($id, $data)
    {
        wp_mail($this->email, __METHOD__, 'Attachment ID '.$id.' was rewritten with a new title and/or description. The new values are:'.chr(10).chr(10).print_r($data, 1));
    }

    public function attachmentCreated($id)
    {
        wp_mail($this->email, __METHOD__, 'Attachment created. The new ID is '.$id.'.');
    }

    public function attachmentUpdated($id)
    {
        wp_mail($this->email, __METHOD__, 'Attachment ID '.$id.' was updated.');
    }

    public function updatedAttachmentMetadata($id, $file)
    {
        wp_mail($this->email, __METHOD__, 'The metadata for Attachment ID '.$id.' (file '.$file.') was rewritten to the database.');
    }
}

new Plugin();
