<?php
namespace App\Services;

use Exception;
use App\Repositories\ImagingRepository;

class ImagingService
{
    private $repo;

    private static $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'dcm'];
    private static $allowedMimes = [
        'image/jpeg',
        'image/png',
        'application/pdf',
        'application/dicom',
        'application/octet-stream' // DICOM sometimes reports this
    ];

    public function __construct($pdo)
    {
        $this->repo = new ImagingRepository($pdo);
    }

    public function getAllOrders(): array
    {
        return [
            'pendientes' => $this->repo->getOrdenesPendientes(),
            'completadas' => $this->repo->getOrdenesCompletadas()
        ];
    }

    /**
     * Actualiza el estado de una orden (Pendiente → En proceso → Completada).
     */
    public function updateEstadoOrden(int $id, string $estado): bool
    {
        return $this->repo->actualizarEstadoOrden($id, $estado);
    }

    /**
     * Valida el archivo, lo guarda en public/uploads/imagenes/ y actualiza la orden.
     */
    public function subirArchivo(int $id, array $file): bool
    {
        $inputKey = 'archivo_imagen';
        if (empty($file[$inputKey]) || $file[$inputKey]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Se debe seleccionar un archivo válido para subir.");
        }

        $upload_dir = __DIR__ . '/../../public/uploads/imagenes/';
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                throw new Exception("Error: No se pudo crear el directorio de subida.");
            }
        }

        $origName = $file[$inputKey]['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::$allowedExtensions, true)) {
            throw new Exception("Solo se permiten archivos jpg, png, pdf o dcm.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file[$inputKey]['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, self::$allowedMimes, true)) {
            throw new Exception("El archivo no tiene un tipo de contenido válido (jpg, png, pdf o dcm).");
        }

        $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($origName));
        $target_file = $upload_dir . $file_name;

        if (!move_uploaded_file($file[$inputKey]['tmp_name'], $target_file)) {
            throw new Exception("Error al subir el archivo.");
        }

        $relativePath = 'uploads/imagenes/' . $file_name;
        return $this->repo->subirArchivoOrden($id, $relativePath);
    }
}
