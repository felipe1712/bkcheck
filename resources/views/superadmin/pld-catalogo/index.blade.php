@extends('layouts.master')
@section('title') Catálogo Global de Fuentes PLD/EBR @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Catálogo Global de Fuentes PLD/EBR (Super Admin)</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('superadmin.dashboard') }}">Super Admin</a></li>
                    <li class="breadcrumb-item active">Catálogo PLD</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row">
    <div class="col-lg-12">
        <div class="card border-primary border-opacity-25">
            <div class="card-header bg-soft-primary">
                <h5 class="card-title mb-0">Regla de Catálogo (Documento 3 §3 y §8)</h5>
            </div>
            <div class="card-body">
                <p class="mb-2 text-muted">
                    Toda fuente que alimente el Motor Determinístico de Expedientes PLD/EBR debe contar con
                    <strong>Categoría</strong> (<code>IDENTIDAD</code>, <code>PLD</code>, <code>CONSISTENCIA</code>, <code>REPUTACIONAL</code>)
                    y <strong>Criticidad</strong> (<code>ALTA</code>, <code>MEDIA</code>, <code>BAJA</code>) en este catálogo global.
                    Si un conector no está activo o registrado aquí, el motor lo excluye del cálculo y lo reporta como
                    <code>"fuente no catalogada, requiere clasificación manual"</code>.
                </p>

                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Clave Conector (<code>fuente_key</code>)</th>
                            <th>Nombre Oficial de la Fuente</th>
                            <th>Categoría</th>
                            <th>Criticidad (Falla HTTP)</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fuentes as $f)
                        <tr>
                            <form method="POST" action="{{ route('superadmin.pld-catalogo.update', $f->id) }}">
                                @csrf
                                @method('PUT')
                                <td><code>{{ $f->fuente_key }}</code></td>
                                <td>
                                    <input type="text" name="nombre" class="form-control form-control-sm" value="{{ $f->nombre }}" required>
                                </td>
                                <td>
                                    <select name="categoria" class="form-select form-select-sm">
                                        @foreach(['IDENTIDAD', 'PLD', 'CONSISTENCIA', 'REPUTACIONAL'] as $cat)
                                            <option value="{{ $cat }}" @selected($f->categoria === $cat)>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="criticidad" class="form-select form-select-sm">
                                        @foreach(['ALTA', 'MEDIA', 'BAJA'] as $crit)
                                            <option value="{{ $crit }}" @selected($f->criticidad === $crit)>{{ $crit }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="activo" value="1" class="form-check-input" @checked($f->activo)>
                                </td>
                                <td>
                                    <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                                </td>
                            </form>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
