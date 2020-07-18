<?php $errorsMessages = []; ?>
<?php foreach ($errors as $error): ?>
    <?php if (!in_array($error->getMessage(), $errorsMessages)): ?>
        <?php $errorsMessages[] = $error->getMessage(); ?>
    <?php endif; ?>
<?php endforeach; ?>

<?php if (count($errorsMessages) > 0): ?>
    <div class="help-block">
        <?php if (count($errorsMessages) > 1): ?>
            <ul>
                <?php foreach ($errorsMessages as $errorMessage): ?>
                    <li><?php echo $view->escape($errorMessage); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <?php echo $view->escape($errorsMessages[0]); ?>
        <?php endif; ?>
    </div>
<?php endif; ?>
