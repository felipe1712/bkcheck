@extends('layouts.master-without-nav')
@section('title') Iniciar Sesión — AvalID @endsection

@section('content')
<div class="auth-page-wrapper pt-5" style="background: #141923; min-height: 100vh; display: flex; flex-direction: column; justify-content: center; font-family: 'Urbanist', sans-serif;">

    <!-- auth page content -->
    <div class="auth-page-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center mt-sm-4 mb-4 text-white-50">
                        <div>
                            <a href="{{ route('root') }}" class="d-inline-block auth-logo text-decoration-none">
                                <h2 class="text-white fw-bold mb-0" style="font-family: 'Rubik', sans-serif; font-size: 32px; letter-spacing: -0.02em;">
                                    Aval <span style="color: #1877f2; font-weight: 800;">ID</span>
                                </h2>
                                <div style="font-family: 'Rubik', sans-serif; font-size: 10px; font-weight: 700; letter-spacing: 0.2em; color: #56657e; text-transform: uppercase; margin-top: 2px;">
                                    LA CONFIANZA DE TU GENTE
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-5">
                    <div class="card mt-2 shadow-lg" style="background: #1b2230; border: 1px solid #2e384e; border-radius: 16px;">
                        <div class="card-body p-4">
                            <div class="text-center mt-2">
                                <h5 class="text-white fw-bold" style="font-family: 'Rubik', sans-serif;">¡Bienvenido de nuevo!</h5>
                                <p class="text-muted fs-14">Inicia sesión en la plataforma confidencial de **AvalID**.</p>
                            </div>
                            <div class="p-2 mt-4">
                                <form action="{{ route('login') }}" method="POST">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="email" class="form-label text-light">Correo Electrónico <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control bg-dark text-white border-secondary @error('email') is-invalid @enderror" value="{{ old('email') }}" id="email" name="email" placeholder="Ingresa tu correo" required autocomplete="email" autofocus style="border-color: #2e384e !important;">
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label text-light" for="password-input">Contraseña <span class="text-danger">*</span></label>
                                        <div class="position-relative auth-pass-inputgroup mb-3">
                                            <input type="password" class="form-control bg-dark text-white border-secondary password-input pe-5 @error('password') is-invalid @enderror" name="password" placeholder="Ingresa tu contraseña" id="password-input" required autocomplete="current-password" style="border-color: #2e384e !important;">
                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon" type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    <strong>{{ $message }}</strong>
                                                </span>
                                            @enderror
                                        </div>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="auth-remember-check" {{ old('remember') ? 'checked' : '' }}>
                                        <label class="form-check-label text-muted" for="auth-remember-check">Recordarme</label>
                                    </div>

                                    <div class="mt-4">
                                        <button class="btn text-white w-100 fw-bold py-3" type="submit" style="background: linear-gradient(135deg, #1877f2, #0a58ed); border: none; border-radius: 12px; font-family: 'Rubik', sans-serif;">Iniciar Sesión</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="text-center">
                        <p class="mb-0 text-muted">&copy; <script>document.write(new Date().getFullYear())</script> AvalID — La Confianza de tu Gente.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/js/pages/password-addon.init.js') }}"></script>
@endsection
