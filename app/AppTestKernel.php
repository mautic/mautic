<?php

class AppTestKernel extends AppKernel
{
    /**
     * {@inheritdoc}
     */
    public function getLocalParams()
    {
        return [
            'secret_key' => '68c7e75470c02cba06dd543431411e0de94e04fdf2b3a2eac05957060edb66d0',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function isInstalled()
    {
        return true;
    }
}
