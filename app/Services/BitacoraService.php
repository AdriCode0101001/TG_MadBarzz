<?php

namespace App\Services;

use App\Models\Administrador;
use App\Models\Aviso;
use App\Models\CalendarioMusculo;
use App\Models\Cliente;
use App\Models\ConfiguracionImagen;
use App\Models\Descuento;
use App\Models\Entrenador;
use App\Models\HorarioEntrenador;
use App\Models\Huella;
use App\Models\Membresia;
use App\Models\MembresiaCliente;
use App\Models\Pago;
use App\Models\Persona;
use App\Models\Promocion;
use App\Models\QrPago;
use App\Models\Recepcionista;
use App\Models\Rutina;
use App\Models\RutinaEjercicio;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * Bitácora de Auditoría — Etapa 3: arma la pantalla legible a partir de la
 * tabla activity_log (Etapas 1 y 2). Traduce cada fila técnica a una frase
 * en español y resuelve el nombre real del causer SIN N+1 — batch de a lo
 * sumo 4 consultas (una por tabla de personal) por página, sin importar
 * cuántas filas tenga la página.
 */
class BitacoraService
{
    private const POR_PAGINA = 20;

    private const VERBOS = [
        'created'  => 'creó',
        'updated'  => 'editó',
        'deleted'  => 'eliminó',
        'restored' => 'restauró',
    ];

    /** subject_type => [artículo, nombre en español]. */
    private const ENTIDADES = [
        Cliente::class             => ['el', 'cliente'],
        MembresiaCliente::class    => ['la', 'membresía del cliente'],
        Pago::class                => ['el', 'pago'],
        User::class                => ['el', 'usuario'],
        Persona::class             => ['la', 'persona'],
        Administrador::class       => ['el', 'administrador'],
        Recepcionista::class       => ['la', 'recepcionista'],
        Entrenador::class          => ['el', 'entrenador'],
        Membresia::class           => ['el', 'tipo de membresía'],
        Descuento::class           => ['el', 'descuento'],
        Promocion::class           => ['la', 'promoción'],
        Rutina::class              => ['la', 'rutina'],
        RutinaEjercicio::class     => ['el', 'ejercicio de la rutina'],
        CalendarioMusculo::class   => ['el', 'calendario de músculos'],
        HorarioEntrenador::class   => ['el', 'horario del entrenador'],
        Aviso::class               => ['el', 'aviso'],
        QrPago::class              => ['el', 'QR de pago'],
        ConfiguracionImagen::class => ['la', 'imagen de la home'],
        // Borrado registrado a mano en HuellaController (forceDelete() no
        // dispara el 'deleted' automático de LogsActivity) — nunca incluye
        // el template en sí, solo dedo/id_cliente/version_formato.
        Huella::class              => ['la', 'huella dactilar'],
    ];

    /** Nombre técnico de campo => etiqueta legible en español. */
    private const CAMPOS = [
        'dias_asistidos'      => 'Días asistidos',
        'estado'              => 'Estado',
        'id_persona'          => 'Persona (ID)',
        'id_usuario'          => 'Usuario (ID)',
        'id_sede'             => 'Sede (ID)',
        'id_cliente'          => 'Cliente (ID)',
        'id_membresia'        => 'Membresía (ID)',
        'id_membresia_cliente'=> 'Membresía de cliente (ID)',
        'id_descuento'        => 'Descuento (ID)',
        'id_administrador'    => 'Administrador (ID)',
        'id_grupo_muscular'   => 'Grupo muscular (ID)',
        'id_rutina'           => 'Rutina (ID)',
        'id_ejercicio'        => 'Ejercicio (ID)',
        'id_entrenador'       => 'Entrenador (ID)',
        'fecha_inicio'        => 'Fecha de inicio',
        'fecha_fin'           => 'Fecha de fin',
        'nombre_descuento'    => 'Nombre del descuento',
        'descuento'           => 'Descuento (%)',
        'precio_final'        => 'Precio final',
        'saldo_pendiente'     => 'Saldo pendiente',
        'fecha_limite_saldo'  => 'Fecha límite de saldo',
        'name'                => 'Nombre de usuario',
        'email'               => 'Correo electrónico',
        'rol_id'              => 'Rol (ID)',
        'nombre_completo'     => 'Nombre completo',
        'apellido_paterno'    => 'Apellido paterno',
        'apellido_materno'    => 'Apellido materno',
        'ci'                  => 'CI',
        'telefono'            => 'Teléfono',
        'telegram_chat_id'    => 'Telegram (chat ID)',
        'genero'              => 'Género',
        'fecha_nacimiento'    => 'Fecha de nacimiento',
        'nivel_acceso'        => 'Nivel de acceso',
        'area_responsable'    => 'Área responsable',
        'turno'               => 'Turno',
        'punto_atencion'      => 'Punto de atención',
        'especialidad'        => 'Especialidad',
        'experiencia'         => 'Experiencia',
        'disponibilidad'      => 'Disponibilidad',
        'tipo_membresia'      => 'Tipo de membresía',
        'precio'              => 'Precio',
        'nombre'              => 'Nombre',
        'porcentaje'          => 'Porcentaje',
        'activo'              => 'Activo',
        'titulo'              => 'Título',
        'descripcion'         => 'Descripción',
        'imagen'              => 'Imagen',
        'fecha'               => 'Fecha',
        'series'              => 'Series',
        'repeticiones'        => 'Repeticiones',
        'orden'               => 'Orden',
        'nota'                => 'Nota',
        'media_propio'        => 'Archivo propio',
        'dia_semana'          => 'Día de la semana',
        'hora_inicio'         => 'Hora de inicio',
        'hora_fin'            => 'Hora de fin',
        'texto'               => 'Texto',
        'fecha_vencimiento'   => 'Fecha de vencimiento',
        'clave'               => 'Clave',
        'ruta'                => 'Ruta',
        'posicion'            => 'Posición',
        'monto'               => 'Monto',
        'fecha_pago'          => 'Fecha de pago',
        'metodo_pago'         => 'Método de pago',
        'observaciones'       => 'Observaciones',
        'imagen_qr'           => 'Comprobante (imagen)',
        'version_formato'     => 'Versión del formato (huella)',
    ];

    /** Tablas de personal a revisar, en orden, para resolver el nombre real de un User (igual criterio que User::nombreCompletoConApellido()). */
    private const TABLAS_PERSONAL = ['administradores', 'recepcionistas', 'entrenadores', 'clientes'];

    /**
     * @return array<string, string> subject_type (FQCN) => etiqueta legible para el <select> de filtro.
     */
    public function opcionesEntidades(): array
    {
        return collect(self::ENTIDADES)
            ->mapWithKeys(fn ($info, $fqcn) => [$fqcn => Str::ucfirst($info[1])])
            ->sort()
            ->all();
    }

    /**
     * Usuarios que aparecen como causer en la bitácora (para el <select> "Usuario").
     * No es la tabla completa de activity_log: solo distinct(causer_id), tabla chica.
     *
     * @return Collection<int, array{id:int, etiqueta:string}>
     */
    public function opcionesCausers(): Collection
    {
        $ids = Activity::query()->whereNotNull('causer_id')->distinct()->pluck('causer_id')->all();

        if (empty($ids)) {
            return collect();
        }

        $usuarios = User::with('rol')->whereIn('id', $ids)->get();
        $nombres  = $this->nombresPorUsuario($ids);

        return $usuarios
            ->map(fn (User $u) => [
                'id'       => $u->id,
                'etiqueta' => ($nombres[$u->id] ?? $u->name) . ' (' . ($u->rol->nombre ?? '—') . ')',
            ])
            ->sortBy('etiqueta')
            ->values();
    }

    /**
     * @param array{causer_id?: string|int|null, evento?: ?string, entidad?: ?string, desde?: ?string, hasta?: ?string} $filtros
     */
    public function paginar(array $filtros): LengthAwarePaginator
    {
        $query = Activity::query()->orderByDesc('created_at');

        if (!empty($filtros['causer_id'])) {
            $query->where('causer_id', $filtros['causer_id']);
        }
        if (!empty($filtros['evento'])) {
            $query->where('event', $filtros['evento']);
        }
        if (!empty($filtros['entidad'])) {
            $query->where('subject_type', $filtros['entidad']);
        }
        if (!empty($filtros['desde'])) {
            $query->whereDate('created_at', '>=', $filtros['desde']);
        }
        if (!empty($filtros['hasta'])) {
            $query->whereDate('created_at', '<=', $filtros['hasta']);
        }

        $paginator = $query->paginate(self::POR_PAGINA)->withQueryString();

        // Resolver TODOS los nombres de causer de esta página en un solo lote
        // (4 consultas fijas, no una por fila) antes de armar las frases.
        $items   = $paginator->getCollection();
        $userIds = $items->pluck('causer_id')->filter()->unique()->values()->all();

        $usuarios = empty($userIds) ? collect() : User::with('rol')->whereIn('id', $userIds)->get()->keyBy('id');
        $nombres  = $this->nombresPorUsuario($userIds);

        $paginator->setCollection(
            $items->map(fn (Activity $actividad) => $this->decorar($actividad, $usuarios, $nombres))
        );

        return $paginator;
    }

    /**
     * @param array<int> $userIds
     * @return array<int, string> id de User => "Nombre Apellido"
     */
    private function nombresPorUsuario(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $nombres = [];

        foreach (self::TABLAS_PERSONAL as $tabla) {
            DB::table($tabla)
                ->join('personas', 'personas.id_persona', '=', "{$tabla}.id_persona")
                ->whereIn("{$tabla}.id_usuario", $userIds)
                ->get(["{$tabla}.id_usuario as user_id", 'personas.nombre_completo', 'personas.apellido_paterno'])
                ->each(function ($fila) use (&$nombres) {
                    if (!isset($nombres[$fila->user_id])) {
                        $nombres[$fila->user_id] = trim($fila->nombre_completo . ' ' . ($fila->apellido_paterno ?? ''));
                    }
                });
        }

        return $nombres;
    }

    /**
     * @param Collection<int, User> $usuarios
     * @param array<int, string> $nombres
     * @return array<string, mixed>
     */
    private function decorar(Activity $actividad, Collection $usuarios, array $nombres): array
    {
        $usuario = $actividad->causer_id ? $usuarios->get($actividad->causer_id) : null;

        if ($usuario) {
            $nombre = $nombres[$usuario->id] ?? $usuario->name;
            $rol    = $usuario->rol->nombre ?? null;
            $quien  = $rol ? "{$nombre} ({$rol})" : $nombre;
        } else {
            $quien = 'Sistema (automático)';
        }

        $verbo = self::VERBOS[$actividad->event] ?? $actividad->event;
        [$articulo, $nombreEntidad] = self::ENTIDADES[$actividad->subject_type]
            ?? ['el', Str::lower(class_basename($actividad->subject_type))];

        return [
            'id'      => $actividad->id,
            'quien'   => $quien,
            'evento'  => $actividad->event,
            'frase'   => "{$quien} {$verbo} {$articulo} {$nombreEntidad} #{$actividad->subject_id}",
            'fecha'   => optional($actividad->created_at)->format('d/m/Y H:i'),
            'detalle' => $this->detalle($actividad),
        ];
    }

    /**
     * @return array<int, array{campo:string, valor?:string, antes?:string, despues?:string}>
     */
    private function detalle(Activity $actividad): array
    {
        $props = $actividad->properties?->toArray() ?? [];
        $attrs = $props['attributes'] ?? [];
        $old   = $props['old'] ?? [];

        // 'deleted': Spatie guarda los valores que tenía bajo 'old' (no hay 'attributes').
        if ($actividad->event === 'deleted') {
            return collect($old)
                ->map(fn ($valor, $campo) => ['campo' => $this->labelCampo($campo), 'valor' => $this->formatearValor($valor)])
                ->values()->all();
        }

        // 'created' (o cualquier evento sin 'old'): solo hay valores nuevos.
        if (empty($old)) {
            return collect($attrs)
                ->map(fn ($valor, $campo) => ['campo' => $this->labelCampo($campo), 'valor' => $this->formatearValor($valor)])
                ->values()->all();
        }

        // 'updated': antes → después, campo por campo.
        return collect($attrs)
            ->map(fn ($valor, $campo) => [
                'campo'   => $this->labelCampo($campo),
                'antes'   => $this->formatearValor($old[$campo] ?? null),
                'despues' => $this->formatearValor($valor),
            ])
            ->values()->all();
    }

    private function labelCampo(string $campo): string
    {
        return self::CAMPOS[$campo] ?? Str::ucfirst(str_replace('_', ' ', $campo));
    }

    private function formatearValor(mixed $valor): string
    {
        if (is_null($valor)) {
            return '—';
        }
        if (is_bool($valor)) {
            return $valor ? 'Sí' : 'No';
        }
        if (is_array($valor)) {
            return implode(', ', $valor) ?: '—';
        }

        return (string) $valor;
    }
}
