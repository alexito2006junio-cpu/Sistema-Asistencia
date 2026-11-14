<?php

/**
 * Generador sencillo de archivos Excel (.xlsx), sin librerías externas.
 * No necesita ninguna extensión especial de PHP: arma el .xlsx por su cuenta.
 *
 * Uso:
 *   $excel = new ExcelSimple();
 *   $excel->agregarHoja("Notas", [30, 12, 12], [
 *       [["Título", "titulo"]],
 *       [["Nombre", "encabezado"], ["Nota", "encabezado"]],
 *       [["Ana", "texto"], [17.5, "nota_ok"]]
 *   ], 2);
 *   $excel->descargar("notas.xlsx");
 *
 * Cada celda es un valor o un par [valor, "estilo"].
 */
class ExcelSimple
{
    private $hojas = [];

    // Estilos disponibles: nombre => número de estilo (ver styles.xml)
    private $estilos = [
        "normal"      => 0,
        "titulo"      => 1,
        "subtitulo"   => 2,
        "encabezado"  => 3,
        "texto"       => 4,
        "centro"      => 5,
        "nota_ok"     => 6,
        "nota_mal"    => 7,
        "final_ok"    => 8,
        "final_mal"   => 9,
        "aprobado"    => 10,
        "desaprobado" => 11,
        "sin_datos"   => 12
    ];


    // $anchos     = ancho de cada columna
    // $filas      = lista de filas, cada una es una lista de celdas
    // $filasFijas = cuántas filas de arriba quedan fijas al desplazarse
    public function agregarHoja($nombre, $anchos, $filas, $filasFijas = 0)
    {
        $this->hojas[] = [
            "nombre"     => $this->limpiarNombre($nombre),
            "anchos"     => $anchos,
            "filas"      => $filas,
            "filasFijas" => (int)$filasFijas
        ];
    }


    // Envía el archivo al navegador para que se descargue
    public function descargar($nombreArchivo)
    {
        $contenido = $this->construir();

        // Nada debe haberse enviado antes, o el archivo se corrompe
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nombreArchivo = preg_replace('/[^A-Za-z0-9._-]/', "_", $nombreArchivo);

        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header("Content-Length: " . strlen($contenido));
        header("Cache-Control: no-store, no-cache, must-revalidate");

        echo $contenido;
        exit;
    }


    // Devuelve el contenido completo del archivo .xlsx
    public function construir()
    {
        $archivos = [
            "[Content_Types].xml"        => $this->xmlTipos(),
            "_rels/.rels"                => $this->xmlRelacionesRaiz(),
            "xl/workbook.xml"            => $this->xmlLibro(),
            "xl/_rels/workbook.xml.rels" => $this->xmlRelacionesLibro(),
            "xl/styles.xml"              => $this->xmlEstilos()
        ];

        foreach ($this->hojas as $i => $hoja) {
            $archivos["xl/worksheets/sheet" . ($i + 1) . ".xml"] = $this->xmlHoja($hoja);
        }

        return $this->crearZip($archivos);
    }


    // Un .xlsx es un ZIP. Aquí se arma sin comprimir (método "stored"),
    // que Excel y LibreOffice leen sin problema.
    private function crearZip($archivos)
    {
        $datosLocales   = "";
        $directorio     = "";
        $desplazamiento = 0;
        $cantidad       = 0;

        $fecha = 33; // 01/01/1980 en formato DOS
        $hora  = 0;

        foreach ($archivos as $nombre => $contenido) {

            $crc    = crc32($contenido);
            $tamano = strlen($contenido);
            $largoNombre = strlen($nombre);

            $cabeceraLocal = pack(
                "VvvvvvVVVvv",
                0x04034b50,
                20,
                0,
                0,
                $hora,
                $fecha,
                $crc,
                $tamano,
                $tamano,
                $largoNombre,
                0
            ) . $nombre;

            $datosLocales .= $cabeceraLocal . $contenido;

            $directorio .= pack(
                "VvvvvvvVVVvvvvvVV",
                0x02014b50,
                20,
                20,
                0,
                0,
                $hora,
                $fecha,
                $crc,
                $tamano,
                $tamano,
                $largoNombre,
                0,
                0,
                0,
                0,
                0,
                $desplazamiento
            ) . $nombre;

            $desplazamiento += strlen($cabeceraLocal) + $tamano;
            $cantidad++;
        }

        $final = pack(
            "VvvvvVVv",
            0x06054b50,
            0,
            0,
            $cantidad,
            $cantidad,
            strlen($directorio),
            $desplazamiento,
            0
        );

        return $datosLocales . $directorio . $final;
    }


    // =========================================================
    // PIEZAS DEL ARCHIVO
    // =========================================================
    private function cabecera()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n";
    }

    private function xmlTipos()
    {
        $hojas = "";

        foreach ($this->hojas as $i => $hoja) {
            $hojas .= '<Override PartName="/xl/worksheets/sheet' . ($i + 1) . '.xml"'
                . ' ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return $this->cabecera()
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $hojas
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function xmlRelacionesRaiz()
    {
        return $this->cabecera()
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function xmlLibro()
    {
        $hojas = "";

        foreach ($this->hojas as $i => $hoja) {
            $hojas .= '<sheet name="' . $this->texto($hoja["nombre"]) . '"'
                . ' sheetId="' . ($i + 1) . '" r:id="rId' . ($i + 1) . '"/>';
        }

        return $this->cabecera()
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $hojas . '</sheets>'
            . '</workbook>';
    }

    private function xmlRelacionesLibro()
    {
        $relaciones = "";

        foreach ($this->hojas as $i => $hoja) {
            $relaciones .= '<Relationship Id="rId' . ($i + 1) . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . ($i + 1) . '.xml"/>';
        }

        $relaciones .= '<Relationship Id="rId' . (count($this->hojas) + 1) . '"'
            . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles"'
            . ' Target="styles.xml"/>';

        return $this->cabecera()
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relaciones
            . '</Relationships>';
    }

    private function xmlEstilos()
    {
        $tipo = '<font>%s<sz val="%d"/>%s<name val="Arial"/></font>';

        $fuentes = [
            sprintf($tipo, "", 10, ""),
            sprintf($tipo, "<b/>", 14, ""),
            sprintf($tipo, "<i/>", 10, '<color rgb="FF595959"/>'),
            sprintf($tipo, "<b/>", 10, '<color rgb="FF000000"/>'),
            sprintf($tipo, "", 10, '<color rgb="FF006100"/>'),
            sprintf($tipo, "", 10, '<color rgb="FF9C0006"/>'),
            sprintf($tipo, "<b/>", 10, '<color rgb="FF006100"/>'),
            sprintf($tipo, "<b/>", 10, '<color rgb="FF9C0006"/>'),
            sprintf($tipo, "<b/>", 10, '<color rgb="FF595959"/>')
        ];

        $relleno = '<fill><patternFill patternType="solid"><fgColor rgb="%s"/><bgColor indexed="64"/></patternFill></fill>';

        $rellenos = [
            '<fill><patternFill patternType="none"/></fill>',
            '<fill><patternFill patternType="gray125"/></fill>',
            sprintf($relleno, "FFFF8800"),
            sprintf($relleno, "FFC6EFCE"),
            sprintf($relleno, "FFFFC7CE"),
            sprintf($relleno, "FFE7E6E6")
        ];

        $lado = '<%1$s style="thin"><color rgb="FFBFBFBF"/></%1$s>';

        $bordes = [
            '<border><left/><right/><top/><bottom/><diagonal/></border>',
            '<border>' . sprintf($lado, "left") . sprintf($lado, "right")
                . sprintf($lado, "top") . sprintf($lado, "bottom") . '<diagonal/></border>'
        ];

        // numFmt 2 = "0.00" (formato incorporado de Excel)
        $centro = '<alignment horizontal="center" vertical="center"/>';

        $formatos = [
            // 0 normal
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>',
            // 1 titulo
            '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>',
            // 2 subtitulo
            '<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>',
            // 3 encabezado (naranja de la empresa)
            '<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
                . '<alignment horizontal="center" vertical="center" wrapText="1"/></xf>',
            // 4 texto
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
                . '<alignment horizontal="left" vertical="center"/></xf>',
            // 5 centro
            '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 6 nota_ok
            '<xf numFmtId="2" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 7 nota_mal
            '<xf numFmtId="2" fontId="5" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 8 final_ok
            '<xf numFmtId="2" fontId="6" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 9 final_mal
            '<xf numFmtId="2" fontId="7" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 10 aprobado
            '<xf numFmtId="0" fontId="6" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 11 desaprobado
            '<xf numFmtId="0" fontId="7" fillId="4" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>',
            // 12 sin_datos
            '<xf numFmtId="0" fontId="8" fillId="5" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">' . $centro . '</xf>'
        ];

        return $this->cabecera()
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="' . count($fuentes) . '">' . implode("", $fuentes) . '</fonts>'
            . '<fills count="' . count($rellenos) . '">' . implode("", $rellenos) . '</fills>'
            . '<borders count="' . count($bordes) . '">' . implode("", $bordes) . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($formatos) . '">' . implode("", $formatos) . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function xmlHoja($hoja)
    {
        // Filas fijas arriba (por ejemplo, los encabezados)
        $vista = '<sheetView workbookViewId="0">';

        if ($hoja["filasFijas"] > 0) {
            $vista .= '<pane ySplit="' . $hoja["filasFijas"] . '"'
                . ' topLeftCell="A' . ($hoja["filasFijas"] + 1) . '"'
                . ' activePane="bottomLeft" state="frozen"/>'
                . '<selection pane="bottomLeft"/>';
        }

        $vista .= '</sheetView>';

        // Ancho de columnas
        $columnas = "";

        foreach ($hoja["anchos"] as $i => $ancho) {
            $columnas .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '"'
                . ' width="' . $ancho . '" customWidth="1"/>';
        }

        // Filas y celdas
        $datos = "";

        foreach ($hoja["filas"] as $numeroFila => $celdas) {

            $fila = $numeroFila + 1;
            $xmlCeldas = "";

            foreach ($celdas as $posicion => $celda) {

                if (is_array($celda)) {
                    $valor = $celda[0];
                    $estilo = $this->estilos[$celda[1] ?? "normal"] ?? 0;
                } else {
                    $valor = $celda;
                    $estilo = 0;
                }

                $xmlCeldas .= $this->xmlCelda(
                    $this->columna($posicion) . $fila,
                    $valor,
                    $estilo
                );
            }

            $datos .= '<row r="' . $fila . '">' . $xmlCeldas . '</row>';
        }

        return $this->cabecera()
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetPr><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews>' . $vista . '</sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . $columnas . '</cols>'
            . '<sheetData>' . $datos . '</sheetData>'
            . '<pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/>'
            . '</worksheet>';
    }

    private function xmlCelda($referencia, $valor, $estilo)
    {
        // Celda vacía (conserva el borde y el color)
        if ($valor === null || $valor === "") {
            return '<c r="' . $referencia . '" s="' . $estilo . '"/>';
        }

        // Números
        if (is_int($valor) || is_float($valor)) {
            $numero = is_int($valor)
                ? (string)$valor
                : number_format($valor, 2, ".", "");

            return '<c r="' . $referencia . '" s="' . $estilo . '"><v>' . $numero . '</v></c>';
        }

        // Texto
        return '<c r="' . $referencia . '" s="' . $estilo . '" t="inlineStr">'
            . '<is><t xml:space="preserve">' . $this->texto($valor) . '</t></is></c>';
    }


    // =========================================================
    // APOYO
    // =========================================================

    // 0 -> A, 1 -> B, 25 -> Z, 26 -> AA
    private function columna($posicion)
    {
        $letras = "";
        $n = $posicion + 1;

        while ($n > 0) {
            $resto = ($n - 1) % 26;
            $letras = chr(65 + $resto) . $letras;
            $n = intdiv($n - 1, 26);
        }

        return $letras;
    }

    // Texto seguro para XML (sin caracteres de control)
    private function texto($valor)
    {
        $valor = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', "", (string)$valor);

        return htmlspecialchars($valor, ENT_XML1 | ENT_QUOTES, "UTF-8");
    }

    // Excel no permite  [ ] : * ? / \  en el nombre de una hoja
    // y limita el nombre a 31 caracteres
    private function limpiarNombre($nombre)
    {
        $nombre = trim(preg_replace('/[\[\]:*?\/\\\\]/', " ", $nombre));

        if ($nombre === "") {
            $nombre = "Hoja";
        }

        // Máximo 31 caracteres (sin cortar letras con tilde por la mitad)
        preg_match('/^.{0,31}/us', $nombre, $recorte);

        return $recorte[0];
    }
}
