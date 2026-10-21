<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Enlix - Iniciar sesión</title>
    <link rel="shortcut icon" href="{{ asset('assets/img/favicon.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    @vite('resources/css/admin.css')
</head>
<body class="flex h-full items-center justify-center bg-page font-sans text-text-primary antialiased">

    <div class="w-full max-w-sm px-4">
        <div class="mb-6 flex items-center justify-center gap-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-accent text-sm font-semibold text-on-accent" aria-hidden="true">e</span>
            <span class="text-base font-semibold text-text-primary">enlix</span>
            <span class="ml-1 rounded-full bg-sidebar/10 px-2 py-0.5 text-xs font-medium text-text-secondary">Admin</span>
        </div>

        <main class="rounded-card border border-border bg-card p-6 shadow-card">
            <h1 class="mb-4 text-center text-lg font-semibold text-text-primary">Panel Enlix</h1>

            @if ($errors->any())
                <div class="mb-4 rounded-nav bg-rechazado-bg px-3 py-2 text-sm text-rechazado-text" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.store') }}" class="flex flex-col gap-4">
                @csrf
                <div>
                    <label for="login-email" class="field-label">Usuario</label>
                    <input id="login-email" type="text" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                           class="field-input" @error('email') aria-invalid="true" @enderror>
                </div>
                <div>
                    <label for="login-password" class="field-label">Contraseña</label>
                    <input id="login-password" type="password" name="password" required autocomplete="current-password"
                           class="field-input" @error('password') aria-invalid="true" @enderror>
                </div>
                <button type="submit" class="btn btn-primary mt-1 w-full">
                    Entrar
                </button>
            </form>
        </main>
    </div>

</body>
</html>
