<?php

namespace App\Http\Requests;

use App\Models\Mototaxista;
use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MototaxistaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $ci = trim((string) $this->input('ci', ''));

        $this->merge([
            'ci' => $ci !== '' ? $ci : null,
        ]);

        $usuario = $this->user();

        if (
            strtolower(
                trim(
                    (string) (
                        $usuario?->role
                        ?? ''
                    )
                )
            ) !== 'secretario'
        ) {
            return;
        }

        $sindicatoId = (int) (
            $usuario?->sindicato_id
            ?? 0
        );

        /*
         * El secretario nunca puede decidir el sindicato desde
         * el frontend. Si tiene un sindicato vinculado, el backend
         * fuerza ese valor ANTES de ejecutar las reglas de validación.
         *
         * Esto también hace que la unicidad del número de chaleco
         * se valide contra el sindicato correcto.
         */
        if ($sindicatoId > 0) {
            $this->merge([
                'id_sindicato' =>
                    $sindicatoId,
            ]);
        }
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'nro_chaleco' => [
                'required',
                'string',
                'max:20',
                Rule::unique(
                    'mototaxistas',
                    'nro_chaleco'
                )
                    ->where(
                        fn ($query) =>
                            $query->where(
                                'id_sindicato',
                                $this->input(
                                    'id_sindicato'
                                )
                            )
                    )
                    ->ignore($id),
            ],

            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],

            'ci' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique(
                    'personas',
                    'ci'
                )->ignore(
                    (int) $this->input(
                        'id_persona'
                    )
                ),
            ],

            'estado' => [
                'required',
                Rule::in([
                    'Activo',
                    'Inactivo',
                ]),
            ],

            'id_persona' => [
                'required',
                'integer',
                'exists:personas,id',

                function (
                    $attribute,
                    $value,
                    $fail
                ) use ($id) {
                    $usuario = $this->user();

                    $personaSeleccionada = Persona::query()->find((int) $value);

                    if (! $personaSeleccionada) {
                        return;
                    }

                    if (
                        trim((string) $personaSeleccionada->ci) === ''
                        && trim((string) $this->input('ci', '')) === ''
                    ) {
                        $fail(
                            'El CI es obligatorio para afiliar a una persona como mototaxista.'
                        );

                        return;
                    }

                    if (
                        strtolower(
                            trim(
                                (string) (
                                    $usuario?->role
                                    ?? ''
                                )
                            )
                        ) === 'secretario'
                    ) {
                        $sindicatoId = (int) (
                            $usuario?->sindicato_id
                            ?? 0
                        );

                        if ($sindicatoId <= 0) {
                            $fail(
                                'La cuenta de secretario no está vinculada a un sindicato.'
                            );

                            return;
                        }

                        $personaVisible =
                            Persona::query()
                                ->where(
                                    'id',
                                    (int) $value
                                )
                                ->where(
                                    function (
                                        $query
                                    ) use (
                                        $sindicatoId
                                    ) {
                                        /*
                                         * Una persona todavía no afiliada puede ser
                                         * incorporada por el secretario de su sindicato.
                                         *
                                         * También se permite una persona que ya esté
                                         * vinculada al mismo sindicato.
                                         *
                                         * Una persona vinculada a otro sindicato continúa
                                         * fuera del ámbito del secretario y requiere una
                                         * gestión de traslado/reasignación administrativa.
                                         */
                                        $query
                                            ->whereNull(
                                                'sindicato_registro_id'
                                            )
                                            ->orWhere(
                                                'sindicato_registro_id',
                                                $sindicatoId
                                            )
                                            ->orWhereHas(
                                                'mototaxista',
                                                function (
                                                    $mototaxista
                                                ) use (
                                                    $sindicatoId
                                                ) {
                                                    $mototaxista
                                                        ->where(
                                                            'id_sindicato',
                                                            $sindicatoId
                                                        );
                                                }
                                            );
                                    }
                                )
                                ->exists();

                        if (! $personaVisible) {
                            $fail(
                                'La persona seleccionada ya está vinculada a otro sindicato.'
                            );

                            return;
                        }
                    }

                    /*
                     * Una misma persona puede conservar afiliaciones
                     * históricas, pero solo una puede estar Activa.
                     */
                    if (
                        $this->input('estado')
                        !== 'Activo'
                    ) {
                        return;
                    }

                    $query = Mototaxista::query()
                        ->where(
                            'id_persona',
                            $value
                        )
                        ->where(
                            'estado',
                            'Activo'
                        );

                    if ($id) {
                        $query->where(
                            'id',
                            '<>',
                            $id
                        );
                    }

                    if ($query->exists()) {
                        $fail(
                            'Esta persona ya tiene una afiliación Activa. '
                            . 'Debe inactivar la anterior antes de continuar.'
                        );
                    }
                },
            ],

            'id_sindicato' => [
                'required',
                'integer',
                'exists:sindicatos,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nro_chaleco.required' =>
                'El número de chaleco es obligatorio.',
            'nro_chaleco.unique' =>
                'Ese número de chaleco ya existe en el sindicato seleccionado.',
            'estado.required' =>
                'El estado es obligatorio.',
            'estado.in' =>
                'El estado debe ser Activo o Inactivo.',
            'id_persona.required' =>
                'Debes seleccionar una persona.',
            'id_persona.exists' =>
                'La persona seleccionada no existe.',
            'id_sindicato.required' =>
                'Debes seleccionar un sindicato.',
            'id_sindicato.exists' =>
                'El sindicato seleccionado no existe.',
        ];
    }
}
