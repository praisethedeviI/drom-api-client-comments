<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        'src',
        'tests'
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PhpCsFixer' => true,
        '@PSR12' => true,
        'declare_strict_types' => true,
        'php_unit_internal_class' => ['types' => []],
        'concat_space' => ['spacing' => 'one'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'global_namespace_import' => [
            'import_classes' => true,
        ],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => ['class', 'function', 'const'],
        ],
        'ordered_class_elements' => true,
    ])
    ->setFinder($finder);