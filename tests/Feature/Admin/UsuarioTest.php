<?php

use App\Enums\EstadoMatricula;
use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Models\Calificacion;
use App\Models\Carrera;
use App\Models\Curso;
use App\Models\Facultad;
use App\Models\Inscripcion;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\PlanEstudio;
use App\Models\User;
use Database\Seeders\RolSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->seed(RolSeeder::class);
});

it('permite al administrador crear un usuario con rol', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Nuevo', 'Docente'),
            'ci' => '8765432',
            'telefono' => '70012345',
            'email' => 'nuevo.docente@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'nuevo.docente@academica.test')->firstOrFail();

    expect($usuario->hasRole(Rol::Docente->value))->toBeTrue()
        ->and($usuario->activo)->toBeTrue()
        ->and(Hash::check('Password123', $usuario->password))->toBeTrue();
});

it('compone el nombre completo a partir de los campos separados', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Ana María', 'Quispe', 'Mamani'),
            'ci' => '8765498',
            'telefono' => null,
            'email' => 'nombre.separado@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'nombre.separado@academica.test')->firstOrFail();

    expect($usuario->nombres)->toBe('Ana María')
        ->and($usuario->primer_apellido)->toBe('Quispe')
        ->and($usuario->segundo_apellido)->toBe('Mamani')
        ->and($usuario->name)->toBe('Ana María Quispe Mamani');
});

it('crea un usuario sin segundo apellido', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Luis', 'Condori'),
            'ci' => '8765497',
            'telefono' => null,
            'email' => 'sin.segundo.apellido@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'sin.segundo.apellido@academica.test')->firstOrFail();

    expect($usuario->segundo_apellido)->toBeNull()
        ->and($usuario->name)->toBe('Luis Condori');
});

it('exige nombres y primer apellido al crear un usuario', function (array $nombre, string $campo) {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', $nombre + [
            'ci' => '8765496',
            'telefono' => null,
            'email' => 'nombre.incompleto@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors($campo);

    expect(User::where('email', 'nombre.incompleto@academica.test')->exists())->toBeFalse();
})->with([
    'sin nombres' => [['primer_apellido' => 'Quispe'], 'nombres'],
    'sin primer apellido' => [['nombres' => 'Ana'], 'primer_apellido'],
]);

it('permite al administrador crear otro administrador', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Nuevo', 'Administrador'),
            'ci' => '8765433',
            'telefono' => '70012346',
            'email' => 'nuevo.admin@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Administrador->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'nuevo.admin@academica.test')->firstOrFail();

    expect($usuario->hasRole(Rol::Administrador->value))->toBeTrue()
        ->and($usuario->activo)->toBeTrue();
});

it('crea un estudiante con matrículas en varias carreras', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $facultad = Facultad::factory()->create(['nombre' => 'Facultad de Ingeniería']);
    $otraFacultad = Facultad::factory()->create(['nombre' => 'Facultad de Ciencias Económicas']);

    $carrera = Carrera::create([
        'codigo' => 'SIS',
        'nombre' => 'Ingeniería de Sistemas',
        'facultad_id' => $facultad->id,
        'activo' => true,
    ]);
    $otraCarrera = Carrera::create([
        'codigo' => 'ADM',
        'nombre' => 'Administración de Empresas',
        'facultad_id' => $otraFacultad->id,
        'activo' => true,
    ]);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Estudiante', 'Nuevo'),
            'ci' => '9000001',
            'telefono' => null,
            'email' => 'estudiante.nuevo@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                ['carrera_id' => $carrera->id, 'modalidad' => Modalidad::Modular->value, 'es_principal' => '1'],
                ['carrera_id' => $otraCarrera->id, 'modalidad' => Modalidad::Semestral->value],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'estudiante.nuevo@academica.test')->firstOrFail();

    expect($usuario->matriculas()->activas()->count())->toBe(2)
        ->and($usuario->matriculas()->activas()->where('es_principal', true)->first()->carrera_id)->toBe($carrera->id)
        ->and($usuario->matriculas()->activas()->where('carrera_id', $otraCarrera->id)->first()->modalidad)->toBe(Modalidad::Semestral);
});

it('guarda el plan de estudios elegido cuando pertenece a la carrera', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create();
    $plan = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
    ]);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Estudiante', 'Con Plan'),
            'ci' => '9000011',
            'telefono' => null,
            'email' => 'estudiante.con.plan@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                [
                    'carrera_id' => $carrera->id,
                    'modalidad' => Modalidad::Semestral->value,
                    'plan_estudio_id' => $plan->id,
                    'es_principal' => '1',
                ],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    $usuario = User::where('email', 'estudiante.con.plan@academica.test')->firstOrFail();

    expect($usuario->matriculas()->activas()->first()->plan_estudio_id)->toBe($plan->id);
});

it('rechaza un plan de estudios de otra carrera en la matrícula', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create();
    $otraCarrera = Carrera::factory()->create();
    $planDeOtraCarrera = PlanEstudio::factory()->create(['carrera_id' => $otraCarrera->id]);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Estudiante', 'Plan Ajeno'),
            'ci' => '9000012',
            'telefono' => null,
            'email' => 'estudiante.plan.ajeno@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                [
                    'carrera_id' => $carrera->id,
                    'modalidad' => Modalidad::Semestral->value,
                    'plan_estudio_id' => $planDeOtraCarrera->id,
                    'es_principal' => '1',
                ],
            ],
            'activo' => '1',
        ])
        ->assertSessionHasErrors('matriculas.0.plan_estudio_id');

    expect(User::where('email', 'estudiante.plan.ajeno@academica.test')->exists())->toBeFalse();
});

it('exige carrera y modalidad válidas al crear un estudiante', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Estudiante', 'Incompleto'),
            'ci' => '9000002',
            'telefono' => null,
            'email' => 'estudiante.incompleto@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                ['carrera_id' => 999999, 'modalidad' => ''],
            ],
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['matriculas', 'matriculas.0.carrera_id', 'matriculas.0.modalidad']);

    expect(User::where('email', 'estudiante.incompleto@academica.test')->exists())->toBeFalse();
});

it('da de baja las matrículas cuando el usuario deja de ser estudiante', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create();

    $usuario = usuarioConRol(Rol::Estudiante);
    $matricula = Matricula::factory()->principal()->create([
        'user_id' => $usuario->id,
        'carrera_id' => $carrera->id,
    ]);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '9000003',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($matricula->fresh()->estado)->toBe(EstadoMatricula::Baja)
        ->and($matricula->fresh()->es_principal)->toBeFalse()
        ->and($matricula->fresh()->fecha_fin)->not->toBeNull();
});

it('muestra el formulario de alta con el selector de matrículas', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->get('/admin/usuarios/create')
        ->assertOk()
        ->assertSee('Matrículas académicas')
        ->assertSee('Agregar carrera');
});

it('muestra las matrículas activas y el historial al editar un estudiante', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carreraActiva = Carrera::factory()->create(['nombre' => 'Sistemas activos']);
    $carreraBaja = Carrera::factory()->create(['nombre' => 'Administración anterior']);
    $estudiante = usuarioConRol(Rol::Estudiante);
    Matricula::factory()->principal()->create([
        'user_id' => $estudiante->id,
        'carrera_id' => $carreraActiva->id,
    ]);
    Matricula::factory()->baja()->create([
        'user_id' => $estudiante->id,
        'carrera_id' => $carreraBaja->id,
    ]);

    $this->actingAs($admin)
        ->get("/admin/usuarios/{$estudiante->id}/edit")
        ->assertOk()
        ->assertSee('Sistemas activos')
        ->assertSee('Historial de bajas')
        ->assertSee('Administración anterior');
});

it('muestra el detalle del usuario con sus matrículas y certificados', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create(['nombre' => 'Ingeniería de Sistemas']);
    $docente = usuarioConRol(Rol::Docente);
    $docente->update([
        'name' => 'Docente Detallado',
        'ci' => '8765300',
        'telefono' => '70012345',
    ]);
    Matricula::factory()->create([
        'user_id' => $docente->id,
        'carrera_id' => $carrera->id,
    ]);
    $docente->certificadosDocentes()->create([
        'titulo' => 'Magíster en Sistemas',
        'institucion' => 'Universidad Nacional',
        'codigo' => 'CERT-DET',
    ]);

    $this->actingAs($admin)
        ->get("/admin/usuarios/{$docente->id}")
        ->assertOk()
        ->assertSee('Docente Detallado')
        ->assertSee('8765300')
        ->assertSee('70012345')
        ->assertSee('Ingeniería de Sistemas')
        ->assertSee('Magíster en Sistemas')
        ->assertSee('Universidad Nacional')
        ->assertSee('CERT-DET')
        ->assertSee('Editar')
        ->assertSee('Eliminar');
});

it('ofrece solo la opción Ver en la lista de usuarios', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Docente);
    $usuario->update(['name' => 'Usuario Listado']);

    $this->actingAs($admin)
        ->get('/admin/usuarios')
        ->assertOk()
        ->assertSee('Usuario Listado')
        ->assertSee('Ver')
        ->assertDontSee('Editar')
        ->assertDontSee('Eliminar');
});

it('oculta la opción Eliminar al ver el propio usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->get("/admin/usuarios/{$admin->id}")
        ->assertOk()
        ->assertSee('Editar')
        ->assertDontSee('Eliminar');
});

it('impide que un no administrador vea el detalle de un usuario', function (Rol $rol) {
    $usuario = usuarioConRol(Rol::Docente);

    $this->actingAs(usuarioConRol($rol))
        ->get("/admin/usuarios/{$usuario->id}")
        ->assertForbidden();
})->with([
    'docente' => Rol::Docente,
    'estudiante' => Rol::Estudiante,
]);

it('cambia la matrícula principal cuando llega primero en el formulario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carreraA = Carrera::factory()->create();
    $carreraB = Carrera::factory()->create();

    $usuario = usuarioConRol(Rol::Estudiante);
    $matriculaA = Matricula::factory()->principal()->create([
        'user_id' => $usuario->id,
        'carrera_id' => $carreraA->id,
    ]);
    $matriculaB = Matricula::factory()->create([
        'user_id' => $usuario->id,
        'carrera_id' => $carreraB->id,
    ]);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '9000005',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                ['carrera_id' => $carreraB->id, 'modalidad' => Modalidad::Modular->value, 'es_principal' => '1'],
                ['carrera_id' => $carreraA->id, 'modalidad' => Modalidad::Semestral->value],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($matriculaA->fresh()->es_principal)->toBeFalse()
        ->and($matriculaB->fresh()->es_principal)->toBeTrue()
        ->and($usuario->matriculas()->activas()->count())->toBe(2);
});

it('actualiza las matrículas de un estudiante conservando las bajas', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carreraAnterior = Carrera::factory()->create();
    $carreraNueva = Carrera::factory()->create();

    $usuario = usuarioConRol(Rol::Estudiante);
    $matriculaAnterior = Matricula::factory()->principal()->create([
        'user_id' => $usuario->id,
        'carrera_id' => $carreraAnterior->id,
    ]);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '9000004',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                ['carrera_id' => $carreraNueva->id, 'modalidad' => Modalidad::Modular->value, 'es_principal' => '1'],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($matriculaAnterior->fresh()->estado)->toBe(EstadoMatricula::Baja)
        ->and($usuario->matriculas()->activas()->count())->toBe(1)
        ->and($usuario->matriculas()->activas()->first()->carrera_id)->toBe($carreraNueva->id);
});

it('resuelve el plan según la modalidad aunque llegue un plan anterior en la matrícula', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $carrera = Carrera::factory()->create();
    $planSemestral = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Semestral,
    ]);
    $planModular = PlanEstudio::factory()->create([
        'carrera_id' => $carrera->id,
        'modalidad' => Modalidad::Modular,
    ]);

    $usuario = usuarioConRol(Rol::Estudiante);
    $matricula = Matricula::factory()->principal()->create([
        'user_id' => $usuario->id,
        'carrera_id' => $carrera->id,
        'plan_estudio_id' => $planSemestral->id,
        'modalidad' => Modalidad::Semestral,
    ]);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '9000006',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Estudiante->value,
            'matriculas' => [
                [
                    'carrera_id' => $carrera->id,
                    'modalidad' => Modalidad::Modular->value,
                    'plan_estudio_id' => $planSemestral->id,
                    'es_principal' => '1',
                ],
            ],
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($matricula->fresh()->modalidad)->toBe(Modalidad::Modular)
        ->and($matricula->fresh()->plan_estudio_id)->toBe($planModular->id);
});

it('valida el correo y el carnet duplicados al crear un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $existente = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Duplicado', 'Usuario'),
            'ci' => $existente->ci ?? '1234567',
            'telefono' => null,
            'email' => $existente->email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Estudiante->value,
        ])
        ->assertSessionHasErrors(['email']);
});

it('rechaza un teléfono duplicado al crear un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $existente = User::factory()->create(['telefono' => '70012345']);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Teléfono', 'Duplicado'),
            'ci' => '8765531',
            'telefono' => $existente->telefono,
            'email' => 'telefono.duplicado@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['telefono' => 'Ya existe un usuario registrado con ese teléfono.']);

    expect(User::where('email', 'telefono.duplicado@academica.test')->exists())->toBeFalse();
});

it('rechaza un teléfono duplicado al actualizar un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = User::factory()->create(['telefono' => '70011111']);
    $otro = User::factory()->create(['telefono' => '70022222']);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '8765532',
            'telefono' => $otro->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['telefono' => 'Ya existe un usuario registrado con ese teléfono.']);

    expect($usuario->fresh()->telefono)->toBe('70011111');
});

it('permite conservar el teléfono propio al actualizar un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = User::factory()->create(['telefono' => '70033333']);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '8765533',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($usuario->fresh()->telefono)->toBe('70033333');
});

it('conserva el teléfono más antiguo y limpia los duplicados antes de crear el índice', function () {
    Schema::table('users', function (Blueprint $table) {
        $table->dropUnique(['telefono']);
    });

    $primero = User::factory()->create(['telefono' => '70012345']);
    $segundo = User::factory()->create(['telefono' => '70012345']);
    $tercero = User::factory()->create(['telefono' => '70012345']);
    $sinTelefono = User::factory()->create(['telefono' => '   ']);

    $migracion = require database_path('migrations/2026_11_26_045503_add_unique_to_telefono_in_users_table.php');
    $migracion->up();

    expect($primero->fresh()->telefono)->toBe('70012345')
        ->and($segundo->fresh()->telefono)->toBeNull()
        ->and($tercero->fresh()->telefono)->toBeNull()
        ->and($sinTelefono->fresh()->telefono)->toBeNull();
});

it('impide guardar teléfonos duplicados directamente en la base de datos', function () {
    $telefono = '70044444';

    User::factory()->create(['telefono' => $telefono]);

    expect(fn () => User::factory()->create(['telefono' => $telefono]))
        ->toThrow(QueryException::class);
});

it('rechaza nombres con números al crear un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Usuario 123', 'Valido'),
            'ci' => '8765509',
            'telefono' => null,
            'email' => 'nombre.no.valido@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['nombres' => 'Los nombres solo pueden contener letras.']);

    expect(User::where('email', 'nombre.no.valido@academica.test')->exists())->toBeFalse();
});

it('rechaza actualizar el apellido con números', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Docente);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre('Docente', 'Apellido 456'),
            'ci' => $usuario->ci ?? '8765512',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['primer_apellido' => 'El primer apellido solo puede contener letras.']);

    expect($usuario->fresh()->name)->not->toBe('Docente Apellido 456');
});

it('rechaza un carnet de identidad con letras al crear un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('CI', 'No Numérico'),
            'ci' => 'ABC123',
            'telefono' => null,
            'email' => 'ci.no.numerico@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['ci' => 'El carnet de identidad solo puede contener números.']);

    expect(User::where('email', 'ci.no.numerico@academica.test')->exists())->toBeFalse();
});

it('rechaza actualizar el carnet de identidad con letras', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Docente);
    $usuario->update(['ci' => '1234567']);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => '12A4567',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['ci' => 'El carnet de identidad solo puede contener números.']);

    expect($usuario->fresh()->ci)->toBe('1234567');
});

it('rechaza un teléfono con caracteres no numéricos al crear un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Teléfono', 'No Numérico'),
            'ci' => '8765510',
            'telefono' => '700-12345',
            'email' => 'telefono.no.numerico@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['telefono' => 'El teléfono solo puede contener números.']);

    expect(User::where('email', 'telefono.no.numerico@academica.test')->exists())->toBeFalse();
});

it('rechaza actualizar el teléfono con caracteres no numéricos', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Docente);
    $usuario->update(['telefono' => '70012345']);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '8765511',
            'telefono' => '700 12345',
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['telefono' => 'El teléfono solo puede contener números.']);

    expect($usuario->fresh()->telefono)->toBe('70012345');
});

it('exige al menos 6 dígitos y sin espacios en el carnet de identidad', function (string $ci, string $mensaje) {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Carnet', 'Inválido'),
            'ci' => $ci,
            'telefono' => null,
            'email' => 'ci.invalido@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['ci' => $mensaje]);
})->with([
    'menos de 6 dígitos' => ['12345', 'El carnet de identidad debe tener al menos 6 dígitos.'],
    'con espacios' => ['123 456', 'El carnet de identidad solo puede contener números.'],
]);

it('muestra un mensaje específico según el error del correo', function (string $email, string $mensaje) {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Correo', 'Inválido'),
            'ci' => '8765520',
            'telefono' => null,
            'email' => $email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['email' => $mensaje]);
})->with([
    'dos arrobas' => ['usuario@@academica.com', 'Escribe el correo con un solo @, por ejemplo nombre@dominio.com.'],
    'sin arroba' => ['usuario.academica.com', 'Escribe el correo con un solo @, por ejemplo nombre@dominio.com.'],
    'con espacio' => ['usuario nombre@academica.com', 'El correo no debe contener espacios y debe tener texto antes y después de @.'],
    'sin punto en el dominio' => ['usuario@academica', 'El dominio del correo debe incluir un punto, por ejemplo dominio.com.'],
    'puntos consecutivos' => ['usuario@academica..com', 'El dominio del correo no debe empezar ni terminar con punto, ni tener puntos consecutivos.'],
    'una sola letra después del punto' => ['usuario@academica.c', 'Después del punto del dominio deben ir al menos 2 letras, por ejemplo .com.'],
]);

it('muestra un único mensaje cuando el correo está vacío', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Correo', 'Vacío'),
            'ci' => '8765522',
            'telefono' => null,
            'email' => '',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['email' => 'El campo correo electrónico es obligatorio.']);

    expect(session('errors')->get('email'))->toBe([
        'El campo correo electrónico es obligatorio.',
    ]);
});

it('exige al menos 8 dígitos y sin espacios en el teléfono', function (string $telefono, string $mensaje) {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->post('/admin/usuarios', [
            ...datosNombre('Teléfono', 'Inválido'),
            'ci' => '8765521',
            'telefono' => $telefono,
            'email' => 'telefono.invalido@academica.test',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors(['telefono' => $mensaje]);
})->with([
    'menos de 8 dígitos' => ['1234567', 'El teléfono debe tener al menos 8 dígitos.'],
    'con espacios' => ['1234 5678', 'El teléfono solo puede contener números.'],
]);

it('permite al administrador cambiar el rol de un usuario', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$usuario->id}", [
            ...datosNombre(),
            'ci' => $usuario->ci ?? '7654321',
            'telefono' => $usuario->telefono,
            'email' => $usuario->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertRedirect('/admin/usuarios');

    expect($usuario->fresh()->hasRole(Rol::Docente->value))->toBeTrue()
        ->and($usuario->fresh()->hasRole(Rol::Estudiante->value))->toBeFalse();
});

it('impide quitar el rol al último administrador', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$admin->id}", [
            ...datosNombre(),
            'ci' => $admin->ci ?? '1111111',
            'telefono' => $admin->telefono,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Docente->value,
            'activo' => '1',
        ])
        ->assertSessionHasErrors('rol');

    expect($admin->fresh()->esAdministrador())->toBeTrue();
});

it('impide desactivar al último administrador', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->put("/admin/usuarios/{$admin->id}", [
            ...datosNombre(),
            'ci' => $admin->ci ?? '2222222',
            'telefono' => $admin->telefono,
            'email' => $admin->email,
            'password' => '',
            'password_confirmation' => '',
            'rol' => Rol::Administrador->value,
        ])
        ->assertSessionHasErrors('activo');

    expect($admin->fresh()->activo)->toBeTrue();
});

it('impide que un administrador elimine su propia cuenta', function () {
    $admin = usuarioConRol(Rol::Administrador);

    $this->actingAs($admin)
        ->delete("/admin/usuarios/{$admin->id}")
        ->assertForbidden();

    expect(User::find($admin->id))->not->toBeNull();
});

it('elimina un usuario sin historial académico', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $usuario = usuarioConRol(Rol::Estudiante);

    $this->actingAs($admin)
        ->delete("/admin/usuarios/{$usuario->id}")
        ->assertRedirect('/admin/usuarios');

    expect(User::find($usuario->id))->toBeNull();
});

it('impide eliminar un docente con calificaciones registradas', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);

    $curso = Curso::factory()->create([
        'docente_id' => $docente->id,
        'periodo_id' => Periodo::factory()->vigente()->create()->id,
    ]);

    $inscripcion = Inscripcion::factory()->create([
        'curso_id' => $curso->id,
        'user_id' => usuarioConRol(Rol::Estudiante)->id,
    ]);

    $evaluacion = $curso->evaluaciones()->create(['nombre' => 'Primer parcial', 'ponderacion' => 100]);

    Calificacion::factory()->create([
        'inscripcion_id' => $inscripcion->id,
        'curso_evaluacion_id' => $evaluacion->id,
        'registrado_por' => $docente->id,
    ]);

    $this->actingAs($admin)
        ->delete("/admin/usuarios/{$docente->id}")
        ->assertSessionHasErrors('usuario');

    expect(User::find($docente->id))->not->toBeNull();
});

it('lista y filtra usuarios por rol', function () {
    $admin = usuarioConRol(Rol::Administrador);
    $docente = usuarioConRol(Rol::Docente);
    $docente->update(['name' => 'Docente Filtrado']);
    $estudiante = usuarioConRol(Rol::Estudiante);
    $estudiante->update(['name' => 'Estudiante Oculto']);

    $this->actingAs($admin)
        ->get('/admin/usuarios?rol=docente')
        ->assertOk()
        ->assertSee('Docente Filtrado')
        ->assertDontSee('Estudiante Oculto');
});

it('impide que un no administrador gestione usuarios', function () {
    $this->actingAs(usuarioConRol(Rol::Docente))
        ->get('/admin/usuarios')
        ->assertForbidden();

    $this->actingAs(usuarioConRol(Rol::Estudiante))
        ->post('/admin/usuarios', [])
        ->assertForbidden();
});

it('impide iniciar sesión a un usuario desactivado', function () {
    $usuario = usuarioConRol(Rol::Estudiante);
    $usuario->update(['activo' => false]);

    $this->post('/login', [
        'email' => $usuario->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
