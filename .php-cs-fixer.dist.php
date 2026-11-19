<?php

// only the folders that exist: the public repository has no bin/
$paths = array_values(array_filter([
    'src/',
    'bin/',
], 'is_dir'));

$rules = [
    '@Symfony'                                         => true,
    '@Symfony:risky'                                   => false,
    // should be enabled later
    'align_multiline_comment'                          => ['comment_type' => 'all_multiline'],
    'blank_line_before_statement'                      => false,
    // do not force blank lines
    'concat_space'                                     => ['spacing' => 'one'],
    'combine_consecutive_issets'                       => true,
    'combine_consecutive_unsets'                       => true,
    'doctrine_annotation_array_assignment'             => true,
    'doctrine_annotation_indentation'                  => true,
    'heredoc_to_nowdoc'                                => true,
    'list_syntax'                                      => ['syntax' => 'short'],
    'no_useless_else'                                  => true,
    'no_useless_return'                                => true,
    'no_whitespace_in_blank_line'                      => false,
    'control_structure_continuation_position'          => ['position' => 'next_line'],
    // put else, elseif, catch etc. in new lines
    'phpdoc_order'                                     => true,
    'phpdoc_separation'                                => false,
    // no empty line between doc type groups
    'phpdoc_summary'                                   => false,
    // no dot at the end of first line
    'static_lambda'                                    => false,
    // risky, breaks tests in Helper/Unit.php
    'strict_comparison'                                => false,
    // risky, replace '==' with '==='
    'strict_param'                                     => true,
    // risky, enable if needed
    'array_syntax'                                     => ['syntax' => 'short'],
    'binary_operator_spaces'                           => [
        'operators' => [
            '='  => 'align_single_space',
            '=>' => 'align_single_space',
        ],
    ],
];

if (version_compare(PhpCsFixer\Console\Application::VERSION, '3.16.0', '<')) {
    throw new \Exception('PhpCsFixer >= 3.16.0 required');
}

$finder = PhpCsFixer\Finder::create()
    ->in($paths)
;

$config = new PhpCsFixer\Config();

return $config->setRules($rules)
    ->setRiskyAllowed(true)
    ->setUsingCache(false)
    ->setFinder($finder)
;
