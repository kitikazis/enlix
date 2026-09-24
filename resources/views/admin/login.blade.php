@extends('layouts.admin')

@section('content')
<div class="row justify-content-center">
  <div class="col-sm-8 col-md-5 col-lg-4">
    <div class="card shadow-sm mt-5">
      <div class="card-body p-4">
        <h1 class="h4 mb-4 text-center">Panel Enlix</h1>

        @if ($errors->any())
          <div class="alert alert-danger py-2">
            {{ $errors->first() }}
          </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
          @csrf
          <div class="mb-3">
            <label class="form-label">Usuario</label>
            <input type="text" name="email" value="{{ old('email') }}" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
