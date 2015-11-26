<?php
namespace PhpDrush\Transport {

    use PhpDrush\PhpDrushException;
    use PhpDrush\PhpDrushTransportInterface;

    /**
     * Class Ssh
     * @package PhpDrush
     */
    class Ssh implements PhpDrushTransportInterface
    {
        private $user;
        private $host;
        private $port;

        public function __construct($user,$host,$port=22)
        {
            $this->user = $user;
            $this->port = $port;
            $this->host = $host;
        }

        public function run($location,$cmd) {
            // encapsulate for ssh remote run :
            $cmd = 'ssh -p '.escapeshellarg($this->port).
                ' '.escapeshellarg($this->user.'@'.$this->host).' '.
                escapeshellarg('cd '.escapeshellarg($location).' ; '.$cmd);
            exec($cmd,$output,$rc);

            if ($rc > 0)
                throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL,$output),$rc);
            // in case drush outputs [error] but rc = 0 anyway :
            return $output;
        }
    }
}