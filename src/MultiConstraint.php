<?php


namespace Fox5\PackageBundlerPlugin;

use Composer\Semver\Constraint\ConstraintInterface;
use Composer\Semver\Constraint\MultiConstraint as SemverMultiConstraint;

class MultiConstraint extends SemverMultiConstraint
{
    /**
     * Tries to optimize the constraints as much as possible, meaning
     * reducing/collapsing congruent constraints etc.
     * Does not necessarily return a MultiConstraint instance if
     * things can be reduced to a simple constraint
     *
     * @param ConstraintInterface[] $constraints A set of constraints
     * @param bool                  $conjunctive Whether the constraints should be treated as conjunctive or disjunctive
     *
     * @return ConstraintInterface
     */
    public static function create(array $constraints, $conjunctive = true)
    {
        $optimized = self::optimizeConstraints($constraints, $conjunctive);
        if ($optimized !== null) {
            [$constraints, $conjunctive] = $optimized;
            if (\count($constraints) === 1) {
                return $constraints[0];
            }
        }

        return new self($constraints, $conjunctive);
    }

    /**
     * @return array|null
     */
    private static function optimizeConstraints(array $constraints, $conjunctive)
    {
        // parse the two OR groups and if they are contiguous we collapse
        // them into one constraint
        // [>= 1 < 2] || [>= 2 < 3] || [>= 3 < 4] => [>= 1 < 4]
        if (!$conjunctive) {
            $left = $constraints[0];
            $mergedConstraints = [];
            $optimized = false;
            for ($i = 1, $l = \count($constraints); $i < $l; $i++) {
                $right = $constraints[$i];
                if ($left instanceof SemverMultiConstraint
                    && $left->conjunctive
                    && $right instanceof SemverMultiConstraint
                    && $right->conjunctive
                    && \count($left->constraints) === 2
                    && \count($right->constraints) === 2
                    && ($left0 = (string) $left->constraints[0])
                    && $left0[0] === '>' && $left0[1] === '='
                    && ($left1 = (string) $left->constraints[1])
                    && $left1[0] === '<'
                    && ($right0 = (string) $right->constraints[0])
                    && $right0[0] === '>' && $right0[1] === '='
                    && ($right1 = (string) $right->constraints[1])
                    && $right1[0] === '<'
                    && substr($left1, 2) === substr($right0, 3)
                ) {
                    $optimized = true;
                    $left = new MultiConstraint(
                        [
                            $left->constraints[0],
                            $right->constraints[1],
                        ],
                        true
                    );
                } else {
                    $mergedConstraints[] = $left;
                    $left = $right;
                }
            }
            if ($optimized) {
                $mergedConstraints[] = $left;
                return [$mergedConstraints, false];
            }
        }

        // TODO: Here's the place to put more optimizations

        return null;
    }
}
// vim:sw=4:ts=4:sts=4:et:
