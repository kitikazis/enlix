<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Formulario público del Libro de Reclamaciones (ver ReclamoController). */
class ReclamoCrearRequest extends FormRequest
{
    /** El libro de reclamaciones es publico, sin autenticacion. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_consumidor' => ['required', 'string', 'max:150'],
            'tipo_documento_consumidor' => ['required', 'in:DNI,RUC,CE'],
            'numero_documento_consumidor' => ['required', 'string', 'max:15', function ($attribute, $value, $fail) {
                $tipo = $this->input('tipo_documento_consumidor');

                if ($tipo === 'DNI' && ! preg_match('/^\d{8}$/', (string) $value)) {
                    $fail('El DNI debe tener 8 dígitos.');
                }

                if ($tipo === 'RUC' && ! preg_match('/^\d{11}$/', (string) $value)) {
                    $fail('El RUC debe tener 11 dígitos.');
                }
            }],
            'domicilio_consumidor' => ['required', 'string', 'max:255'],
            'telefono_consumidor' => ['required', 'string', 'regex:/^[0-9+ ]{6,20}$/'],
            'correo_consumidor' => ['required', 'email:rfc'],

            'es_menor_edad' => ['nullable', 'boolean'],
            'nombre_padre_tutor' => ['required_if:es_menor_edad,1', 'nullable', 'string', 'max:150'],
            'documento_padre_tutor' => ['required_if:es_menor_edad,1', 'nullable', 'string', 'max:15'],

            'tipo_bien' => ['required', 'in:producto,servicio'],
            'descripcion_bien' => ['required', 'string', 'max:255'],
            'monto' => ['nullable', 'numeric', 'min:0', 'max:999999'],

            'tipo' => ['required', 'in:reclamo,queja'],
            'detalle' => ['required', 'string', 'max:2000'],
            'pedido_consumidor' => ['required', 'string', 'max:2000'],

            'acepta_privacidad' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'acepta_privacidad.accepted' => 'Debes aceptar la política de privacidad para continuar.',
            'nombre_padre_tutor.required_if' => 'El nombre del padre o tutor es obligatorio si el consumidor es menor de edad.',
            'documento_padre_tutor.required_if' => 'El documento del padre o tutor es obligatorio si el consumidor es menor de edad.',
        ];
    }
}
