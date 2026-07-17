<?php

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer\Fixture;

class SingleParamController
{
    public function autowireSingleParamController(FormModel $formModel): void
    {
    }
}

?>
-----
<?php

namespace Utils\ECS\Tests\StandaloneLineAutowireParamFixer\Fixture;

class SingleParamController
{
    public function autowireSingleParamController(
        FormModel $formModel
    ): void
    {
    }
}

?>
