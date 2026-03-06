<?php
namespace App\Core;

use Exception;

class Validator
{
    /**
     * Valida un arreglo de datos contra un conjunto de reglas.
     *
     * @param array $data Los datos a validar (ej. $_POST)
     * @param array $rules Las reglas que se deben cumplir
     * @throws Exception Si alguna regla no se cumple
     * @return bool True si pasa la validación
     */
    public static function validate(array $data, array $rules)
    {
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = isset($data[$field]) ? $data[$field] : null;

            foreach ($fieldRules as $rule) {
                // Validación: required
                if ($rule === 'required') {
                    if ($value === null || trim((string) $value) === '') {
                        throw new Exception("El campo '$field' es obligatorio.");
                    }
                }

                // Si el valor está vacío y no es required, omitimos el resto de validaciones para este campo
                if ($value === null || trim((string) $value) === '') {
                    continue;
                }

                // Validación: email
                if ($rule === 'email') {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        throw new Exception("El campo '$field' debe ser un correo electrónico válido.");
                    }
                }

                // Validación: min:N
                if (str_starts_with($rule, 'min:')) {
                    $min = (int) explode(':', $rule)[1];
                    if (mb_strlen((string) $value) < $min) {
                        throw new Exception("El campo '$field' debe tener al menos $min caracteres.");
                    }
                }

                // Validación: max:N
                if (str_starts_with($rule, 'max:')) {
                    $max = (int) explode(':', $rule)[1];
                    if (mb_strlen((string) $value) > $max) {
                        throw new Exception("El campo '$field' no debe exceder los $max caracteres.");
                    }
                }
            }
        }

        return true;
    }
}
