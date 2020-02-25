<?php

namespace App\Http\Controllers;

use Symfony\Component\Process\Process;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Jobs\BlindftpServerJob;

/**
 * Controller used to restart the BLindFTP program and read its output.
 */
class BlindftpServerController extends Controller
{
    /**
     * The command to kill the BlindFTP program (whithout the pids).
     * 
     * @var string
     */
    protected $killCommand;

    /**
     * The command to read the output of the BlindFTP program.
     * 
     * @var string
     */
    protected $catCommand;

    /**
     * The command to get the pids of the the BlindFTP program running 
     * processes.
     * 
     * @var string
     */
    protected $pidCommand;
    
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(["auth", "default-password"]);
        $this->pidCommand = "PID=`ps auxw | grep bftp.py | grep -v grep | awk '{ print $2 }'` && echo \$PID";
        if (!env('DIODE_IN', false)) {
            // DIODE OUT
            $this->killCommand = 'sudo kill -15 ';
            $this->catCommand = 'if [ -f /var/www/data-diode/src/storage/app/bftp-diodeout.log ]; ' . 
                'then cat /var/www/data-diode/src/storage/app/bftp-diodeout.log; ' . 
                'else echo "There is currently no log info."; ' . 
                'fi;';
        } else {
            // DIODE IN
            $this->killCommand = 'sudo kill -9 ';
            $this->catCommand = 'if [ -f /var/www/data-diode/src/storage/app/bftp-diodein.log ]; ' . 
            'then cat /var/www/data-diode/src/storage/app/bftp-diodein.log; ' . 
            'else echo "There is currently no log info."; ' . 
            'fi;';
        }
    }

    /**
     * Get the pids corresponding the BlindFTP running processes.
     * 
     * @return string the pids.
     */
    private function getPids() {
        $pidsProcess = new Process($this->pidCommand);
        $pidsProcess->mustRun();
        return $pidsProcess->getOutput();
    }

    /**
     * Get the view showing the state (ON/OFF) of the server or the client.
     * 
     * @return mixed the view.
     */
    public function index()
    {        
        $catProcess = new Process($this->catCommand);
        $catProcess->mustRun();
        $logInfo = $catProcess->getOutput();

        return view('ftpview', [
            'logInfo' => $logInfo,
        ]);
    }    

    /**
     * Restart the server or the client (kills its processes launch it. Also notifies 
     * the vue of all  the changes that have been made.
     * 
     * @param Request the request.
     * 
     * @return mixed The json response containing data about the state of the server 
     * or the client.
     */
    public function restart(Request $request)
    {
        $pids = self::getPids();

        $killProcess = new Process($this->killCommand . $pids);
        $killProcess->mustRun();
        
        BlindftpServerJob::dispatch()->onConnection('database')->onQueue('async');

        while (empty($pids)) {
            $pids = self::getPids();
            sleep(1);
            // TODO: show error after a certain number of loops
        }

        return response()->json([]);
    }

}