<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude([
        'vendor',
        'fg',
        // Ajouter d'autres répertoires à exclure si nécessaire ...
    ])
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR1' => true,
        '@PSR12' => true,
        'psr_autoloading' => true,
        'class_definition' => true,
        'blank_line_before_statement' => true,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
