<div id="mautic-video-form-embed">
    <form method="post" action="<?php echo $view['router']->url('mautic_form_postresults', ['formId' => $form->getId()]) ?>">
        <?php
        foreach ($form->getFields() as $f):
            if ($f->isCustom()):
                $params = $f->getCustomParameters();
                $template = $params['template'];
            else:
                $template = 'MauticFormBundle:Field:' . $f->getType() . '.html.php';
            endif;

            echo $view->render($template, ['field' => $f->convertToArray(), 'id' => $f->getAlias(), 'formName' => $f->getForm()->generateFormName()]);
        endforeach;
        ?>
    </form>
</div>
