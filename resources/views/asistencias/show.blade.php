@if($asistencias->count())
    <div class="table-responsive" style="overflow-x:auto; -webkit-overflow-scrolling:touch;">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Fecha y Hora de Entrada</th>
                </tr>
            </thead>
            <tbody>
                @foreach($asistencias as $i => $asistencia)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $asistencia->fecha_hora_entrada }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info text-center">
        Todavía no tienes asistencias registradas.
    </div>
@endif
