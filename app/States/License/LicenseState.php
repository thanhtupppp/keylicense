<?php

namespace App\States\License;

use App\Contracts\LicenseStateContract;
use Spatie\ModelStates\State;

/**
 * @template TModel of \App\Models\License
 * @extends State<TModel>
 */
abstract class LicenseState extends State implements LicenseStateContract
{
    protected static string $name = '';

    public function getValue(): string
    {
        return static::getMorphClass();
    }

    public static function config(): \Spatie\ModelStates\StateConfig
    {
        return parent::config()
            ->default(InactiveState::class)
            ->allowTransition(InactiveState::class, ActiveState::class)
            ->allowTransition(InactiveState::class, RevokedState::class)
            ->allowTransition(ActiveState::class, ExpiredState::class)
            ->allowTransition(ActiveState::class, SuspendedState::class)
            ->allowTransition(ActiveState::class, RevokedState::class)
            ->allowTransition(ExpiredState::class, SuspendedState::class)
            ->allowTransition(SuspendedState::class, ActiveState::class)
            ->allowTransition(SuspendedState::class, RevokedState::class)
            ->allowTransition(SuspendedState::class, SuspendedState::class)
            ->allowTransition(RevokedState::class, InactiveState::class);
    }
}
