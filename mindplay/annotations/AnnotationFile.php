<?php

/*
 * This file is part of the php-annotation framework.
 *
 * (c) Rasmus Schultz <rasmus@mindplay.dk>
 *
 * This software is licensed under the GNU LGPL license
 * for more information, please see:
 *
 * <https://github.com/mindplay-dk/php-annotations>
 */

namespace mindplay\annotations;

/**
 * This class represents a php source-code file inspected for Annotations.
 */
class AnnotationFile
{
    /**
     * @param string $path absolute path to php source-file
     * @param array $data annotation data (as provided by AnnotationParser)
     */
    public function __construct($path, array $data)
    {
        $this->path = $path;
        $this->data = $data;
        $this->namespace = $data['#namespace'];
        $this->uses = $data['#uses'];
    }

    /**
     * @var array hash where member name => annotation data
     */
    public $data;

    /**
     * @var string $path absolute path to php source-file
     */
    public $path;

    /**
     * @var string $namespace fully qualified namespace
     */
    public $namespace;

    /**
     * @var string[] $uses hash where local class-name => fully qualified class-name
     */
    public $uses;
}
