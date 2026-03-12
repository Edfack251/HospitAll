# HospitAll – Base de datos

## Orden de ejecución para instalación nueva

1. **database_schema.sql** – Esquema base (tablas, FKs, datos iniciales)
2. **add_soft_delete_20260312.sql** – Columnas `deleted_at` e índices
3. **migration.sql** – Procedimiento `SafeMigration` (opcional, para esquemas antiguos)

## Cuándo usar cada archivo

| Archivo | Uso |
|---------|-----|
| `database_schema.sql` | Instalación nueva. Esquema principal. Usa tabla `factura_detalle` (no `factura_items`). |
| `add_soft_delete_20260312.sql` | Añade soft delete a: pacientes, usuarios, medicos, medicamentos, ordenes_laboratorio, prescripciones. |
| `migrate_phase2.sql` | Para bases creadas antes de episodios_clinicos y columnas en citas/historial_clinico. **No usar** si ya ejecutaste `database_schema.sql` actualizado. |
| `migration.sql` | Procedimiento para migrar esquemas antiguos (servicios, columnas). Idempotente. |
| `init_billing.sql` | **No usar** en instalaciones nuevas. Usa `factura_items`; la app usa `factura_detalle`. Solo referencia histórica. |

## Dependencias por Foreign Keys (orden de tablas)

Orden lógico según referencias:

1. roles
2. usuarios (→ roles)
3. pacientes (→ usuarios)
4. pacientes_identidad (→ pacientes)
5. medicos
6. episodios_clinicos (→ pacientes)
7. citas (→ pacientes, medicos, episodios_clinicos)
8. atenciones (→ citas, medicos, pacientes)
9. historial_clinico (→ citas, pacientes, medicos, episodios_clinicos, adenda_de_id)
10. servicios
11. facturas (→ pacientes)
12. factura_detalle (→ facturas, servicios)
13. ordenes_laboratorio (→ historial_clinico)
14. orden_laboratorio_detalle (→ ordenes_laboratorio)
15. medicamentos
16. movimientos_inventario (→ medicamentos, usuarios)
17. prescripciones (→ citas, medicos, pacientes)
18. prescripcion_detalle (→ prescripciones, medicamentos)
19. ventas_farmacia (→ pacientes, usuarios, facturas)
20. ventas_farmacia_detalle (→ ventas_farmacia, medicamentos)
21. logs (→ usuarios)

## Soft delete

Las tablas con soft delete usan la columna `deleted_at`. Los repos que extienden `BaseRepository` filtran `deleted_at IS NULL` por defecto.
