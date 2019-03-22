<?php

namespace OFFLINE\Bootstrapper\October\Manager;

/**
 * Plugin manager class
 */
class ThemeManager extends BaseManager
{
    /**
     * Parse the theme's name and remote path out of the
     * given theme declaration.
     *
     * @param $theme theme declaration like Theme (Remote)
     *
     * @return array array $theme[, remote]
     */
    protected function parseDeclaration(string $theme): array
    {
        preg_match("/([^ ]+)(?: ?\(([^\)]+))?/", $theme, $matches);

        array_shift($matches);

        if (count($matches) < 2) {
            $matches[1] = false;
        }

        return $matches;
    }

    public function createDir(string $themeDeclaration)
    {
        $themeDir = $this->getDirPath($themeDeclaration);

        if (is_dir($themeDir)) {
            return $themeDir;
        }

        return $this->mkdir($themeDir);
    }

    public function removeDir(string $themeDeclaration)
    {
        $themeDir = $this->getDirPath($themeDeclaration);

        $this->rmdir($themeDir);
    }

    public function getDirPath(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parseDeclaration($themeDeclaration);
        $themeDir = $this->pwd() . implode(DS, ['themes', $theme]);
        return $themeDir;
    }

    /**
     * Install a theme via git or artisan.
     *
     * @throws LogicException
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws \RuntimeException
     * @throws \LogicException
     */
    public function install(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parse($themeDeclaration);

        if ($remote === false) {
            return $this->installViaArtisan($theme);
        }

        $themeDir = $this->createDir($themeDeclaration);

        if (!$this->isEmpty($themeDir)) {
            throw new RuntimeException("<error> - Theme directory not empty. Aborting. </error>");
        }

        $repo = Git::repo($themeDir);
        try {
            $repo->cloneFrom($remote, $themeDir);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Error while cloning theme repo: ' . $e->getMessage());
        }

        $this->removeGitRepo($themeDir);

        return true;
    }

    /**
     * Installs a theme via artisan command.
     *
     * @param string theme declaration string
     *
     * @return string
     * @throws RuntimeException
     */
    public function installViaArtisan(string $themeDeclaration)
    {
        list($theme, $remote) = $this->parseDeclaration($themeDeclaration);

        try {
            $this->artisan->call("theme:install {$theme}");
        } catch (RuntimeException $e) {
            throw new RuntimeException(sprintf('Error while installing theme "%s" via artisan.', $theme));
        }

        return "${theme} theme installed";
    }

}