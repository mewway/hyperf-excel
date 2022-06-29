<?php

declare(strict_types=1);

// This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.

$header = <<<'EOF'
    This file is part of HuanLeGuang Project, Created by php-cs-fixer 3.0.
    EOF;

$finder = PhpCsFixer\Finder::create()
    ->exclude('tests/Fixtures')
    ->exclude('public')
    ->exclude('runtime')
    ->exclude('vendor')
    ->in(__DIR__)
    ->append([
        __DIR__ . '/dev-tools/doc.php',
        // __DIR__.'/php-cs-fixer', disabled, as we want to be able to run bootstrap file even on lower PHP version, to show nice message
        __FILE__,
    ]);

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHP74Migration' => true,
        '@PHPUnit75Migration:risky' => true,
        '@PhpCsFixer' => true,
        'no_whitespace_in_blank_line' => false,
        'no_blank_lines_after_class_opening' => false,
        'no_trailing_whitespace_in_comment' => false,
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
        'phpdoc_summary' => false,
        'general_phpdoc_annotation_remove' => ['annotations' => ['author']], // one should use PHPUnit built-in method instead
        'ordered_imports' => ['sort_algorithm' => 'alpha', 'imports_order' => ['const', 'class', 'function']],
        'not_operator_with_successor_space' => true,
        'concat_space' => ['spacing' => 'one'],
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'header_comment' => ['header' => $header],
    ])
    ->setFinder($finder);

return $config;
