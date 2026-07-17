<?php

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer\Fixture;

class PlainController
{
    public function setDependencies(FormModel $formModel, SubmissionModel $submissionModel): void
    {
    }
}
