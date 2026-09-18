<?php

namespace App\Services;

use App\Models\Mototaxista;
use App\Models\Pasajero;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class MotrixPhoneRegistry
{
    private const ROLES_RECUPERABLES = [
        'conductor',
        'pasajero',
    ];

    public function normalize(mixed $phone): string
    {
        $number = preg_replace(
            '/\D+/u',
            '',
            trim((string) $phone)
        ) ?? '';

        if (
            str_starts_with($number, '00591')
            && strlen($number) === 13
        ) {
            return substr($number, 5);
        }

        if (
            str_starts_with($number, '591')
            && strlen($number) === 11
        ) {
            return substr($number, 3);
        }

        return $number;
    }

    public function isPhoneLike(mixed $value): bool
    {
        $original = trim((string) $value);

        if (
            $original === ''
            || ! preg_match('/^[+\d\s()-]+$/u', $original)
        ) {
            return false;
        }

        return (bool) preg_match(
            '/^[0-9]{7,15}$/',
            $this->normalize($original)
        );
    }

    public function firstValidPhone(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($this->isPhoneLike($value)) {
                return $this->normalize($value);
            }
        }

        return '';
    }

    public function assertAvailableForNewAccount(
        string $phone,
        string $field = 'telefono'
    ): void {
        $this->assertAvailableForAccount(
            $phone,
            null,
            null,
            $field
        );
    }

    public function assertAvailableForAccount(
        string $phone,
        ?int $allowedPersonaId = null,
        ?int $allowedMototaxistaId = null,
        string $field = 'telefono'
    ): void {
        $phone = $this->normalize($phone);

        if (! preg_match('/^[0-9]{7,15}$/', $phone)) {
            return;
        }

        if (
            $this->normalizedIds(
                User::query(),
                'nickname',
                $phone
            ) !== []
        ) {
            $this->throwPhoneInUse($field);
        }

        /*
         * También bloquea cuentas históricas cuyo nickname no era
         * el celular, pero cuya Persona/Pasajero/Mototaxista sí tiene
         * asociado el mismo número.
         */
        if ($this->matchingEligibleUserIds($phone) !== []) {
            $this->throwPhoneInUse($field);
        }

        $personas = Persona::query();

        if ($allowedPersonaId !== null) {
            $personas->where('id', '<>', $allowedPersonaId);
        }

        if (
            $this->normalizedIds(
                $personas,
                'telefono',
                $phone
            ) !== []
        ) {
            $this->throwPhoneInUse($field);
        }

        $mototaxistas = Mototaxista::query();

        if ($allowedMototaxistaId !== null) {
            $mototaxistas->where('id', '<>', $allowedMototaxistaId);
        }

        if (
            $this->normalizedIds(
                $mototaxistas,
                'telefono',
                $phone
            ) !== []
        ) {
            $this->throwPhoneInUse($field);
        }
    }

    /**
     * Devuelve las cuentas de pasajero/conductor asociadas al celular,
     * incluso si el dato histórico está guardado con +591, 00591,
     * espacios, guiones o solamente en una relación.
     *
     * @return array<int>
     */
    public function matchingEligibleUserIds(string $phone): array
    {
        $phone = $this->normalize($phone);

        if (! preg_match('/^[0-9]{7,15}$/', $phone)) {
            return [];
        }

        $personaIds = $this->normalizedIds(
            Persona::query(),
            'telefono',
            $phone
        );

        $mototaxistaIds = $this->normalizedIds(
            Mototaxista::query(),
            'telefono',
            $phone
        );

        if ($personaIds !== []) {
            $mototaxistaIds = array_merge(
                $mototaxistaIds,
                Mototaxista::query()
                    ->whereIn('id_persona', $personaIds)
                    ->pluck('id')
                    ->map(static fn ($id) => (int) $id)
                    ->all()
            );
        }

        $mototaxistaIds = $this->uniqueIds(
            $mototaxistaIds
        );

        $pasajeroIds = $personaIds !== []
            ? Pasajero::query()
                ->whereIn('id_persona', $personaIds)
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->all()
            : [];

        $userIds = $this->normalizedIds(
            User::query()->whereIn(
                'role',
                self::ROLES_RECUPERABLES
            ),
            'nickname',
            $phone
        );

        if (
            $personaIds !== []
            || $mototaxistaIds !== []
            || $pasajeroIds !== []
        ) {
            $relacionados = User::query()
                ->whereIn(
                    'role',
                    self::ROLES_RECUPERABLES
                )
                ->where(function (Builder $query) use (
                    $personaIds,
                    $mototaxistaIds,
                    $pasajeroIds
                ) {
                    if ($personaIds !== []) {
                        $query->orWhereIn(
                            'persona_id',
                            $personaIds
                        );
                    }

                    if ($mototaxistaIds !== []) {
                        $query->orWhereIn(
                            'mototaxista_id',
                            $mototaxistaIds
                        );
                    }

                    if ($pasajeroIds !== []) {
                        $query->orWhereIn(
                            'pasajero_id',
                            $pasajeroIds
                        );
                    }
                })
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->all();

            $userIds = array_merge(
                $userIds,
                $relacionados
            );
        }

        return $this->uniqueIds($userIds);
    }

    /**
     * @return array<int>
     */
    private function normalizedIds(
        Builder $query,
        string $column,
        string $phone
    ): array {
        $ids = [];

        $query
            ->select(['id', $column])
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->orderBy('id')
            ->chunkById(
                250,
                function ($rows) use (
                    &$ids,
                    $column,
                    $phone
                ) {
                    foreach ($rows as $row) {
                        if (
                            $this->normalize(
                                $row->getAttribute($column)
                            ) === $phone
                        ) {
                            $ids[] = (int) $row->getKey();
                        }
                    }
                },
                'id',
                'id'
            );

        return $this->uniqueIds($ids);
    }

    /**
     * @param array<int|string> $ids
     * @return array<int>
     */
    private function uniqueIds(array $ids): array
    {
        $ids = array_values(
            array_unique(
                array_map(
                    static fn ($id) => (int) $id,
                    $ids
                )
            )
        );

        sort($ids);

        return $ids;
    }

    private function throwPhoneInUse(string $field): never
    {
        throw ValidationException::withMessages([
            $field => [
                'Este número de celular ya está asociado a otra cuenta o registro MOTRIX.',
            ],
        ]);
    }
}
