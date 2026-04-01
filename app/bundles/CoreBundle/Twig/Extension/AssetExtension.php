public function makeLinks(string $text, array $protocols = ['http', 'mail'], array $attributes = []): string
    {
        if ($text === null) {
            return '';
        }
        return $this->assetsHelper->makeLinks($text, $protocols, $attributes);
    }