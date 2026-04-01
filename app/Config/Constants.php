<?php

namespace App\Config;

class Constants
{
    // Estados de citas
    const CITA_PROGRAMADA = 'Programada';
    const CITA_CONFIRMADA = 'Confirmada';
    const CITA_EN_ESPERA = 'En espera';
    const CITA_ATENDIDA = 'Atendida';
    const CITA_CANCELADA = 'Cancelada';
    const CITA_NO_ASISTIO = 'No asistió';

    // Estados de órdenes de laboratorio e imágenes
    const ORDEN_PENDIENTE = 'Pendiente';
    const ORDEN_EN_PROCESO = 'En proceso';
    const ORDEN_COMPLETADA = 'Completada';

    // Estados de emergencias
    const EMERGENCIA_EN_ESPERA = 'En espera';
    const EMERGENCIA_EN_ATENCION = 'En atención';
    const EMERGENCIA_ATENDIDA = 'Atendida';
    const EMERGENCIA_TRANSFERIDA = 'Transferida';

    // Niveles de triaje
    const TRIAGE_ROJO = 'Rojo';
    const TRIAGE_NARANJA = 'Naranja';
    const TRIAGE_AMARILLO = 'Amarillo';
    const TRIAGE_VERDE = 'Verde';

    // Estados de internamiento
    const INTERNAMIENTO_ACTIVO = 'activo';
    const INTERNAMIENTO_ALTA = 'alta';
    const INTERNAMIENTO_TRANSFERIDO = 'transferido';

    // Estados de camas
    const CAMA_DISPONIBLE = 'disponible';
    const CAMA_OCUPADA = 'ocupada';
    const CAMA_EN_LIMPIEZA = 'en_limpieza';

    // Estados de turnos
    const TURNO_ESPERANDO = 'esperando';
    const TURNO_LLAMADO = 'llamado';
    const TURNO_ATENDIDO = 'atendido';
    const TURNO_CANCELADO = 'cancelado';

    // Tipos de turno
    const TURNO_PREFERENCIAL = 'preferencial';
    const TURNO_GENERAL = 'general';

    // Áreas de turnos
    const AREA_CONSULTA = 'consulta';
    const AREA_LABORATORIO = 'laboratorio';
    const AREA_FARMACIA = 'farmacia';
    const AREA_IMAGENES = 'imagenes';

    // Estados de prescripciones
    const PRESCRIPCION_PENDIENTE = 'Pendiente';
    const PRESCRIPCION_DISPENSADA = 'Dispensada';
    const PRESCRIPCION_CANCELADA = 'Cancelada';

    // Estados de facturas
    const FACTURA_PENDIENTE = 'Pendiente';
    const FACTURA_PAGADA = 'Pagada';
    const FACTURA_CANCELADA = 'Cancelada';

    // Roles del sistema
    const ROL_ADMINISTRADOR = 'administrador';
    const ROL_MEDICO = 'medico';
    const ROL_RECEPCIONISTA = 'recepcionista';
    const ROL_ENFERMERA = 'enfermera';
    const ROL_TECNICO_LABORATORIO = 'tecnico_laboratorio';
    const ROL_TECNICO_IMAGENES = 'tecnico_imagenes';
    const ROL_FARMACEUTICO = 'farmaceutico';
    const ROL_PACIENTE = 'paciente';
}
