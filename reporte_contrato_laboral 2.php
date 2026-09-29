<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: *");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', '1');

include '../../servicios/includes/conexion.php';
require_once "../../servicios/includes/captura_errores.php";
include '../../servicios/includes/rutas_globales.php';
include '../../servicios/includes/funciones_generales.php';
include '../../servicios/includes/funciones_generales_imagenes.php';
include '../../servicios/includes/jwt.php';

require "../../dist/php/FPDF186/fpdf.php";

class PDF extends FPDF
{
    // Page header
    function Header()
    {
        // Logo
        $this->Image('../../dist/images/header_secretaria.jpg',5,5,180);
        // Line break
        $this->Ln(25);
    }

    // Page footer
    function Footer()
    {   
        
        // Position at 1.5 cm from bottom
        $this->SetY(-20);
        $this->Image('../../dist/images/footer.jpg',0,265,205);

        
    }

    function SetWidths($w) {
        //Set the array of column widths
        $this->widths = $w;
    }

}

$con = connect_db($db_academico);

ini_set('max_execution_time', 0);

$id_contrato = $_POST["ref"];
$id_token = (isset($_POST["id_token"])) ? addslashes(trim($_POST["id_token"])) : "";

get_token_empleado_validate_report($id_token,$KeyServer);

$id_contrato_decode = decode_md5("empleados_empleado_contrato_laboral","eecol_id",$id_contrato);

$sql_contrato = "SELECT *from empleados_empleado_contrato_laboral
                   inner join empleados_empleado on empleados_empleado_contrato_laboral.eecol_id_empleado = empleados_empleado.id_empleados
                   left join opc_categoria_empleado on empleados_empleado_contrato_laboral.eecol_id_tipo_contrato = opc_categoria_empleado.opc_ce_id and opc_categoria_empleado.opc_ce_estado = 1
                   left join opc_documentos_relacion_laboral on empleados_empleado.empleados_id_documento_relacion_laboral_cargo_directivo = opc_documentos_relacion_laboral.opc_drl_id and opc_documentos_relacion_laboral.opc_drl_estado = 1
                   left join gen_carreras on empleados_empleado.empleados_empleado_id_carrera = gen_carreras.carr_id and gen_carreras.carr_estado = 1
                   left join opc_tiempo_dedicacion_empleado on empleados_empleado.id_tiempo_dedicacion = opc_tiempo_dedicacion_empleado.opc_tde_id and opc_tiempo_dedicacion_empleado.opc_tde_estado = 1
                   left join empleados_cargos on empleados_empleado.id_empleados_cargo = empleados_cargos.id_empleados_cargo 
                   left join gen_escuelas_carreras on gen_carreras.carr_id_escuela_carrera = gen_escuelas_carreras.esc_carr_id and esc_carr_estado = 1
                   left join opc_tratamiento_empleado on empleados_empleado.empleado_id_tratamiento = opc_tratamiento_empleado.trat_emp_id and trat_emp_estado = 1
                   where empleados_empleado_contrato_laboral.eecol_id = '$id_contrato_decode' and empleados_empleado_contrato_laboral.eecol_estado = 1";

$result_contrato = execute_query_db($sql_contrato,$con);
$row_contrato = get_array_result_db($result_contrato);

$documento = get_result("CI",$row_contrato);
$documento_relacion_laboral = get_result("opc_drl_nombre",$row_contrato);
$nombres = get_result("Nombres",$row_contrato);
$apellidos_paternos = get_result("Apellidos",$row_contrato);
$apellidos_maternos = get_result("apellido_materno",$row_contrato);
$fecha_hora = get_result("eecol_fecha_hora",$row_contrato);
$sueldo = (double)get_result("eecol_sueldo",$row_contrato);
$horario_laboral_inicio = get_result("eecol_horario_laboral_inicio",$row_contrato);
$horario_laboral_fin = get_result("eccol_horario_laboral_fin",$row_contrato);
$codigo_contrato = get_result("eecol_codigo_contrato",$row_contrato);
$id_tipo_contrato = get_result("eecol_id_tipo_contrato",$row_contrato);
$id_documento_relacion_laboral = get_result("opc_drl_id",$row_contrato);
$tipo_contrato = get_result("opc_ce_nombre",$row_contrato);
$fecha_inicio = get_result("eecol_fecha_inicio",$row_contrato);
$fecha_fin = get_result("eecol_fecha_fin",$row_contrato);
$carrera = get_result("carr_nombre",$row_contrato);
$escuela = get_result("esc_carr_nombre_escuela",$row_contrato);
$tiempo_dedicacion = get_result("opc_tde_nombre",$row_contrato);
$cargo = get_result("nombre_cargo",$row_contrato);
$genero = get_result("id_genero",$row_contrato);
$tratamiento = get_result("trat_emp_nombre",$row_contrato);

$meses = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

$dia = date('j', strtotime(utf8_decode($fecha_inicio)));
$mes = date('n', strtotime(utf8_decode($fecha_inicio)));
$mes = $mes * 1;
$ann = date('Y', strtotime(utf8_decode($fecha_inicio)));

$fecha_inicio_string = ' a los '.$dia. ' días del mes de '.$meses[$mes-1].' del año '.$ann;
if ($dia == 1) {
    $fecha_inicio_string = ' al '.$dia. ' día del mes de '.$meses[$mes-1].' del año '.$ann;
}

$pdf = new PDF('P', 'mm', 'A4');

$pdf->SetAutoPageBreak(1, 43);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(0, 0);
$pdf->SetFont('helvetica', 'B', 9);

$denominacion = ($genero=='1') ? 'EL PROFESOR' : 'LA PROFESORA';

$anio = date("Y");
#EMPIEZA DOCENTE TIPO 1 opc_categoria_empleado y que sea plazo fijo  opc_documentos_relacion_laboral=9 CONTRATO PLAZO FIJO el 1 es CONTRATO INDEFINIDO
if($id_tipo_contrato==5 || $id_documento_relacion_laboral==9){
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, utf8_decode("CONTRATO ESPECIAL DE TRABAJO PARA LA EDUCACIÓN SUPERIOR PARTICULAR"), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode("POR TIEMPO DETERMINADO"), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode($cargo." ".preg_replace('/\s\d+$/', '', $tipo_contrato)." A ".$tiempo_dedicacion), 0, 0, 'C');
    $pdf->Ln(4);
    #$pdf->Cell(0, 6, utf8_decode("CÓDIGO SECTORIAL # 2013803001031"), 0, 0, 'C');
    $pdf->Cell(0, 6, utf8_decode("CÓDIGO SECTORIAL No. 2013803001032"), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode("PUCEM-C-PA-".$anio."-".str_pad($codigo_contrato, 3, "0", STR_PAD_LEFT)), 0, 0, 'C');
    /* $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode("PERSONAL ACADÉMICO"), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode($documento_relacion_laboral), 0, 0, 'C'); */

    $pdf->SetMargins(20, 0);

    $pdf->Ln(12);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("COMPARECIENTES:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
   /* $pdf->MultiCell(0, 5,utf8_decode('En Portoviejo, '.$fecha_inicio_string.', intervienen en la celebración de este contrato, por una parte, la PONTIFICIA UNIVERSIDAD CATÓLICA DEL ECUADOR, SEDE MANABÍ, representada legalmente por su Prorrector, el DR. JOSÉ LUIS CAGIGAL GARCÍA, a la cual en adelante se le denominará la PUCEM o la UNIVERSIDAD, indistintamente; y por otra, '.(($tratamiento!='') ? $tratamiento.' ' : '').$nombres.' '.$apellidos_paternos.' '.$apellidos_maternos.', portador de la cédula de identidad número '.$documento.' por sus propios derechos, a quien en adelante se le denominará '.$denominacion.', sin perjuicio de identificarle por sus nombres, quienes libre y voluntariamente convienen en celebrar el contrato de trabajo contenido en las siguientes cláusulas:'), 'J');*/

$pdf->MultiCell(0, 5, utf8_decode('Comparecen a la celebración del presente contrato individual de trabajo, por una parte, la Pontificia Universidad Católica del Ecuador, Sede Manabí, legalmente representada por su Prorrector Dr. José Luis Cagigal García, a quien en adelante se le denominará "LA UNIVERSIDAD"; y, por otra parte, '.(($tratamiento!='') ? $tratamiento.' ' : '').$nombres.' '.$apellidos_paternos.' '.$apellidos_maternos.', portador(a) de la cédula de ciudadanía No. '.$documento.', a quien en adelante se le denominará "EL PERSONAL ACADÉMICO".'), 'J');
$pdf->Ln(6);
$pdf->MultiCell(0, 5, utf8_decode('Las partes comparecientes, libre y voluntariamente, convienen en celebrar el presente contrato de trabajo, al tenor de las siguientes cláusulas:'), 'J');
$pdf->Ln(6);

    $pdf->Ln(1);
    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("PRIMERA: ANTECEDENTES. - "), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("PRIMERA. - ANTECEDENTES:"), 0, 0, 'L');
    $pdf->Ln(7);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(25);
  /*$pdf->MultiCell(0, 5, utf8_decode("a) La PUCEM es una persona jurídica de derecho privado, sin fines de lucro, y se halla identificada con los principios fundamentales católicos, de conformidad con la ley y los estatutos que rigen su existencia."), 'J');
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("b) Para el desarrollo de las actividades del personal académico no titular, la PUCEM necesita contratar personal idóneo y calificado que garantice el desenvolvimiento eficiente de sus actividades y la consecución de los objetivos propios de la Universidad, de conformidad al artículo 258 del Reglamento de Carrera y Escalafón del Personal Académico del Sistema de Educación Superior."), 'J');
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("c) ".$denominacion." declara reunir los requisitos exigidos por la PUCEM y someterse a los principios fundamentales de la UNIVERSIDAD, por lo que se lo contrata para ".(($carrera == 'VARIAS') ? "la ".$escuela : $carrera).", o colaborará en otras carreras de acuerdo a las necedidades de la PUCEM; habiendo hecho la selección de carpetas, y reuniendo los requisitos ".(($tratamiento!='') ? $tratamiento." " : "").$nombres." ".$apellidos_paternos." ".$apellidos_maternos."; el señor Prorrector autoriza su contratación."), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode("a) LA UNIVERSIDAD es una persona jurídica de derecho privado, sin fines de lucro, que se rige por la normativa vigente y por su Estatuto institucional, y se halla identificada con los principios fundamentales de la doctrina católica."), 'J');
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("b) Para el desarrollo de sus labores académicas, de investigación, vinculación y gestión académica, LA UNIVERSIDAD requiere contar con personal académico idóneo y calificado que garantice el adecuado desenvolvimiento de sus actividades y la consecución de sus fines institucionales."), 'J');
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("c) EL PERSONAL ACADÉMICO declara reunir los requisitos exigidos por LA UNIVERSIDAD para el ejercicio de actividades académicas, de investigación, vinculación y gestión educativa, y manifiesta conocer y aceptar los principios y la normativa institucional."), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("SEGUNDA: OBJETO. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("SEGUNDA. - OBJETO:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('Con los antecedentes señalados en la cláusula anterior y en virtud del presente  contrato, '.$denominacion.' se compromete a prestar sus servicios lícitos y personales a la PUCEM, en calidad de Profesor '.$cargo.' '.preg_replace('/\s\d+$/', '', $tipo_contrato).' A '.$tiempo_dedicacion.(($carrera == 'VARIAS') ? ' en la '.$escuela : (($carrera!='VARIAS CARRERAS') ? ' en la carrera de '.$carrera : '')).' o de acuerdo a las necedidades de la PUCEM, y sometiéndose a las estipulaciones del presente contrato, a las disposiciones legales aplicables, a los reglamentos vigentes, a las instrucciones que reciba de sus superiores y a las modalidades, principios y disposiciones propios de la UNIVERSIDAD, que declara conocer y aceptar.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('EL PERSONAL ACADÉMICO se obliga a prestar sus servicios lícitos y personales a favor de LA UNIVERSIDAD, en calidad de '.$cargo.' '.preg_replace('/\s\d+$/', '', $tipo_contrato).' A '.$tiempo_dedicacion.', para el desarrollo de actividades académicas, de investigación, vinculación, y gestión educativa, en '.(($carrera == 'VARIAS') ? 'la '.$escuela : (($carrera != 'VARIAS CARRERAS') ? 'la carrera de '.$carrera : 'la '.$escuela)).', pudiendo realizar dichas actividades en otras escuelas, carreras o programas, de acuerdo con las necesidades institucionales y las disposiciones de LA UNIVERSIDAD.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("TERCERA: LUGAR Y MODO DE EJECUCIÓN. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("TERCERA. - LUGAR Y MODO DE EJECUCIÓN:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode($denominacion.' realizará sus actividades EN FORMA PRESENCIAL en la PUCE Sede Manabí,'.(($carrera == 'VARIAS') ? ' en la '.$escuela.',' : (($carrera!='VARIAS CARRERAS') ? ' en la carrera de '.$carrera.',' : '')).' o en la unidad académica que le designe la Pontificia Universidad Católica del Ecuador, Sede Manabí, de acuerdo con sus necesidades.'), 'J');*/
    $pdf->MultiCell(0, 5, utf8_decode('EL PERSONAL ACADÉMICO realizará sus actividades en la Pontificia Universidad Católica del Ecuador, Sede Manabí, en los campus que determine LA UNIVERSIDAD, de acuerdo con las necesidades institucionales. EL PERSONAL ACADÉMICO podrá desarrollar actividades académicas en modalidad presencial, virtual o híbrida, cuando así lo disponga LA UNIVERSIDAD, de conformidad con la normativa aplicable.'), 'J');

    $pdf->Ln(10);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("CUARTA: OBLIGACIONES ESPECÍFICAS DEL PROFESOR. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("CUARTA. - OBLIGACIONES ESPECÍFICAS DEL PERSONAL ACADÉMICO:"), 0, 0, 'L');
    $pdf->Ln(6);
  /*$pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('A más de las establecidas en las leyes correspondientes y los Reglamentos Interno y de Profesores de la PUCEM, son obligaciones específicas de '.$denominacion.' las siguientes:'), 'J');
    $pdf->Ln(1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("1.- Laborar a ".$tiempo_dedicacion." para la PUCEM durante el tiempo previsto en el contrato, manteniendo siempre el grado de eficiencia necesario para el desempeño de sus actividades."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("2.- Cumplir con las labores correspondientes a la función contratada, con absoluta responsabilidad, dedicación y honorabilidad."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("3.- Observar las normas éticas y morales en sus relaciones con sus superiores, compañeros y estudiantes."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("4.- Cumplir sus actividades con la intensidad, esmero y cuidado aprobados, responsabilizándose de los perjuicios que por su acción u omisión puedan causarse a la PUCEM o a terceros."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("5.- Guardar absoluta reserva y discreción respecto de la información de cualquier clase que fuere, que tuviere la calidad de reservada, y que llegue a su conocimiento con ocasión de su trabajo."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("6.- Informar inmediatamente a las autoridades de la UNIVERSIDAD sobre cualquier asunto o acontecimiento que llegare a su conocimiento y que pudiera afectar a la UNIVERSIDAD, sus funcionarios, empleados, profesores, estudiantes o a los bienes de esta."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("7.- Reconocer la propiedad de la PUCEM respecto de los trabajos y resultados que obtenga en su actividad dependiente."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("8.- Programar y dictar la cátedra en los horarios aprobados por la respectiva unidad académica."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("9.- Someterse a las medidas que para el control de la asistencia y evaluación de rendimiento ponga en vigencia la PUCEM."), 'J');
    $pdf->Ln(1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('De acuerdo al Reglamento de Carrera y Escalafón del Personal Académico del Sistema de Educación Superior, debe cumplir con las actividades contempladas en los artículos 238, 239, 240, 241 y 242, que establece actividades de docencia, investigación y gestión educativa, las cuales serán reajustadas semestralmente en el tiempo de dedicación docente.'), 'J');*/

    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode('Además de las obligaciones establecidas en la legislación aplicable, en el Reglamento Interno y demás normativa institucional de LA UNIVERSIDAD, son obligaciones específicas de EL PERSONAL ACADÉMICO las siguientes:'), 'J');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("a) Cumplir con responsabilidad, diligencia y ética profesional las obligaciones y funciones inherentes a su cargo."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("b) Observar las disposiciones contenidas en el Código del Trabajo, reglamentos internos, políticas y normativa institucional."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("c) Prestar sus servicios manteniendo el nivel de eficiencia requerido para el adecuado desempeño de sus funciones."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("d) Observar normas éticas y morales en sus relaciones con autoridades, compañeros, estudiantes y demás miembros de la comunidad universitaria."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("e) Informar oportunamente a las autoridades de LA UNIVERSIDAD sobre cualquier hecho o situación que pudiere afectar a la institución, a sus miembros o a sus bienes."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("f) Reconocer los derechos institucionales de LA UNIVERSIDAD respecto de los trabajos, investigaciones o resultados que se generen en el marco de sus actividades académicas o laborales."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("g) Programar y dictar la cátedra en los horarios aprobados por la respectiva unidad académica y conforme al distributivo académico asignado."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("h) Someterse a los mecanismos de control de asistencia, evaluación de desempeño y demás procedimientos establecidos por LA UNIVERSIDAD."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("i) Ejecutar otras obligaciones y funciones inherentes a la naturaleza de su cargo y competencia, asignadas por su inmediato superior o establecidas en la normativa institucional vigente."), 'J');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode('De conformidad con la normativa aplicable al personal académico del Sistema de Educación Superior, EL PERSONAL ACADÉMICO, en función del distributivo académico del período en curso, aprobado por LA UNIVERSIDAD, desarrollará actividades relacionadas con docencia, investigación, vinculación con la sociedad y/o gestión educativa.'), 'J');
    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("QUINTA: JORNADA DE TRABAJO. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("QUINTA. - JORNADA DE TRABAJO:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode($denominacion.' se obliga a efectuar su labor a '.$tiempo_dedicacion.', en el horario de: lunes a viernes de '.$horario_laboral_inicio.' a '.$horario_laboral_fin.'  con un receso de 60 minutos para el almuerzo. '.$denominacion.' declara que conoce lo estipulado en el Reglamento de Carrera y Escalafón del Personal Académico del Sistema de Educación Superior, '.$denominacion.' se compromete a trabajar de conformidad con las condiciones y horarios establecidos por la Universidad a través de la Unidad en que presta sus servicios y que declara conocer y aceptar.'), 'J');*/

$pdf->MultiCell(0, 5, utf8_decode('EL PERSONAL ACADÉMICO se obliga a prestar sus servicios a tiempo completo, en una jornada de ocho (8) horas diarias, de lunes a viernes, en el horario comprendido de '.$horario_laboral_inicio.' a '.$horario_laboral_fin.', con un receso de sesenta (60) minutos destinados al almuerzo, de conformidad con los límites establecidos en la legislación laboral vigente.'), 'J');
$pdf->Ln(6);
$pdf->MultiCell(0, 5, utf8_decode('Adicionalmente, EL PERSONAL ACADÉMICO reconoce el derecho de LA UNIVERSIDAD a modificar los horarios y condiciones de trabajo, de acuerdo con las necesidades institucionales, siempre que dichos cambios no excedan los límites legales o contractuales y sean comunicados oportunamente.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("SEXTA: REMUNERACIÓN Y FORMA DE PAGO. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("SEXTA. - REMUNERACIÓN Y FORMA DE PAGO:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('La PUCEM pagará a '.$denominacion.' por la prestación de sus servicios a '.$tiempo_dedicacion.', la remuneración ($'.number_format($sueldo, 2).') convenida de mutuo acuerdo, que será liquidada y cancelada por mensualidades vencidas de conformidad con la modalidad establecida por la UNIVERSIDAD; se deja constancia de que en estos valores está incluida la movilización a los Campus de la Sede Manabí. Adicionalmente, la PUCEM reconocerá a '.$denominacion.' los demás derechos y beneficios establecidos en la ley. De la remuneración mensual correspondiente se realizarán previamente las deducciones y descuentos impuestos por la ley, por orden judicial o por autorización expresa del PROFESOR.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('LA UNIVERSIDAD pagará a EL PERSONAL ACADÉMICO una remuneración mensual de USD $'.number_format($sueldo, 2).' ('.numero_a_letras($sueldo).'), convenida de mutuo acuerdo, que será liquidada y cancelada por mensualidades vencidas de conformidad con la modalidad establecida por LA UNIVERSIDAD. Adicionalmente, LA UNIVERSIDAD reconocerá a EL PERSONAL ACADÉMICO los demás derechos y beneficios establecidos en la ley.'), 'J');
    $pdf->Ln(12);
    $pdf->MultiCell(0, 5, utf8_decode('De la remuneración mensual correspondiente se realizarán previamente las deducciones y descuentos impuestos por la ley, por orden judicial o por autorización expresa de EL PERSONAL ACADÉMICO.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("SÉPTIMA: CLÁUSULA ESPECIAL. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("SÉPTIMA. - PLAZO DEL CONTRATO:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode($denominacion.', pondrá atención especial a lo dispuesto en el Código de Ética y Reglamento Interno de Trabajo, afin de evitar inconvenientes entre la comunidad Educativa.'), 'J');*/
    $pdf->MultiCell(0, 5, utf8_decode('El presente contrato de trabajo tendrá una duración determinada, desde el '.date('j', strtotime(utf8_decode($fecha_inicio))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_inicio)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_inicio))).' hasta el '.date('j', strtotime(utf8_decode($fecha_fin))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_fin)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_fin))).', fecha en la cual terminará de pleno derecho, sin necesidad de requerimiento o notificación previa entre las partes, sin perjuicio de las causales de terminación previstas en el Código del Trabajo del Ecuador.'), 'J');
    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("OCTAVA: PLAZO Y TERMINACIÓN. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("OCTAVA. - TERMINACIÓN ANTICIPADA:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('El presente contrato de trabajo es A '.preg_replace('/^\s+/', '', str_ireplace('contrato', '', $documento_relacion_laboral)).' y se enmarca con el acuerdo Ministerial Nº MTD-2020-286 que indica "cuando se trate de personal académico no titular, el contrato podrá renovarse cuantas veces sea necesario. La IES podrá celebrar un contrato indefinido de trabajo cuando lo considere necesario" de no ser renovado el contrato terminará su relación laboral.'), 'J');
    $pdf->Ln(1);
    $pdf->MultiCell(0, 5,utf8_decode('El presente contrato es a partir del '.date('j', strtotime(utf8_decode($fecha_inicio))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_inicio)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_inicio))).' al '.date('j', strtotime(utf8_decode($fecha_fin))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_fin)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_fin))).'.  Sin embargo, las partes acuerdan un tiempo de prueba de hasta noventa (90) días al tenor de lo dispuesto en el artículo 15 del Código del Trabajo, por lo que, durante este período, cualquiera de las partes podrá dar por terminado el presente contrato sin que ninguna de ellas tenga derecho a indemnización alguna.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('El presente contrato podrá darse por terminado cuando EL PERSONAL ACADÉMICO incurra en incumplimiento de las obligaciones establecidas en la legislación vigente, en el presente contrato o en la normativa institucional de LA UNIVERSIDAD, previo el cumplimiento del procedimiento legal correspondiente y de conformidad con las causales previstas en la legislación ecuatoriana aplicable.'), 'J');
    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("NOVENA: TERMINACIÓN ANTICIPADA. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("NOVENA. - VACACIONES:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('En caso de que '.$denominacion.' incumpla una o más de las obligaciones que le impone la ley y los reglamentos de la Universidad, el contrato de trabajo y las instrucciones que reciba de sus superiores, la PUCEM podrá dar por terminado en cualquier tiempo, previo el cumplimiento del trámite legal, sin incurrir en responsabilidades indemnizatorias.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('EL PERSONAL ACADÉMICO tendrá derecho a las vacaciones que correspondan, en proporción al tiempo efectivamente laborado durante la vigencia del presente contrato, de conformidad con el Código del Trabajo y demás normativa aplicable.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("DÉCIMA: LEGISLACIÓN APLICABLE. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA. - LEGISLACIÓN APLICABLE:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('Para todo lo que no se halla expresamente previsto en el presente contrato, las partes incorporan las disposiciones del Código del Trabajo que declaran conocer y aceptar; y para el improbable caso de controversias que no puedan ser solucionadas mediante acuerdo personal y directo, expresamente se someten a los Jueces de Trabajo de la provincia de Manabí, Cantón Portoviejo, y al trámite oral.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('En todo lo no previsto en el presente contrato, las partes se sujetarán a las disposiciones de la legislación ecuatoriana vigente, particularmente a lo establecido en el Código del Trabajo, así como a la normativa especial aplicable al personal académico de las instituciones de educación superior particulares y a los reglamentos internos de LA UNIVERSIDAD.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("DÉCIMA PRIMERA: CONSENTIMIENTO. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA PRIMERA. - JURISDICCIÓN Y COMPETENCIA:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('Con la firma de este contrato estoy dando mi consentimiento para que la Universidad trate mis datos con fines laborales con relación a mi puesto de trabajo o que la información sea de interés público.'), 'J');
    $pdf->Ln(2);
    $pdf->MultiCell(0, 5,utf8_decode('Asimismo, me comprometo a mantener la confidencialidad de la información y a no divulgarla por ningún medio, sea este escrito, impreso, verbal o digital; responsabilizándome de las consecuencias que pueden derivar en acciones civiles o penales por divulgar la información de la que tengo acceso o que llegare a tener conocimiento con relación al puesto de trabajo.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('En caso de controversia derivada de la interpretación, ejecución o terminación del presente contrato, que no pueda ser resuelta de manera directa entre las partes, estas se someten a la jurisdicción y competencia de los jueces de trabajo de la provincia de Manabí, con sede en el cantón Portoviejo, y al procedimiento establecido en la legislación laboral ecuatoriana.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
   #$pdf->Cell(0, 6, utf8_decode("DÉCIMA SEGUNDA: FINAL. -"), 0, 0, 'L');
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA SEGUNDA. - PROTECCIÓN DE DATOS PERSONALES:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
  /*$pdf->MultiCell(0, 5,utf8_decode('Las partes aceptan y ratifican en su integridad el contrato contenido en las cláusulas que anteceden, sin reserva de ninguna clase y por convenir a sus intereses. Para constancia de lo cual lo firman en ejemplares de igual tenor y valor. En la ciudad de Portoviejo, al '.date('j', strtotime(utf8_decode($fecha_inicio))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_inicio)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_inicio))).' y autorizan su registro de conformidad con la ley.'), 'J');*/

    $pdf->MultiCell(0, 5, utf8_decode('Con la suscripción del presente contrato, EL PERSONAL ACADÉMICO autoriza a LA UNIVERSIDAD al tratamiento de sus datos personales para fines relacionados con la gestión laboral, administrativa y académica derivados de su relación de trabajo, de conformidad con la normativa vigente y con la Ley Orgánica de Protección de Datos Personales de Ecuador.'), 'J');

    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA TERCERA. - CONFIDENCIALIDAD:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode('EL PERSONAL ACADÉMICO se compromete a mantener absoluta confidencialidad respecto de la información institucional, académica, administrativa o de cualquier otra naturaleza a la que tenga acceso con ocasión del ejercicio de sus funciones, obligándose a no divulgarla por ningún medio, sea escrito, impreso, verbal o digital.'), 'J');
    $pdf->Ln(6);
    $pdf->MultiCell(0, 5, utf8_decode('El incumplimiento de esta obligación podrá dar lugar a las responsabilidades civiles, administrativas o penales previstas en la legislación vigente.'), 'J');
    $pdf->Ln(6);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA CUARTA. - ACEPTACIÓN:"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode('Las partes declaran haber leído y comprendido el contenido del presente contrato y, en señal de aceptación, lo ratifican en todas sus partes, sin reserva de ninguna naturaleza.'), 'J');
    $pdf->Ln(6);
    $pdf->MultiCell(0, 5, utf8_decode('Para constancia de lo cual, lo firman en dos ejemplares de igual tenor y valor, en la ciudad de Portoviejo, a los '.date('j', strtotime(utf8_decode($fecha_inicio))).' días del mes de '.$meses[date('n', strtotime(utf8_decode($fecha_inicio)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_inicio))).'.'), 'J');

  

    $pdf->SetAutoPageBreak(1, 1);
    
    $pdf->Ln(30);

    $pdf->SetFont('helvetica', '', 9);

    $pdf->SetXY(22, $pdf->GetY());
    $pdf->Cell(70, 4, utf8_decode("DR. JOSÉ LUIS CAGIGAL GARCÍA"), 0, 2, 'L');
    $pdf->Cell(70, 4, "C.I.: 1702550524", 0, 2, 'L');
    $pdf->Cell(70, 4, "PRORRECTOR", 0, 2, 'L');

    $pdf->SetXY(125, $pdf->GetY()-12);
    $pdf->Cell(70, 4, utf8_decode((($tratamiento!='') ? $tratamiento.' ' : '').$nombres.' '.$apellidos_paternos.' '.$apellidos_maternos), 0, 2, 'L');
    $pdf->Cell(70, 4, utf8_decode("C.I.: ".$documento), 0, 2, 'L');
    $pdf->Cell(70, 4, utf8_decode("EL PERSONAL ACADÉMICO"), 0, 2, 'L');
    
#AQUI TERMINA EL TIPO 1

}else if($id_tipo_contrato==6){

    $pdf->Cell(0, 6, utf8_decode($cargo." ".preg_replace('/\s\d+$/', '', $tipo_contrato)." A ".$tiempo_dedicacion), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode("PUCEM-C1-".$anio."-".str_pad($codigo_contrato, 3, "0", STR_PAD_LEFT)), 0, 0, 'C');
    $pdf->Ln(4);
    #$pdf->Cell(0, 6, utf8_decode("CÓDIGO SECTORIAL # 2013803001031"), 0, 0, 'C');
    $pdf->Cell(0, 6, utf8_decode("CÓDIGO SECTORIAL # 2013803001032"), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode("PERSONAL ACADÉMICO"), 0, 0, 'C');
    $pdf->Ln(4);
    $pdf->Cell(0, 6, utf8_decode($documento_relacion_laboral), 0, 0, 'C');

    $pdf->SetMargins(20, 0);

    $pdf->Ln(6);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("PARTES. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('En Portoviejo, '.$fecha_inicio_string.', intervienen en la celebración de este contrato, por una parte, la PONTIFICIA UNIVERSIDAD CATÓLICA DEL ECUADOR, SEDE MANABÍ, representada legalmente por su Prorrector, el DR. JOSÉ LUIS CAGIGAL GARCÍA,  en adelante se le denominará la PUCEM o la UNIVERSIDAD, indistintamente; y por otra, '.(($tratamiento!='') ? $tratamiento.' ' : '').$nombres.' '.$apellidos_paternos.' '.$apellidos_maternos.', portador de la cédula de identidad número '.$documento.' , por sus propios derechos, a quien en adelante se le denominará '.$denominacion.', sin perjuicio de identificarle por sus nombres, quienes libre y voluntariamente convienen en celebrar el contrato de trabajo contenido en las siguientes cláusulas:'), 'J');

    $pdf->Ln(1);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("PRIMERA: ANTECEDENTES. - "), 0, 0, 'L');
    $pdf->Ln(7);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode("La PUCEM es una persona jurídica de derecho privado, sin fines de lucro, y se halla identificada con los principios fundamentales católicos, de conformidad con la ley y los estatutos que rigen su existencia. Para el desarrollo de las actividades del personal académico no titular, la PUCEM necesita contratar personal idóneo y calificado que garantice el desenvolvimiento eficiente de sus actividades y la consecución de los objetivos propios de la Universidad, de conformidad al artículo 258 del Reglamento de Carrera y Escalafón del Personal Académico del Sistema de Educación Superior ".$denominacion." declara reunir los requisitos exigidos por la PUCEM y someterse a los principios fundamentales de la UNIVERSIDAD, por lo que se autoriza su contratación para la ".(($carrera == 'VARIAS') ? $escuela : 'carrera de '.$carrera)."; por parte del señor Prorrector."), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("SEGUNDA: OBJETO. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('Con los antecedentes señalados en la cláusula anterior y en virtud del presente  contrato, '.$denominacion.' se compromete a prestar sus servicios lícitos y personales a la PUCEM, en calidad de Profesor '.$cargo.' '.preg_replace('/\s\d+$/', '', $tipo_contrato).' A '.$tiempo_dedicacion.' en  la '.(($carrera == 'VARIAS') ? $escuela : 'carrera de '.$carrera).' o de acuerdo con las necesidades de la PUCEM, y sometiéndose a las estipulaciones del presente contrato, a las disposiciones legales aplicables, a los reglamentos vigentes, a las instrucciones que reciba de sus superiores y a las modalidades, principios y disposiciones propios de la UNIVERSIDAD, que declara conocer y aceptar.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("TERCERA: LUGAR Y MODO DE EJECUCIÓN. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode($denominacion.' realizará sus actividades EN FORMA PRESENCIAL 25 HORAS a la semana entre lunes y sábado, en la PUCE Sede Manabí, o en la unidad académica que le designe la Pontificia Universidad Católica del Ecuador, Sede Manabí, de acuerdo con sus necesidades y 15 HORAS en modalidad TELETRABAJO.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("CUARTA: OBLIGACIONES ESPECÍFICAS DEL PROFESOR. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('A más de las establecidas en las leyes correspondientes y los Reglamentos Interno y de Profesores de la PUCEM, son obligaciones específicas de '.$denominacion.' las siguientes:'), 'J');
    
    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("HORAS DE CLASE PRESENCIALES."), 0, 0, 'L');
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("1.- Impartir clases presenciales."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 5, utf8_decode("2.- Jornada de trabajo presencial con actividades de docencia."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);

    $pdf->Ln(25);

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(190, 7, utf8_decode("HORARIO DE CLASES"), 0, 0, 'L');
    $pdf->Ln(7);

    $horariosAgrupados = [];
    disconnect_procedure($con);
    $sql_periodos_docente_carrera = "SELECT *from empleados_empleado_contrato_laboral_docente_carrera where eecoldc_id_contrato = '$id_contrato_decode' and eecoldc_estado = 1";
    $result_periodos_docente_carrera=execute_query_db($sql_periodos_docente_carrera,$con);

    while($row_periodos_docente_carrera = get_array_result_db($result_periodos_docente_carrera)){
        
        $id_docente_carrera = get_result("eecoldc_id_docente_carrera", $row_periodos_docente_carrera);

        disconnect_procedure($con);
        $sql = "CALL `consulta_planta_docente_horario_materias`('$id_docente_carrera');";
        $result = execute_query_db($sql, $con);
        while ($row = get_array_result_db($result)) {
            $dia = get_result("dia_nombre", $row);
            $horariosAgrupados[$dia][] = $row;
        }
    
    }

    disconnect_procedure($con);
    $cantidad_registros = 0;
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->Cell(22, 7, utf8_decode("DIA"), 1, 0, 'C');
    $pdf->Cell(20, 7, utf8_decode("HORA"), 1, 0, 'C');
    $pdf->Cell(50, 7, utf8_decode("CARRERA"), 1, 0, 'C');
    $pdf->Cell(70, 7, utf8_decode("MATERIA"), 1, 0, 'C');
    $pdf->Cell(20, 7, utf8_decode("PARALELO"), 1, 0, 'C');
    $pdf->Ln(7);

    $pdf->SetFont('helvetica', '', 7);
    $rowHeight = 7;

    foreach ($horariosAgrupados as $dia => $horarios) {
        $first = true;
        $totalRows = count($horarios);
        $dayCellHeight = 0;
    
        foreach ($horarios as $horario) {
            $textoMateria = utf8_decode(recortarTexto(get_result("niv_mat_nombre", $horario), 45));
            $nbLines = $pdf->NbLines(70, $textoMateria);
            $altura = 5 * $nbLines;
            $dayCellHeight += $altura;
        }
    
        foreach ($horarios as $horario) {
            $textoMateria = utf8_decode(recortarTexto(get_result("niv_mat_nombre", $horario), 45));
            $nbLines = $pdf->NbLines(70, $textoMateria);
            $alturaMateria = 5 * $nbLines;
    
            if ($first) {
                $pdf->MultiCell(22, $dayCellHeight, utf8_decode($dia), 1, 'C');
                $x = $pdf->GetX();
                $y = $pdf->GetY() - $dayCellHeight;
                $pdf->SetXY($x + 22, $y);
                $first = false;
            }
    
            $pdf->SetX(42);
            $pdf->Cell(20, $alturaMateria, utf8_decode(get_result("hor_hora_desde", $horario) . " - " . get_result("hor_hora_hasta", $horario)), 1, 0, 'C');
            $pdf->Cell(50, $alturaMateria, utf8_decode(get_result("carr_nombre", $horario)), 1, 0, 'L');
    
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell(70, 5, $textoMateria, 1, 'L');
            $pdf->SetXY($x + 70, $y);
    
            $pdf->Cell(20, $alturaMateria, utf8_decode(get_result("paral_nombre", $horario)), 1, 0, 'C');
    
            $pdf->Ln($alturaMateria);
            $cantidad_registros++;
        }
    }

    $pdf->SetAutoPageBreak(1, 43);
    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("TELETRABAJO."), 0, 0, 'L');
    
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("1.- Preparar y actualizar las clases, seminarios, talleres u otras actividades educativas de similares características."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("2.- Diseñar y elaborar textos, material didáctico, metodologías, guías docentes, guías de prácticas y/o sílabos."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("3.- Orientar y acompañar a los estudiantes a través de tutorías virtuales, individuales o grupales."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("4.- Ejecutar visitas de campo, docencia en servicio y formación dual (bajo autorización de coordinación de carrera)."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("5.- Preparar, elaborar y calificar actividades de evaluación y retroalimentación."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("6.- Dirigir, leer y evaluar trabajos de titulación."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("7- Participar en redes y organización de debates, capacitación o intercambio de experiencias de enseñanza."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("8- Incorporar actividades de internacionalización en el ejercicio docente."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("9- Planificar y ejecutar el uso pedagógico de la investigación formativa y la sistematización como soporte o parte de la enseñanza."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("10- Planificar y aplicar clases de refuerzo para estudiantes con bajo rendimiento académico."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("11- Preparar y actualizar aulas virtuales."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("12- Realizar actividades de promoción, captación y admisión de nuevos estudiantes a carreras y programas."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("13- Realizar actividades de aseguramiento de la calidad que se le asignen."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);
    $pdf->MultiCell(0, 3, utf8_decode("14- Participar como mentor en actividades asignadas por la Unidad Académica."), 'J');
    $pdf->Ln(1);
    $pdf->SetX(25);

    $pdf->Ln(1);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, utf8_decode("De acuerdo al Reglamento de Carrera y Escalafón del Personal Académico  del Sistema de Educación Superior, debe cumplir con las actividades contempladas en los artículos 238, 239, 240, 241 y 242, que establece actividades de docencia, investigación y gestión educativa, las cuales serán reajustadas semestralmente en el tiempo de dedicación docente."), 'J');
    
    $pdf->Ln(1);

    $total_horas_docente_n = 0;

    disconnect_procedure($con);
    $sql_periodos_docente_carrera = "SELECT *from empleados_empleado_contrato_laboral_docente_carrera where eecoldc_id_contrato = '$id_contrato_decode' and eecoldc_estado = 1";
    $result_periodos_docente_carrera=execute_query_db($sql_periodos_docente_carrera,$con);

    while($row_periodos_docente_carrera = get_array_result_db($result_periodos_docente_carrera)){

        $id_docente_carrera = get_result("eecoldc_id_docente_carrera", $row_periodos_docente_carrera);

        disconnect_procedure($con);
        list(
            $total_horas_docente,
            $total_horas_investigacion,
            $total_horas_direccion,
            $total_horas_gestion,
            $total_horas_vinculacion,
            $total_horas_tutoria,
            $total_horas_mentoria,
            $total_horas_administrativas,
            $total_horas) = Consultar_HorasPlantaDocente2($id_docente_carrera);

        $total_horas_docente_n += $total_horas_docente;

    }

    disconnect_procedure($con);

    $actividades_gestion_educativa = 25 - $total_horas_docente_n;

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("QUINTA: JORNADA DE TRABAJO. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode($denominacion.' se obliga a efectuar su labor a '.$tiempo_dedicacion.', en el horario entre lunes y sábado cumpliendo 40 horas semanales, distribuidas de la siguiente manera: '.$total_horas_docente_n.' horas presenciales sincrónicas, '.$actividades_gestion_educativa.' horas actividades de gestión educativa en la Institución, asignadas por la unidad académica, y las restantes 15 horas las realizará en teletrabajo, conforme el tiempo de dedicación y horario establecido por el docente. '.$denominacion.' declara que conoce lo estipulado en el Reglamento de Carrera y Escalafón del Personal Académico  del Sistema de Educación Superior. '.$denominacion.' se compromete a trabajar de conformidad con las condiciones y horarios establecidos por la Universidad a través de la Unidad en que presta sus servicios y que declara conocer y aceptar.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("SEXTA: REMUNERACIÓN Y FORMA DE PAGO. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('La PUCEM pagará a '.$denominacion.' por la prestación de sus servicios a '.$tiempo_dedicacion.', la remuneración mensual de ($'.number_format($sueldo, 2).') mensuales, convenida de mutuo acuerdo, que será liquidada y cancelada por mensualidades vencidas de conformidad con la modalidad establecida por la UNIVERSIDAD; se deja constancia de que en estos valores está incluida la movilización a los Campus de la Sede Manabí. Adicionalmente, la PUCEM reconocerá a '.$denominacion.' los demás derechos y beneficios establecidos en la ley. De la remuneración mensual correspondiente se realizarán previamente las deducciones y descuentos impuestos por la ley, por orden judicial o por autorización expresa de '.$denominacion.'.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("SÉPTIMA: CLÁUSULA ESPECIAL. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode($denominacion.', pondrá atención especial a lo dispuesto en el Código de Ética y Reglamento Interno de Trabajo, afín de evitar inconvenientes entre la comunidad Educativa.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("OCTAVA: PLAZO Y TERMINACIÓN. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('El presente contrato de trabajo es A '.preg_replace('/^\s+/', '', str_ireplace('contrato', '', $documento_relacion_laboral)).' y se enmarca con el acuerdo Ministerial Nº MTD-2020-286 que indica "cuando se trate de personal académico no titular, el contrato podrá renovarse cuantas veces sea necesario. La IES podrá celebrar un contrato indefinido de trabajo cuando lo considere necesario" de no ser renovado el contrato terminará su relación laboral..'), 'J'); 
    $pdf->Ln(1);
    $pdf->MultiCell(0, 5,utf8_decode('El presente contrato de trabajo es desde el '.date('j', strtotime(utf8_decode($fecha_inicio))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_inicio)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_inicio))).' al '.date('j', strtotime(utf8_decode($fecha_fin))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_fin)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_fin))).'. Sin embargo, las partes acuerdan un tiempo de prueba de hasta noventa (90) días al tenor de lo dispuesto en el artículo 15 del Código del Trabajo, por lo que, durante este período, cualquiera de las partes podrá dar por terminado el presente contrato sin que ninguna de ellas tenga derecho a indemnización alguna.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("NOVENA: TERMINACIÓN ANTICIPADA. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('En caso de que '.$denominacion.' incumpla una o más de las obligaciones que le impone la ley y los reglamentos de la Universidad, el contrato de trabajo y las instrucciones que reciba de sus superiores, la PUCEM podrá dar por terminado en cualquier tiempo, previo el cumplimiento del trámite legal, sin incurrir en responsabilidades indemnizatorias.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA: LEGISLACIÓN APLICABLE. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('Para todo lo que no se halla expresamente previsto en el presente contrato, las partes incorporan las disposiciones del Código del Trabajo que declaran conocer y aceptar; y para el improbable caso de controversias que no puedan ser solucionadas mediante acuerdo personal y directo, expresamente se someten a los Jueces de Trabajo de la provincia de Manabí, Cantón Portoviejo, y al trámite oral.'), 'J');

    $pdf->Ln(1);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA PRIMERA: CONSENTIMIENTO. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('Con la firma de este contrato estoy dando mi consentimiento para que la Universidad trate mis datos con fines laborales con relación a mi puesto de trabajo o que la información sea de interés público.'), 'J');
    $pdf->Ln(2);
    $pdf->MultiCell(0, 5,utf8_decode('Asimismo, me comprometo a mantener la confidencialidad de la información y a no divulgarla por ningún medio, sea este escrito, impreso, verbal o digital; responsabilizándome de las consecuencias que pueden derivar en acciones civiles o penales por divulgar la información de la que tengo acceso o que llegare a tener conocimiento con relación al puesto de trabajo.'), 'J');

    $pdf->Ln(1);
    
    if($cantidad_registros>10){
        $pdf->AddPage();
    }

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(0, 6, utf8_decode("DÉCIMA SEGUNDA: FINAL. -"), 0, 0, 'L');
    $pdf->Ln(6);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5,utf8_decode('Las partes aceptan y ratifican en su integridad el contrato contenido en las cláusulas que anteceden, sin reserva de ninguna clase y por convenir a sus intereses. Para constancia de lo cual lo firman en ejemplares de igual tenor y valor. En la ciudad de Portoviejo, al '.date('j', strtotime(utf8_decode($fecha_inicio))).' de '.$meses[date('n', strtotime(utf8_decode($fecha_inicio)))-1].' de '.date('Y', strtotime(utf8_decode($fecha_inicio))).' y autorizan su registro de conformidad con la ley.'), 'J');
    
    $pdf->SetAutoPageBreak(1, 1);

    $pdf->Ln(15);

    $pdf->SetFont('helvetica', '', 9);

    $pdf->SetXY(20, $pdf->GetY());
    $pdf->Cell(70, 3.5, utf8_decode("DR. JOSÉ LUIS CAGIGAL GARCÍA"), 0, 2, 'L');
    $pdf->Cell(70, 3.5, "PRORRECTOR", 0, 2, 'L');
    $pdf->Cell(70, 3.5, "C.C # 1702550524", 0, 2, 'L');

    $pdf->SetXY(125, $pdf->GetY()-10.5);
    $pdf->Cell(70, 3.5, utf8_decode((($tratamiento!='') ? $tratamiento.' ' : '').$nombres.' '.$apellidos_paternos.' '.$apellidos_maternos), 0, 2, 'L');
    $pdf->Cell(70, 3.5, $denominacion, 0, 2, 'L');
    $pdf->Cell(70, 3.5, utf8_decode("C.C # ".$documento), 0, 2, 'L');

}

close($con);
$pdf->Output();

?>