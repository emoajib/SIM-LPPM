<?php

namespace App\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

class MigrationDriverBranchRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Expr\BinaryOp\Identical::class;
    }

    public function processNode(
        Node $node,
        Scope $scope
    ): array {
        $errors = [];

        if ($node->left instanceof Node\Expr\PropertyFetch) {
            $propertyFetch = $node->left;

            if ($propertyFetch->name instanceof Node\Identifier) {
                $propertyName = $propertyFetch->name->toString();

                if ($propertyName === 'driver') {
                    $errors[] = RuleErrorBuilder::message("Do not use driver-specific branches (if (\$driver === 'pgsql')). Use portable migration patterns.")
                        ->build();
                }
            }
        }

        return $errors;
    }
}
