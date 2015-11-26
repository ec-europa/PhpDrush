<?php
namespace PhpDrush {

    /**
     * Class PhpDrush
     * @package PhpDrush
     */
    interface PhpDrushTransportInterface
    {
        public function run($location,$command);
    }
}