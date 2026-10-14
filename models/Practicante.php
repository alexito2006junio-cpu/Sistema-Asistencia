<?php

class Practicante
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerTodos()
    {
        $sql = "SELECT * FROM Practicantes ORDER BY id_practicante ASC";

        return $this->conexion->query($sql);
    }


    public function obtenerPorDia($dia)
    {
        $fecha = date("Y-m-d");

        $sql = "SELECT DISTINCT
                p.id_practicante,
                p.nombres,
                p.apellidos,
                h.hora_entrada,
                h.hora_salida,
                a.hora_llegada,
                a.hora_salida AS hora_salida_real
            FROM Practicantes p

            INNER JOIN Horarios h
                ON p.id_practicante = h.id_practicante

            LEFT JOIN Asistencia a
                ON p.id_practicante = a.id_practicante
                AND a.fecha = ?

            WHERE h.dia_semana = ?
            AND p.activo = 1

            ORDER BY h.hora_entrada ASC,
                     p.apellidos ASC";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ss",
            $fecha,
            $dia
        );

        $stmt->execute();

        return $stmt->get_result();
    }

    public function obtenerActivos()
    {
        $sql = "SELECT * 
                FROM Practicantes 
                WHERE activo = 1
                ORDER BY apellidos ASC";

        return $this->conexion->query($sql);
    }

    public function actualizar($id, $nombres, $apellidos, $dni, $carrera, $semestre, $horas_semanales, $activo)
    {
        $sql = "UPDATE Practicantes
            SET nombres = ?,
                apellidos = ?,
                dni = ?,
                carrera = ?,
                semestre = ?,
                horas_semanales = ?,
                activo = ?
            WHERE id_practicante = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "ssssssii",
            $nombres,
            $apellidos,
            $dni,
            $carrera,
            $semestre,
            $horas_semanales,
            $activo,
            $id
        );

        return $stmt->execute();
    }

    public function cambiarEstado($id, $activo)
    {
        $sql = "UPDATE Practicantes
            SET activo = ?
            WHERE id_practicante = ?";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param("ii", $activo, $id);

        return $stmt->execute();
    }


    public function obtenerHorariosPorPracticante($id)
    {
        $sql = "SELECT dia_semana, hora_entrada, hora_salida
            FROM Horarios
            WHERE id_practicante = ?
            ORDER BY dia_semana";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();

        return $stmt->get_result();
    }


    public function actualizarDiasTrabajo($id, $dias)
    {
        $permitidos = [
            "LUNES",
            "MARTES",
            "MIERCOLES",
            "JUEVES",
            "VIERNES",
            "SABADO",
            "DOMINGO"
        ];

        // Obtener los horarios actuales
        $stmt = $this->conexion->prepare(
            "SELECT dia_semana, hora_entrada, hora_salida
         FROM Horarios
         WHERE id_practicante = ?"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        $actuales = [];

        while ($fila = $resultado->fetch_assoc()) {
            $actuales[$fila["dia_semana"]] = $fila;
        }

        $stmt->close();

        // Validar los días seleccionados
        $dias = array_values(array_unique($dias));

        foreach ($dias as $dia) {
            if (!in_array($dia, $permitidos, true)) {
                return false;
            }
        }

        $this->conexion->begin_transaction();

        try {
            // Eliminar solamente los días desmarcados
            foreach ($actuales as $dia => $horario) {
                if (!in_array($dia, $dias, true)) {
                    $stmt = $this->conexion->prepare(
                        "DELETE FROM Horarios
                     WHERE id_practicante = ? AND dia_semana = ?"
                    );

                    $stmt->bind_param("is", $id, $dia);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            // Agregar los días nuevos conservando sus horas existentes
            // para los días que ya estaban registrados.
            $stmt = $this->conexion->prepare(
                "INSERT INTO Horarios
             (id_practicante, dia_semana, hora_entrada, hora_salida)
             VALUES (?, ?, ?, ?)"
            );

            foreach ($dias as $dia) {
                if (isset($actuales[$dia])) {
                    continue;
                }

                // Un día nuevo necesita horas definidas.
                // Por ahora no se inventan ni se copian horas.
                throw new Exception(
                    "El día nuevo " . $dia .
                        " necesita un horario de entrada y salida."
                );
            }

            $stmt->close();
            $this->conexion->commit();

            return true;
        } catch (Throwable $e) {
            $this->conexion->rollback();
            return false;
        }
    }
}
