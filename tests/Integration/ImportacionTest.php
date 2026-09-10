<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Services\ExcelImportService;
use Core\Model;
use Tests\TestCase;

/**
 * CP-05 y CP-06 · Importación del reporte de Sofía Plus.
 *
 * Prueban el corazón del sistema de extremo a extremo: del archivo Excel a la
 * base de datos normalizada. Se usa siempre el archivo anonimizado, nunca un
 * reporte con datos personales reales.
 *
 * La base de datos es desechable: se crea al empezar y se destruye al terminar.
 * Si no hay un servidor MySQL disponible, la clase se omite en lugar de fallar.
 *
 * Configuración por variables de entorno:
 *   TEST_DB_HOST (127.0.0.1) · TEST_DB_PORT (3306)
 *   TEST_DB_USER (root)      · TEST_DB_PASS ('')
 */
final class ImportacionTest extends TestCase
{
    private const BD = 'sgpd_pruebas';

    /** Cifras de referencia del archivo de muestra (docs/16_PLAN_PRUEBAS.md). */
    private const ESPERADO = [
        'programa'              => 1,
        'ficha'                 => 1,
        'aprendiz'              => 31,
        'competencia'           => 20,
        'resultado_aprendizaje' => 75,
        'funcionario'           => 14,
        'calificacion'          => 2325,
    ];

    private ?\PDO $pdo = null;

    /** @var array{stats: array, errors: array} */
    private array $resultadoImportacion = ['stats' => [], 'errors' => []];

    public function prepararClase(): ?string
    {
        $muestra = RAIZ . '/docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx';
        if (!is_file($muestra)) {
            return 'falta docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx';
        }

        $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
        $port = getenv('TEST_DB_PORT') ?: '3306';
        $user = getenv('TEST_DB_USER') ?: 'root';
        $pass = getenv('TEST_DB_PASS') ?: '';

        try {
            $servidor = new \PDO("mysql:host={$host};port={$port}", $user, $pass, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (\Throwable $e) {
            return sprintf('sin servidor MySQL en %s:%s (%s)', $host, $port, $e->getMessage());
        }

        // Base desechable, jamás la de trabajo.
        $servidor->exec('DROP DATABASE IF EXISTS ' . self::BD);
        $servidor->exec('CREATE DATABASE ' . self::BD . ' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        $this->pdo = new \PDO("mysql:host={$host};port={$port};dbname=" . self::BD . ';charset=utf8mb4', $user, $pass, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        foreach ($this->sentenciasDelEsquema() as $sql) {
            $this->pdo->exec($sql);
        }

        // Apuntar los modelos a la base de pruebas.
        Model::setConnection($this->pdo);

        // Se importa una sola vez para toda la clase: cada importación tarda unos
        // 30 s y las pruebas de lectura no necesitan repetirla.
        $this->resultadoImportacion = $this->importarMuestra();

        return null;
    }

    public function limpiarClase(): void
    {
        Model::setConnection(null);
        if ($this->pdo !== null) {
            $this->pdo->exec('DROP DATABASE IF EXISTS ' . self::BD);
            $this->pdo = null;
        }
    }

    /** El script de esquema, sin las sentencias que fijan la base de producción. */
    private function sentenciasDelEsquema(): array
    {
        $sql = file_get_contents(RAIZ . '/docs/SGPD_SENA.sql') ?: '';
        $sql = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $sql) ?? '';

        return array_values(array_filter(
            array_map('trim', explode(';', $sql)),
            static fn(string $s): bool => $s !== ''
        ));
    }

    private function contar(string $tabla): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM {$tabla}")->fetchColumn();
    }

    private function importarMuestra(): array
    {
        return (new ExcelImportService())->import(RAIZ . '/docs/Reporte_Juicios_Evaluativos_MUESTRA.xlsx');
    }

    // ── CP-05 ───────────────────────────────────────────────────────────────

    public function testLaImportacionProduceLosConteosEsperados(): void
    {
        $r = $this->resultadoImportacion;

        $this->assertSame(0, $r['stats']['errores'], 'la importación no debe registrar errores');
        $this->assertSame(2325, $r['stats']['filas_procesadas'], 'deben procesarse las 2.325 filas de datos');

        foreach (self::ESPERADO as $tabla => $esperado) {
            $this->assertSame($esperado, $this->contar($tabla), "la tabla {$tabla} debe tener {$esperado} registros");
        }
    }

    /** RF-02 · los metadatos salen del encabezado, no de las filas de datos. */
    public function testExtraeLosMetadatosDeLaFichaYDelPrograma(): void
    {
        $ficha = $this->pdo->query('SELECT * FROM ficha LIMIT 1')->fetch();
        $this->assertSame('3142784', $ficha['nu_ficha']);
        $this->assertSame('EN EJECUCION', $ficha['estado']);
        $this->assertSame('2025-02-10', $ficha['fecha_inicio']);
        $this->assertSame('2027-05-10', $ficha['fecha_fin']);

        $programa = $this->pdo->query('SELECT * FROM programa LIMIT 1')->fetch();
        $this->assertSame('228118', $programa['codigo_programa']);
        $this->assertSame('PRESENCIAL', $programa['modalidad']);
    }

    /** RN-12 · los juicios quedan en los tres valores controlados. */
    public function testLosJuiciosQuedanNormalizados(): void
    {
        $porTipo = [];
        foreach ($this->pdo->query('SELECT jui_evaluativo, COUNT(*) n FROM calificacion GROUP BY jui_evaluativo') as $fila) {
            $porTipo[$fila['jui_evaluativo']] = (int) $fila['n'];
        }

        $this->assertSame(1077, $porTipo['APROBADO'] ?? 0);
        $this->assertSame(1248, $porTipo['POR EVALUAR'] ?? 0);
        $this->assertSame(2325, array_sum($porTipo), 'todo juicio cae en una de las categorías válidas');
    }

    /** RN-15 · la separación entre activos y retirados es la que usan los KPIs. */
    public function testLosEstadosDeLosAprendicesSeConservan(): void
    {
        $porEstado = [];
        foreach ($this->pdo->query('SELECT estado, COUNT(*) n FROM aprendiz GROUP BY estado') as $fila) {
            $porEstado[$fila['estado']] = (int) $fila['n'];
        }

        $this->assertSame(24, $porEstado['EN FORMACION'] ?? 0);
        $this->assertSame(6, $porEstado['RETIRO VOLUNTARIO'] ?? 0);
        $this->assertSame(1, $porEstado['TRASLADADO'] ?? 0);
    }

    // ── CP-06 ───────────────────────────────────────────────────────────────

    /** RF-05 · reimportar actualiza, nunca duplica. */
    public function testReimportarNoDuplicaRegistros(): void
    {
        // prepararClase() ya hizo la primera importación: se toman esos conteos
        // como referencia y se importa una segunda vez sobre ellos.
        $primera = [];
        foreach (array_keys(self::ESPERADO) as $tabla) {
            $primera[$tabla] = $this->contar($tabla);
        }

        $this->importarMuestra();

        foreach ($primera as $tabla => $conteo) {
            $this->assertSame($conteo, $this->contar($tabla), "reimportar no debe cambiar el conteo de {$tabla}");
        }
    }

    /** RN-11 · un aprendiz no puede tener dos juicios del mismo resultado. */
    public function testUnAprendizNoTieneJuiciosDuplicados(): void
    {
        $duplicados = (int) $this->pdo->query(
            'SELECT COUNT(*) FROM (
                SELECT id_aprendiz, id_resultado FROM calificacion
                GROUP BY id_aprendiz, id_resultado HAVING COUNT(*) > 1
             ) d'
        )->fetchColumn();

        $this->assertSame(0, $duplicados, 'la clave única (id_aprendiz, id_resultado) debe impedirlo');
    }

    // ── Estado del módulo de deserción ──────────────────────────────────────

    // ── CP-29 · Novedades de retiro ─────────────────────────────────────────

    /**
     * RF-29 · regresión de F-02.
     *
     * El módulo de deserción leía `novedad_retiro`, una tabla en la que ninguna
     * línea del sistema escribía: las tres gráficas y la tabla de trazabilidad
     * salían siempre vacías. Ahora la importación deriva las novedades del estado
     * reportado por Sofía Plus.
     */
    public function testLaImportacionRegistraUnaNovedadPorCadaAprendizRetirado(): void
    {
        $retirados = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM aprendiz WHERE estado LIKE '%RETIRO%' OR estado LIKE '%TRASLADADO%'"
        )->fetchColumn();
        $this->assertSame(7, $retirados, 'la muestra trae 6 retiros voluntarios y 1 traslado');

        $this->assertSame(7, $this->contar('novedad_retiro'), 'F-02: debe haber una novedad por cada aprendiz que salió');
    }

    /** El motivo debe ser el estado real, no un texto genérico. */
    public function testLaNovedadConservaElMotivoReal(): void
    {
        $motivos = [];
        foreach ($this->pdo->query('SELECT motivo, COUNT(*) n FROM novedad_retiro GROUP BY motivo') as $fila) {
            $motivos[$fila['motivo']] = (int) $fila['n'];
        }

        $this->assertSame(6, $motivos['RETIRO VOLUNTARIO'] ?? 0);
        $this->assertSame(1, $motivos['TRASLADADO'] ?? 0);
    }

    /** Ningún aprendiz activo puede tener novedad de retiro. */
    public function testNingunAprendizActivoTieneNovedad(): void
    {
        $intrusos = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM novedad_retiro nr
               JOIN aprendiz a ON nr.id_aprendiz = a.id_aprendiz
              WHERE a.estado LIKE '%FORMACION%'"
        )->fetchColumn();

        $this->assertSame(0, $intrusos, 'un aprendiz en formación no ha desertado');
    }

    /** Reimportar no debe duplicar novedades: la sincronización es idempotente. */
    public function testLasNovedadesNoSeDuplicanAlReimportar(): void
    {
        $antes = $this->contar('novedad_retiro');
        $this->importarMuestra();

        $this->assertSame($antes, $this->contar('novedad_retiro'), 'la sincronización debe actualizar, no duplicar');
    }

    /** Si un aprendiz vuelve a formación, su novedad desaparece. */
    public function testLaNovedadSeRetiraSiElAprendizVuelveAFormacion(): void
    {
        $id = (int) $this->pdo->query(
            "SELECT id_aprendiz FROM aprendiz WHERE estado LIKE '%RETIRO%' LIMIT 1"
        )->fetchColumn();
        $this->assertTrue($id > 0, 'debe existir al menos un aprendiz retirado');

        $this->pdo->exec("UPDATE aprendiz SET estado = 'EN FORMACION' WHERE id_aprendiz = {$id}");
        (new \App\Services\NovedadRetiroService())->sincronizar();

        $quedan = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM novedad_retiro WHERE id_aprendiz = {$id}"
        )->fetchColumn();
        $this->assertSame(0, $quedan, 'al reingresar, la novedad de retiro debe eliminarse');

        // Se restaura el estado para no afectar a las demás pruebas.
        $this->pdo->exec("UPDATE aprendiz SET estado = 'RETIRO VOLUNTARIO' WHERE id_aprendiz = {$id}");
        (new \App\Services\NovedadRetiroService())->sincronizar();
    }

    // ── CP-15 · Filtros del enunciado ───────────────────────────────────────

    /**
     * RF-14 · el enunciado exige filtrar también por competencia y por resultado
     * de aprendizaje. Se comprueba que el backend sepa hacerlo.
     */
    public function testElCatalogoDeResultadosCubreLasCompetencias(): void
    {
        $resultados = $this->pdo->query(
            'SELECT DISTINCT r.id_resultado, r.id_competencia
               FROM resultado_aprendizaje r
               JOIN calificacion c ON c.id_resultado = r.id_resultado'
        )->fetchAll();

        $this->assertCount(75, $resultados, 'los 75 resultados deben poder ofrecerse en el filtro');

        $competencias = array_unique(array_column($resultados, 'id_competencia'));
        $this->assertSame(20, count($competencias), 'cada competencia debe tener al menos un resultado filtrable');
    }
}
