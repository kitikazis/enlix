<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * La mayoría de formularios valida inline en el controller; este (y
 * ProductoGuardarRequest) se pidieron explícitamente como FormRequest
 * porque juntan bastantes reglas condicionales
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
            // Con letras: un nombre como "123" no pasa el antifraude de Izipay
            // ni sirve para emitir la boleta.
            'first_name' => ['required', 'string', 'max:60', 'regex:/^\p{L}[\p{L}\s.\'-]*$/u'],
            'last_name' => ['required', 'string', 'max:60', 'regex:/^\p{L}[\p{L}\s.\'-]*$/u'],
            'email' => ['required', 'email:rfc'],
            'telefono' => ['required', 'string', 'regex:/^\+?[0-9 ]{9,20}$/'],
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
            // Factura se quitó del checkout: la emite el equipo de Enlix por
            // fuera de la web, no el sitio. 'boleta' queda fijo (lo manda el
            // JS), pero se sigue validando por si alguna integración vieja
            // todavía posta 'factura'.
            'tipo_comprobante' => ['required', 'in:boleta'],
            'razon_social' => ['nullable', 'string', 'max:150'],
            'metodo_entrega' => ['required', 'in:envio,recojo'],
            'direccion' => ['required_if:metodo_entrega,envio', 'nullable', 'string', 'max:255'],
            'distrito' => ['required_if:metodo_entrega,envio', 'nullable', 'string', 'max:100'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'terminos' => ['accepted'],
            // Checkbox distinto del de "terminos": la fecha de aceptacion se
            // guarda en pedidos.acepto_privacidad_en (ver CheckoutController).
            'acepta_privacidad' => ['accepted'],
            // Ids de items del carrito a cobrar (checkbox de seleccion en
            // /carrito), "12,15,19". Ausente = todo el carrito (ver
            // CheckoutController::parseItemIds).
            'items' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'Escribe tu nombre.',
            'first_name.regex' => 'Escribe tu nombre real, solo con letras.',
            'first_name.max' => 'El nombre no puede pasar de 60 caracteres.',
            'last_name.required' => 'Escribe tu apellido.',
            'last_name.regex' => 'Escribe tu apellido real, solo con letras.',
            'last_name.max' => 'El apellido no puede pasar de 60 caracteres.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Revisa tu correo electrónico: no parece válido.',
            'telefono.required' => 'Escribe tu teléfono.',
            'telefono.regex' => 'Escribe un teléfono válido, de al menos 9 dígitos.',
            'numero_documento.required' => 'Escribe tu número de documento.',
            'numero_documento.max' => 'El número de documento no puede pasar de 15 caracteres.',
            'direccion.required_if' => 'Escribe la dirección de envío.',
            'distrito.required_if' => 'Escribe el distrito de envío.',
            'terminos.accepted' => 'Debes aceptar los términos y condiciones para continuar.',
            'acepta_privacidad.accepted' => 'Debes aceptar la política de privacidad para continuar.',
        ];
    }
}
