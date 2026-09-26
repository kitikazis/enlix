<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Único FormRequest del proyecto (el resto valida inline en el controller,
 * ver docblock de Admin\ProductosController) - se pidió explícitamente
 * para este formulario porque junta bastantes reglas condicionales
 * (factura/boleta, envío/recojo, DNI/RUC) y el checkbox de términos.
 */
class CheckoutCrearRequest extends FormRequest
{
    /** El checkout es de invitado, sin autenticación. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60'],
            'last_name' => ['required', 'string', 'max:60'],
            'email' => ['required', 'email:rfc'],
            'telefono' => ['required', 'string', 'regex:/^[0-9+ ]{6,20}$/'],
            'tipo_documento' => ['required', 'in:DNI,RUC,CE'],
            // 8 digitos para DNI, 11 para RUC - CE no tiene formato fijo, no se valida.
            'numero_documento' => ['required', 'string', 'max:15', function ($attribute, $value, $fail) {
                $tipo = $this->input('tipo_documento');

                if ($tipo === 'DNI' && ! preg_match('/^\d{8}$/', (string) $value)) {
                    $fail('El DNI debe tener 8 dígitos.');
                }

                if ($tipo === 'RUC' && ! preg_match('/^\d{11}$/', (string) $value)) {
                    $fail('El RUC debe tener 11 dígitos.');
                }
            }],
            'tipo_comprobante' => ['required', 'in:boleta,factura'],
            'razon_social' => ['required_if:tipo_comprobante,factura', 'nullable', 'string', 'max:150'],
            'metodo_entrega' => ['required', 'in:envio,recojo'],
            'direccion' => ['required_if:metodo_entrega,envio', 'nullable', 'string', 'max:255'],
            'distrito' => ['required_if:metodo_entrega,envio', 'nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'terminos' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'terminos.accepted' => 'Debes aceptar los términos y condiciones para continuar.',
        ];
    }
}
