// ...

if (is_array($data[$name]) && array_key_exists('date', $data[$name])) {
    return \DateTime::createFromFormat('H:i', $data[$name]['date'])
        ?: new \DateTime($data[$name]['date']);
} elseif (is_string($data[$name])) {
    return \DateTime::createFromFormat('H:i', $data[$name])
        ?: new \DateTime($data[$name]);
}

// ...