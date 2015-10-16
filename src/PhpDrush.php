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
         * @param $drushLocation
         * @param $siteLocation
         * @throws \Exception
         */
        public function __construct($drushLocation,$siteLocation) {
            if(!is_file($drushLocation))
                throw new \Exception('Drush tool not found');
            if(!is_file($siteLocation.DIRECTORY_SEPARATOR.'settings.php'))
                throw new \Exception($siteLocation.' doesn\'t seem to be a valid drupal installation');

            $this->drushLocation = $drushLocation;
            $this->siteLocation = $siteLocation;
        }


        /**
         * Run a drush command and throws an exception if failing
         *
         * @param $arguments Argument to pass to drush as a string
         * @return array An array of all the lines returned by drush (including PHP warnings/errors)
         * @throws \Exception
         */
        private function runDrush($arguments) {
            cwd($this->siteLocation);
            $cmd = $this->drushLocation;
            $cmd .= ' -y '.$arguments;
            $cmd .= ' 2>&1';
            $output = array();
            $rc = 0;
            exec($cmd,$output,$rc);
            if ($rc > 0)
                throw new \Exception('Drush execution failed : '.PHP_EOL.implode(PHP_EOL,$output),$rc);

            //TODO : Here we need to loop over $output to catch errors when drush doesn't return an approritate rc
            // self::validateOutput() ?

            return $output;
        }

        /**
         * Run a database upgrade
         * @return array
         * @throws \Exception
         */
        public function updateDatabase() {
            return $this->runDrush('updb');
        }


        /**
         * Run a registry rebuild
         * @return array
         * @throws \Exception
         */
        public function registryRebuild() {
            return $this->runDrush('rr');
        }

        /**
         * @param array $featureList List of features to revert
         * @param bool|false $force If true, pass the --force argument
         * @return array
         * @throws \Exception
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
    }
}