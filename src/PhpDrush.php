<?php

namespace PhpDrush {
    class PhpDrush {

        private $drushLocation;
        private $siteLocation;

        public function __construct($drushLocation,$siteLocation) {
            if(!is_file($drushLocation))
                throw new \Exception('Drush tool not found');
            if(!is_file($siteLocation.DIRECTORY_SEPARATOR.'settings.php'))
                throw new \Exception($siteLocation.' doesn\'t seem to be a valid drupal installation');

            $this->drushLocation = $drushLocation;
            $this->siteLocation = $siteLocation;
        }
    }
}