<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Modalidad;
use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUsuarioRequest;
use App\Http\Requests\Admin\UpdateUsuarioRequest;
use App\Models\Carrera;
use App\Models\User;
use App\Services\UsuarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(private readonly UsuarioService $usuarios) {}

    /**
     * RF09: listado de usuarios con búsqueda y filtro por rol.
     */
    public function index(Request $request): View
    {
        $filtros = [
            'busqueda' => trim((string) $request->query('busqueda')),
            'rol' => (string) $request->query('rol'),
        ];

        if (! in_array($filtros['rol'], Rol::valores(), true)) {
            $filtros['rol'] = '';
        }

        $usuarios = User::query()
            ->with(['roles', 'matriculas.carrera'])
            ->when($filtros['busqueda'] !== '', function ($query) use ($filtros) {
                $termino = "%{$filtros['busqueda']}%";

                $query->where(function ($sub) use ($termino) {
                    $sub->where('name', 'ilike', $termino)
                        ->orWhere('email', 'ilike', $termino)
                        ->orWhere('ci', 'ilike', $termino);
                });
            })
            ->when($filtros['rol'] !== '', fn ($query) => $query->role($filtros['rol']))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'filtros' => $filtros,
            'roles' => Rol::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.usuarios.create', [
            'roles' => Rol::cases(),
            'carreras' => Carrera::query()->where('activo', true)->orderBy('nombre')->get(),
            'modalidades' => Modalidad::cases(),
        ]);
    }

    public function store(StoreUsuarioRequest $request): RedirectResponse
    {
        $usuario = $this->usuarios->crear($request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', "Usuario {$usuario->name} creado correctamente.");
    }

    public function edit(User $usuario): View
    {
        $usuario->load(['roles', 'matriculas.carrera', 'certificadosDocentes']);

        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Rol::cases(),
            'carreras' => Carrera::query()->where('activo', true)->orderBy('nombre')->get(),
            'modalidades' => Modalidad::cases(),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario): RedirectResponse
    {
        $this->usuarios->actualizar($usuario, $request->validated());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        $this->usuarios->eliminar($usuario, request()->user());

        return redirect()
            ->route('admin.usuarios.index')
            ->with('status', 'Usuario eliminado.');
    }
}
