<?php

class ZipDirectory extends AP5L_Php_InflexibleObject {
    protected $_zip;

    /**
     * Add file event handler.
     *
     * @param array Information on the source file.
     * @return boolean True if the file should be added.
     */
    function onAddFile($info) {
        return true;
    }

    /**
     * Process the directory trees.
     *
     * @return unknown_type
     */
    function process($zipFile, $dir) {
        $this -> _zip = new ZipArchive;
        $this -> _zip -> open($zipFile, ZipArchive::OVERWRITE);
        // Get the global event dispatcher
        $disp = AP5L_Event_Dispatcher::getInstance();
        $disp -> setOption('queue', false);
        // Listen to add directory and end directory events
        $disp -> listen($this);
        // Create a directory listing, set the dispatcher
        $listing = new AP5L_FileSystem_Listing();
        $listing -> setDispatcher($disp);
        // Get the listing
        $listing -> execute(
            $dir,
            array(
                //'filter' => AP5L_FileSystem_Listing::TYPE_FILE,
                'directories' => 'first'
            )
        );
        $disp -> unlisten($this);
        $this -> _zip -> close();
    }

    /**
     * Receive a file event.
     *
     * This method uses events from the directory walk to process matching files.
     *
     * @param AP5L_Event_Notification The event object.
     */
    function update(AP5L_Event_Notification $subject) {
        switch ($subject -> getName()) {
            case AP5L_Filesystem_Listing::EVENT_ADD_FILE: {
                $info = $subject -> getInfo();
                if ($this -> onAddFile($info)) {
                    $this -> _zip -> addFile($info['fullname'], $info['relname']);
                }
            }
            break;

        }
    }

}
