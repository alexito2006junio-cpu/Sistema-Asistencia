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


    // ¿Ya existe otro practicante con ese DNI?
    public function existeDni($dni, $excluirId = 0)
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_practicante
             FROM Practicantes
             WHERE dni = ?
             AND id_practicante <> ?"
        );

        $stmt->bind_param("si", $dni, $excluirId);
        $stmt->execute();

        return $stmt->get_result()->num_rows > 0;
    }


    // Suma las horas de todos los días y devuelve "HH:MM:SS"
    // $horarios = ["LUNES" => ["08:00:00", "13:00:00"], ...]
    public function calcularHorasSemanales($horarios)
    {
        $segundos = 0;

        foreach ($horarios as $horario) {
            $segundos += strtotime("1970-01-01 " . $horario[1] . " UTC")
                - strtotime("1970-01-01 " . $horario[0] . " UTC");
        }

        return sprintf(
            "%02d:%02d:00",
            floor($segundos / 3600),
            floor(($segundos % 3600) / 60)
        );
    }


    // Crea el practicante junto con sus horarios.
    // Devuelve true si todo salió bien.
    public function crear($nombres, $apellidos, $dni, $carrera, $semestre, $horarios)
    {
        $horas = $this->calcularHorasSemanales($horarios);

        $this->conexion->begin_transaction();

        try {
            $stmt = $this->conexion->prepare(
                "INSERT INTO Practicantes
                 (nombres, apellidos, dni, carrera, semestre, horas_semanales, activo)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );

            $stmt->bind_param(
                "ssssss",
                $nombres,
                $apellidos,
                $dni,
                $carrera,
                $semestre,
                $horas
            );

            $this->ejecutar($stmt);

            $id = $this->conexion->insert_id;
            $stmt->close();

            $this->guardarHorarios($id, $horarios);

            $this->conexion->commit();

            return true;
        } catch (Throwable $e) {
            $this->conexion->rollback();

            return false;
        }
    }


    // Reemplaza todos los horarios del practicante por los nuevos
    // y actualiza sus horas semanales.
    public function actualizarHorarios($id, $horarios)
    {
        $horas = $this->calcularHorasSemanales($horarios);

        $this->conexion->begin_transaction();

        try {
            $stmt = $this->conexion->prepare(
                "DELETE FROM Horarios WHERE id_practicante = ?"
            );
            $stmt->bind_param("i", $id);
            $this->ejecutar($stmt);
            $stmt->close();

            $this->guardarHorarios($id, $horarios);

            $stmt = $this->conexion->prepare(
                "UPDATE Practicantes
                 SET horas_semanales = ?
                 WHERE id_practicante = ?"
            );
            $stmt->bind_param("si", $horas, $id);
            $this->ejecutar($stmt);
            $stmt->close();

            $this->conexion->commit();

            return true;
        } catch (Throwable $e) {
            $this->conexion->rollback();

            return false;
        }
    }


    private function guardarHorarios($id, $horarios)
    {
        $stmt = $this->conexion->prepare(
            "INSERT INTO Horarios
             (id_practicante, dia_semana, hora_entrada, hora_salida)
             VALUES (?, ?, ?, ?)"
        );

        foreach ($horarios as $dia => $horario) {
            $stmt->bind_param("isss", $id, $dia, $horario[0], $horario[1]);
            $this->ejecutar($stmt);
        }

        $stmt->close();
    }


    // Ejecuta una consulta y lanza un error si falla,
    // para que la transacción se deshaga completa.
    private function ejecutar($stmt)
    {
        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
    }
}
