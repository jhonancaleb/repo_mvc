<?php

if ($requestFetch) {
  require_once "./../Models/ProcessModel.php";
} else {
  require_once "./Models/ProcessModel.php";
}

class ProcessController extends ProcessModel
{
  // Función controlador generar tramite o proyecto
  public function generateProcessController()
  {
    $title = MainModel::clearString($_POST["tx_title"]);
    $type = MainModel::clearString($_POST["tx_type"]);
    $authors = MainModel::clearString($_POST["tx_authors"]);
    $description = MainModel::clearString($_POST["tx_descri"]);
    $file = $_FILES["file"];

    // Comprobar campos vacios
    if ($title == "" || $type == "" || $authors == "" || $description == "" || $file["name"] == "") {
      $alert = [
        "Alert" => "simple",
        "title" => "Campos vacios",
        "text" => "Por favor. Complete todos los campos.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    // Comprobar que los autores no tengan tramites generados
    $arrayAuthors = explode(",", $authors);
    $hasProcess = false;
    foreach ($arrayAuthors as $author) {
      $sql_verify = "SELECT t.estudiante_id FROM tramites t
      INNER JOIN detalle_tramite dt ON t.tramite_id=dt.tramite_id WHERE t.estudiante_id=$author AND dt.estado_id<>7 AND dt.nota>" . GRADE_MIN . "";
      $chek_process = MainModel::executeQuerySimple($sql_verify);
      if ($chek_process->rowCount() > 0) $hasProcess = true;
    }
    if ($hasProcess) {
      $alert = [
        "Alert" => "simple",
        "title" => "Oops...",
        "text" => "Al parecer uno de los autores ya tiene un tramite generado. Un estudiante solo puede tener un trámite",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    // Subida de archivo a servidor y obtencion de nombre
    $filename = strtotime("now") . "_" . $file['name'];
    $filename = str_replace(' ', '_', $filename);
    $file_tmp = $file['tmp_name'];

    // Mover el archivo a una ubicación permanente en el servidor
    $dir_destiny = '../uploads/';
    $fileRute = $dir_destiny . $filename;
    move_uploaded_file($file_tmp, $fileRute);

    // Datos del form a enviar
    $arrayProcess = [
      "process_id" => "TR_" . uniqid(),
      "authors" => explode(",", $authors),
      "student_gen" => $_SESSION["usuario_id"],
      "project_id" => "PR_" . uniqid(),
      "datetime_gen" => date('Y-m-d H:i:s'),
      "title" => $title,
      "type" => $type,
      "description" => $description,
      "filename" => $filename,
      "state" => 1,
    ];

    $generateProcess = ProcessModel::generateProcessModel($arrayProcess);

    // $generateProcess obtiene un booleano 
    if ($generateProcess) {
      $alert = [
        "Alert" => "alert&reload",
        "title" => "Trámite generado",
        "text" => "Su trámite se generó exitosamente.",
        "icon" => "success"
      ];
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Ocurrio un error inesperado...",
        "text" => "No pudimos registrar su trámite",
        "icon" => "error"
      ];
    }
    echo json_encode($alert);
  }

  // Funcion copntrolador cambiar estado
  public function changeStateProcessController()
  {
    $project_id = $_POST['proyecto_id'];
    $new_state = $_POST['new_state'];

    if (empty($project_id) || empty($new_state)) {
      $alert = [
        "Alert" => "simple",
        "title" => "Campos vacios",
        "text" => "Por favor. Complete todos los campos.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    $stm = ProcessModel::changeStateProcessModel($new_state, $project_id);

    if ($stm) {
      $alert = [
        "Alert" => "simple",
        "title" => "Operación realizada",
        "text" => "El proyecto cambio de estado",
        "icon" => "success"
      ];
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Opps...Al parece ocurrió un error",
        "text" => "La operación no se realizó.",
        "icon" => "error"
      ];
    }

    echo json_encode($alert);
  }

  // Funcion copntrolador asignar instructor a tramite en tabla dt
  public function assignInstructorController()
  {
    $instructor_id = $_POST['instructor_id'];
    $project_id = $_POST['project_id'];

    if (empty($instructor_id) || empty($project_id)) {
      $alert = [
        "Alert" => "simple",
        "title" => "Campos vacios",
        "text" => "Por favor. Complete todos los campos.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    $stm_ins = ProcessModel::assignInstructorModel($instructor_id, $project_id);
    $stm_state = ProcessModel::changeStateProcessModel(2, $project_id);

    if ($stm_ins && $stm_state) {
      $alert = [
        "Alert" => "alert&reload",
        "title" => "Asignación realizada",
        "text" => "El instructor se asignó exitosamente.",
        "icon" => "success"
      ];
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Opps...Al parece ocurrió un error",
        "text" => "No se pudo asignar el instructor.",
        "icon" => "error"
      ];
    }
    echo json_encode($alert);
    exit();
  }

  // Funcion controlador asignar la nota..nota distinta por alumno/tramite
  public function assignProcessGradeController()
  {
    $grade = intval($_POST['nota']);
    $dt_id = $_POST['detalle_id'];

    if (empty($grade) || empty($dt_id)) {
      $alert = [
        "Alert" => "simple",
        "title" => "Campos vacios",
        "text" => "Por favor. Asigne la nota correspondiente.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    $stm = ProcessModel::assignProcessGradeModel($grade, $dt_id);

    if ($stm) {

      ProcessModel::changeStateProcessModel(5, null, $dt_id);

      $alert = [
        "Alert" => "simple",
        "title" => "Calificación asignada",
        "text" => "El trámite se calificó exitosamente.",
        "icon" => "success"
      ];

      echo json_encode($alert);
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Opps...Al parece ocurrió un error",
        "text" => "Las calificaciones no se asignaron.",
        "icon" => "error"
      ];
    }
    echo json_encode($alert);
    exit();
  }

  // Funcion controlador para cancelar tramite
  public function cancelProcessController()
  {
    $proyecto_id = MainModel::clearString($_POST['proyecto_id']);

    if (empty($proyecto_id)) {
      $alert = [
        "Alert" => "simple",
        "title" => "Proyecto no definido",
        "text" => "No se realizó la cancelación.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    $stm = ProcessModel::changeStateProcessModel(7, $proyecto_id);

    if ($stm) {
      $alert = [
        "Alert" => "alert&reload",
        "title" => "Trámite ancelado",
        "text" => "El trámite se canceló. Puede generar otro si desea.",
        "icon" => "success"
      ];
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Opps...Al parece ocurrió un error",
        "text" => "El trámite no se canceló.",
        "icon" => "error"
      ];
    }

    echo json_encode($alert);
    exit();
  }

  // Funcion controlador para pasar tramite a area academica
  public function passProcessController()
  {
    $proyecto_id = MainModel::clearString($_POST['proyecto_id']);

    if (empty($proyecto_id)) {
      $alert = [
        "Alert" => "simple",
        "title" => "Proyecto no definido",
        "text" => "No se delegó el proyecto.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    $hasObs = ObservationModel::getObservationsModel($proyecto_id);
    $hasObs = count($hasObs) > 0;

    if ($hasObs) {
      $alert = [
        "Alert" => "simple",
        "title" => "Proyecto con observaciones",
        "text" => "El proyecto tiene observaciones. Elimine sus observaciones si el alumno ya las solucionó para poder derivar el proyecto.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    $stm = ProcessModel::changeStateProcessModel(3, $proyecto_id);

    if ($stm) {
      $alert = [
        "Alert" => "alert&reload",
        "title" => "Proyecto delegado",
        "text" => "El proyecto se derivó al área académica.",
        "icon" => "success"
      ];
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Opps...Al parece ocurrió un error",
        "text" => "No se derivó el proyecto.",
        "icon" => "error"
      ];
    }

    echo json_encode($alert);
    exit();
  }

  // Funcion controlador para agendar sustentación y asignar jurados
  public function scheduleProjectPresentationController(string $toMail)
  {
    $project_id = MainModel::clearString($_POST['proyecto_id']);
    $jurados = MainModel::clearString($_POST['jurados']);
    $fecha_sustentacion = MainModel::clearString($_POST['fecha_sustentacion']);

    if (empty($project_id) || empty($fecha_sustentacion) || empty($jurados)) {
      $alert = [
        "Alert" => "simple",
        "title" => "Campos vacios",
        "text" => "Por favor. complete todos los campos.",
        "icon" => "error"
      ];

      echo json_encode($alert);
      exit();
    }

    // obtención de archivo de proyecto
    $file = MainModel::executeQuerySimple("SELECT nombre_archivo FROM proyectos WHERE proyecto_id=$project_id");
    $file = SERVER_URL . "/uploads/" . $file->fetchColumn();

    // obtenación de corres de estudiantes
    $authors_mails = MainModel::executeQuerySimple("SELECT correo FROM usuarios u 
    INNER JOIN tramites t ON u.usuario_id=t.estudiante_id
    WHERE  t.proyecto_id=$project_id")->fetchAll();

    // Obtencion de correo de jurados
    $array_ids_juries = explode(",", $jurados);

    $juries_mails = [];
    foreach ($array_ids_juries as $key => $id_jury) {
      $mail = MainModel::executeQuerySimple("SELECT correo FROM usuarios WHERE usuario_id=$id_jury");
      array_push($juries_mails, $mail->fetchColumn());
    }

    // Envio de correo a estudiantes autores
    foreach ($authors_mails as $key => $author_mail) {
      $message = "Buenos días. Su proyecto fue agendado para sustentación en la siguiente fecha: \n $fecha_sustentacion\n Por favor, llegar a la hora indicada o minutos antes.";
      $stm_mail_student = MainModel::sendMail($author_mail['correo'], "PROYECTO AGENDADO PARA SUSTENTACIÓN", $message);
    }
    // Envio de correo a jurados
    foreach ($juries_mails as $key => $jury_mail) {
      $message = "Buenos días. A sido elegido como jurado para la sustentación de un proyecto. La fecha de la sustentación es la siguiente:\n  $fecha_sustentacion\n Por favor, llegar a la hora indicada o minutos antes. En caso de no presentarse, recibirá una sanción.";
      $stm_mail_jury= MainModel::sendMail($jury_mail, "DELEGACIÓN COMO JURADO", $message, $file);
    }

    // data para agenda
    $data = [
      "project_id" => $project_id,
      "jurados" => $jurados,
      "fecha_sustentacion" => $fecha_sustentacion,
    ];

    $stm_schedule = ProcessModel::scheduleProjectPresentationModel($data);

    if ($stm_schedule /* && $stm_mail_jury && $stm_mail_student */) {
      $alert = [
        "Alert" => "alert&reload",
        "title" => "Proyecto agendado",
        "text" => "El proyecto se agendó. Se enviaron los correos correspódientes a los jurados y a los alumnos.",
        "icon" => "success"
      ];
    } else {
      $alert = [
        "Alert" => "simple",
        "title" => "Opps...Al parece ocurrió un error",
        "text" => "No se agendó la sustentación.",
        "icon" => "error"
      ];
    }

    echo json_encode($alert);
    exit();
  }
}
