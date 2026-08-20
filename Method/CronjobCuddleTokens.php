<?php
namespace GDO\LinkUUp\Method;

use GDO\Cronjob\MethodCronjob;

final class CronjobCuddleTokens extends MethodCronjob
{

    public function runAt(): string
    {
        return ''; // at 0:05 UTC (crontab syntax)
    }

    public function run(): void
    {
        // clear outdated cuddle tokens.
    }
}
