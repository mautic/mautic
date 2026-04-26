<?php

namespace Helper;

use Codeception\Module;

class EnvHelper extends Module
{
    private string $envLocalPath       = '.env.local';
    private string $envLocalBackupPath = '.env.local.e2e.backup';
    private bool $envLocalOverridden   = false;

    public function _beforeSuite($settings = [])
    {
        $this->backupLocalEnv();
        $this->useTestLocalEnv();
    }

    public function _afterSuite(): void
    {
        $this->restoreLocalEnv();
    }

    private function backupLocalEnv(): void
    {
        if (file_exists($this->envLocalBackupPath)) {
            return;
        }

        if (!file_exists($this->envLocalPath)) {
            return;
        }

        copy($this->envLocalPath, $this->envLocalBackupPath);
    }

    private function useTestLocalEnv(): void
    {
        if (file_exists($this->envLocalPath)) {
            unlink($this->envLocalPath);
        }

        file_put_contents($this->envLocalPath, "APP_ENV=test\nAPP_DEBUG=1\n");

        $this->envLocalOverridden = true;
    }

    private function restoreLocalEnv(): void
    {
        if ($this->envLocalOverridden && file_exists($this->envLocalPath)) {
            unlink($this->envLocalPath);
            $this->envLocalOverridden = false;
        }

        if (!file_exists($this->envLocalBackupPath)) {
            return;
        }

        if (file_exists($this->envLocalPath)) {
            unlink($this->envLocalPath);
        }

        rename($this->envLocalBackupPath, $this->envLocalPath);
    }
}
