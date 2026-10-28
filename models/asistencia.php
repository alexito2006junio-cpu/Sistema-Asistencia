<?php

class Asistencia
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // =========================================================
    // VERIFICA SI EL PRACTICANTE YA REGISTRÓ ASISTENCIA HOY
    // =========================================================
    public function yaRegistroHoy($id_practicante, $fecha)
    {
        // Un día JUSTIFICADO no cuenta como asistencia registrada.
        $sql = "SELECT id_asistencia
                FROM Asistencia
                WHERE id_practicante = ?
                AND fecha = ?
                AND (estado IS NULL OR estado <> 'JUSTIFICADO')";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $id_practicante, $fecha);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }


    // =========================================================
    // OBTIENE EL HORARIO DEL PRACTICANTE PARA UN DÍA
    // =========================================================
    public function obtenerHorario($id_practicante, $dia)
    {
        $sql = "SELECT hora_entrada, hora_salida
                FROM Horarios
                WHERE id_practicante = ?
                AND dia_semana = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $id_practicante, $dia);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->fetch_assoc();
    }


    // =========================================================
    // VERIFICA QUE EL PRACTICANTE ESTÉ ACTIVO
    // =========================================================
    public function practicanteActivo($id_practicante)
    {
        $sql = "SELECT id_practicante
                FROM Practicantes
                WHERE id_practicante = ?
                AND activo = 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_practicante);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }


    // =========================================================
    // REGISTRA LA ASISTENCIA
    // =========================================================
    public function registrar(
        $id_practicante,
        $fecha,
        $hora_llegada,
        $estado
    ) {
        $sql = "INSERT INTO Asistencia
                (id_practicante, fecha, hora_llegada, estado)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "isss",
            $id_practicante,
            $fecha,
            $hora_llegada,
            $estado
        );

        return $stmt->execute();
    }

    public function registrarSalida($id_practicante, $fecha, $hora_salida)
    {
        $sql = "UPDATE Asistencia
            SET hora_salida = ?,
                horas_trabajadas = TIMEDIFF(?, hora_llegada)
            WHERE id_practicante = ?
            AND fecha = ?
            AND hora_llegada IS NOT NULL
            AND hora_salida IS NULL";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "ssis",
            $hora_salida,
            $hora_salida,
            $id_practicante,
            $fecha
        );

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->affected_rows > 0;
    }


    // =========================================================
    // QUITA LA JUSTIFICACIÓN DE UN DÍA (si existe).
    // Se usa cuando el practicante sí asistió ese día, o
    // cuando se justificó por error.
    // =========================================================
    public function eliminarJustificacion($id_practicante, $fecha)
    {
        $sql = "DELETE FROM Asistencia
                WHERE id_practicante = ?
                AND fecha = ?
                AND estado = 'JUSTIFICADO'";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $id_practicante, $fecha);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }


    // =========================================================
    // OBTIENE HISTORIAL
    // =========================================================
    public function obtenerHistorial($desde = null, $hasta = null)
    {
        $sql = "SELECT
                    a.id_asistencia,
                    a.fecha,
                    a.hora_llegada,
                    a.hora_salida,
                    a.estado,
                    a.motivo,
                    CONCAT(p.nombres, ' ', p.apellidos) AS practicante
                FROM Asistencia a
                INNER JOIN Practicantes p
                    ON a.id_practicante = p.id_practicante";

        if ($desde && $hasta) {
            $sql .= " WHERE a.fecha BETWEEN ? AND ?";
        }

        $sql .= " ORDER BY a.fecha DESC, a.hora_llegada DESC";

        if ($desde && $hasta) {

            $stmt = $this->conexion->prepare($sql);

            $stmt->bind_param(
                "ss",
                $desde,
                $hasta
            );

            $stmt->execute();

            return $stmt->get_result();
        }

        return $this->conexion->query($sql);
    }
}
