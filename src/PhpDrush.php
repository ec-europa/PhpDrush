<?php

namespace PhpDrush {

  use RedisClient\RedisClient;

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

    /**
     * @param $drushLocation
     * @param $siteLocation
     * @throws PhpDrushException
     */
    public function __construct($drushLocation, $siteLocation, $alias = null)
    {
      if (!is_file($drushLocation)) {
          throw new PhpDrushException('Drush tool not found');
      }
      if (is_null($alias) && !is_file($siteLocation.DIRECTORY_SEPARATOR.'settings.php')) {
          // Not an alias and settings.php not found :
          throw new PhpDrushException($siteLocation.' doesn\'t seem to be a valid drupal installation');
      } elseif (!is_null($alias) && !is_file($siteLocation.DIRECTORY_SEPARATOR.'index.php')) {
          // Alias but no index.php found:
          throw new PhpDrushException($siteLocation.' doesn\'t seem to be a valid drupal master');
      }
        $this->drushLocation = $drushLocation;
        $this->siteLocation = $siteLocation;
        $this->alias = $alias;
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
      if (!is_null($this->alias)) {
          $cmd .= ' @'.$this->alias;
      }
      if ($this->noColor) {
          $cmd .= ' --nocolor ';
      }
        $cmd .= ' -y '.$arguments;
        $cmd .= ' 2>&1';
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
      foreach ($output as $line) {
        if (preg_match('/no database updates required/i', $line)) {
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
    public function updateDatabase($doubleCheck = true)
    {
      if ($doubleCheck) {
          $outputUpDb = $this->runDrush('updb');
        if (!$this->getUpDbStatus()) {
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
    public function registryRebuild($noCacheClear = false, $fireBazooka = false)
    {
        $arg = '';
      if ($noCacheClear) {
          $arg .= ' --no-clear-cache ';
      }
      if ($fireBazooka) {
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
    public function featuresRevert($featureList = array(), $force = false)
    {
      if (count($featureList) == 0) {
          $arg = 'features-revert-all';
      } else {
          $arg = 'features-revert';
        foreach ($featureList as $feature) {
            $arg .= ' '.escapeshellarg($feature);
        }
      }
      if ($force) {
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
    public function setVariable($key, $value)
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
    public function clearCache($type = 'all')
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
      if (is_array($modules)) {
        foreach ($modules as $module) {
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
      if (is_array($modules)) {
        foreach ($modules as $module) {
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
      if (is_array($modules)) {
        foreach ($modules as $module) {
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
    public function userLogin($user = null, $path = null)
    {
        $args = ' uli ';

      if (!is_null($user)) {
          $args .= escapeshellarg($user).' ';
      }
      if (!is_null($path)) {
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
    public function updatePassword($authname, $password)
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
    public static function validateDrushOutput(array $output)
    {
      foreach ($output as $line) {
        if (preg_match('/^\[error\]/i', $line)
              || preg_match('/^\[fatal\]/i', $line)
          ) {
          throw new PhpDrushException('Drush execution failed : '.PHP_EOL.implode(PHP_EOL, $output), 10);
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
     * Add user
     * @param $username
     * @param $mail
     * @param $password
     * @return array
     * @throws PhpDrushException
     */
    public function userAdd($username, $mail = null, $password = false)
    {
      if (!$password) {
        $password  = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10);
      }
      if (!$mail) {
        $mail = escapeshellarg($username) . "@localhost.localdomain";
      }
        $args = ' user-create ';
        $args .= escapeshellarg($username);
        $args .= ' --mail="'.escapeshellarg($mail) . '"';
        $args .= ' --password="'.escapeshellarg($password) . '"';
        return $this->runDrush($args);
    }

    /**
     * Add role to user
     * @param $username
     * @param $mail
     * @param $password
     * @return array
     * @throws PhpDrushException
     */
    public function userAddRole($username, $role)
    {
        $args = ' user-add-role ';
        $args .= escapeshellarg($role);
        $args .= " ";
        $args .= escapeshellarg($username);
        return $this->runDrush($args);
    }

    /**
     * Get variable value
     * @param $variable
     * @return string
     * @throws PhpDrushException
     */
    public function getVariable($variable)
    {
      $args = ' vget --format=json ';
      $args .= escapeshellarg($variable);
      $output = $this->runDrush($args);
      $json_result = implode(PHP_EOL, $output);

      // Remove potential missing module warnings
      $output_array = array();
      if (preg_match("/The following.*bootstrap.inc:1143(.*)/s", $json_result, $output_array)) {
        $json_result = $output_array[1];
      }

      $result = json_decode($json_result, true);

      if (isset($result[$variable])) {
        return $result[$variable];
      } else {
        return null;
      }
    }

    /**
     * Run SQLQuery
     */
    public function sqlQuery($query)
    {
      $args = ' sqlq ';
      $args .= escapeshellarg($query);
      $output = $this->runDrush($args);
      return $output;
    }
    
    /**
     * Get redis-cli
     */
    private function getRedisClient()
    {
      try {
        $redis_client_host = $this->getVariable("redis_client_host");
        $redis_client_port = $this->getVariable("redis_client_port");
        $redis_client_password = $this->getVariable("redis_client_password");
        $redis_client_base = $this->getVariable("redis_client_base");

        if (empty($redis_client_host) || preg_match('#^([0-9]{1,3}\.){3}[0-9]{1,3}$#', $redis_client_host) === 0) {
          return null;
        }

        $config = [
          'server' =>"${redis_client_host}:${redis_client_port}",
          'timeout' => 2,
          'password' => $redis_client_password,
          'database' => $redis_client_base,
        ];

        return new RedisClient($config);
      } catch (\PhpDrush\PhpDrushException $e) {
        return null;
      }
    }

    /**
     * Flush Redis DB
     *
     * Remove all keys from the current database
     *
     * @throws ErrorResponseException
     */
    public function redisFlushDB()
    {
      if ($Redis = $this->getRedisClient()) {
        return $Redis->flushdb();
      } else {
        return null;
      }
    }
  }
}
