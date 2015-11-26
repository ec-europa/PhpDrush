<?php
namespace PhpDrush\Transport {

    use PhpDrush\PhpDrushException;
    use PhpDrush\PhpDrushTransportInterface;

    /**
     * Class PhpDrush
     * @package PhpDrush
     */
    class Local implements PhpDrushTransportInterface
    {
        public function run($location,$cmd) {
            exec($cmd,$output,$rc);
            if ($rc > 0)
                throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL,$output),$rc);
            // in case drush outputs [error] but rc = 0 anyway :
            return $output;
        }
    }
}