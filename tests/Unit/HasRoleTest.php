<?php

namespace Tests\Unit;

use App\Models\Rol;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class HasRoleTest extends TestCase
{
    private function makeUser(?string $rolNombre): User
    {
        $user = new User();

        if ($rolNombre !== null) {
            $rol = new Rol();
            $rol->nombre = $rolNombre;
            $user->setRelation('rol', $rol);
        } else {
            $user->setRelation('rol', null);
        }

        return $user;
    }

    public function test_hasRole_returns_true_for_matching_role(): void
    {
        $user = $this->makeUser('Administrador');
        $this->assertTrue($user->hasRole('Administrador'));
    }

    public function test_hasRole_is_case_insensitive(): void
    {
        $user = $this->makeUser('Cliente');
        $this->assertTrue($user->hasRole('cliente'));
        $this->assertTrue($user->hasRole('CLIENTE'));
        $this->assertTrue($user->hasRole('ClIeNtE'));
    }

    public function test_hasRole_returns_false_for_wrong_role(): void
    {
        $user = $this->makeUser('Recepcionista');
        $this->assertFalse($user->hasRole('Administrador'));
        $this->assertFalse($user->hasRole('Cliente'));
    }

    public function test_hasRole_returns_false_when_rol_is_null(): void
    {
        $user = $this->makeUser(null);
        $this->assertFalse($user->hasRole('Administrador'));
    }
}
