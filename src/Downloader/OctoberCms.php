<?php

namespace OFFLINE\Bootstrapper\October\Downloader;


use GuzzleHttp\Client;
use Symfony\Component\Process\Exception\LogicException;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class OctoberCms
{
    protected $zipFile;

    /**
     * Downloads and extracts October CMS.
     *
     */
    public function __construct()
    {
        $this->zipFile = $this->makeFilename();
    }

    /**
     * Download latest October CMS.
     *
     * @throws LogicException
     * @throws RuntimeException
     * @return $this
     */
    public function download()
    {
        $this->fetchZip()
             ->extract()
             ->cleanUp();

        return $this;
    }

    /**
     * Download the temporary Zip to the given file.
     *
     * @throws LogicException
     * @throws RuntimeException
     * @return $this
     */
    protected function fetchZip()
    {
        $response = (new Client)->get('https://github.com/octobercms/october/archive/master.zip');
        file_put_contents($this->zipFile, $response->getBody());

        return $this;
    }

    /**
     * Extract the zip file into the given directory.
     *
     * @return $this
     */
    protected function extract()
    {
        $directory = getcwd();

        $archive = new ZipArchive;
        $archive->open($this->zipFile);
        $archive->extractTo($directory);
        $archive->close();

        return $this;
    }

    /**
     * Clean-up the Zip file, move folder contents one level up.
     *
     * @throws LogicException
     * @throws \Symfony\Component\Process\Exception\RuntimeException
     * @return $this
     */
    protected function cleanUp()
    {
        @chmod($this->zipFile, 0777);
        @unlink($this->zipFile);

        $directory = getcwd();
        $source    = $directory . '/october-master';

        (new Process(sprintf('mv %s %s', $source . '/*', $directory)))->run();
        (new Process(sprintf('rm -rf %s', $source)))->run();

        if (is_dir($source)) {
            echo "<comment>Install directory could not be removed. Delete ${source} manually</comment>";
        }

        return $this;
    }

    /**
     * Generate a random temporary filename.
     *
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd() . '/october_' . md5(time() . uniqid()) . '.zip';
    }

}