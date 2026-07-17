<?php

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer\Fixture;

class AlreadyStandaloneController
{
    public function autowireAlreadyStandaloneController(
        FormModel $formModel,
        SubmissionModel $submissionModel
    ): void
    {
    }
}
