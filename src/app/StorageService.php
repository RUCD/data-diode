<?php

namespace App;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use League\Flysystem\NotSupportedException;
use Symfony\Component\Process\Process;

class StorageService {
 
    /**
     * @var Storage
     */
    private $storage;

    /**
     * StorageService constructor
     *
     * @param FilesystemAdapter $storage
     */
    public function __construct( FilesystemAdapter $storage )
    {
        $this->storage = $storage;
    }

    /**
     * Get directory content
     *
     * @param String $path the directory path to get its content
     *
     * @return array containing the data needed about the directory
     */
    
    public function list( String $path ) {
        $cantBeSeen  = false;
        $dirInfo     = $this->pathInfo( $path );
        $dirList     = [];
        $filesList   = [];
        if (!$this->storage->exists($path)) {
            // send root if the path does not exist
            $path = '.';
        }
        try {
            $dirList     = $this->directories( $path );
            $filesList   = $this->files( $path );
        } catch(NotSupportedException $exc) {
            $cantBeSeen  = true;
        }
        $qNavigation = $this->quickNavigation( $path );
    
        return [
                'info'       => [
                'dirPath'    => $path,
                'dirName'    => $dirInfo[ 'basename' ] === '.' ? 'Root' : $dirInfo[ 'basename' ],
                'dirCount'   => count( $dirList ),
                'filesCount' => count( $filesList ),
            ],
    
            'directories'      => $dirList,
            'files'            => $filesList,
            'quick_navigation' => $qNavigation,
            'cannot_be_seen'   => $cantBeSeen,
        ];
    }

    /**
     * Get path info
     *
     * @param string $path
     *
     * @return array
     */
    
    private function pathInfo( $path ) {
        $pathInfo = pathinfo( $path );
    
        return [
            'path'      => $path,
            'basename'  => $pathInfo[ 'basename' ],
            'dirname'   => $pathInfo[ 'dirname' ],
            'filename'  => $pathInfo[ 'filename' ],
            'extension' => $pathInfo[ 'extension' ] ?? '',
        ];
    
    }
    
    /**
     * Get directories list
     *
     * @param string $directory
     *
     * @return array
     */
    
    private function directories( $directory ) {
        $diodeDirectories = $this->storage->directories( $directory );
    
        $directoriesList = [];
    
        foreach ( $diodeDirectories    as  $diodeDirectory ) {
            $dirInfo           = pathinfo( $diodeDirectory );
            $directoriesList[] = [
                'name' => $dirInfo[ 'basename' ],
                'path' => $diodeDirectory,
            ];
        }
        return $directoriesList;
    }
    
    /**
     * Get files list for directory
     *
     * @param string $directory
     *
     * @return array
     */
    
    private function files( $directory ) {
        $diodeFiles = $this->storage->files( $directory );
    
        $filesList = [];
        foreach ( $diodeFiles  as  $diodeFile ) {
            $fileInfo = pathinfo( $diodeFile );
    
            $filesList[] = [
                'name' => $fileInfo[ 'basename' ],
                'path' => $diodeFile,
            ];
        }
    
        return $filesList;
    }
    
    /**
     * Get list of path parts for navigation
     *
     * @param string $directory
     *
     * @return array
     */
    
    private function quickNavigation( $directory ) {
        $directoriesList = [];
    
        if ( $directory === '.' ) {
            return $directoriesList;
        }
    
        $directoriesList = [
            [
                'name' => 'Root',
                'path' => '.',
            ],
        ];
    
        $pathParts = explode( '/', $directory );
    
        if ( count( $pathParts ) === 1 ) {
            return $directoriesList;
        }
    
        $path = '/';
    
        foreach ( $pathParts as $diodeDirectory ) {
    
            if ( $diodeDirectory === '' ) {
                continue;
            }
    
            $path .= $diodeDirectory;
    
            $directoriesList[] = [
                'name' => $diodeDirectory,
                'path' => $path,
            ];
    
            $path .= '/';
        }
    
        return array_slice(
            $directoriesList, 0, -1
        );
    }

    /**
     * Download file
     *
     * @param Request $request
     *
     * @return StreamedResponse
     * @throws StorageException
     */
    public function download( Request $request )
    {
        $path = $request->path;
        //$this->checkExists( $path );
        return $this->storage->download( $path );
    }

    public function downloadZippedFolder(String $path) 
    {
        return $this->storage->download( $path );
    }


    /**
     * Upload file(s) in the 'diode_local' filesystem.
     *
     * @param Request the request.
     *
     * @return int the number of files that have been uploaded.
     * @throws StorageException
     */
    public function upload( Request $request )
    {        
        $i = 0;
        $uploaderName = 'ftp';
        while ($request->hasFile('input_file_' . $i)) {
            $file = $request->file('input_file_' . $i);
            $fullPath = $request['input_file_full_path_' . $i];
            $uploaderName = $request['uploader'];
            if ($uploaderName == null) {
                $uploaderName = 'ftp';
            }
            $this->uploadFile($file, $fullPath, $uploaderName);
            $i++;
        }
        if ($i > 0) {
            $cmd = "sudo python /var/www/data-diode/uploadersScripts/db_uploaders_clie.py update " . $uploaderName . " 1";
            $process = new Process($cmd);
            $process->mustRun();
        }
        return response()->json(['i' => $i, 'uploaderName' => $uploaderName], 200);
        return $i;
    }
    
    /**
     * Upload file.
     *
     * @param UploadedFile the file.
     * @param string the path.
     * @param string the name of the uploader's channel.
     *
     * @return string
     */
    private function uploadFile( $file, $path, $uploaderName )
    {        
        $this->storage->putFileAs( $uploaderName . '/', $file, $path );
    }

}