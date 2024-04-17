<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\CompleteDynamicPropertiesRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\ArrowFunction\StaticArrowFunctionRector;
use Rector\CodingStyle\Rector\Closure\StaticClosureRector;
use Rector\Config\RectorConfig;
use Rector\Php54\Rector\Array_\LongArrayToShortArrayRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/resources',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withRules([
        TypedPropertyFromStrictConstructorRector::class,
        InlineConstructorDefaultToPropertyRector::class,
        AddReturnTypeDeclarationRector::class,
        CompleteDynamicPropertiesRector::class,
    ])->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        earlyReturn: true,
        strictBooleans: true
    )->withSets([
        LevelSetList::UP_TO_PHP_83,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_110,
        \RectorLaravel\Set\LaravelSetList::LARAVEL_100,
    ])->withSkip([
        StaticClosureRector::class,
        LongArrayToShortArrayRector::class,
        StringClassNameToClassConstantRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        ClosureToArrowFunctionRector::class,
        NullToStrictStringFuncCallArgRector::class,
        StaticArrowFunctionRector::class,
    ]);
