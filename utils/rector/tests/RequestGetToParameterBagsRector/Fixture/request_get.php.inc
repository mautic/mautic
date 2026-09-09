<?php

namespace Utils\Rector\Tests\RequestGetToParameterBagsRector\Fixture;

use Symfony\Component\HttpFoundation\Request;

class RequestGet
{
    public function run(Request $request)
    {
        $tmpl = $request->get('tmpl', 'index');
        $id   = (int) $request->get('id');
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequestGetToParameterBagsRector\Fixture;

use Symfony\Component\HttpFoundation\Request;

class RequestGet
{
    public function run(Request $request)
    {
        $tmpl = $request->attributes->all()['tmpl'] ?? $request->query->all()['tmpl'] ?? $request->request->all()['tmpl'] ?? 'index';
        $id   = (int) ($request->attributes->all()['id'] ?? $request->query->all()['id'] ?? $request->request->all()['id'] ?? null);
    }
}

?>
