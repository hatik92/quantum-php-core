<?php

/**
 * Quantum PHP Framework
 *
 * An open source software development framework for PHP
 *
 * @package Quantum
 * @author Arman Ag. <arman.ag@softberg.org>
 * @copyright Copyright (c) 2018 Softberg LLC (https://softberg.org)
 * @link http://quantum.softberg.org/
 * @since 2.8.0
 */

namespace Quantum\Console\Commands;

use Quantum\Libraries\Storage\FileSystem;
use Quantum\Console\QtCommand;

/**
 * Class DebugBarAssetsCommand
 * @package Quantum\Console\Commands
 */
class DebugBarAssetsCommand extends QtCommand
{

    /**
     * Command name
     * @var string
     */
    protected $name = 'install:debugbar';

    /**
     * Command description
     * @var string
     */
    protected $description = 'Publishes debugbar assets';

    /**
     * Command help text
     * @var string
     */
    protected $help = 'The command will publish debugbar assets';

    /**
     * Path to public debug bar resources
     * @var string 
     */
    private $publicDebugbarFolderPath = 'public/assets/DebugBar/Resources';

    /**
     * Path to vendor debug bar resources
     * @var string 
     */
    private $vendorDebugbarFolderPath = 'vendor/maximebf/debugbar/src/DebugBar/Resources';

    /**
     * Executes the command and publishes the debug bar assets
     */
    public function exec()
    {
        if ($this->installed()) {
            $this->error('The debuger already installed');
            return;
        }

        $this->recursive_copy($this->vendorDebugbarFolderPath, $this->publicDebugbarFolderPath);

        $this->info('Debugbar assets successfully published');
    }

    /**
     * Recursively copies the debug bar assets
     * @param string $src
     * @param string $dst
     * @throws \RuntimeException
     */
    private function recursive_copy(string $src, string $dst)
    {
        $dir = opendir($src);

        if ($dst != $this->publicDebugbarFolderPath) {
            if (mkdir($dst, 777, true) === false) {
                throw new \RuntimeException(t('exception.directory_cant_be_created', $dst));
            }
        }

        if (is_resource($dir)) {
            while (($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        if ($file) {
                            copy($src . '/' . $file, $dst . '/' . $file);
                        }
                    }
                }
            }

            closedir($dir);
        }
    }

    /**
     * Checks if already installed
     * @return bool
     */
    private function installed(): bool
    {
        $fs = new FileSystem();

        if ($fs->exists(assets_dir() . DS . 'DebugBar' . DS . 'Resources' . DS . 'debugbar.css')) {
            return true;
        }

        return false;
    }
}
