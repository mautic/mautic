<?php if (!empty($form['subjectID'])) { ?>
    <div class="form-group mb-0">
        <div class="row">
            <div class="col-sm-6<?php echo (count($form['subjectID']->vars['errors'])) ? ' has-error' : ''; ?>">
                <?php
                echo $view['form']->widget($form['subjectID']);
                echo $view['form']->errors($form['subjectID']);
                ?>
            </div>
        </div>
    </div>
<?php } ?>
