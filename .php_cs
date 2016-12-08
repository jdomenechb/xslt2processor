<?php

$header = <<<'HEADER'
This file is part of the Wenalyzer package.

(c) Jordi Domènech Bonilla

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
HEADER;

$config = PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'concat_space' => ['spacing' => 'one'],
        'combine_consecutive_unsets' => true,
        'header_comment' => [
            'header' => $header,
            'commentType' => 'PHPDoc',
        ],
        'ordered_imports' => true,
        'ordered_class_elements' => true,
        'phpdoc_order' => true,
        'phpdoc_add_missing_param_annotation' => [
            'only_untyped' => false,
        ],
        'no_useless_else' => true,
        'no_useless_return' => true,
    ])

    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in('app/')
            ->in('disabled_plugins/')
            ->in('plugins/')
            ->in('src/')
            ->in('tests/')
            ->in('web/')
    )

    ->setUsingCache(false)
    ;

return $config;