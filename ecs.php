<?php

declare(strict_types=1);

use SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::SETS, [SetList::CLEAN_CODE, SetList::PSR_12]);
    $parameters->set(
        Option::SKIP,
        [
            PhpCsFixer\Fixer\Casing\ConstantCaseFixer::class => [
                __DIR__ . '/src/PhpGenerator/Model/Type.php',
            ],
            UselessParenthesesSniff::class . '.UselessParentheses' => null,
        ]
    );
};
