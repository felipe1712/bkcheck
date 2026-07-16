@extends('layouts.master')
@section('title') Configuración — Organización @endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><i class="ri-settings-3-line me-2"></i>Configuración de la Organización</h4>
            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('tenant.dashboard') }}">Panel</a></li>
                    <li class="breadcrumb-item active">Configuración</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-xxl-9">

        {{-- Mensajes de éxito / error --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="ri-check-circle-line me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="ri-error-warning-line me-2"></i>{{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ── TARJETA: Términos y Condiciones de Enrolamiento ── --}}
        <div class="card">
            <div class="card-header align-items-center d-flex">
                <div class="flex-grow-1">
                    <h4 class="card-title mb-0">
                        <i class="ri-file-text-line me-2 text-primary"></i>
                        Términos y Condiciones de Enrolamiento
                    </h4>
                    <p class="text-muted fs-13 mb-0 mt-1">
                        Este texto es lo primero que verá el investigado al abrir su enlace de verificación. Debe aceptarlo expresamente antes de poder subir sus documentos.
                    </p>
                </div>
                @if($tenant->enrollment_terms_updated_at)
                    <div class="flex-shrink-0 ms-3">
                        <span class="badge bg-success-subtle text-success fs-11">
                            <i class="ri-time-line me-1"></i>
                            Actualizado {{ $tenant->enrollment_terms_updated_at->diffForHumans() }}
                        </span>
                    </div>
                @endif
            </div>
            <div class="card-body">
                <form action="{{ route('tenant.settings.update') }}" method="POST" id="settingsForm">
                    @csrf

                    {{-- Editor de T&C --}}
                    <div class="mb-3">
                        <label for="enrollment_terms" class="form-label fw-semibold">
                            Texto de Términos y Condiciones
                            <span class="text-muted fw-normal">(hasta 20,000 caracteres)</span>
                        </label>
                        <textarea
                            id="enrollment_terms"
                            name="enrollment_terms"
                            class="form-control @error('enrollment_terms') is-invalid @enderror"
                            rows="14"
                            maxlength="20000"
                            placeholder="Escribe aquí los términos y condiciones que el investigado deberá aceptar antes de subir sus documentos...
Ejemplo:
Al aceptar estos términos, usted autoriza expresamente a [Nombre de la Empresa] a recopilar y procesar sus datos personales (incluyendo imagen de su identificación oficial y fotografía) con la finalidad de verificar su identidad, en cumplimiento con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP)..."
                        >{{ old('enrollment_terms', $tenant->enrollment_terms) }}</textarea>
                        @error('enrollment_terms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="d-flex justify-content-between mt-1">
                            <div class="text-muted fs-12">
                                <i class="ri-information-line me-1"></i>
                                Si lo dejas en blanco, se usará un texto genérico de autorización conforme a la LFPDPPP.
                            </div>
                            <div class="text-muted fs-12" id="charCount">0 / 20,000</div>
                        </div>
                    </div>

                    {{-- Previsualización --}}
                    <div class="mb-4">
                        <button type="button" class="btn btn-soft-info btn-sm" id="btnPreview">
                            <i class="ri-eye-line me-1"></i> Previsualizar como lo verá el investigado
                        </button>
                    </div>

                    {{-- Panel de previsualización (oculto) --}}
                    <div id="previewPanel" class="d-none mb-4">
                        <div class="border rounded-3 p-3 bg-dark" style="max-height:300px; overflow-y:auto; font-size:13px; color:#adb5bd; white-space:pre-wrap; line-height:1.7;">
                            <div class="text-muted fs-11 text-uppercase fw-semibold mb-2">Vista previa — pantalla del investigado</div>
                            <div id="previewText"></div>
                        </div>
                    </div>

                    <div class="hstack gap-2">
                        <button type="submit" class="btn btn-primary" id="btnSave">
                            <i class="ri-save-line me-1"></i> Guardar Términos y Condiciones
                        </button>
                        @if($tenant->hasEnrollmentTerms())
                            <button type="button" class="btn btn-soft-danger btn-sm" id="btnClear">
                                <i class="ri-delete-bin-line me-1"></i> Borrar texto (usar texto genérico)
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- ── INFO TARJETA: Cómo funciona el enrolamiento ── --}}
        <div class="card border border-dashed">
            <div class="card-body">
                <h6 class="card-title text-uppercase fs-11 fw-semibold text-muted mb-3">
                    <i class="ri-information-line me-1"></i> ¿Cómo funciona el enrolamiento?
                </h6>
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="text-center p-2">
                            <div style="font-size:28px; margin-bottom:8px;">🔗</div>
                            <div class="fw-semibold fs-13">1. Generar enlace</div>
                            <div class="text-muted fs-12">Se crea al registrar el sujeto. Válido 24 horas.</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-2">
                            <div style="font-size:28px; margin-bottom:8px;">📱</div>
                            <div class="fw-semibold fs-13">2. Compartir</div>
                            <div class="text-muted fs-12">El analista comparte el enlace por WhatsApp o SMS.</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-2">
                            <div style="font-size:28px; margin-bottom:8px;">📸</div>
                            <div class="fw-semibold fs-13">3. El investigado sube</div>
                            <div class="text-muted fs-12">Acepta T&C, foto de INE frente, reverso y selfie.</div>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-2">
                            <div style="font-size:28px; margin-bottom:8px;">✅</div>
                            <div class="fw-semibold fs-13">4. OCR automático</div>
                            <div class="text-muted fs-12">El sistema procesa las imágenes y actualiza el expediente.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const textarea  = document.getElementById('enrollment_terms');
    const charCount = document.getElementById('charCount');
    const btnPreview  = document.getElementById('btnPreview');
    const previewPanel= document.getElementById('previewPanel');
    const previewText = document.getElementById('previewText');
    const btnClear    = document.getElementById('btnClear');

    // Contador de caracteres
    function updateCount() {
        const n = textarea.value.length;
        charCount.textContent = `${n.toLocaleString()} / 20,000`;
        charCount.classList.toggle('text-danger', n > 19000);
    }
    textarea.addEventListener('input', updateCount);
    updateCount();

    // Previsualización
    btnPreview.addEventListener('click', function () {
        const text = textarea.value.trim() || 'Al continuar, usted autoriza expresamente el uso de sus datos personales (incluyendo imágenes de su identificación oficial y fotografía personal) para fines de verificación de identidad, en cumplimiento con la Ley Federal de Protección de Datos Personales en Posesión de los Particulares (LFPDPPP).';
        previewText.textContent = text;
        previewPanel.classList.toggle('d-none');
        this.innerHTML = previewPanel.classList.contains('d-none')
            ? '<i class="ri-eye-line me-1"></i> Previsualizar como lo verá el investigado'
            : '<i class="ri-eye-off-line me-1"></i> Ocultar previsualización';
    });

    // Borrar texto (limpia el textarea para usar fallback)
    if (btnClear) {
        btnClear.addEventListener('click', function () {
            if (confirm('¿Deseas borrar el texto personalizado? Se usará el texto genérico de autorización.')) {
                textarea.value = '';
                updateCount();
            }
        });
    }
});
</script>
@endsection
