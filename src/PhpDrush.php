<?php

namespace PhpDrush {

    use Symfony\Component\Process\Process;
    use Jack\Symfony\ProcessManager;
    /**
     * Class PhpDrush
     * @package PhpDrush
     */
    class PhpDrush
    {

        /**
         * @var Drush location
         */
        private $drushLocation;

        /**
         * @var Site location
         */
        private $siteLocation;

        /**
         * @var Enable or disable coloring in drush output
         * By default disabled
         */
        private $noColor = true;

        /**
         * @var string Alias to run commands on ( for targeting multiple sites )
         */
        private $alias = null;


        private $parallel = false;
        private $process  = array();
        private $uri = null;

        /**
         * PhpDrush constructor.
         * @param $drushLocation
         * @param $siteLocation
         * @param null $alias
         * @param $this
         */
        public function __construct($drushLocation,$siteLocation,$alias=null,$uri=null)
        {
            if(!is_file($drushLocation)) {
                throw new PhpDrushException('Drush tool not found'); 
            }
            if(is_null($alias) && is_null($uri) && !is_file($siteLocation.DIRECTORY_SEPARATOR.'settings.php')) {
                // Not an alias and settings.php not found :
                throw new PhpDrushException($siteLocation.' doesn\'t seem to be a valid drupal installation'); 
            } elseif(!is_null($alias) && !is_file($siteLocation.DIRECTORY_SEPARATOR.'index.php')) {
                // Alias but no index.php found:
                throw new PhpDrushException($siteLocation.' doesn\'t seem to be a valid drupal master');
            }
            $this->drushLocation = $drushLocation;
            $this->siteLocation = $siteLocation;
            $this->alias = $alias;
            $this->uri = $uri;
        }


        /**
         * Run a drush command and throws an exception if failing
         *
         * @param  $arguments Argument to pass to drush as a string
         * @return array An array of all the lines returned by drush (including PHP warnings/errors)
         * @throws PhpDrushException
         */
        private function runDrush($arguments) 
        {
            chdir($this->siteLocation);
            $cmd = $this->drushLocation;
            if(!is_null($this->alias)) {
                $cmd .= ' @'.$this->alias; 
            }
            if(!is_null($this->uri)) {
                $cmd .= ' --uri='.$this->uri;
            }
            if($this->noColor) {
                $cmd .= ' --nocolor '; 
            }
            $cmd .= ' -y '.$arguments;
            $cmd .= ' 2>&1';

            if ($this->parallel){
                $process = new Process($cmd);
                $pObjectId = spl_object_hash ($process);
                $this->process[$pObjectId] = &$process;
                return $pObjectId;
            }else{
                $output = array();
                $rc = 0;
                exec($cmd, $output, $rc);
                if ($rc > 0) {
                    throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL, $output), $rc);
                }

                // in case drush outputs [error] but rc = 0 anyway :

                self::validateDrushOutput($output);

                return $output;
            }
        }

        /**
         * @param array $output
         * @param array $rc
         * @param int $maxProcess
         * @param int $pollingInterval
         */
        public function runDrushParall(&$output = array(), &$rc = array(), $maxProcess = 2, $pollingInterval  = 1000){
            $result = array();
            if (!empty ($this->process)){
                print 'totot'.PHP_EOL;
                $proc_mgr = new ProcessManager();
                $proc_mgr->runParallel($this->process, $maxProcess, $pollingInterval);
                foreach ($this->process as $id => $process) {
                    if (!empty($process->getErrorOutput())){ $result[$id]['error'] = $process->getExitCode();}
                    $rc[$id] = $process->getExitCode();
                    $output[$id] = $process->getOutput();
                }
            }
        }



        /**
         * @return bool
         * @throws PhpDrushException
         */
        public function getUpDbStatus() 
        {
            $output = $this->runDrush('updatedb-status');
            /**
             * Very elegant
             */
            foreach($output as $line) {
                if(preg_match('/no database updates required/i', $line)) {
                    return true; 
                }
            }
            // gfy
            return false;
        }

        /**
         * Run a database upgrade
         *
         * @param  bool|true $doubleCheck Double check that updb did its job since rc code are for idiots (or sysadmins)
         * @return array
         * @throws PhpDrushException
         */
        public function updateDatabase($doubleCheck=true) 
        {
            if($doubleCheck) {
                $outputUpDb = $this->runDrush('updb');
                if(!$this->getUpDbStatus()) {
                    throw new PhpDrushException(
                        'Updb failed, updb-status double check didn\'t pass'.
                        PHP_EOL.implode(PHP_EOL, $outputUpDb)
                    ); 
                }
                return $outputUpDb;
            }
            return $this->runDrush('updb');
        }


        /**
         * Runs a registry rebuild
         *
         * @param  bool|false $noCacheClear Avoid clearing the cache after rr
         * @param  bool|false $fireBazooka  Fire a professional bazooka ... Yes ... A bazooka mtf!
         * @return array
         * @throws PhpDrushException
         */
        public function registryRebuild($noCacheClear=false,$fireBazooka=false) 
        {
            $arg = '';
            if($noCacheClear) {
                $arg .= ' --no-clear-cache '; 
            }
            if($fireBazooka) {
                $arg .= ' --fire-bazooka '; 
            }

            return $this->runDrush($arg.'rr');
        }

        /**
         * @param array      $featureList List of features to revert
         * @param bool|false $force       If true, pass the --force argument
         * @return array
         * @throws PhpDrushException
         */
        public function featuresRevert($featureList=array(),$force=false) 
        {
            if (count($featureList) == 0) {
                $arg = 'features-revert-all';
            } else {
                $arg = 'features-revert';
                foreach ($featureList as $feature) {
                    $arg .= ' '.escapeshellarg($feature);
                }
            }
            if ($force ) {
                $arg .= ' --force ';
            }

            return $this->runDrush($arg);
        }

        /**
         * Set the maintenance mode for the site
         *
         * @param  $bool True to enable, False to disable
         * @return array
         * @throws PhpDrushException
         */
        public function setMaintenanceMode($bool) 
        {
            $value = $bool ? '1' : '0';
            return $this->setVariable('maintenance_mode', $value);
        }

        /**
         * Set a variable name
         *
         * @param  $key
         * @param  $value
         * @return array
         * @throws PhpDrushException
         */
        public function setVariable($key,$value) 
        {
            return $this->runDrush(
                sprintf('vset %s %s', escapeshellarg($key), escapeshellarg($value))
            );
        }

        /**
         * Run a clear cache
         *
         * @param  string $type Type of cache to clean ( default : all )
         * @return array
         * @throws PhpDrushException
         */
        public function clearCache($type='all') 
        {
            $arg = 'cc ';
            $arg .= escapeshellarg($type);
            return $this->runDrush($arg);
        }

        /**
         * Enable one or multiple modules
         *
         * @param  mixed $modules Either a string (single module) or an array
         * @return array
         * @throws PhpDrushException
         */
        public function enableModules($modules) 
        {
            $arg = 'pm-enable ';
            if(is_array($modules)) {
                foreach($modules as $module) {
                    $arg .= escapeshellarg($module).' ';
                }
            } else {
                $arg = escapeshellarg($modules);
            }
            return $this->runDrush($arg);
        }

        /**
         * Disable one or multiple modules
         *
         * @param  mixed $modules Either a string (single module) or an array
         * @return array
         * @throws PhpDrushException
         */
        public function disableModules($modules) 
        {
            $arg = 'pm-disable ';
            if(is_array($modules)) {
                foreach($modules as $module) {
                    $arg .= escapeshellarg($module).' ';
                }
            } else {
                $arg = escapeshellarg($modules);
            }
            return $this->runDrush($arg);
        }

        /**
         * Uninstall one or multiple modules
         *
         * @param  mixed $modules Either a string (single module) or an array
         * @return array
         * @throws PhpDrushException
         */
        public function uninstallModules($modules) 
        {
            $arg = 'pm-uninstall ';
            if(is_array($modules)) {
                foreach($modules as $module) {
                    $arg .= escapeshellarg($module).' ';
                }
            } else {
                $arg = escapeshellarg($modules);
            }
            return $this->runDrush($arg);
        }
        
        /**
         * Eval PHP code in current drush session
         *
         * @param  $toEval
         * @return array
         * @throws PhpDrushException
         */
        public function ev($toEval) 
        {
            return $this->runDrush(' ev '.escapeshellarg($toEval));
        }

        /**
         * Get logged in URL
         *
         * @param  null $user User to log into
         * @param  null $path Path to log into
         * @return string URL
         * @throws PhpDrushException
         */
        public function userLogin($user=null,$path=null) 
        {
            $args = ' uli ';

            if(!is_null($user)) {
                $args .= escapeshellarg($user).' '; 
            }
            if(!is_null($path)) {
                $args .= escapeshellarg($path).' '; 
            }

            list($link) = $this->runDrush($args);

            return $link;
        }

        /**
         * Update password for user by auth name
         * @param $authname
         * @param $password
         * @return array
         * @throws PhpDrushException
         */
        public function updatePassword($authname,$password) 
        {
            $args = ' upwd ';
            $args .= escapeshellarg($authname);
            $args .= ' --password='.escapeshellarg($password);
            return $this->runDrush($args);
        }

        /**
         * Check drush output to handle drush [error]
         *
         * @param  array $output
         * @return bool
         * @throws PhpDrushException
         */
        static function validateDrushOutput(array $output) 
        {
            foreach($output as $line) {
                if (preg_match('/^\[error\]/i', $line) 
                    || preg_match('/^\[fatal\]/i', $line)
                ) { throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL, $output), 10); 
                }
            }
            return true;
        }

        /**
         * Enable/disable coloring drush output
         * @param $color
         */
        public function setColoring($color) 
        {
            $this->noColor = !$color;
        }

        /**
         * Enable/disable coloring drush output
         * @param $color
         */
        public function setParallelMode($parallel)
        {
            $this->parallel = $parallel;
        }

        public function setUri($uri)
        {
            $this->uri = $uri;
        }

    }
}
