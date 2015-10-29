<?php
namespace PhpDrush {

    /**
     * Class PhpDrush
     * @package PhpDrush
     */
    class PhpDrush {

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
         * @param $drushLocation
         * @param $siteLocation
         * @throws PhpDrushException
         */
        public function __construct($drushLocation,$siteLocation) {
            if(!is_file($drushLocation))
                throw new PhpDrushException('Drush tool not found');
            if(!is_file($siteLocation.DIRECTORY_SEPARATOR.'settings.php'))
                throw new PhpDrushException($siteLocation.' doesn\'t seem to be a valid drupal installation');

            $this->drushLocation = $drushLocation;
            $this->siteLocation = $siteLocation;
        }


        /**
         * Run a drush command and throws an exception if failing
         *
         * @param $arguments Argument to pass to drush as a string
         * @return array An array of all the lines returned by drush (including PHP warnings/errors)
         * @throws PhpDrushException
         */
        private function runDrush($arguments) {
            chdir($this->siteLocation);
            $cmd = $this->drushLocation;
            if($this->noColor)
                $cmd .= ' --nocolor ';
            $cmd .= ' -y '.$arguments;
            $cmd .= ' 2>&1';
            $output = array();
            $rc = 0;
            exec($cmd,$output,$rc);
            if ($rc > 0)
                throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL,$output),$rc);

            // in case drush outputs [error] but rc = 0 anyway :

            self::validateDrushOutput($output);

            return $output;
        }

        /**
         * Run a database upgrade
         * @return array
         * @throws PhpDrushException
         */
        public function updateDatabase() {
            return $this->runDrush('updb');
        }


        /**
         * Run a registry rebuild
         * @return array
         * @throws PhpDrushException
         */
        public function registryRebuild() {
            return $this->runDrush('rr');
        }

        /**
         * @param array $featureList List of features to revert
         * @param bool|false $force If true, pass the --force argument
         * @return array
         * @throws PhpDrushException
         */
        public function featuresRevert($featureList=array(),$force=false) {
            if ( count($featureList) == 0)  {
                $arg = 'features-revert-all';
            } else {
                $arg = 'features-revert';
                foreach ($featureList as $feature) {
                    $arg .= ' '.escapeshellarg($feature);
                }
            }
            if ( $force ) {
                $arg .= ' --force ';
            }
            return $this->runDrush($arg);
        }

        /**
         * Set the maintenance mode for the site
         * @param $bool True to enable, False to disable
         * @return array
         * @throws PhpDrushException
         */
        public function setMaintenanceMode($bool) {
            $arg = 'vset maintenance_mode ';
            $arg .= $bool ? '1' : '0';
            return $this->runDrush($arg);
        }

        /**
         * Run a clear cache
         * @param string $type Type of cache to clean ( default : all )
         * @return array
         * @throws PhpDrushException
         */
        public function clearCache($type='all') {
            $arg = 'cc ';
            $arg .= escapeshellarg($type);
            return $this->runDrush($arg);
        }

        /**
         * Enable one or multiple modules
         * @param mixed $modules Either a string (single module) or an array
         * @return array
         * @throws PhpDrushException
         */
        public function enableModules($modules) {
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
         * Eval PHP code in current drush session
         * @param $toEval
         * @return array
         * @throws PhpDrushException
         */
        public function ev($toEval) {
            return $this->runDrush(' ev '.escapeshellarg($toEval));
        }

        /**
         * Check drush output to handle drush [error]
         * @param array $output
         * @return bool
         * @throws PhpDrushException
         */
        static function validateDrushOutput(array $output) {
            foreach($output as $line) {
                if (
                    preg_match('/^\[error\]/i',$line) ||
                    preg_match('/^\[fatal\]/i',$line)
                ) throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL,$output),10);
            }
            return true;
        }

        public function setColoring($color) {
            $this->noColor = !$color;
        }
    }
}