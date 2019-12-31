<?php declare(strict_types=1);

$finder = new PhpCsFixer\Finder();
$config = new PhpCsFixer\Config('jddf');
$finder->in(__DIR__);

$config
  ->setRules(array(
    '@PSR2' => true,
    '@Symfony' => true,
    '@PhpCsFixer' => true,
    'declare_strict_types' => true
  ))
  ->setFinder($finder);

return $config;
