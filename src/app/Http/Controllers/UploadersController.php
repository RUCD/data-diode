<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Uploader;

class UploadersController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(["auth", "default-password"]);
    }

    public function index()
    {
        $statuses = array();        
        $uploaders = Uploader::all();
        foreach ($uploaders as $uploader) {
            $cmd = 'supervisorctl pid blindftp-' . $uploader->name;
            $process = new Process($cmd);
            try {
                $process->mustRun();
                $output = $process->getOutput();
                if ($output == '0') {
                    array_push($statuses, 'stopped');
                } else {
                    array_push($statuses, 'running');
                }
            } catch (ProcessFailedException $exception) {
                array_push($statuses, 'no process');
            }
        }
        return view('uploaders', ['uploaders' => $uploaders], $statuses);
    }

    public function update()
    {
        $statuses = array();        
        $uploaders = Uploader::all();
        foreach ($uploaders as $uploader) {
            $cmd = 'supervisorctl pid blindftp-' . $uploader->name;
            $process = new Process($cmd);
            try {
                $process->mustRun();
                $output = $process->getOutput();
                if ($output == '0') {
                    array_push($statuses, 'stopped');
                } else {
                    array_push($statuses, 'running');
                }
            } catch (ProcessFailedException $exception) {
                array_push($statuses, 'no process');
            }
        }
        return response()->json(['uploaders' => Uploader::all(), 'statuses' => $statuses], 200);
    }
}
