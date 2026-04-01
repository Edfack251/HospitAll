<?php
namespace App\Repositories;

use PDO;

class BillingRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTodayRevenue()
    {
        return $this->pdo->query("SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(created_at) = CURDATE() AND estado = 'Pagada'")->fetchColumn();
    }

    public function getPendingInvoicesCount()
    {
        return $this->pdo->query("SELECT COUNT(*) FROM facturas WHERE estado = 'Pendiente'")->fetchColumn();
    }

    public function createInvoice($paciente_id)
    {
        $stmt = $this->pdo->prepare("INSERT INTO facturas (paciente_id, total, estado) VALUES (?, 0.00, 'Pendiente')");
        $stmt->execute([$paciente_id]);
        return $this->pdo->lastInsertId();
    }

    public function getServiceBase($tipo, $codigo)
    {
        $stmt = $this->pdo->prepare("SELECT id, codigo, nombre, precio FROM servicios WHERE tipo = ? AND codigo = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$tipo, $codigo]);
        return $stmt->fetch();
    }

    public function addItem($factura_id, $data, $cantidad, $precio, $subtotal)
    {
        $sql = "INSERT INTO factura_detalle (factura_id, servicio_id, tipo_item, descripcion, cantidad, precio, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            $factura_id,
            $data['servicio_id'] ?? null,
            $data['tipo_item'],
            $data['descripcion'],
            $cantidad,
            $precio,
            $subtotal
        ]);
    }

    public function calculateTotal($factura_id)
    {
        $stmt = $this->pdo->prepare("SELECT SUM(subtotal) FROM factura_detalle WHERE factura_id = ?");
        $stmt->execute([$factura_id]);
        $total = (float) $stmt->fetchColumn();

        $updateStmt = $this->pdo->prepare("UPDATE facturas SET total = ? WHERE id = ?");
        $updateStmt->execute([$total, $factura_id]);

        return $total;
    }

    public function markAsPaid($factura_id, $metodo_pago)
    {
        $stmt = $this->pdo->prepare("UPDATE facturas SET estado = 'Pagada', metodo_pago = ? WHERE id = ?");
        return $stmt->execute([$metodo_pago, $factura_id]);
    }

    public function getInvoice($factura_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $stmt = $this->pdo->prepare("SELECT f.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion 
                                     FROM facturas f JOIN pacientes p ON f.paciente_id = p.id WHERE f.id = ?");
        $stmt->execute([$factura_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getInvoiceItems($factura_id)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $stmt = $this->pdo->prepare("SELECT * FROM factura_detalle WHERE factura_id = ? ORDER BY id ASC");
        $stmt->execute([$factura_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllInvoices($limit = null, $offset = 0)
    {
        // TODO: Refactorizar SELECT * cuando se estabilice la vista
        $sql = "SELECT f.*, p.nombre as paciente_nombre, p.apellido as paciente_apellido, p.identificacion as paciente_identificacion, p.id as paciente_id_real 
                FROM facturas f 
                JOIN pacientes p ON f.paciente_id = p.id 
                ORDER BY f.created_at DESC";

        if ($limit !== null) {
            $sql .= " LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $this->pdo->query($sql);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * IDs de órdenes de laboratorio que ya tienen factura (ya fueron cobradas/billadas).
     * Detecta por descripcion "Orden de Laboratorio #X" en factura_detalle.
     */
    public function getOrdenesLabFacturados()
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT fd.descripcion FROM factura_detalle fd 
             WHERE fd.tipo_item = 'laboratorio' AND fd.descripcion LIKE 'Orden de Laboratorio #%'"
        );
        $ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $desc = $row['descripcion'];
            if (preg_match('/Orden de Laboratorio #(\d+)/', $desc, $m)) {
                $ids[] = (int) $m[1];
            }
        }
        return $ids;
    }

    public function getOrdenesImgFacturadas()
    {
        $stmt = $this->pdo->query(
            "SELECT DISTINCT fd.descripcion FROM factura_detalle fd 
             WHERE fd.tipo_item = 'imagenes' AND fd.descripcion LIKE 'Estudio de Imágenes #%'"
        );
        $ids = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $desc = $row['descripcion'];
            if (preg_match('/Estudio de Imágenes #(\d+)/', $desc, $m)) {
                $ids[] = (int) $m[1];
            }
        }
        return $ids;
    }

    public function getMonthlyRevenue($year, $month)
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-d', strtotime("$startDate +1 month"));

        $sql = "SELECT COALESCE(SUM(total), 0) FROM facturas 
                WHERE created_at >= ? AND created_at < ? 
                AND estado = 'Pagada'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate]);
        return (float)$stmt->fetchColumn();
    }
}
