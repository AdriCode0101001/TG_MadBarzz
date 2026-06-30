<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Rol;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SedeScopeTest extends TestCase
{
    use RefreshDatabase;

    private Sede $sede1;
    private Sede $sede2;
    private User $userSede1;
    private User $userSede2;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Sede 1 is inserted by the create_sedes_table migration; grab it.
        $this->sede1 = Sede::first();
        $this->sede2 = Sede::create(['nombre' => 'Sede 2']);

        $rolClienteId = Rol::create(['nombre' => 'Cliente'])->id;
        $rolAdminId   = Rol::create(['nombre' => 'Administrador'])->id;

        $this->userSede1 = User::factory()->create(['id_sede' => $this->sede1->id_sede, 'rol_id' => $rolClienteId]);
        $this->userSede2 = User::factory()->create(['id_sede' => $this->sede2->id_sede, 'rol_id' => $rolClienteId]);
        $this->admin     = User::factory()->create(['id_sede' => $this->sede1->id_sede, 'rol_id' => $rolAdminId]);

        // Create one persona per sede (all required columns).
        $persona1 = DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Juan',
            'apellido_paterno' => 'Perez',
            'apellido_materno' => 'Lopez',
            'ci'               => '11111111',
            'telefono'         => '70000001',
            'genero'           => 'M',
            'fecha_nacimiento' => '1990-01-01',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $persona2 = DB::table('personas')->insertGetId([
            'nombre_completo'  => 'Ana',
            'apellido_paterno' => 'Gomez',
            'apellido_materno' => 'Ruiz',
            'ci'               => '22222222',
            'telefono'         => '70000002',
            'genero'           => 'F',
            'fecha_nacimiento' => '1992-05-15',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Insert clientes directly to bypass the SedeScope creating-event
        // (scope only filters SELECT; creating-event would auto-assign id_sede from
        //  Auth::user() which is not authenticated during setUp).
        DB::table('clientes')->insert([
            [
                'id_persona'    => $persona1,
                'id_usuario'    => $this->userSede1->id,
                'id_sede'       => $this->sede1->id_sede,
                'dias_asistidos' => 0,
                'estado'        => 'activo',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id_persona'    => $persona2,
                'id_usuario'    => $this->userSede2->id,
                'id_sede'       => $this->sede2->id_sede,
                'dias_asistidos' => 0,
                'estado'        => 'activo',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }

    public function test_non_admin_sees_only_their_sede(): void
    {
        $this->actingAs($this->userSede1);

        $clientes = Cliente::all();

        $this->assertCount(1, $clientes);
        $this->assertEquals($this->sede1->id_sede, $clientes->first()->id_sede);
    }

    public function test_non_admin_from_other_sede_sees_their_own_data(): void
    {
        $this->actingAs($this->userSede2);

        $clientes = Cliente::all();

        $this->assertCount(1, $clientes);
        $this->assertEquals($this->sede2->id_sede, $clientes->first()->id_sede);
    }

    public function test_admin_sees_all_sedes(): void
    {
        $this->actingAs($this->admin);

        $this->assertCount(2, Cliente::all());
    }

    public function test_unauthenticated_query_is_unfiltered(): void
    {
        // SedeScope bails early when not authenticated so Artisan commands
        // and other non-HTTP contexts can still access all records.
        $this->assertCount(2, Cliente::all());
    }
}
