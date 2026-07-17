<?php

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer\Fixture;

class MultipleParamsController
{
    #[Required]
    public function autowireMultipleParamsController(FormModel $formModel, SubmissionModel $submissionModel): void
    {
    }
}

-----
<?php

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer\Fixture;

class MultipleParamsController
{
    #[Required]
    public function autowireMultipleParamsController(
        FormModel $formModel,
        SubmissionModel $submissionModel
    ): void
    {
    }
}
